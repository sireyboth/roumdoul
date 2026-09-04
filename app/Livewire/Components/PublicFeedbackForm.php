<?php

namespace App\Livewire\Components;

use App\Models\Review;
use App\Rules\NoProfanity;
use Livewire\Component;

class PublicFeedbackForm extends Component
{
    public string $customer_name = '';

    public string $email = '';

    public int $rating = 5;

    public string $comment = '';

    // Honeypot: real visitors never see or fill this field; bots that
    // auto-fill every input will, so a non-empty value marks spam.
    public string $website = '';

    public bool $submitted = false;

    protected function rules(): array
    {
        return [
            'customer_name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['required', 'string', 'max:1000', new NoProfanity],
        ];
    }

    public function submit(): void
    {
        if (filled($this->website)) {
            // Silently pretend it worked so bots don't retry.
            $this->reset(['customer_name', 'email', 'rating', 'comment', 'website']);
            $this->rating = 5;
            $this->submitted = true;

            return;
        }

        $validated = $this->validate();
        unset($validated['website']);

        Review::create([
            ...$validated,
            'source' => 'public_form',
            'is_approved' => false,
        ]);

        $this->reset(['customer_name', 'email', 'comment']);
        $this->rating = 5;
        $this->submitted = true;
    }

    public function render()
    {
        return view('livewire.components.public-feedback-form');
    }
}
