{{-- Full-screen ROUMDOUL loader shown while a form is being sent (login, sign-up, checkout, saving an
     invitation, adding guests…). It blocks the page at once so nothing can be clicked twice, and only
     fades in visibly if the request takes longer than a blink. Driven by resources/js/modules/loader.js;
     styles: «Loading overlay» in resources/css/app.css. Included once in every site layout. --}}
<div id="rd-loader" class="rd-loader" data-state="idle" role="status" aria-live="polite" aria-hidden="true">
  <div class="rd-loader__veil"></div>

  <div class="rd-loader__card">
    <div class="rd-loader__stage" aria-hidden="true">
      <span class="rd-loader__ripple"></span>
      <span class="rd-loader__ripple rd-loader__ripple--late"></span>
      <span class="rd-loader__ring"></span>
      <span class="rd-loader__dashes"></span>
      <span class="rd-loader__orbit"><i></i></span>
      <span class="rd-loader__orbit rd-loader__orbit--slow"><i></i></span>
      <span class="rd-loader__orbit rd-loader__orbit--rev"><i></i></span>

      <span class="rd-loader__disc">
        <span class="rd-loader__logo">
          <img src="/images/Roumdoul_Logo.png" alt="" width="500" height="500" decoding="async">
          <span class="rd-loader__shine"></span>
        </span>
      </span>
    </div>

    <p class="rd-loader__brand">ROUMDOUL</p>
    <p class="rd-loader__text">
      <span data-loader-text>{{ __('Please wait') }}</span><span class="rd-loader__dots" aria-hidden="true"><i>.</i><i>.</i><i>.</i></span>
    </p>
  </div>
</div>
