<?php

namespace App\Livewire\Pages\Auth;

use Illuminate\Support\Facades\Auth;
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

    public function login(): void
    {
        $credentials = $this->validate();

        if (! Auth::guard('customer')->attempt($credentials, $this->remember)) {
            $this->addError('email', 'អ៊ីមែល ឬពាក្យសម្ងាត់មិនត្រឹមត្រូវ។');

            return;
        }

        session()->regenerate();

        $this->redirect(session()->pull('url.intended', '/dashboard'), navigate: true);
    }

    public function render()
    {
        return view('livewire.pages.auth.login-page');
    }
}
