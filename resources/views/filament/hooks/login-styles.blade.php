<style>
    /* Scoped to the "simple" auth layout (login/forgot-password) only —
       never touches the authenticated dashboard/resource pages. */
    @property --angle {
        syntax: '<angle>';
        initial-value: 0deg;
        inherits: false;
    }

    .fi-simple-layout {
        position: relative;
        min-height: 100dvh;
        overflow: hidden;
        background: #170b12;
        isolation: isolate;
    }

    .fi-simple-main-ctn {
        position: relative;
        z-index: 1;
        padding-block: 4rem;
        padding-inline: 1.25rem;
    }

    @media (max-width: 640px) {
        .fi-simple-main-ctn {
            padding-block: 2rem;
        }
    }

    /* ---------- Aurora canvas + watermark + particles (from login-canvas hook) ---------- */

    .rd-aurora {
        position: fixed;
        inset: 0;
        width: 100%;
        height: 100%;
        z-index: 0;
        display: block;
    }

    .rd-watermark {
        position: fixed;
        inset: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 0;
        pointer-events: none;
        overflow: hidden;
        font-family: 'Kantumruy Pro', sans-serif;
        font-weight: 700;
        font-size: clamp(4.5rem, 22vw, 16rem);
        color: transparent;
        -webkit-text-stroke: 1px rgba(253, 243, 247, 0.06);
        background: linear-gradient(180deg, rgba(253, 243, 247, 0.05), rgba(253, 243, 247, 0));
        -webkit-background-clip: text;
        background-clip: text;
        user-select: none;
        white-space: nowrap;
    }

    @media (max-width: 480px) {
        .rd-watermark {
            opacity: 0.5;
        }
    }

    .rd-particles {
        position: fixed;
        inset: 0;
        z-index: 0;
        pointer-events: none;
        overflow: hidden;
    }

    .rd-particle {
        position: absolute;
        top: 100%;
        border-radius: 9999px;
        background: radial-gradient(circle, rgba(217, 182, 106, 0.9), rgba(217, 182, 106, 0));
        animation-name: rd-float, rd-sway;
        animation-timing-function: linear, ease-in-out;
        animation-iteration-count: infinite, infinite;
        animation-direction: normal, alternate;
    }

    @keyframes rd-float {
        from { transform: translateY(0); opacity: 0; }
        5% { opacity: 0.9; }
        95% { opacity: 0.7; }
        to { transform: translateY(-115vh); opacity: 0; }
    }

    @keyframes rd-sway {
        from { margin-left: calc(var(--drift) * -1); }
        to { margin-left: var(--drift); }
    }

    @media (prefers-reduced-motion: reduce) {
        .rd-particle {
            animation: none;
            display: none;
        }
    }

    /* ---------- Header ---------- */

    .fi-simple-header {
        position: relative;
        z-index: 1;
        margin-bottom: 2rem;
        display: flex;
        flex-direction: column;
        align-items: center;
        text-align: center;
    }

    .fi-simple-layout .fi-logo {
        height: 4.5rem !important;
        width: 4.5rem !important;
        border-radius: 9999px;
        object-fit: contain;
        padding: 0.5rem;
        background: rgba(23, 11, 18, 0.6);
        box-shadow:
            0 0 0 1px rgba(217, 182, 106, 0.35),
            0 0 40px -4px rgba(176, 35, 97, 0.65),
            0 12px 32px -8px rgba(0, 0, 0, 0.6);
        margin-bottom: 1.5rem;
    }

    @media (max-width: 480px) {
        .fi-simple-layout .fi-logo {
            height: 3.5rem !important;
            width: 3.5rem !important;
            margin-bottom: 1rem;
        }
    }

    @media (prefers-reduced-motion: no-preference) {
        .fi-simple-layout .fi-logo {
            animation: rd-logo-in 0.7s cubic-bezier(0.16, 1, 0.3, 1) both;
        }
    }

    @keyframes rd-logo-in {
        from { opacity: 0; transform: scale(0.6) translateY(-12px); }
        to { opacity: 1; transform: scale(1) translateY(0); }
    }

    .fi-simple-header-heading {
        font-weight: 700;
        font-size: clamp(1.4rem, 5vw, 1.85rem);
        letter-spacing: -0.01em;
        background: linear-gradient(100deg, #fdf3f7 20%, #e0709f 40%, #d9b66a 50%, #fdf3f7 70%);
        background-size: 220% auto;
        -webkit-background-clip: text;
        background-clip: text;
        color: transparent;
    }

    @media (prefers-reduced-motion: no-preference) {
        .fi-simple-header-heading {
            animation: rd-text-shine 5s linear infinite;
        }
    }

    @keyframes rd-text-shine {
        to { background-position: -220% center; }
    }

    .fi-simple-header-subheading {
        position: relative;
        z-index: 1;
        color: rgba(253, 243, 247, 0.6);
        font-size: 0.875rem;
        margin-top: 0.5rem;
        letter-spacing: 0.02em;
    }

    /* ---------- Login card: animated rotating gradient border ---------- */

    .fi-simple-page-content {
        position: relative;
        z-index: 1;
    }

    .fi-simple-page-content .fi-section {
        position: relative;
        border-radius: 1.5rem;
        border: none;
        background-color: rgba(23, 11, 18, 0.78);
        backdrop-filter: blur(20px);
        -webkit-backdrop-filter: blur(20px);
        box-shadow: 0 30px 80px -24px rgba(0, 0, 0, 0.7);
        transition: transform 0.15s ease-out;
    }

    @media (max-width: 480px) {
        .fi-simple-page-content .fi-section {
            border-radius: 1.125rem;
            box-shadow: 0 16px 40px -16px rgba(0, 0, 0, 0.7);
            backdrop-filter: blur(14px);
            -webkit-backdrop-filter: blur(14px);
        }
    }

    .fi-simple-page-content .fi-section::before {
        content: '';
        position: absolute;
        inset: -1px;
        border-radius: inherit;
        padding: 1px;
        background: conic-gradient(from var(--angle), #b02361, #d9b66a, #e0709f, #b02361);
        -webkit-mask:
            linear-gradient(#fff 0 0) content-box,
            linear-gradient(#fff 0 0);
        -webkit-mask-composite: xor;
        mask-composite: exclude;
        pointer-events: none;
        z-index: -1;
    }

    @media (prefers-reduced-motion: no-preference) {
        .fi-simple-page-content .fi-section::before {
            animation: rd-rotate-border 6s linear infinite;
        }

        .fi-simple-page-content .fi-section {
            animation: rd-card-in 0.6s 0.15s cubic-bezier(0.16, 1, 0.3, 1) both;
        }
    }

    @keyframes rd-rotate-border {
        to { --angle: 360deg; }
    }

    @keyframes rd-card-in {
        from { opacity: 0; transform: translateY(16px) scale(0.98); }
        to { opacity: 1; transform: translateY(0) scale(1); }
    }

    .fi-simple-page-content .fi-section-content-ctn {
        padding: 1.5rem;
    }

    @media (max-width: 480px) {
        .fi-simple-page-content .fi-section-content-ctn {
            padding: 1.25rem 1rem;
        }
    }

    /* ---------- Form fields: forced dark-glass theme, independent of light/dark toggle ---------- */

    .fi-simple-page-content .fi-fo-field-label,
    .fi-simple-page-content .fi-fo-field-label-content {
        color: rgba(253, 243, 247, 0.75) !important;
        font-weight: 600;
        font-size: 0.8125rem;
        letter-spacing: 0.03em;
    }

    .fi-simple-page-content .fi-fo-field-label-required-mark {
        color: #e0709f !important;
    }

    .fi-simple-page-content .fi-input-wrp {
        background-color: rgba(10, 5, 8, 0.55) !important;
        border: 1px solid rgba(217, 182, 106, 0.18) !important;
        border-radius: 0.75rem !important;
        box-shadow: none !important;
        transition: border-color 0.2s ease, box-shadow 0.2s ease;
    }

    .fi-simple-page-content .fi-input-wrp:focus-within {
        border-color: rgba(217, 182, 106, 0.6) !important;
        box-shadow: 0 0 0 3px rgba(217, 182, 106, 0.16) !important;
    }

    .fi-simple-page-content .fi-input {
        color: #fdf3f7 !important;
        caret-color: #d9b66a;
    }

    .fi-simple-page-content .fi-input::placeholder {
        color: rgba(253, 243, 247, 0.32) !important;
    }

    .fi-simple-page-content .fi-input-wrp-actions .fi-icon-btn,
    .fi-simple-page-content .fi-input-wrp-actions svg {
        color: rgba(253, 243, 247, 0.45) !important;
    }

    .fi-simple-page-content .fi-input-wrp-actions .fi-icon-btn:hover svg {
        color: #d9b66a !important;
    }

    /* Remember-me checkbox row */
    .fi-simple-page-content .fi-checkbox-input {
        accent-color: #b02361;
        background-color: rgba(10, 5, 8, 0.55);
        border-color: rgba(217, 182, 106, 0.35);
    }

    .fi-simple-page-content label.fi-fo-field-label {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        color: rgba(253, 243, 247, 0.65) !important;
        font-weight: 500;
    }

    /* Submit button: gradient, glow, lift */
    .fi-simple-page-content .fi-ac-btn-action {
        width: 100%;
        justify-content: center;
        background: linear-gradient(100deg, #b02361, #d9427f 45%, #c79a44) !important;
        background-size: 180% auto;
        background-position: 0% center;
        color: #fdf3f7 !important;
        border: none !important;
        border-radius: 0.75rem !important;
        font-weight: 700;
        letter-spacing: 0.02em;
        padding-block: 0.7rem !important;
        box-shadow: 0 12px 32px -10px rgba(176, 35, 97, 0.65);
        transition: background-position 0.4s ease, transform 0.15s ease, box-shadow 0.2s ease;
    }

    .fi-simple-page-content .fi-ac-btn-action:hover {
        background-position: 100% center;
        box-shadow: 0 16px 40px -8px rgba(176, 35, 97, 0.8);
        transform: translateY(-1px);
    }

    .fi-simple-page-content .fi-ac-btn-action:active {
        transform: translateY(0);
    }

    @media (max-width: 480px) {
        .fi-simple-page-content .fi-ac-btn-action {
            padding-block: 0.65rem !important;
            font-size: 0.875rem;
        }
    }

    /* Tighten vertical rhythm between fields on short mobile viewports */
    @media (max-width: 480px) {
        .fi-simple-page-content .fi-sc-form {
            gap: 1rem !important;
        }
    }
</style>
