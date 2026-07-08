{{-- Small connect-first invite floating over the demo-data dashboard. The
     wrapper ignores clicks (nav + demo widgets stay reachable); only the card
     itself is interactive. Theme-aware via the `dark` class on <html>. --}}
<style>
    .demo-cta-card { pointer-events: auto; max-width: 26rem; width: 100%; background: #fff; border: 1px solid #e5e7eb; border-radius: 1rem; box-shadow: 0 22px 60px -12px rgba(0,0,0,.3); padding: 1.5rem 1.6rem; text-align: center; }
    .dark .demo-cta-card { background: #18181b; border-color: rgba(255,255,255,.12); box-shadow: 0 22px 60px -12px rgba(0,0,0,.7); }
    .demo-cta-title { font-size: 1.05rem; font-weight: 700; margin: .55rem 0 .3rem; color: #111827; }
    .dark .demo-cta-title { color: #f4f4f5; }
    .demo-cta-text { font-size: .875rem; line-height: 1.55; color: #6b7280; margin: 0 0 1rem; }
    .dark .demo-cta-text { color: #a1a1aa; }
    .demo-cta-note { display: block; margin-top: .6rem; font-size: .75rem; color: #9ca3af; }
</style>

<div style="position:fixed; inset:0; z-index:45; display:flex; align-items:center; justify-content:center; padding:1rem; pointer-events:none;">
    <div class="demo-cta-card">
        <div style="width:2.6rem; height:2.6rem; margin:0 auto; border-radius:999px; background:#2d19ec; color:#fff; display:flex; align-items:center; justify-content:center;">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z"/></svg>
        </div>
        <div class="demo-cta-title">{{ __('pages/dashboard.demo_title') }}</div>
        <p class="demo-cta-text">{{ __('pages/dashboard.demo_text') }}</p>
        <a href="{{ route('zernio.google.connect') }}"
           style="display:inline-flex; align-items:center; gap:6px; border-radius:8px; padding:8px 14px; font-size:14px; line-height:20px; font-weight:500; text-decoration:none; background:rgb(24,0,255); color:#fff;">
            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 0 1 1.242 7.244l-4.5 4.5a4.5 4.5 0 0 1-6.364-6.364l1.757-1.757m13.35-.622 1.757-1.757a4.5 4.5 0 0 0-6.364-6.364l-4.5 4.5a4.5 4.5 0 0 0 1.242 7.244"/></svg>
            {{ __('pages/dashboard.empty_cta') }}
        </a>
        <span class="demo-cta-note">{{ __('pages/dashboard.demo_note') }}</span>
    </div>
</div>
