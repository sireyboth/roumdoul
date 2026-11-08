/**
 * Personal portfolio — progressive enhancement only.
 * Every feature here has a no-JS fallback (links, query strings, plain forms).
 */

const root = document.documentElement;
const reduceMotion = matchMedia('(prefers-reduced-motion: reduce)');
const KHMER_DIGITS = ['០', '១', '២', '៣', '៤', '៥', '៦', '៧', '៨', '៩'];

// The inline head script already added `pf-js`; this flag stops its 3s safety timer from removing it.
window.__pfReady = true;
root.classList.add('pf-js');

const $ = (sel, ctx = document) => ctx.querySelector(sel);
const $$ = (sel, ctx = document) => [...ctx.querySelectorAll(sel)];
const store = {
    get(key) { try { return localStorage.getItem(key); } catch { return null; } },
    set(key, value) { try { localStorage.setItem(key, value); } catch { /* private mode */ } },
};

/* Theme ------------------------------------------------------------------ */
function initTheme() {
    const button = $('[data-theme-toggle]');
    if (!button) return;

    const sync = () => {
        const dark = root.dataset.theme === 'dark';
        button.setAttribute('aria-label', dark ? button.dataset.labelLight : button.dataset.labelDark);
        button.setAttribute('aria-pressed', String(dark));
    };

    const apply = (theme, animate) => {
        if (animate && !reduceMotion.matches) {
            root.classList.add('pf-theming');
            setTimeout(() => root.classList.remove('pf-theming'), 500);
        }
        root.dataset.theme = theme;
        sync();
    };

    button.addEventListener('click', () => {
        const next = root.dataset.theme === 'dark' ? 'light' : 'dark';
        store.set('pf-theme', next);
        apply(next, true);
    });

    // Follow the OS setting until the visitor makes an explicit choice.
    matchMedia('(prefers-color-scheme: dark)').addEventListener('change', (e) => {
        if (!store.get('pf-theme')) apply(e.matches ? 'dark' : 'light', true);
    });

    sync();
}

/* Mobile menu ------------------------------------------------------------- */
function initMenu() {
    const button = $('[data-menu-toggle]');
    const menu = $('[data-menu]');
    if (!button || !menu) return;

    const setOpen = (open) => {
        button.setAttribute('aria-expanded', String(open));
        menu.hidden = !open;
        menu.classList.toggle('is-open', open);
        root.classList.toggle('pf-menu-open', open);
        if (open) $('a', menu)?.focus({ preventScroll: true });
    };

    button.addEventListener('click', () => setOpen(button.getAttribute('aria-expanded') !== 'true'));
    menu.addEventListener('click', (e) => { if (e.target.closest('a')) setOpen(false); });
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && !menu.hidden) { setOpen(false); button.focus(); }
    });
    matchMedia('(min-width: 1180px)').addEventListener('change', (e) => { if (e.matches) setOpen(false); });
}

/* Language switch: stay at the same place on the page --------------------- */
/* Each language is its own URL, so switching is a page load that would start at the top.
   Khmer text is taller than English, so a pixel offset would land in the wrong spot —
   instead remember which block was at the top of the screen (both languages share the
   same structure). The new page scrolls back to that block in an inline script at the
   end of layouts/site.blade.php — keep its selector in sync with anchorCandidates(). */
const LANG_SCROLL_KEY = 'pf-lang-scroll';
const anchorCandidates = () => $$('main section, main header, main article, main li, main [id]')
    .filter((el) => !el.closest('[hidden]') && !el.hasAttribute('data-reveal'));

function initLanguageSwitch() {
    document.addEventListener('click', (e) => {
        const link = e.target.closest('.pf-lang__opt');
        if (!link || link.classList.contains('is-active') || e.metaKey || e.ctrlKey || e.shiftKey) return;
        link.search = location.search; // filters changed without a reload (Work page) travel along
        if (scrollY < 40) return; // already at the top: nothing to keep

        const headerBottom = $('[data-header]')?.getBoundingClientRect().bottom ?? 0;
        const items = anchorCandidates();
        const index = items.findIndex((el) => el.getBoundingClientRect().bottom > headerBottom + 8);
        if (index < 0) return;

        try {
            sessionStorage.setItem(LANG_SCROLL_KEY, JSON.stringify({
                path: new URL(link.href).pathname,
                index,
                count: items.length,
                offset: items[index].getBoundingClientRect().top,
            }));
        } catch { /* storage unavailable: fall back to normal navigation */ }
    });
}

