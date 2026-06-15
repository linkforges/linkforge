<?php

namespace App\Services\Ai;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Provider-aware AI client. Talks to either:
 *   - Anthropic Messages API (POST /v1/messages) — native Claude, or
 *   - OpenRouter (OpenAI-compatible /chat/completions) — any model by slug
 *     (openai/gpt-4o, google/gemini-2.0-flash, meta-llama/..., anthropic/...).
 *
 * Built on Laravel's HTTP client (no SDK dependency to ship to buyers). The whole
 * AI layer is inert until the active provider's API key is configured. The public
 * surface (enabled/model/text/structured) is unchanged, so callers don't care
 * which provider is active.
 */
class ClaudeClient
{
    public function provider(): string
    {
        return config('linkforge.ai.provider') === 'openrouter' ? 'openrouter' : 'anthropic';
    }

    /** Is the active provider configured and usable? */
    public function enabled(): bool
    {
        return $this->provider() === 'openrouter'
            ? (bool) config('linkforge.ai.openrouter.key')
            : (bool) config('linkforge.ai.key');
    }

    public function model(): string
    {
        return $this->provider() === 'openrouter'
            ? (string) (config('linkforge.ai.openrouter.model') ?: 'openrouter/auto')
            : (string) config('linkforge.ai.model', 'claude-opus-4-8');
    }

    /** Single-turn message; returns the response text. */
    public function text(string $system, string $prompt, int $maxTokens = 768): string
    {
        $this->guard();

        return $this->provider() === 'openrouter'
            ? $this->openrouterText($system, $prompt, $maxTokens)
            : $this->extractText($this->anthropicMessage($system, $prompt, $maxTokens));
    }

    /**
     * Single-turn message constrained to a JSON Schema; returns the decoded object.
     *
     * @param  array<string, mixed>  $schema
     * @return array<string, mixed>
     */
    public function structured(string $system, string $prompt, array $schema, int $maxTokens = 768): array
    {
        $this->guard();

        $text = $this->provider() === 'openrouter'
            ? $this->openrouterStructured($system, $prompt, $schema, $maxTokens)
            : $this->extractText($this->anthropicMessage($system, $prompt, $maxTokens, $schema));

        return $this->decodeJson($text);
    }

    /**
     * Low-level Anthropic call (kept for backward compatibility / direct use).
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function messages(array $payload): array
    {
        $this->guard();
        $payload['model'] ??= (string) config('linkforge.ai.model', 'claude-opus-4-8');

        $response = $this->anthropicRequest()->post('/v1/messages', $payload);
        if (! $response->successful()) {
            throw new RuntimeException((string) data_get($response->json(), 'error.message', 'AI request failed.'), $response->status());
        }

        return (array) $response->json();
    }

    // -- Anthropic ---------------------------------------------------------

    /** @param array<string,mixed>|null $schema */
    private function anthropicMessage(string $system, string $prompt, int $maxTokens, ?array $schema = null): array
    {
        $payload = [
            'model' => (string) config('linkforge.ai.model', 'claude-opus-4-8'),
            'system' => $system,
            'max_tokens' => $maxTokens,
            'messages' => [['role' => 'user', 'content' => $prompt]],
        ];
        if ($schema) {
            $payload['output_config'] = ['format' => ['type' => 'json_schema', 'schema' => $schema]];
        }

        return $this->messages($payload);
    }

    private function anthropicRequest(): PendingRequest
    {
        return Http::baseUrl((string) config('linkforge.ai.base_url'))
            ->timeout((int) config('linkforge.ai.timeout', 30))
            ->retry(2, 250, throw: false)
            ->withHeaders([
                'x-api-key' => (string) config('linkforge.ai.key'),
                'anthropic-version' => (string) config('linkforge.ai.version', '2023-06-01'),
                'content-type' => 'application/json',
            ])
            ->acceptJson();
    }

    /** Concatenate the text blocks of a Messages API response. */
    private function extractText(array $data): string
    {
        $out = '';
        foreach ((array) data_get($data, 'content', []) as $block) {
            if (($block['type'] ?? null) === 'text') {
                $out .= (string) ($block['text'] ?? '');
            }
        }

        return trim($out);
    }

    // -- OpenRouter --------------------------------------------------------

    private function openrouterText(string $system, string $prompt, int $maxTokens): string
    {
        return $this->openrouterContent($this->chatMessages($system, $prompt), $maxTokens);
    }

    /** @param array<string,mixed> $schema */
    private function openrouterStructured(string $system, string $prompt, array $schema, int $maxTokens): string
    {
        // Instruct JSON in the prompt rather than relying on response_format, so any
        // model works (not all OpenRouter models support strict json_schema). The
        // decoder below extracts the object even if the model adds stray prose.
        $system = trim($system."\n\nRespond ONLY with a single minified JSON object that conforms to this JSON Schema. No markdown, no commentary:\n".json_encode($schema));

        return $this->openrouterContent($this->chatMessages($system, $prompt), $maxTokens);
    }

    /** @param list<array{role:string,content:string}> $messages */
    private function openrouterContent(array $messages, int $maxTokens): string
    {
        $response = $this->openrouterRequest()->post('/chat/completions', [
            'model' => $this->model(),
            'max_tokens' => $maxTokens,
            'messages' => $messages,
        ]);
        if (! $response->successful()) {
            throw new RuntimeException((string) data_get($response->json(), 'error.message', 'AI request failed.'), $response->status());
        }

        return trim((string) data_get($response->json(), 'choices.0.message.content', ''));
    }

    private function openrouterRequest(): PendingRequest
    {
        return Http::baseUrl((string) config('linkforge.ai.openrouter.base_url', 'https://openrouter.ai/api/v1'))
            ->timeout((int) config('linkforge.ai.timeout', 30))
            ->retry(2, 250, throw: false)
            ->withToken((string) config('linkforge.ai.openrouter.key'))
            ->withHeaders([
                'HTTP-Referer' => (string) config('app.url'),
                'X-Title' => (string) config('linkforge.name'),
                'content-type' => 'application/json',
            ])
            ->acceptJson();
    }

    /** @return list<array{role:string,content:string}> */
    private function chatMessages(string $system, string $prompt): array
    {
        $messages = [];
        if (trim($system) !== '') {
            $messages[] = ['role' => 'system', 'content' => $system];
        }
        $messages[] = ['role' => 'user', 'content' => $prompt];

        return $messages;
    }

    // -- Shared ------------------------------------------------------------

    private function guard(): void
    {
        if (! $this->enabled()) {
            throw new RuntimeException('The AI layer is not configured.');
        }
    }

    /** @return array<string,mixed> */
    private function decodeJson(string $text): array
    {
        $decoded = json_decode($text, true);

        if (! is_array($decoded) && preg_match('/\{.*\}/s', $text, $m)) {
            $decoded = json_decode($m[0], true);
        }

        if (! is_array($decoded)) {
            throw new RuntimeException('The AI response was not valid JSON.');
        }

        return $decoded;
    }
}
