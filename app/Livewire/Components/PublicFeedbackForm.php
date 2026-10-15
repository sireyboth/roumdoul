<?php

namespace App\Livewire\Components;

use App\Models\ModerationLog;
use App\Models\Review;
use App\Rules\NoProfanity;
use App\Services\ContentModerator;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Component;

class PublicFeedbackForm extends Component
{
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
            'email' => ['nullable', 'email', 'max:255'],
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['required', 'string', 'max:1000', new NoProfanity],
        ];
    }

    public function submit(): void
    {
        if (filled($this->website)) {
            // Silently pretend it worked so bots don't retry.
            $this->reset(['email', 'rating', 'comment', 'website']);
            $this->rating = 5;
            $this->submitted = true;

            return;
        }

        $limiterKey = 'feedback|'.request()->ip();
        if (RateLimiter::tooManyAttempts($limiterKey, (int) config('moderation.feedback_per_hour_per_ip', 5))) {
            $this->addError('comment', 'សូមព្យាយាមម្ដងទៀតនៅពេលក្រោយ។ / Too many submissions — please try again later.');

            return;
        }

        $validated = $this->validate();
        RateLimiter::hit($limiterKey, 3600);

        $moderator = app(ContentModerator::class);
        $validated['comment'] = $moderator->sanitize($validated['comment']);

        if ($validated['comment'] === '' || $moderator->isRecentDuplicateReview($validated['comment'])) {
            ModerationLog::record('review_rejected', 'review', null, ['duplicate_or_empty'], $moderator->fingerprint($validated['comment']));
            $this->addError('comment', 'មតិនេះត្រូវបានផ្ញើរួចហើយ។ / This comment has already been submitted.');

            return;
        }

        Review::create([
            ...$validated,
            'source' => 'public_form',
            'is_approved' => false,
        ]);

        $this->reset(['email', 'comment']);
        $this->rating = 5;
        $this->submitted = true;
    }

    public function render()
    {
        return view('livewire.components.public-feedback-form');
    }
}
