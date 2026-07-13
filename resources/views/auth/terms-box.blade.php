{{-- Google-style terms gate: the register button unlocks only after the user
     scrolled the document to the end (short documents unlock immediately). --}}
<div x-data="{
        read: false,
        check(el) {
            if (this.read) return;
            if (el.scrollTop + el.clientHeight >= el.scrollHeight - 24) {
                this.read = true;
                $wire.set('data.terms_read', true);
            }
        },
    }"
    x-init="$nextTick(() => check($refs.box))"
>
    <div
        x-ref="box"
        @scroll.passive="check($el)"
        style="max-height: 19rem; overflow-y: auto; border: 1px solid rgb(0 0 0 / .1); border-radius: .75rem; padding: 1rem 1.25rem; background: rgb(0 0 0 / .02); font-size: .875rem; line-height: 1.6;"
        class="terms-box-doc"
    >
        <style>
            .terms-box-doc h2 { font-size: .95rem; font-weight: 700; margin: 1.1rem 0 .35rem; }
            .terms-box-doc h2:first-child { margin-top: 0; }
            .terms-box-doc p { margin: .4rem 0; }
        </style>
        {!! $html !!}
    </div>

    {{-- Plain one-line hint that disappears once the end was reached — the
         button coming alive is feedback enough. --}}
    <p x-show="!read" style="margin-top: .6rem; font-size: .8rem; color: rgb(107 114 128); text-align: center;">
        {{ __('auth.terms_scroll_hint') }}
    </p>
</div>
