@extends('portfolio.layouts.site')

@section('title', $pf->t('nav.contact'))
@section('description', $pf->copy('contact.meta_description'))
@section('body_class', 'pf-page-contact')

@php
  $email = $pf->portfolio->get('contact.email');
  $socials = collect($pf->portfolio->get('socials', []))->filter(fn ($s) => ! empty($s['url']));
  $sent = session('portfolio_contact_sent');
  $field = fn (string $name) => $errors->has($name) ? ['aria-invalid' => 'true', 'aria-describedby' => "err-{$name}"] : [];
@endphp

@section('content')
  <div class="pf-container pf-contact">
    <header class="pf-contact__head">
      <x-pf::breadcrumbs :items="[[$pf->t('nav.home'), $pf->url('home')], [$pf->t('nav.contact'), null]]" />
      <x-pf::section-label :number="1">{{ $pf->copy('contact.label') }}</x-pf::section-label>
      <h1 class="pf-contact__title pf-display" data-reveal>{{ $pf->copy('contact.title') }}</h1>
      <p class="pf-contact__intro" data-reveal>{{ $pf->copy('contact.intro') }}</p>

      <div class="pf-contact__details">
        @if ($email)
          <div>
            <p class="pf-contact__detail-label">{{ $pf->t('contact.email_label') }}</p>
            <a class="pf-contact__email pf-display" href="mailto:{{ $email }}">{{ $email }}</a>
          </div>
        @else
          <x-pf::editor-note>No public email set — add contact.email in portfolios/{{ $pf->portfolio->slug }}/portfolio.php (optional; the form works without it).</x-pf::editor-note>
        @endif

        @foreach (['professional' => 'contact.professional', 'social' => 'contact.elsewhere'] as $group => $label)
          @php $links = $socials->where('group', $group); @endphp
          @if ($links->isNotEmpty())
            <div>
              <p class="pf-contact__detail-label">{{ $pf->t($label) }}</p>
              <ul class="pf-contact__links">
                @foreach ($links as $link)
                  <li><a href="{{ $link['url'] }}" target="_blank" rel="noopener me">{{ $link['label'] }} <span aria-hidden="true">↗</span></a></li>
                @endforeach
              </ul>
            </div>
          @endif
        @endforeach
      </div>

      <div class="pf-contact__reasons">
        <p class="pf-contact__detail-label">{{ $pf->copy('contact.invitation.title') }}</p>
        <ol>
          @foreach ((array) $pf->copy('contact.invitation.items', []) as $item)
            <li><span>{{ $pf->num($loop->iteration) }}</span>{{ $item }}</li>
          @endforeach
        </ol>
      </div>
    </header>

    <section class="pf-contact__form-wrap" id="contact-form" aria-labelledby="form-title" tabindex="-1">
      @if ($sent)
        <div class="pf-sent" role="status">
          <x-pf::monogram :size="40" />
          <h2 id="form-title" class="pf-sent__title pf-display">{{ $pf->t('contact.sent_title') }}</h2>
          <p>{{ $pf->t('contact.sent_body') }}</p>
          <a href="{{ $pf->url('contact') }}#contact-form" class="pf-chip">{{ $pf->t('contact.send_another') }}</a>
        </div>
      @else
        <h2 id="form-title" class="pf-contact__form-title">{{ $pf->copy('contact.form_title') }}</h2>

        @if ($errors->any())
          <div class="pf-form-error" role="alert">
            <p>{{ $pf->t('contact.errors.summary') }}</p>
            {{-- Errors on hidden anti-spam fields have no visible input to sit under. --}}
            @foreach (['_started', 'website'] as $hidden)
              @error($hidden)<p>{{ $message }}</p>@enderror
            @endforeach
          </div>
        @endif

        <form method="post" action="{{ $pf->url('contact.send') }}" class="pf-form" data-contact-form novalidate>
          @csrf
          <input type="hidden" name="_started" value="{{ $formToken }}">

          {{-- Honeypot: invisible to people and screen readers; bots tend to fill it. --}}
          <div class="pf-hp" aria-hidden="true">
            <label for="website">Website</label>
            <input type="text" id="website" name="website" tabindex="-1" autocomplete="off">
          </div>

          <div class="pf-field">
            <label for="name">{{ $pf->t('contact.fields.name') }}</label>
            <input id="name" name="name" type="text" required maxlength="120" autocomplete="name" value="{{ old('name') }}" {{ new \Illuminate\View\ComponentAttributeBag($field('name')) }}>
            @error('name')<p class="pf-field__error" id="err-name">{{ $message }}</p>@enderror
          </div>

          <div class="pf-field">
            <label for="email">{{ $pf->t('contact.fields.email') }}</label>
            <input id="email" name="email" type="email" required maxlength="190" autocomplete="email" inputmode="email" value="{{ old('email') }}" {{ new \Illuminate\View\ComponentAttributeBag($field('email')) }}>
            @error('email')<p class="pf-field__error" id="err-email">{{ $message }}</p>@enderror
          </div>

          <div class="pf-field">
            <label for="subject">{{ $pf->t('contact.fields.subject') }} <span class="pf-field__opt">({{ $pf->t('contact.fields.optional') }})</span></label>
            <input id="subject" name="subject" type="text" maxlength="160" value="{{ old('subject') }}" {{ new \Illuminate\View\ComponentAttributeBag($field('subject')) }}>
            @error('subject')<p class="pf-field__error" id="err-subject">{{ $message }}</p>@enderror
          </div>

          <div class="pf-field">
            <label for="message">{{ $pf->t('contact.fields.message') }}</label>
            <textarea id="message" name="message" rows="6" required minlength="10" maxlength="5000" {{ new \Illuminate\View\ComponentAttributeBag($field('message')) }}>{{ old('message') }}</textarea>
            @error('message')<p class="pf-field__error" id="err-message">{{ $message }}</p>@enderror
          </div>

          <div class="pf-form__foot">
            <p class="pf-form__privacy">{{ $pf->t('contact.privacy') }}</p>
            <button type="submit" class="pf-button" data-sending-label="{{ $pf->t('contact.sending') }}">
              <span>{{ $pf->t('contact.send') }}</span>
              <svg width="28" height="12" viewBox="0 0 28 12" aria-hidden="true"><path d="M0 6h26M21 1l5 5-5 5" fill="none" stroke="currentColor" stroke-width="1.2"/></svg>
            </button>
          </div>
        </form>
      @endif
    </section>
  </div>
@endsection