/* Header: hairline once scrolled, tucks away while reading downwards ------- */
function initHeader() {
    const header = $('[data-header]');
    if (!header) return;
    let lastY = scrollY;
    let ticking = false;

    const update = () => {
        const y = scrollY;
        header.classList.toggle('is-scrolled', y > 8);
        const menuOpen = root.classList.contains('pf-menu-open');
        const hide = !menuOpen && y > 480 && y > lastY + 4 && !header.contains(document.activeElement);
        if (hide) header.classList.add('is-hidden');
        else if (y < lastY - 4 || y <= 480) header.classList.remove('is-hidden');
        lastY = y;
        ticking = false;
    };

    addEventListener('scroll', () => {
        if (!ticking) { requestAnimationFrame(update); ticking = true; }
    }, { passive: true });
    header.addEventListener('focusin', () => header.classList.remove('is-hidden'));
    update();
}

/* Reveal on scroll -------------------------------------------------------- */
function initReveal() {
    const items = $$('[data-reveal]');
    if (!items.length) return;

    if (reduceMotion.matches || !('IntersectionObserver' in window)) {
        items.forEach((el) => el.classList.add('is-in'));
        return;
    }

    const io = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            if (!entry.isIntersecting) return;
            entry.target.classList.add('is-in');
            io.unobserve(entry.target);
        });
    }, { rootMargin: '0px 0px -8% 0px', threshold: 0.08 });

    // Arrived through a page transition: what's on screen is already shown by the
    // transition itself, so mark it revealed with no animation of its own.
    if (window.__pfVt) {
        items.forEach((el) => {
            const r = el.getBoundingClientRect();
            if (r.top < innerHeight && r.bottom > 0) {
                el.style.transition = 'none';
                el.classList.add('is-in');
                requestAnimationFrame(() => requestAnimationFrame(() => { el.style.transition = ''; }));
            }
        });
    }

    // Stagger whatever is already on screen at load (capped, so tall screens never wait long).
    let delay = 0;
    items.forEach((el) => {
        if (el.classList.contains('is-in')) { io.observe(el); return; }
        const r = el.getBoundingClientRect();
        if (r.top < innerHeight && r.bottom > 0) {
            el.style.transitionDelay = `${delay}ms`;
            delay = Math.min(delay + 90, 450);
            el.addEventListener('transitionend', () => { el.style.transitionDelay = ''; }, { once: true });
        }
        io.observe(el);
    });
}

/* Work: filters + view switch without reloading ---------------------------- */
function initWork() {
    const work = $('[data-work]');
    if (!work) return;

    const params = new URLSearchParams(location.search);
    const state = {
        category: params.get('category') || '',
        status: params.get('status') || '',
        view: work.dataset.view || 'index',
    };
    const count = $('[data-count]', work);
    const empty = $('[data-empty]', work);
    const select = $('[data-filter-select="status"]', work);

    const toLocalDigits = (n) => (count?.dataset.khmer === '1'
        ? String(n).padStart(2, '0').replace(/\d/g, (d) => KHMER_DIGITS[d])
        : String(n).padStart(2, '0'));

    const apply = (push = true) => {
        let visible = 0;
        $$('[data-view-panel]', work).forEach((panel) => {
            const isCurrent = panel.dataset.viewPanel === state.view;
            panel.hidden = !isCurrent;
            $$('[data-item]', panel).forEach((item) => {
                const show = (!state.category || item.dataset.category === state.category)
                    && (!state.status || item.dataset.status === state.status);
                item.hidden = !show;
                if (show && isCurrent) visible += 1;
            });
        });

        $$('[data-filter="category"]', work).forEach((chip) => {
            const active = chip.dataset.value === state.category;
            chip.classList.toggle('is-active', active);
            chip.setAttribute('aria-current', String(active));
        });
        $$('[data-view-btn]', work).forEach((btn) => {
            const active = btn.dataset.viewBtn === state.view;
            btn.classList.toggle('is-active', active);
            btn.setAttribute('aria-pressed', String(active));
        });
        if (select) select.value = state.status;
        if (empty) empty.hidden = visible > 0;
        if (count) count.textContent = count.dataset.template.replace('{count}', toLocalDigits(visible));

        if (push) {
            const url = new URL(location.href);
            ['category', 'status'].forEach((k) => (state[k] ? url.searchParams.set(k, state[k]) : url.searchParams.delete(k)));
            state.view === 'gallery' ? url.searchParams.set('view', 'gallery') : url.searchParams.delete('view');
            history.replaceState(null, '', url);
        }
    };

    work.addEventListener('click', (e) => {
        const chip = e.target.closest('[data-filter="category"]');
        const viewBtn = e.target.closest('[data-view-btn]');
        const reset = e.target.closest('[data-reset]');
        if (chip) { e.preventDefault(); state.category = chip.dataset.value; apply(); }
        else if (viewBtn) { e.preventDefault(); state.view = viewBtn.dataset.viewBtn; apply(); }
        else if (reset) { e.preventDefault(); state.category = ''; state.status = ''; apply(); }
    });
    select?.addEventListener('change', () => { state.status = select.value; apply(); });
    $('[data-status-form]', work)?.addEventListener('submit', (e) => e.preventDefault());

    initIndexPreview(work);
}

