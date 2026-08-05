<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ \App\Support\Locales::direction(app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>{{ $share->title ?: $branding['name'] }}</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; background:#f3f4f6; color:#1f2937; margin:0; }
        .top { background:#fff; border-bottom:1px solid #e5e7eb; padding:.7rem 1.2rem; display:flex; align-items:center; gap:.6rem; }
        .top img { height:26px; display:block; }
        .top .nm { font-weight:700; font-size:.95rem; }
        .top select { margin-left:auto; border:1px solid #d1d5db; border-radius:.5rem; padding:.35rem .6rem; font-size:.82rem; background:#fff; color:inherit; }
        .wrap { max-width:58rem; margin:0 auto; padding:1.6rem 1rem 3rem; display:grid; grid-template-columns:minmax(0,26rem) minmax(0,1fr); gap:1.4rem; align-items:start; }
        @media (max-width: 780px) { .wrap { grid-template-columns:1fr; } }
        .cmts { background:#fff; border:1px solid #e5e7eb; border-radius:.9rem; padding:1rem 1.1rem; }
        .cmts h2 { font-size:.95rem; margin:0 0 .6rem; }
        .cmt { display:flex; gap:.55rem; padding:.5rem 0; border-top:1px solid #f1f2f4; }
        .cmt:first-of-type { border-top:0; }
        .cmt.reply { margin-inline-start:1.6rem; }
        .cmt .av { flex:none; width:1.8rem; height:1.8rem; border-radius:999px; background:#eef2ff; color:{{ $branding['color'] }}; font-size:.75rem; font-weight:700; display:grid; place-items:center; }
        .cmt .who { font-size:.78rem; }
        .cmt .who b { font-size:.8rem; }
        .cmt .who span { color:#9ca3af; font-size:.7rem; }
        .cmt .txt { font-size:.84rem; margin-top:.1rem; overflow-wrap:anywhere; }
        .empty { color:#9ca3af; font-size:.83rem; padding:.3rem 0 .6rem; }
        form.guest { margin-top:.6rem; }
        .glabel { font-size:.72rem; font-weight:600; color:#6b7280; margin:0 0 .25rem; }
        input[type="text"], textarea { width:100%; border:1px solid #d1d5db; border-radius:.55rem; padding:.5rem .65rem; font-size:.86rem; font-family:inherit; background:#fff; color:inherit; }
        textarea { resize:vertical; min-height:4.2rem; }
        .bar { display:flex; justify-content:flex-end; margin-top:.55rem; }
        button.send { background:{{ $branding['color'] }}; color:#fff; border:none; border-radius:999px; padding:.5rem 1.1rem; font-size:.85rem; font-weight:600; cursor:pointer; }
        .hello { font-size:.78rem; color:#6b7280; margin-bottom:.35rem; }
        .err { color:#b91c1c; font-size:.8rem; margin-top:.5rem; }
        .foot { text-align:center; font-size:.75rem; color:#9ca3af; padding:0 0 2rem; }
        .foot a { color:{{ $branding['color'] }}; text-decoration:none; font-weight:600; }
    </style>
</head>
<body>
    <div class="top">
        @if ($branding['logo'])
            <img src="{{ $branding['logo'] }}" alt="{{ $branding['name'] }}">
        @else
            <span class="nm">{{ $branding['name'] }}</span>
        @endif

        {{-- Guest language switcher (?lang=xx, remembered per session). --}}
        <select onchange="location.href = '?lang=' + this.value" aria-label="Language">
            @foreach (\App\Support\Locales::ALL as $code => $meta)
                <option value="{{ $code }}" @selected(app()->getLocale() === $code)>{{ $meta['name'] }}</option>
            @endforeach
        </select>
    </div>

    <div class="wrap">
        <div>{!! $share->html !!}</div>

        @if ($canComment)
            <div class="cmts">
                <h2>{{ __('shared.comments') }}</h2>

                @forelse ($comments as $comment)
                    <div class="cmt {{ $comment['reply'] ? 'reply' : '' }}">
                        <span class="av">{{ mb_strtoupper(mb_substr($comment['name'], 0, 1)) }}</span>
                        <span>
                            <span class="who"><b>{{ $comment['name'] }}</b> <span>· {{ $comment['when'] }}</span></span>
                            <div class="txt">{{ $comment['body'] }}</div>
                        </span>
                    </div>
                @empty
                    <div class="empty">{{ __('shared.empty') }}</div>
                @endforelse

                <form class="guest" method="POST" action="{{ route('posts.shared.comment', $share->token) }}">
                    @csrf
                    @if ($guestName === '')
                        {{-- First visit: ask who they are, once. --}}
                        <div class="glabel">{{ __('shared.your_name') }}</div>
                        <input type="text" name="name" required minlength="2" maxlength="60" placeholder="{{ __('shared.your_name') }}" value="{{ old('name') }}">
                        <div class="glabel" style="margin-top:.55rem;">{{ __('shared.comment') }}</div>
                    @else
                        <div class="hello">{{ __('shared.commenting_as') }} <b>{{ $guestName }}</b></div>
                        <input type="hidden" name="name" value="{{ $guestName }}">
                    @endif
                    <textarea name="body" required maxlength="2000" placeholder="{{ __('shared.feedback_ph') }}">{{ old('body') }}</textarea>
                    @if (($errors ?? null)?->any())
                        <div class="err">{{ $errors->first() }}</div>
                    @endif
                    @if ($error)
                        <div class="err">{{ $error }}</div>
                    @endif
                    <div class="bar"><button type="submit" class="send">{{ __('shared.post') }}</button></div>
                </form>
            </div>
        @endif
    </div>

    @unless ($branding['whiteLabel'])
        <div class="foot">{{ __('shared.shared_via') }} <a href="{{ config('app.url') }}" rel="noopener">{{ $branding['name'] }}</a></div>
    @endunless
</body>
</html>
