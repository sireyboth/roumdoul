<?php

namespace App\Livewire\Pages\Dashboard;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

/** "My account": the customer's own name, email, phone and password. */
#[Layout('components.layouts.app')]
class ProfilePage extends Component
{
    public string $name = '';

    public string $email = '';

    public string $phone = '';

    public string $current_password = '';

    public string $password = '';

    public string $password_confirmation = '';

    public ?string $profileSaved = null;

    public ?string $passwordSaved = null;

    public function mount(): void
    {
        $customer = Auth::guard('customer')->user();

        $this->name = $customer->name;
        $this->email = $customer->email;
        $this->phone = (string) $customer->phone;
    }

    public function saveProfile(): void
    {
        $customer = Auth::guard('customer')->user();

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('customers', 'email')->ignore($customer->id)],
            'phone' => ['required', 'string', 'max:50'],
        ]);

        $customer->update($validated);

        $this->profileSaved = __('Your details have been saved.');
    }

    public function updatePassword(): void
    {
        $customer = Auth::guard('customer')->user();

        // guards against guessing the current password from a hijacked session
        $throttleKey = 'update-password|'.$customer->id;

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $this->addError('current_password', __('Too many requests. Please try again in :seconds seconds.', ['seconds' => RateLimiter::availableIn($throttleKey)]));

            return;
        }

        $this->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        if (! Hash::check($this->current_password, $customer->password)) {
            RateLimiter::hit($throttleKey, 300);
            $this->addError('current_password', __('Your current password is incorrect.'));

            return;
        }

        RateLimiter::clear($throttleKey);
        $customer->update(['password' => Hash::make($this->password)]);

        $this->reset('current_password', 'password', 'password_confirmation');
        $this->passwordSaved = __('Your password has been changed.');
    }

    public function render()
    {
        return view('livewire.pages.dashboard.profile-page')->title(__('My account | ROUMDOUL'));
    }
}
