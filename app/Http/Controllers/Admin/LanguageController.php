<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Setting;
use App\Support\Locales;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Validation\Rule;

/**
 * In-admin translation manager: list installed locales with their coverage,
 * pick the default UI language, add/translate/import/export/remove locales —
 * all by writing the lang/{code}.json files Laravel reads, no shell access or
 * code edits required by the operator.
 */
class LanguageController extends Controller
{
    public function index()
    {
        $source = $this->sourceStrings();
        $total = count($source);

        $locales = [];
        foreach (Locales::available() as $code => $name) {
            $translated = $code === 'en'
                ? $total
                : count(array_filter(
                    array_intersect_key($this->translations($code), $source),
                    fn ($v) => is_string($v) && trim($v) !== ''
                ));

            $locales[] = [
                'code' => $code,
                'name' => $name,
                'translated' => $translated,
                'total' => $total,
                'percent' => $total > 0 ? (int) round($translated / $total * 100) : 100,
                'rtl' => Locales::isRtl($code),
                'source' => $code === 'en',
            ];
        }

        // Known languages not yet installed, for the "add a language" picker.
        $addable = array_diff_key(Locales::catalog(), Locales::available());

        return view('admin.languages.index', [
            'locales' => $locales,
            'total' => $total,
            'default' => $this->defaultLocale(),
            'available' => Locales::available(),
            'addable' => $addable,
        ]);
    }

    public function store(Request $request)
    {
        $code = strtolower(trim((string) $request->input('code')));

        if (! $this->isValidCode($code)) {
            return back()->with('error', 'Enter a valid language code such as "fr" or "pt-BR".');
        }
        if ($code === 'en') {
            return back()->with('error', 'English is the source language and is always available.');
        }
        if (is_file($this->path($code))) {
            return back()->with('error', Locales::name($code).' ('.$code.') is already installed.');
        }

        $this->write($code, []); // empty file — every key falls back to English until translated
        AuditLog::record('language.create', 'Added language: '.Locales::name($code).' ('.$code.')');

        return redirect()->route('admin.languages.edit', $code)
            ->with('status', Locales::name($code).' added — start translating below.');
    }

    public function edit(string $code)
    {
        $code = strtolower($code);
        abort_unless($this->isTranslatable($code), 404);

        return view('admin.languages.edit', [
            'code' => $code,
            'name' => Locales::name($code),
            'rtl' => Locales::isRtl($code),
            'source' => $this->sourceStrings(),
            'translations' => $this->translations($code),
        ]);
    }

    public function update(Request $request, string $code)
    {
        $code = strtolower($code);
        abort_unless($this->isTranslatable($code), 404);

        $source = $this->sourceStrings();
        $input = (array) $request->input('t', []);

        $out = [];
        foreach ($source as $key => $_default) {
            $value = isset($input[$key]) ? trim((string) $input[$key]) : '';
            if ($value !== '') {
                $out[$key] = $value; // omit empties so untranslated keys fall back to English
            }
        }

        $this->write($code, $out);
        AuditLog::record('language.update', 'Updated '.$code.' translations ('.count($out).'/'.count($source).')');

        return redirect()->route('admin.languages.edit', $code)
            ->with('status', 'Saved '.count($out).' of '.count($source).' translations for '.Locales::name($code).'.');
    }

    public function import(Request $request, string $code)
    {
        $code = strtolower($code);
        abort_unless($this->isTranslatable($code), 404);

        $request->validate(['json' => ['required', 'string', 'max:300000']]);

        $decoded = json_decode((string) $request->input('json'), true);
        if (! is_array($decoded)) {
            return back()->with('error', 'That is not a valid JSON object of key to translation pairs.');
        }

        $source = $this->sourceStrings();
        $merged = $this->translations($code);
        $applied = 0;
        foreach ($source as $key => $_default) {
            if (array_key_exists($key, $decoded) && is_string($decoded[$key]) && trim($decoded[$key]) !== '') {
                $merged[$key] = trim($decoded[$key]);
                $applied++;
            }
        }

        $this->write($code, array_intersect_key($merged, $source)); // keep only current source keys
        AuditLog::record('language.import', 'Imported '.$applied.' translations into '.$code);

        return redirect()->route('admin.languages.edit', $code)
            ->with('status', 'Imported '.$applied.' translation(s) into '.Locales::name($code).'.');
    }

    public function export(string $code)
    {
        $code = strtolower($code);
        abort_unless($this->isValidCode($code) && is_file($this->path($code)), 404);

        return response(file_get_contents($this->path($code)), 200, [
            'Content-Type' => 'application/json; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="'.$code.'.json"',
        ]);
    }

    public function destroy(string $code)
    {
        $code = strtolower($code);
        abort_unless($this->isValidCode($code) && $code !== 'en', 404);

        // Capture this before unlinking — afterwards the code is no longer "available".
        $wasDefault = strtolower((string) Setting::get('default_locale')) === $code;

        if (is_file($this->path($code))) {
            @unlink($this->path($code));
        }
        if ($wasDefault) {
            Setting::put('default_locale', 'en'); // don't leave the site pointing at a deleted locale
        }
        AuditLog::record('language.delete', 'Removed language: '.$code);

        return redirect()->route('admin.languages')->with('status', strtoupper($code).' removed.');
    }

    public function setDefault(Request $request)
    {
        $data = $request->validate([
            'default_locale' => ['required', Rule::in(array_keys(Locales::available()))],
        ]);

        Setting::put('default_locale', $data['default_locale']);
        AuditLog::record('language.default', 'Default language set to '.Locales::name($data['default_locale']));

        return redirect()->route('admin.languages')
            ->with('status', 'Default language set to '.Locales::name($data['default_locale']).'.');
    }

    public function scan()
    {
        Artisan::call('lang:scan');
        AuditLog::record('language.scan', 'Rescanned translatable strings');

        return redirect()->route('admin.languages')->with('status', trim(Artisan::output()) ?: 'Strings rescanned.');
    }

    /* ---- helpers ------------------------------------------------------- */

    /** English source strings — the canonical key list every locale translates. */
    private function sourceStrings(): array
    {
        return $this->read('en');
    }

    private function translations(string $code): array
    {
        return $this->read($code);
    }

    private function read(string $code): array
    {
        $path = $this->path($code);

        return is_file($path) ? (json_decode((string) file_get_contents($path), true) ?: []) : [];
    }

    private function write(string $code, array $pairs): void
    {
        ksort($pairs);
        $json = $pairs === []
            ? "{}\n"
            : json_encode($pairs, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)."\n";

        if (! is_dir(lang_path())) {
            mkdir(lang_path(), 0755, true);
        }
        file_put_contents($this->path($code), $json);
    }

    private function path(string $code): string
    {
        return lang_path($code.'.json');
    }

    /** A code we accept as a locale: letters with an optional region, no path chars. */
    private function isValidCode(string $code): bool
    {
        return (bool) preg_match('/^[a-z]{2,3}(-[a-z]{2,4})?$/', strtolower($code));
    }

    /** A locale we can edit: valid, not the English source, and already installed. */
    private function isTranslatable(string $code): bool
    {
        return $this->isValidCode($code) && $code !== 'en' && is_file($this->path($code));
    }

    private function defaultLocale(): string
    {
        $code = strtolower((string) (Setting::get('default_locale') ?: config('app.locale')));

        return Locales::isAvailable($code) ? $code : 'en';
    }
}
