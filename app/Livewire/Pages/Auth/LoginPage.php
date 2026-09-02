<?php

namespace App\Livewire\Pages\Auth;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('ចូលគណនី | ROUMDOUL')]
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

        if (RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            $seconds = RateLimiter::availableIn($this->throttleKey());
            $this->addError('email', "ការសាកល្បងចូលច្រើនពេក។ សូមព្យាយាមម្តងទៀតក្នុងរយៈពេល {$seconds} វិនាទី។");

            return;
        }

        if (! Auth::guard('customer')->attempt($credentials, $this->remember)) {
            RateLimiter::hit($this->throttleKey(), 60);
            $this->addError('email', 'អ៊ីមែល ឬពាក្យសម្ងាត់មិនត្រឹមត្រូវ។');

            return;
        }

        RateLimiter::clear($this->throttleKey());
        session()->regenerate();

        $this->redirect(session()->pull('url.intended', '/dashboard'), navigate: true);
    }

    public function render()
    {
        return view('livewire.pages.auth.login-page');
    }
}
