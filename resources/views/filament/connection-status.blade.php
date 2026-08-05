{{-- Live connection indicator. A persistent toast while the browser is offline
     and a short "back online" toast when it returns. Self-contained (Alpine +
     scoped CSS), injected panel-wide via a BODY_END render hook. --}}
<div
    x-cloak
    x-data="{
        online: navigator.onLine,
        showOnline: false,
        timer: null,
        markOffline() {
            clearTimeout(this.timer);
            this.online = false;
            this.showOnline = false;
        },
        markOnline() {
            this.online = true;
            this.showOnline = true;
            clearTimeout(this.timer);
            this.timer = setTimeout(() => (this.showOnline = false), 4000);
        },
    }"
    @offline.window="markOffline()"
    @online.window="markOnline()"
    class="connection-status"
    aria-live="polite"
>
    {{-- Offline: stays until the connection returns --}}
    <div
        x-show="!online"
        x-transition:enter="cs-enter"
        x-transition:enter-start="cs-enter-start"
        x-transition:enter-end="cs-enter-end"
        x-transition:leave="cs-leave"
        x-transition:leave-start="cs-enter-end"
        x-transition:leave-end="cs-enter-start"
        class="cs-toast cs-offline"
        role="alert"
    >
        <span class="cs-icon">
            <span class="cs-pulse"></span>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M1 1l22 22"/>
                <path d="M16.72 11.06A10.94 10.94 0 0119 12.55"/>
                <path d="M5 12.55a10.94 10.94 0 015.17-2.39"/>
                <path d="M10.71 5.05A16 16 0 0122.58 9"/>
                <path d="M1.42 9a15.91 15.91 0 014.7-2.88"/>
                <path d="M8.53 16.11a6 6 0 016.95 0"/>
                <line x1="12" y1="20" x2="12.01" y2="20"/>
            </svg>
        </span>
        <span class="cs-text">
            <strong>{{ __('connection.offline_title') }}</strong>
            <small>{{ __('connection.offline_body') }}</small>
        </span>
    </div>

    {{-- Back online: appears briefly, then fades out on its own --}}
    <div
        x-show="online && showOnline"
        x-transition:enter="cs-enter"
        x-transition:enter-start="cs-enter-start"
        x-transition:enter-end="cs-enter-end"
        x-transition:leave="cs-leave"
        x-transition:leave-start="cs-enter-end"
        x-transition:leave-end="cs-enter-start"
        class="cs-toast cs-online"
        role="status"
    >
        <span class="cs-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M5 12.55a10.94 10.94 0 0114 0"/>
                <path d="M1.42 9a15.91 15.91 0 0121.16 0"/>
                <path d="M8.53 16.11a6 6 0 016.95 0"/>
                <line x1="12" y1="20" x2="12.01" y2="20"/>
            </svg>
        </span>
        <span class="cs-text">
            <strong>{{ __('connection.online_title') }}</strong>
            <small>{{ __('connection.online_body') }}</small>
        </span>
    </div>
</div>

<style>
    [x-cloak] { display: none !important; }

    .connection-status {
        position: fixed;
        left: 50%;
        bottom: calc(1.25rem + env(safe-area-inset-bottom, 0px));
        transform: translateX(-50%);
        z-index: 9999;
        display: flex;
        justify-content: center;
        pointer-events: none;
    }

    .cs-toast {
        pointer-events: auto;
        display: flex;
        align-items: center;
        gap: 0.75rem;
        max-width: min(92vw, 24rem);
        padding: 0.7rem 1.1rem 0.7rem 0.8rem;
        border-radius: 9999px;
        border: 1px solid var(--cs-border);
        background: var(--cs-bg);
        color: var(--cs-fg);
        box-shadow:
            0 1px 2px rgba(15, 23, 42, 0.08),
            0 12px 32px -8px var(--cs-shadow);
        backdrop-filter: blur(12px) saturate(140%);
        -webkit-backdrop-filter: blur(12px) saturate(140%);
    }

    .cs-icon {
        position: relative;
        flex: none;
        display: grid;
        place-items: center;
        width: 2.1rem;
        height: 2.1rem;
        border-radius: 9999px;
        background: var(--cs-icon-bg);
        color: var(--cs-icon-fg);
    }

    .cs-icon svg { width: 1.15rem; height: 1.15rem; }

    .cs-pulse {
        position: absolute;
        inset: 0;
        border-radius: 9999px;
        background: var(--cs-icon-fg);
        opacity: 0.35;
        animation: cs-pulse 1.8s ease-out infinite;
    }

    .cs-text { display: flex; flex-direction: column; line-height: 1.25; min-width: 0; }
    .cs-text strong { font-size: 0.86rem; font-weight: 600; }
    .cs-text small { font-size: 0.76rem; opacity: 0.72; }

    /* Offline palette (amber/red) */
    .cs-offline {
        --cs-bg: rgba(255, 251, 247, 0.92);
        --cs-fg: #7c2d12;
        --cs-border: rgba(234, 88, 12, 0.28);
        --cs-shadow: rgba(234, 88, 12, 0.35);
        --cs-icon-bg: rgba(234, 88, 12, 0.14);
        --cs-icon-fg: #ea580c;
    }

    /* Online palette (emerald) */
    .cs-online {
        --cs-bg: rgba(247, 254, 250, 0.92);
        --cs-fg: #064e3b;
        --cs-border: rgba(16, 185, 129, 0.28);
        --cs-shadow: rgba(16, 185, 129, 0.32);
        --cs-icon-bg: rgba(16, 185, 129, 0.16);
        --cs-icon-fg: #059669;
    }

    .dark .cs-offline {
        --cs-bg: rgba(38, 24, 16, 0.86);
        --cs-fg: #fed7aa;
        --cs-border: rgba(234, 88, 12, 0.4);
        --cs-shadow: rgba(0, 0, 0, 0.55);
        --cs-icon-bg: rgba(234, 88, 12, 0.24);
        --cs-icon-fg: #fb923c;
    }

    .dark .cs-online {
        --cs-bg: rgba(15, 34, 28, 0.86);
        --cs-fg: #a7f3d0;
        --cs-border: rgba(16, 185, 129, 0.4);
        --cs-shadow: rgba(0, 0, 0, 0.55);
        --cs-icon-bg: rgba(16, 185, 129, 0.26);
        --cs-icon-fg: #34d399;
    }

    /* Alpine transitions: slide up + fade + slight scale */
    .cs-enter { transition: transform 0.32s cubic-bezier(0.22, 1, 0.36, 1), opacity 0.32s ease; }
    .cs-leave { transition: transform 0.24s ease, opacity 0.24s ease; }
    .cs-enter-start { opacity: 0; transform: translateY(14px) scale(0.96); }
    .cs-enter-end { opacity: 1; transform: translateY(0) scale(1); }

    @keyframes cs-pulse {
        0% { transform: scale(1); opacity: 0.35; }
        70% { transform: scale(1.9); opacity: 0; }
        100% { transform: scale(1.9); opacity: 0; }
    }

    @media (prefers-reduced-motion: reduce) {
        .cs-enter, .cs-leave { transition-duration: 0.001s; }
        .cs-pulse { animation: none; }
    }
</style>