/* Index view: image preview that follows the pointer (fine pointers only) */
function initIndexPreview(scope) {
    if (!matchMedia('(hover: hover) and (pointer: fine)').matches) return;
    const index = $('.pf-index', scope);
    if (!index) return;

    let frame = null;
    index.addEventListener('pointermove', (e) => {
        if (frame) return;
        frame = requestAnimationFrame(() => {
            index.style.setProperty('--px', `${e.clientX}px`);
            index.style.setProperty('--py', `${e.clientY}px`);
            frame = null;
        });
    });
    $$('.pf-index__row', index).forEach((row) => {
        row.addEventListener('pointerenter', () => row.classList.add('is-previewing'));
        row.addEventListener('pointerleave', () => row.classList.remove('is-previewing'));
    });
}

/* Lightbox ---------------------------------------------------------------- */
function initLightbox() {
    const dialog = $('[data-lightbox-dialog]');
    if (!dialog || typeof dialog.showModal !== 'function') return;

    const img = $('[data-lightbox-img]', dialog);
    const caption = $('[data-lightbox-caption]', dialog);
    const counter = $('[data-lightbox-count]', dialog);
    const prev = $('[data-lightbox-prev]', dialog);
    const next = $('[data-lightbox-next]', dialog);
    let group = [];
    let index = 0;
    let opener = null;

    const show = (i) => {
        index = (i + group.length) % group.length;
        const btn = group[index];
        img.src = btn.dataset.full;
        img.alt = btn.querySelector('img')?.alt || '';
        caption.textContent = btn.dataset.caption || '';
        counter.textContent = group.length > 1 ? `${index + 1} / ${group.length}` : '';
        prev.hidden = next.hidden = group.length < 2;
    };

    document.addEventListener('click', (e) => {
        const btn = e.target.closest('[data-lightbox]');
        if (!btn) return;
        group = $$(`[data-lightbox="${CSS.escape(btn.dataset.lightbox)}"]`);
        opener = btn;
        show(group.indexOf(btn));
        dialog.showModal();
    });

    prev.addEventListener('click', () => show(index - 1));
    next.addEventListener('click', () => show(index + 1));
    $('[data-lightbox-close]', dialog).addEventListener('click', () => dialog.close());
    dialog.addEventListener('click', (e) => { if (e.target === dialog) dialog.close(); });
    dialog.addEventListener('keydown', (e) => {
        if (e.key === 'ArrowLeft') show(index - 1);
        if (e.key === 'ArrowRight') show(index + 1);
    });
    dialog.addEventListener('close', () => { img.removeAttribute('src'); opener?.focus(); });
}

/* Case study: highlight the current section in the contents rail --------- */
function initToc() {
    const links = $$('[data-toc-link]');
    if (!links.length || !('IntersectionObserver' in window)) return;

    const byId = new Map(links.map((a) => [a.hash.slice(1), a]));
    const io = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            if (!entry.isIntersecting) return;
            links.forEach((a) => a.classList.remove('is-current'));
            byId.get(entry.target.id)?.classList.add('is-current');
        });
    }, { rootMargin: '-35% 0px -60% 0px' });

    byId.forEach((_, id) => { const el = document.getElementById(id); if (el) io.observe(el); });
}

/* Contact: native validation messages, and no double submits ------------- */
function initContact() {
    const form = $('[data-contact-form]');
    if (!form) return;

    form.addEventListener('submit', (e) => {
        if (!form.checkValidity()) {
            e.preventDefault();
            form.reportValidity();
            return;
        }
        const button = $('button[type="submit"]', form);
        button.disabled = true;
        $('span', button).textContent = button.dataset.sendingLabel;
    });

    // After a redirect back to #contact-form, move focus to the result for screen readers.
    if (location.hash === '#contact-form') $('#contact-form')?.focus({ preventScroll: true });
}

initTheme();
initMenu();
initLanguageSwitch();
initHeader();
initReveal();
initWork();
initLightbox();
initToc();
initContact();
