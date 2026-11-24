/* Chrome Bow — modern birthday (resources/views/invitations/templates/birthday/chrome-bow.blade.php)
   Built on the shared InviteKit (sound, reveals, FLIP preview, countdown, RSVP, copy, fly, settle).
   Own parts: canvas confetti, the bubble-balloon pop opening, candles to blow, holo ticket tilt, scroll effects. */
(function () {
  'use strict';

  window.ChromeBow = function (opt) {
    opt = opt || {};
    var root = document.querySelector('.cb');
    if (!root) return;
    root.classList.add('js');
    var $ = function (id) { return document.getElementById(id); };
    var cover = $('cb-cover');
    var kit = InviteKit({ root: root, cover: !!cover });
    var reduce = kit.reduce;
    var css = getComputedStyle(root);
    var accent = css.getPropertyValue('--cb-accent').trim() || '#ff4fa0';
    var accentL = css.getPropertyValue('--cb-accent-l').trim() || '#ffb0d4';

    /* ───────── confetti (one fixed canvas, runs only while pieces are alive) ───────── */
    var cv = document.createElement('canvas'), ctx = cv.getContext('2d'), parts = [], running = false, dpr = 1;
    cv.className = 'cb-confetti'; cv.setAttribute('aria-hidden', 'true');
    document.body.appendChild(cv);
    var size = function () { dpr = Math.min(2, window.devicePixelRatio || 1); cv.width = innerWidth * dpr; cv.height = innerHeight * dpr; };
    size(); addEventListener('resize', size);
    var COLORS = [accent, accentL, '#d9dce3', '#c9b6ff', '#ffe08a', '#ffffff', '#9be7ff'];
    var star = function (s) {
      ctx.beginPath();
      for (var i = 0; i < 8; i++) { var r = i % 2 ? s * .35 : s, a = i * Math.PI / 4; ctx[i ? 'lineTo' : 'moveTo'](Math.cos(a) * r, Math.sin(a) * r); }
      ctx.closePath(); ctx.fill();
    };
    var step = function () {
      ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
      ctx.clearRect(0, 0, innerWidth, innerHeight);
      parts = parts.filter(function (p) { return p.life++ < p.max && p.y < innerHeight + 40; });
      parts.forEach(function (p) {
        p.vx *= p.drag; p.vy = p.vy * p.drag + p.g;
        p.x += p.vx + Math.sin(p.t) * .7; p.y += p.vy; p.r += p.vr; p.t += p.vt;
        ctx.save();
        ctx.globalAlpha = p.life > p.max - 40 ? (p.max - p.life) / 40 : 1;
        ctx.translate(p.x, p.y); ctx.rotate(p.r); ctx.scale(1, Math.cos(p.t));   // flips in 3D as it falls
        ctx.fillStyle = p.c;
        if (p.shape === 0) ctx.fillRect(-p.s / 2, -p.s / 4, p.s, p.s / 2);
        else if (p.shape === 1) { ctx.beginPath(); ctx.arc(0, 0, p.s * .32, 0, 6.283); ctx.fill(); }
        else star(p.s * .55);
        ctx.restore();
      });
      if (parts.length) requestAnimationFrame(step);
      else { running = false; ctx.clearRect(0, 0, innerWidth, innerHeight); }
    };
    var confetti = function (x, y, o) {
      if (reduce) return;
      o = o || {};
      var n = o.n || 80, ang = (o.angle == null ? -90 : o.angle) * Math.PI / 180, spread = (o.spread || 70) * Math.PI / 180, pw = o.power || 14;
      var colors = o.colors || COLORS;
      for (var i = 0; i < n; i++) {
        var a = ang + (Math.random() - .5) * spread, v = pw * (.45 + Math.random() * .75);
        parts.push({ x: x, y: y, vx: Math.cos(a) * v, vy: Math.sin(a) * v, g: o.g || .3 + Math.random() * .14, drag: o.drag || .975 + Math.random() * .01,
          r: Math.random() * 6.28, vr: (Math.random() - .5) * .3, t: Math.random() * 6.28, vt: .08 + Math.random() * .14,
          s: 6 + Math.random() * 7, shape: Math.random() < .55 ? 0 : (Math.random() < .55 ? 1 : 2), c: colors[i % colors.length], life: 0, max: 200 + Math.random() * 90 });
      }
      if (!running) { running = true; requestAnimationFrame(step); }
    };
    var cannons = function () {
      confetti(-10, innerHeight + 10, { angle: -62, spread: 34, power: 26, n: 90 });
      confetti(innerWidth + 10, innerHeight + 10, { angle: -118, spread: 34, power: 26, n: 90 });
    };
    var center = function (el) { var r = el.getBoundingClientRect(); return [r.left + r.width / 2, r.top + r.height / 2]; };

    /* ───────── kit pieces ───────── */
    kit.words('[data-ik="words"]');
    kit.lightbox();
    root.querySelectorAll('[data-copy]').forEach(function (b) { b.addEventListener('click', function () { kit.copy(b.getAttribute('data-copy'), 'បានចម្លងលេខគណនី ✓'); }); });
    var count = $('cb-count');
    if (count) kit.countdown(count, function () { count.hidden = true; var p = $('cb-party-now'); if (p) p.hidden = false; });

    var thanks = $('cb-rsvp-thanks');
    kit.rsvp({
      opts: $('cb-rsvp-opts'), form: $('cb-rsvp-form'), note: $('cb-rsvp-note'), url: opt.rsvpUrl || null,
      onChoose: function (status, b) { var c = center(b); if (status === 'yes') confetti(c[0], c[1], { n: 26, power: 9, spread: 120 }); },
      onDone: function (status, ok) {
        if (!thanks) return;
        thanks.textContent = !ok ? 'មានបញ្ហាបន្តិច សូមព្យាយាមម្ដងទៀត' : (status === 'yes' ? 'Yay! ជួបគ្នានៅថ្ងៃជប់លៀង 🥳' : 'អរគុណ! យើងនឹងនឹកអ្នក ♥');
        if (!ok) return;
        $('cb-rsvp-form').classList.remove('show');
        if (status === 'yes') { cannons(); kit.sfx('party', .35); }
        else { var c = center(thanks); kit.burst(c[0], c[1], { n: 14 }); }
      }
    });

    /* ───────── make a wish: blow out the candles ───────── */
    var candles = Array.prototype.slice.call(root.querySelectorAll('.cb-candle'));
    var cake = $('cb-cake'), wishDone = $('cb-wish-done');
    candles.forEach(function (c) {
      c.addEventListener('click', function () {
        if (c.classList.contains('out')) {          // tap a blown candle: light it again
          c.classList.remove('out', 'puff'); kit.sfx('tap', .25); return;
        }
        c.classList.add('out'); c.classList.remove('puff'); void c.offsetWidth; c.classList.add('puff');
        c.setAttribute('aria-label', 'អុជទៀនម្ដងទៀត');
        kit.sfx('paper', .25);
        if (candles.every(function (x) { return x.classList.contains('out'); })) {
          setTimeout(function () {
            kit.sfx('party', .4);
            cake.classList.remove('is-wished'); void cake.offsetWidth; cake.classList.add('is-wished');
            var p = center(cake); confetti(p[0], p[1] - 40, { n: 120, power: 16, spread: 100 });
            cannons();
            wishDone.classList.add('show');
          }, 450);
        }
      });
    });
    var relight = $('cb-relight');
    if (relight) relight.addEventListener('click', function () {
      candles.forEach(function (c, i) { setTimeout(function () { c.classList.remove('out', 'puff'); c.setAttribute('aria-label', 'ផ្លុំទៀន'); kit.sfx('tap', .2); }, i * 160); });
      wishDone.classList.remove('show');
    });

    /* ───────── hero numerals: tap = jelly + confetti ───────── */
    var age = $('cb-age');
    if (age) age.addEventListener('click', function (e) {
      age.classList.remove('is-jelly'); void age.getBoundingClientRect(); age.classList.add('is-jelly');
      kit.sfx('pop', .35);
      confetti(e.clientX, e.clientY, { n: 40, power: 11, spread: 160 });
    });

    /* ───────── holographic ticket: follows the finger / pointer, drifts with scroll otherwise ───────── */
    var ticket = $('cb-ticket'), touching = false;
    var setTilt = function (px, py) {
      ticket.style.setProperty('--rx', ((.5 - py) * 16).toFixed(2) + 'deg');
      ticket.style.setProperty('--ry', ((px - .5) * 20).toFixed(2) + 'deg');
      ticket.style.setProperty('--mx', (px * 100).toFixed(1) + '%');
      ticket.style.setProperty('--my', (py * 100).toFixed(1) + '%');
    };
    if (ticket && !reduce) {
      ticket.addEventListener('pointermove', function (e) {
        var r = ticket.getBoundingClientRect();
        touching = true; ticket.classList.add('is-live');
        setTilt(Math.min(1, Math.max(0, (e.clientX - r.left) / r.width)), Math.min(1, Math.max(0, (e.clientY - r.top) / r.height)));
      });
      ticket.addEventListener('pointerleave', function () { touching = false; ticket.classList.remove('is-live'); setTilt(.5, .5); });
      ticket.addEventListener('click', function (e) { kit.sfx('card', .3); kit.burst(e.clientX, e.clientY, { n: 10, chars: ['✦', '♥'] }); });
    }

    /* ───────── dress-code balloons + gift box ───────── */
    root.querySelectorAll('.cb-balloon').forEach(function (b) {
      b.addEventListener('click', function () {
        b.classList.remove('is-boing'); void b.offsetWidth; b.classList.add('is-boing');
        kit.sfx('tap', .3);
        var c = center(b), col = getComputedStyle(b.parentNode).getPropertyValue('--c').trim();
        confetti(c[0], c[1], { n: 18, power: 7, spread: 140, colors: [col, '#ffffff', col] });
      });
    });
    var gift = $('cb-gift');
    if (gift) gift.addEventListener('click', function () {
      var open = !gift.classList.contains('is-open');
      gift.classList.toggle('is-open', open);
      gift.setAttribute('aria-expanded', open ? 'true' : 'false');
      var hint = $('cb-gift-hint'); if (hint) hint.textContent = open ? 'ស្កេន QR ខាងលើ ♥' : 'ប៉ះដើម្បីបើកកាដូ · tap to open';
      kit.sfx(open ? 'envelope' : 'tap', .35);
      if (open) { var c = center(gift); setTimeout(function () { confetti(c[0], c[1] - 60, { n: 50, power: 11 }); kit.sfx('sparkle', .25); }, 350); }
    });
    if (gift && reduce) gift.classList.add('is-open');

    var thr = $('cb-throw');
    if (thr) thr.addEventListener('click', function () { cannons(); var c = center(thr); confetti(c[0], c[1], { n: 60, power: 15 }); kit.sfx('pop', .4); setTimeout(function () { kit.sfx('party', .3); }, 200); });
    root.querySelectorAll('.cb-pl li').forEach(function (li) {
      li.addEventListener('click', function () { root.querySelectorAll('.cb-pl li.on').forEach(function (x) { x.classList.remove('on'); }); li.classList.add('on'); });
    });

    /* ───────── scroll: parallax decor, hero numerals, marquees, timeline, balloon sway ───────── */
    var depthEls = [];
    var measure = function () {
      depthEls = Array.prototype.map.call(root.querySelectorAll('[data-depth]'), function (el) {
        var prev = el.style.translate; el.style.translate = '';
        var r = el.getBoundingClientRect(); el.style.translate = prev;
        return { el: el, y: r.top + scrollY + r.height / 2, d: parseFloat(el.getAttribute('data-depth')) || 0 };
      });
    };
    var marquees = Array.prototype.map.call(root.querySelectorAll('[data-marquee]'), function (el) { return { el: el, dir: +el.getAttribute('data-marquee'), x: 0 }; });
    var years = $('cb-years'), yearsFill = $('cb-years-fill'), balloons = root.querySelectorAll('.cb-balloon');
    var vel = 0;
    if (!reduce) {
      kit.onScroll(function (s) {
        vel = s.velocity;
        depthEls.forEach(function (o) { o.el.style.translate = '0 ' + ((o.y - (s.y + s.vh / 2)) * o.d).toFixed(1) + 'px'; });
        if (age) age.style.transform = 'rotate(' + (-8 + s.y * .02).toFixed(2) + 'deg) scale(' + (1 + Math.min(s.y, 600) * .0006).toFixed(3) + ')';
        if (yearsFill && years) {
          var r = years.getBoundingClientRect();
          yearsFill.style.transform = 'scaleY(' + Math.min(1, Math.max(0, (s.vh * .7 - r.top) / r.height)).toFixed(3) + ')';
        }
        var sway = Math.max(-14, Math.min(14, -s.velocity * .8));
        balloons.forEach(function (b) { b.style.setProperty('--sway', sway.toFixed(1) + 'deg'); });
        if (ticket && !touching) setTilt(.5 + Math.max(-.3, Math.min(.3, s.velocity * .01)), Math.min(1, Math.max(0, ticket.getBoundingClientRect().top / s.vh)));
      });
      var mq = function () {          // marquees: steady drift, faster while scrolling
        marquees.forEach(function (m) {
          var w = m.el.scrollWidth / 2;
          m.x -= (0.6 + Math.min(8, Math.abs(vel) * .25)) * m.dir;
          if (m.x <= -w) m.x += w; if (m.x > 0) m.x -= w;
          m.el.style.transform = 'translate3d(' + m.x.toFixed(1) + 'px,0,0)';
        });
        vel *= .92;
        requestAnimationFrame(mq);
      };
      requestAnimationFrame(mq);
    }
    addEventListener('resize', measure);
    addEventListener('load', measure);

    /* ───────── pop effects shared by the cover game and the floating balloons ───────── */
    var shards = function (c, rad, n, colour) {
      for (var i = 0; i < n; i++) {
        var sh = document.createElement('i'), a = i / n * Math.PI * 2 + Math.random() * .3, d = rad * (1.3 + Math.random());
        sh.className = 'cb-shard' + (colour ? ' sm' : '');
        if (colour) sh.style.setProperty('--c', colour);
        sh.style.left = (c[0] + Math.cos(a) * rad - (colour ? 8 : 13)) + 'px'; sh.style.top = (c[1] + Math.sin(a) * rad - 6) + 'px';
        document.body.appendChild(sh);
        sh.animate([{ transform: 'rotate(' + (a * 57 + 90) + 'deg)', opacity: 1 },
          { transform: 'translate(' + Math.cos(a) * d + 'px,' + (Math.sin(a) * d + 50) + 'px) rotate(' + (a * 57 + 90 + (Math.random() * 300 - 150)) + 'deg) scale(.4)', opacity: 0 }],
          { duration: 700 + Math.random() * 400, easing: 'cubic-bezier(.15,.7,.3,1)', fill: 'forwards' }).finished.then(function (an) { an.effect.target.remove(); });
      }
    };
    var WORDS = ['POP!', 'YAY!', 'WOO!', 'BOOM!', 'POP!', 'PARTY!'];
    var popText = function (c) {
      var t = document.createElement('span');
      t.className = 'cb-poptext'; t.setAttribute('aria-hidden', 'true');
      t.textContent = WORDS[Math.floor(Math.random() * WORDS.length)];
      t.style.left = c[0] + 'px'; t.style.top = c[1] + 'px';
      root.appendChild(t);
      var r = Math.random() * 30 - 15;
      t.animate([{ transform: 'scale(.2) rotate(' + r + 'deg)', opacity: 1 }, { transform: 'scale(1.15) rotate(' + r + 'deg)', opacity: 1, offset: .3 },
        { transform: 'translateY(-50px) scale(1) rotate(' + (r * 1.5) + 'deg)', opacity: 0 }], { duration: 850, easing: 'cubic-bezier(.2,.9,.3,1)', fill: 'forwards' })
        .finished.then(function () { t.remove(); });
    };
    var popAt = function (el, colour, vol) {
      var c = center(el);
      kit.sfx('pop', vol == null ? .35 + Math.random() * .15 : vol);
      if (reduce) return;
      shards(c, el.offsetWidth / 2, 7, colour);
      popText(c);
      confetti(c[0], c[1], { n: 34, power: 9, spread: 360, colors: [colour, '#ffffff', colour, accentL, '#c9b6ff'] });
    };

    /* ───────── balloons floating up the page now and then (tap = pop) ───────── */
    var floating = 0, FLOAT_COLOURS = [accent, '#c9b6ff', '#ffd36e', '#9fd8f0', accentL, '#d9dce3'];
    var floater = function () {
      if (reduce || document.hidden || floating >= 2 || !document.body.animate) return;
      var b = document.createElement('button'), col = FLOAT_COLOURS[Math.floor(Math.random() * FLOAT_COLOURS.length)];
      b.type = 'button'; b.className = 'cb-floater'; b.tabIndex = -1; b.setAttribute('aria-hidden', 'true');
      b.style.setProperty('--c', col); b.style.left = (6 + Math.random() * 80) + 'vw';
      root.appendChild(b); floating++;
      var drift = (Math.random() - .5) * 140;
      var an = b.animate([{ transform: 'translate(0,0) rotate(-5deg)' }, { transform: 'translate(' + drift / 2 + 'px,-62vh) rotate(6deg)', offset: .5 },
        { transform: 'translate(' + drift + 'px,calc(-100vh - 160px)) rotate(-4deg)' }], { duration: 10000 + Math.random() * 4000, easing: 'linear' });
      var done = function () { if (b.parentNode) { b.remove(); floating--; } };
      an.finished.then(done, done);
      b.addEventListener('click', function () { popAt(b, col); an.pause(); done(); });
    };
    var startFloaters = function () { setTimeout(floater, 3000); setInterval(floater, 7000); };

    /* ───────── cover: pop the bouquet one by one (each pop adds a sticker word), then the big finale ───────── */
    var start = function () { root.classList.add('is-open'); measure(); kit.reveal('.ik-r'); };
    if (!cover) { start(); startFloaters(); return; }
    kit.lock(true);
    var smalls = Array.prototype.slice.call(cover.querySelectorAll('.cb-pb'));
    var slots = cover.querySelectorAll('.cb-word'), words = [];
    try { words = JSON.parse(cover.getAttribute('data-words') || '[]'); } catch (e) { words = []; }
    var popped = 0, finished = false;
    var drizzle = setInterval(function () {   // a slow confetti drizzle while the cover is up
      confetti(Math.random() * innerWidth, -12, { n: 1, angle: 90, spread: 40, power: 1.5, g: .035, drag: .99 });
    }, 260);

    var popSmall = function (b, inChain) {
      if (b.classList.contains('is-pop')) return;
      kit.startSong();                          // the first tap is the user gesture the song needs
      b.classList.add('is-pop'); b.disabled = true;
      popAt(b, b.style.getPropertyValue('--c').trim());
      var str = cover.querySelector('[data-str="' + b.getAttribute('data-i') + '"]');
      if (str) str.classList.add('is-cut');
      if (slots[popped]) { slots[popped].textContent = words[popped] || '✦'; slots[popped].classList.add('on'); }
      popped++;
      if (popped >= smalls.length && !inChain) setTimeout(finale, 550);
    };
    smalls.forEach(function (b) { b.addEventListener('click', function () { popSmall(b, false); }); });
    var popAll = function () {
      if (finished) return;
      kit.startSong();
      var left = smalls.filter(function (b) { return !b.classList.contains('is-pop'); });
      if (!left.length) { finale(); return; }
      left.forEach(function (b, i) { setTimeout(function () { popSmall(b, true); }, i * 140); });
      setTimeout(finale, left.length * 140 + 420);
    };

    // finale: disco goes wild → the photo balloon squishes and bursts → the scene lifts away → the photo flies into the hero
    var finale = function () {
      if (finished) return;
      finished = true;
      clearInterval(drizzle);
      var bubble = $('cb-bubble'), hero = $('cb-hero-photo'), photo = $('cb-cover-photo');
      cover.classList.add('is-party');
      kit.sfx('party', .35);
      start();                                      // lay out the page behind the cover first
      hero.style.visibility = 'hidden';
      if (reduce) { cover.classList.add('is-gone'); hero.style.visibility = ''; kit.lock(false); startFloaters(); return; }
      kit.settle(function () {
        cover.classList.add('is-squish');
        setTimeout(function () {
          cover.classList.add('is-popped');
          kit.sfx('pop', .65);
          var c = center(bubble), rad = bubble.offsetWidth / 2;
          var flash = document.createElement('i'); flash.className = 'cb-flash';
          flash.style.left = c[0] + 'px'; flash.style.top = c[1] + 'px'; document.body.appendChild(flash);
          flash.animate([{ transform: 'scale(1)', opacity: 1 }, { transform: 'scale(' + (rad / 5) + ')', opacity: 0 }], { duration: 650, easing: 'cubic-bezier(.2,.8,.3,1)' }).finished.then(function () { flash.remove(); });
          shards(c, rad, 12);
          confetti(c[0], c[1], { n: 160, power: 18, spread: 360 });
          cannons();
          cover.querySelectorAll('.cb-cv').forEach(function (el, k) { el.style.setProperty('--o', k); });
          cover.classList.add('is-leaving');
          kit.fly(photo, hero, { duration: 1400, lift: .02 }).then(function () {
            hero.style.visibility = '';
            cover.classList.add('is-gone');
            kit.lock(false);
            kit.sfx('chime', .3);
            var h = center(hero);
            confetti(h[0], h[1] - 60, { n: 60, power: 12, spread: 120 });
            measure();
            startFloaters();
            setTimeout(function () { cover.remove(); }, 400);
          });
        }, 480);
      });
    };
    $('cb-bubble').addEventListener('click', popAll);
    $('cb-open').addEventListener('click', popAll);
  };
})();
