@php
    $linkStyle = 'color:#1800ff; text-decoration:underline;';
    $link = fn (string $route, string $label): string => '<a href="'.route($route).'" target="_blank" rel="noopener" style="'.$linkStyle.'">'.e($label).'</a>';
@endphp

<div style="max-width:26rem; margin:1.25rem auto 0; text-align:center;">
    <div style="margin-bottom:.85rem;">
        @include('partials.locale-switcher')
    </div>

    <p style="font-size:.75rem; line-height:1.5; color:#9ca3af;">
        {!! __('auth.legal', [
            'terms' => $link('legal.terms', __('auth.terms')),
            'privacy' => $link('legal.privacy', __('auth.privacy')),
            'cookie' => $link('legal.cookies', __('auth.cookie')),
        ]) !!}
    </p>
</div>
