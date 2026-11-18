<?php

namespace App\Livewire\Pages\Auth;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class LoginPage extends Component
{
    public string $email = '';

    public string $password = '';

    public bool $remember = false;

    protected function rules(): array
    {
        return [
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * Keyed by email+IP (Laravel's standard login-throttle pattern) rather than IP alone,
     * so a single attacker can't lock out every OTHER customer's account just by
     * hammering their email, while still capping brute-force attempts against any one
     * account.
     */
    protected function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->email)).'|'.request()->ip();
    }

    public function login(): void
    {
        $credentials = $this->validate();

        // Two caps: 5 tries a minute per email+IP, and a wider 20 wrong passwords an hour per
        // email alone, so an attacker rotating IPs can't keep guessing one account's password.
        $emailKey = 'login-email|'.Str::transliterate(Str::lower($this->email));

        foreach ([[$this->throttleKey(), 5], [$emailKey, 20]] as [$key, $max]) {
            if (RateLimiter::tooManyAttempts($key, $max)) {
                $this->addError('email', __('auth.throttle', ['seconds' => RateLimiter::availableIn($key)]));

                return;
            }
        }

        if (! Auth::guard('customer')->attempt($credentials, $this->remember)) {
            RateLimiter::hit($this->throttleKey(), 60);
            RateLimiter::hit($emailKey, 3600);
            $this->addError('email', __('auth.failed'));

            return;
        }

        RateLimiter::clear($this->throttleKey());
        RateLimiter::clear($emailKey);
        session()->regenerate();

        $this->redirect(session()->pull('url.intended', '/dashboard'), navigate: true);
    }

    public function render()
    {
        return view('livewire.pages.auth.login-page')->title(__('Log in | ROUMDOUL'));
    }
}
