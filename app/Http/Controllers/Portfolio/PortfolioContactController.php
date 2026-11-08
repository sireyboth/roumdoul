<?php

namespace App\Http\Controllers\Portfolio;

use App\Http\Controllers\Controller;
use App\Models\PortfolioMessage;
use App\Portfolio\Portfolio;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

/**
 * Portfolio contact form. A message counts as "sent" once it is saved in
 * portfolio_messages (readable in the admin panel under Portfolio → Messages).
 * Email notification is optional on top of that and never decides success.
 */
class PortfolioContactController extends Controller
{
    /** Anything submitted faster than this after the page rendered is treated as a bot. */
    protected const MIN_SECONDS = 3;

    public function __invoke(Request $request, Portfolio $portfolio, string $locale): RedirectResponse
    {
        abort_unless($portfolio->supports($locale), 404);
        app()->setLocale($locale);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'string', 'email:rfc', 'max:190'],
            'subject' => ['nullable', 'string', 'max:160'],
            'message' => ['required', 'string', 'min:10', 'max:5000'],
            // Honeypot: hidden from people, filled in by naive bots.
            'website' => ['prohibited'],
            '_started' => ['required', 'string'],
        ], [
            'website.prohibited' => __('portfolio.contact.errors.spam'),
            '_started.required' => __('portfolio.contact.errors.spam'),
        ], [
            'name' => __('portfolio.contact.fields.name'),
            'email' => __('portfolio.contact.fields.email'),
            'subject' => __('portfolio.contact.fields.subject'),
            'message' => __('portfolio.contact.fields.message'),
        ]);

        $this->guardAgainstBots($data['_started']);

        $message = PortfolioMessage::create([
            'portfolio' => $portfolio->slug,
            'locale' => $locale,
            'name' => $data['name'],
            'email' => $data['email'],
            'subject' => $data['subject'] ?? null,
            'message' => $data['message'],
            'ip_address' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 255),
        ]);

        $this->notify($portfolio, $message);

        return redirect()
            ->route('portfolio.contact', ['portfolio' => $portfolio->slug, 'locale' => $locale])
            ->with('portfolio_contact_sent', true)
            ->withFragment('contact-form');
    }

    protected function guardAgainstBots(string $token): void
    {
        try {
            $started = (int) Crypt::decryptString($token);
        } catch (\Throwable) {
            $started = 0;
        }

        $elapsed = now()->timestamp - $started;

        if ($started === 0 || $elapsed < self::MIN_SECONDS || $elapsed > 60 * 60 * 6) {
            throw ValidationException::withMessages(['message' => __('portfolio.contact.errors.too_fast')]);
        }
    }

    /** Best-effort email to the owner, only when notify_email is set and a real mailer is configured. */
    protected function notify(Portfolio $portfolio, PortfolioMessage $message): void
    {
        $to = $portfolio->get('contact.notify_email');

        if (! $to || in_array(config('mail.default'), ['log', 'array', null], true)) {
            return;
        }

        try {
            Mail::raw(
                "New message from your portfolio ({$portfolio->slug})\n\n"
                ."From: {$message->name} <{$message->email}>\n"
                .'Subject: '.($message->subject ?: '—')."\n\n"
                .$message->message,
                fn ($mail) => $mail->to($to)->replyTo($message->email, $message->name)
                    ->subject('Portfolio: '.($message->subject ?: 'New message from '.$message->name)),
            );
        } catch (\Throwable $e) {
            // The message is already saved; a mail outage must not turn that into an error.
            Log::warning('Portfolio contact notification failed', ['id' => $message->id, 'error' => $e->getMessage()]);
        }
    }
}
