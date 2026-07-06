@php
    /** @var string $mode 'login' | 'register' */
    $isLogin = ($mode ?? 'login') === 'login';
@endphp

{{-- Split-screen auth: marketing hero on the left (desktop only), the Filament
     card moves to the right half. Below lg the hero hides and the default
     centered card returns. --}}
<style>
    @media (min-width: 1024px) {
        .fi-simple-main-ctn { margin-inline-start: 50vw; width: 50vw; }
        .auth-hero { display: flex !important; }
        .auth-corner-logo { display: none !important; }
    }
</style>

<div class="auth-hero"
     style="display:none; position:fixed; top:0; bottom:0; left:0; width:50vw; z-index:10;
            background:radial-gradient(120% 120% at 15% 0%, #241a5e 0%, #170f3d 45%, #0e0a26 100%);
            flex-direction:column; justify-content:center; padding:4.5rem 5rem; overflow:hidden;">

    {{-- Soft accent glow --}}
    <div style="position:absolute; right:-8rem; bottom:-8rem; width:26rem; height:26rem; border-radius:999px; background:#2d19ec; opacity:.25; filter:blur(90px);"></div>

    {{-- Logo, top-left of the hero --}}
    <a href="{{ url('/') }}" style="position:absolute; top:2rem; left:5rem; display:inline-flex; height:2.1rem;">
        {!! view('filament.logo', ['theme' => 'dark'])->render() !!}
    </a>

    <div style="position:relative; max-width:30rem;">
        @if ($isLogin)
            <span style="display:inline-block; background:rgba(255,255,255,.1); border:1px solid rgba(255,255,255,.2); color:#c7bfff; font-size:.78rem; font-weight:700; letter-spacing:.02em; border-radius:999px; padding:.3rem .85rem; margin-bottom:1.25rem;">
                {{ __('auth.hero_badge') }}
            </span>
            <h1 style="color:#fff; font-size:2.6rem; line-height:1.15; font-weight:800; margin:0 0 1.1rem;">
                {!! __('auth.hero_update_title', ['accent' => '<span style="color:#a08cff;">']) !!}
            </h1>
            <p style="color:#b9b3d9; font-size:1.05rem; line-height:1.65; margin:0;">
                {{ __('auth.hero_update_text') }}
            </p>

            {{-- Product mockup: floating UI cards (hidden on short screens). --}}
            <style>
                @media (max-height: 730px) { .auth-hero-mockup { display:none; } }
            </style>
            <div class="auth-hero-mockup" style="position:relative; margin-top:3rem; height:15.5rem;">
                {{-- Review card with an AI reply --}}
                <div style="position:absolute; left:0; top:1.2rem; width:19rem; background:rgba(255,255,255,.08); border:1px solid rgba(255,255,255,.16); border-radius:1rem; padding:1rem 1.1rem; backdrop-filter:blur(8px); transform:rotate(-2deg); box-shadow:0 18px 44px rgba(0,0,0,.35);">
                    <div style="display:flex; align-items:center; gap:.55rem;">
                        <span style="width:1.9rem; height:1.9rem; border-radius:999px; background:#7c6cf0; color:#fff; display:inline-flex; align-items:center; justify-content:center; font-size:.8rem; font-weight:700;">AM</span>
                        <div>
                            <div style="color:#fff; font-size:.82rem; font-weight:600;">Anna M.</div>
                            <div style="color:#f5b301; font-size:.78rem; letter-spacing:.1em;">★★★★★</div>
                        </div>
                    </div>
                    <p style="color:#d6d2ec; font-size:.82rem; line-height:1.5; margin:.6rem 0 .7rem;">“Amazing experience! Monica was the best game master we ever had.”</p>
                    <div style="display:flex; align-items:center; gap:.45rem; border-top:1px solid rgba(255,255,255,.12); padding-top:.65rem;">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="#a08cff" style="width:.95rem; height:.95rem;"><path fill-rule="evenodd" d="M9 4.5a.75.75 0 0 1 .721.544l.813 2.846a3.75 3.75 0 0 0 2.576 2.576l2.846.813a.75.75 0 0 1 0 1.442l-2.846.813a3.75 3.75 0 0 0-2.576 2.576l-.813 2.846a.75.75 0 0 1-1.442 0l-.813-2.846a3.75 3.75 0 0 0-2.576-2.576l-2.846-.813a.75.75 0 0 1 0-1.442l2.846-.813A3.75 3.75 0 0 0 7.466 7.89l.813-2.846A.75.75 0 0 1 9 4.5Z" clip-rule="evenodd"/></svg>
                        <span style="color:#b9b3d9; font-size:.76rem;">AI reply published</span>
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="#34d399" stroke-width="3" style="width:.8rem; height:.8rem; margin-left:auto;"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                    </div>
                </div>

                {{-- Review growth card --}}
                <div style="position:absolute; right:1.5rem; top:0; width:11.5rem; background:rgba(255,255,255,.08); border:1px solid rgba(255,255,255,.16); border-radius:1rem; padding:.85rem 1rem; backdrop-filter:blur(8px); transform:rotate(2deg); box-shadow:0 18px 44px rgba(0,0,0,.35);">
                    <div style="color:#8f88b8; font-size:.68rem; text-transform:uppercase; letter-spacing:.06em;">Reviews · 30d</div>
                    <div style="color:#fff; font-size:1.35rem; font-weight:800; margin:.1rem 0 .4rem;">+38</div>
                    <div style="display:flex; align-items:flex-end; gap:3px; height:2rem;">
                        @foreach ([35, 50, 40, 65, 55, 80, 100] as $h)
                            <span style="flex:1; height:{{ $h }}%; background:linear-gradient(180deg, #a08cff, #2d19ec); border-radius:2px 2px 0 0;"></span>
                        @endforeach
                    </div>
                </div>

                {{-- Competitor comparison card --}}
                <div style="position:absolute; right:0; bottom:0; width:14.5rem; background:rgba(255,255,255,.08); border:1px solid rgba(255,255,255,.16); border-radius:1rem; padding:.85rem 1rem; backdrop-filter:blur(8px); transform:rotate(-1deg); box-shadow:0 18px 44px rgba(0,0,0,.35);">
                    <div style="color:#8f88b8; font-size:.68rem; text-transform:uppercase; letter-spacing:.06em;">vs Puzzle Peak</div>
                    <div style="display:flex; align-items:center; gap:.5rem; margin-top:.35rem;">
                        <span style="background:rgba(52,211,153,.15); border:1px solid rgba(52,211,153,.4); color:#34d399; font-size:.76rem; font-weight:700; border-radius:999px; padding:.2rem .6rem;">You lead by 0.4 ★</span>
                    </div>
                </div>
            </div>
        @else
            <h1 style="color:#fff; font-size:2.6rem; line-height:1.15; font-weight:800; margin:0 0 1.1rem;">
                {!! __('auth.hero_register_title', ['accent' => '<span style="color:#a08cff;">']) !!}
            </h1>
            <p style="color:#b9b3d9; font-size:1.05rem; line-height:1.65; margin:0 0 1.6rem;">
                {{ __('auth.hero_register_subtitle') }}
            </p>
            <ul style="list-style:none; margin:0; padding:0; display:flex; flex-direction:column; gap:.8rem;">
                @foreach (__('auth.hero_register_points') as $point)
                    <li style="display:flex; align-items:flex-start; gap:.65rem; color:#e4e1f5; font-size:.98rem; line-height:1.5;">
                        <span style="flex-shrink:0; width:1.35rem; height:1.35rem; border-radius:999px; background:#2d19ec; display:inline-flex; align-items:center; justify-content:center; margin-top:.1rem;">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="3" style="width:.75rem; height:.75rem;"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                        </span>
                        {{ $point }}
                    </li>
                @endforeach
            </ul>
            <p style="color:#8f88b8; font-size:.85rem; margin:1.8rem 0 0;">
                {{ __('auth.hero_register_footnote') }}
            </p>

            {{-- Product mockup: floating UI cards (hidden on short screens). --}}
            <style>
                @media (max-height: 860px) { .auth-hero-mockup-reg { display:none; } }
            </style>
            <div class="auth-hero-mockup-reg" style="position:relative; margin-top:2.6rem; height:13.5rem;">
                {{-- Monthly report card --}}
                <div style="position:absolute; left:0; top:.9rem; width:17rem; background:rgba(255,255,255,.08); border:1px solid rgba(255,255,255,.16); border-radius:1rem; padding:1rem 1.1rem; backdrop-filter:blur(8px); transform:rotate(-2deg); box-shadow:0 18px 44px rgba(0,0,0,.35);">
                    <div style="color:#8f88b8; font-size:.68rem; text-transform:uppercase; letter-spacing:.06em;">Monthly report</div>
                    <div style="display:flex; align-items:baseline; gap:.5rem; margin:.15rem 0 .55rem;">
                        <span style="color:#fff; font-size:1.5rem; font-weight:800;">4.8 ★</span>
                        <span style="color:#34d399; font-size:.78rem; font-weight:700;">▲ +0.2</span>
                    </div>
                    <div style="display:flex; align-items:center; gap:.45rem; border-top:1px solid rgba(255,255,255,.12); padding-top:.6rem;">
                        <span style="color:#b9b3d9; font-size:.76rem;">Scheduled monthly</span>
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="#34d399" stroke-width="3" style="width:.8rem; height:.8rem; margin-left:auto;"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                    </div>
                </div>

                {{-- QR review page card --}}
                <div style="position:absolute; right:1.2rem; top:0; width:12.5rem; background:rgba(255,255,255,.08); border:1px solid rgba(255,255,255,.16); border-radius:1rem; padding:.9rem 1rem; backdrop-filter:blur(8px); transform:rotate(2deg); box-shadow:0 18px 44px rgba(0,0,0,.35); display:flex; align-items:center; gap:.8rem;">
                    <div style="display:grid; grid-template-columns:repeat(4, .45rem); grid-auto-rows:.45rem; gap:.14rem; flex-shrink:0;">
                        @foreach ([1,1,0,1, 1,0,1,0, 0,1,1,1, 1,0,1,1] as $cell)
                            <span style="border-radius:2px; background:{{ $cell ? '#fff' : 'rgba(255,255,255,.15)' }};"></span>
                        @endforeach
                    </div>
                    <div>
                        <div style="color:#fff; font-size:.82rem; font-weight:700;">Scan to review</div>
                        <div style="color:#8f88b8; font-size:.72rem;">QR review page</div>
                    </div>
                </div>

                {{-- AI autopilot card: the AI writing a reply --}}
                <div style="position:absolute; right:0; bottom:0; width:17rem; background:rgba(255,255,255,.08); border:1px solid rgba(255,255,255,.16); border-radius:1rem; padding:.9rem 1rem; backdrop-filter:blur(8px); transform:rotate(-1deg); box-shadow:0 18px 44px rgba(0,0,0,.35);">
                    <div style="display:flex; align-items:center; gap:.45rem;">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="#a08cff" style="width:.95rem; height:.95rem;"><path fill-rule="evenodd" d="M9 4.5a.75.75 0 0 1 .721.544l.813 2.846a3.75 3.75 0 0 0 2.576 2.576l2.846.813a.75.75 0 0 1 0 1.442l-2.846.813a3.75 3.75 0 0 0-2.576 2.576l-.813 2.846a.75.75 0 0 1-1.442 0l-.813-2.846a3.75 3.75 0 0 0-2.576-2.576l-2.846-.813a.75.75 0 0 1 0-1.442l2.846-.813A3.75 3.75 0 0 0 7.466 7.89l.813-2.846A.75.75 0 0 1 9 4.5Z" clip-rule="evenodd"/></svg>
                        <span style="color:#fff; font-size:.82rem; font-weight:700;">AI Autopilot</span>
                        <span style="margin-left:auto; display:inline-flex; align-items:center; gap:.3rem; color:#34d399; font-size:.72rem; font-weight:700;">
                            <span style="width:.5rem; height:.5rem; border-radius:999px; background:#34d399; box-shadow:0 0 8px rgba(52,211,153,.8);"></span> on
                        </span>
                    </div>
                    <div style="background:rgba(160,140,255,.12); border:1px solid rgba(160,140,255,.25); border-radius:.65rem; padding:.5rem .65rem; margin:.55rem 0 .45rem; color:#d6d2ec; font-size:.74rem; line-height:1.45; font-style:italic;">
                        “Thanks Anna! So glad Monica made your visit special.”
                    </div>
                    <div style="color:#8f88b8; font-size:.72rem;">12 AI replies sent this week</div>
                </div>
            </div>
        @endif
    </div>
</div>
