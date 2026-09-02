<?php

namespace App\Livewire\Pages\Auth;

use App\Models\Customer;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('បង្កើតគណនី | ROUMDOUL')]
class RegisterPage extends Component
{
    public string $name = '';

    public string $email = '';

    public string $password = '';

    public string $password_confirmation = '';

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:customers,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }

    public function register(): void
    {
        // IP-only key (unlike login's email+IP) — registration has no "account" yet to
        // scope by, and this exists purely to slow down mass fake-account/spam creation.
        $throttleKey = 'register|'.request()->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            $this->addError('email', "សំណើច្រើនពេក។ សូមព្យាយាមម្តងទៀតក្នុងរយៈពេល {$seconds} វិនាទី។");

            return;
        }
        RateLimiter::hit($throttleKey, 3600);

        $validated = $this->validate();

        $customer = Customer::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        Auth::guard('customer')->login($customer);

        $this->redirect(session()->pull('url.intended', '/dashboard'), navigate: true);
    }

    public function render()
    {
        return view('livewire.pages.auth.register-page');
    }
}
