<?php

namespace App\Livewire\Pages\Auth;

use App\Models\Customer;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class RegisterPage extends Component
{
    public string $name = '';

    public string $email = '';

    public string $phone = '';

    public string $password = '';

    public string $password_confirmation = '';

    public bool $terms = false;

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:customers,email'],
            'phone' => ['required', 'string', 'max:50'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'terms' => ['accepted'],
        ];
    }

    protected function messages(): array
    {
        return ['terms.accepted' => __('Please accept the Terms of Use to continue.')];
    }

    public function register(): void
    {
        // Only accounts actually created count toward the limit (5 per hour per IP): a customer
        // who mistypes a field or hits "email already taken" a few times must never get locked
        // out, while a bot still can't mass-create accounts. IP-only key — there's no account yet.
        $validated = $this->validate();

        $throttleKey = 'register|'.request()->ip();
        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            $this->addError('email', __('Too many requests. Please try again in :seconds seconds.', ['seconds' => $seconds]));

            return;
        }

        $customer = Customer::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'password' => Hash::make($validated['password']),
            'terms_accepted_at' => now(),
        ]);
        RateLimiter::hit($throttleKey, 3600);

        Auth::guard('customer')->login($customer);

        $this->redirect(session()->pull('url.intended', '/dashboard'), navigate: true);
    }

    public function render()
    {
        return view('livewire.pages.auth.register-page')->title(__('Create an account | ROUMDOUL'));
    }
}
