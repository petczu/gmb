{{-- Shared branded shell for the public invitation pages (accept / invalid /
     wrong-account): light theme matching the app panel, soft brand glow,
     top-left logo, centered card. --}}
<style>
    * { box-sizing: border-box; margin: 0; }
    body {
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
        min-height: 100vh; display: flex; align-items: center; justify-content: center;
        padding: 2rem 1.25rem;
        background: radial-gradient(120% 120% at 15% 0%, #eef0ff 0%, #f7f7fc 45%, #ffffff 100%);
        color: #111827; overflow: hidden;
    }
    .glow {
        position: fixed; right: -10rem; bottom: -10rem;
        width: 28rem; height: 28rem; border-radius: 999px;
        background: #2d19ec; opacity: .08; filter: blur(90px);
    }
    .logo { position: fixed; top: 1.75rem; left: 2rem; height: 2rem; display: inline-flex; }
    .lang { position: fixed; top: 1.75rem; right: 2rem; z-index: 20; }
    .wrap { position: relative; width: 26rem; max-width: 92vw; }
    .card {
        background: #fff; border: 1px solid #e5e7eb;
        border-radius: 1.25rem; padding: 2.2rem 2rem; text-align: center;
        box-shadow: 0 18px 45px rgba(17, 12, 60, .08);
    }
    .badge {
        display: inline-block; font-size: .72rem; font-weight: 700; letter-spacing: .04em;
        text-transform: uppercase; padding: .3rem .7rem; border-radius: 999px; margin-bottom: 1.2rem;
    }
    .badge-brand { background: rgba(45, 25, 236, .08); color: #2d19ec; }
    .badge-muted { background: #f3f4f6; color: #6b7280; }
    .icon-ring {
        width: 4rem; height: 4rem; border-radius: 1.1rem; margin: 0 auto 1rem;
        display: flex; align-items: center; justify-content: center;
        background: linear-gradient(135deg, #7c6cf0, #2d19ec);
        box-shadow: 0 10px 26px rgba(45, 25, 236, .3);
    }
    .icon-ring svg { width: 1.9rem; height: 1.9rem; color: #fff; }
    .ws-avatar {
        width: 4rem; height: 4rem; border-radius: 1.1rem; margin: 0 auto 1rem;
        display: flex; align-items: center; justify-content: center;
        font-size: 1.7rem; font-weight: 800; color: #fff;
        background: linear-gradient(135deg, #7c6cf0, #2d19ec);
        box-shadow: 0 10px 26px rgba(45, 25, 236, .3);
    }
    h1 { font-size: 1.4rem; font-weight: 700; margin-bottom: .5rem; letter-spacing: -.01em; color: #111827; }
    p.sub { color: #6b7280; font-size: .95rem; line-height: 1.6; }
    p.sub strong { color: #111827; }
    .role {
        display: inline-block; background: rgba(45, 25, 236, .08); color: #2d19ec;
        padding: .05rem .5rem; border-radius: .4rem; font-weight: 600; font-size: .88em;
    }
    .actions { display: flex; gap: .75rem; justify-content: center; margin-top: 1.6rem; flex-wrap: wrap; }
    .btn {
        display: inline-flex; align-items: center; justify-content: center; gap: 6px;
        border-radius: 8px; padding: 10px 18px; font-size: 14px; line-height: 20px; font-weight: 600;
        text-decoration: none; cursor: pointer; outline: none; border: 0; font-family: inherit;
        transition: background-color .15s ease;
    }
    .btn-primary { background: rgb(24, 0, 255); color: #fff; }
    .btn-primary:hover { background: rgb(45, 25, 236); }
    .btn-ghost { background: #fff; color: #374151; box-shadow: inset 0 0 0 1px #d1d5db; }
    .btn-ghost:hover { background: #f9fafb; }
</style>
