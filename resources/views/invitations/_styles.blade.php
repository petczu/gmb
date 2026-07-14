{{-- Shared branded shell for the public invitation pages (accept / invalid /
     wrong-account): dark gradient, glow, top-left logo, centered card. Mirrors
     the design language of resources/views/errors/*. --}}
<style>
    * { box-sizing: border-box; margin: 0; }
    body {
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
        min-height: 100vh; display: flex; align-items: center; justify-content: center;
        padding: 2rem 1.25rem;
        background: radial-gradient(120% 120% at 15% 0%, #241a5e 0%, #170f3d 45%, #0e0a26 100%);
        color: #fff; overflow: hidden;
    }
    .glow {
        position: fixed; right: -10rem; bottom: -10rem;
        width: 28rem; height: 28rem; border-radius: 999px;
        background: #2d19ec; opacity: .25; filter: blur(90px);
    }
    .logo { position: fixed; top: 1.75rem; left: 2rem; height: 2rem; display: inline-flex; }
    .wrap { position: relative; width: 26rem; max-width: 92vw; }
    .card {
        background: rgba(255,255,255,.07); border: 1px solid rgba(255,255,255,.14);
        border-radius: 1.25rem; padding: 2.2rem 2rem; text-align: center;
        backdrop-filter: blur(10px); box-shadow: 0 24px 60px rgba(0,0,0,.4);
    }
    .badge {
        display: inline-block; font-size: .72rem; font-weight: 700; letter-spacing: .04em;
        text-transform: uppercase; padding: .3rem .7rem; border-radius: 999px; margin-bottom: 1.2rem;
    }
    .badge-brand { background: rgba(124,108,240,.2); color: #c9c0ff; }
    .badge-muted { background: rgba(255,255,255,.1); color: #cfc9ec; }
    .icon-ring {
        width: 4.2rem; height: 4.2rem; border-radius: 999px; margin: 0 auto 1rem;
        display: flex; align-items: center; justify-content: center; font-size: 1.9rem;
        background: linear-gradient(135deg, rgba(124,108,240,.35), rgba(45,25,236,.25));
        border: 1px solid rgba(255,255,255,.16);
    }
    .ws-avatar {
        width: 4rem; height: 4rem; border-radius: 1.1rem; margin: 0 auto 1rem;
        display: flex; align-items: center; justify-content: center;
        font-size: 1.7rem; font-weight: 800; color: #fff;
        background: linear-gradient(135deg, #7c6cf0, #2d19ec);
        box-shadow: 0 10px 26px rgba(45,25,236,.45);
    }
    h1 { font-size: 1.4rem; font-weight: 700; margin-bottom: .5rem; letter-spacing: -.01em; }
    p.sub { color: #b9b3d9; font-size: .95rem; line-height: 1.6; }
    p.sub strong { color: #fff; }
    .role {
        display: inline-block; background: rgba(255,255,255,.12); color: #fff;
        padding: .05rem .5rem; border-radius: .4rem; font-weight: 600; font-size: .88em;
    }
    .actions { display: flex; gap: .75rem; justify-content: center; margin-top: 1.6rem; flex-wrap: wrap; }
    .btn {
        display: inline-flex; align-items: center; justify-content: center; gap: 6px;
        border-radius: 8px; padding: 10px 18px; font-size: 14px; line-height: 20px; font-weight: 600;
        text-decoration: none; cursor: pointer; outline: none; border: 0;
        transition: background-color .15s ease;
    }
    .btn-primary { background: rgb(24, 0, 255); color: #fff; }
    .btn-primary:hover { background: rgb(45, 25, 236); }
    .btn-ghost { background: rgba(255,255,255,.06); color: #fff; box-shadow: inset 0 0 0 1px rgba(255,255,255,.2); }
    .btn-ghost:hover { background: rgba(255,255,255,.12); }
</style>
