<?php

namespace App\Actions\Fortify;

use App\Models\Plan;
use App\Models\User;
use App\Rules\Turnstile;
use App\Services\Safety\LinkSafety;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules;

    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, string>  $input
     *
     * @throws ValidationException
     */
    public function create(array $input): User
    {
        Validator::make($input, [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique(User::class),
            ],
            'password' => $this->passwordRules(),
            'cf-turnstile-response' => [new Turnstile()],
        ])->validate();

        // Honeypot: a hidden field that only bots fill in.
        if (! empty($input['company'])) {
            throw ValidationException::withMessages(['email' => 'Registration could not be completed.']);
        }

        // Block throwaway / disposable email domains.
        if (app(LinkSafety::class)->isDisposableEmail($input['email'])) {
            throw ValidationException::withMessages(['email' => 'Please sign up with a permanent email address.']);
        }

        // New accounts start on the Free plan (if seeded) with its AI credit allowance.
        $free = Plan::where('slug', 'free')->first();

        $user = User::create([
            'name' => $input['name'],
            'email' => $input['email'],
            'password' => Hash::make($input['password']),
            'plan_id' => $free?->id,
            'ai_credits' => (int) ($free?->limit('ai_credits') ?? 0),
        ]);

        $postman = app(\App\Services\Mail\Postman::class);
        $postman->send('welcome', $user->email, [
            'name' => $user->name, 'email' => $user->email, 'action_url' => route('dashboard'),
        ]);
        $postman->send('admin_new_user', User::where('role', 'admin')->pluck('email')->all(), [
            'customer_name' => $user->name, 'customer_email' => $user->email, 'action_url' => route('admin.users.show', $user),
        ]);

        return $user;
    }
}
