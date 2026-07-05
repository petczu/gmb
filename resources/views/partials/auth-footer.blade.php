@php
    $linkStyle = 'color:#1800ff; text-decoration:underline;';
    $link = fn (string $route, string $label): string => '<a href="'.route($route).'" target="_blank" rel="noopener" style="'.$linkStyle.'">'.e($label).'</a>';
@endphp

{{-- Hide the default centered brand logo — it moves to the top-left corner. --}}
<style>
    .fi-simple-header .fi-logo { display: none; }
</style>

{{-- Logo, pinned top-left (Cloudflare-style). --}}
<a href="{{ url('/') }}" style="position:fixed; top:1.25rem; left:1.5rem; z-index:20; display:inline-flex; align-items:center; height:2rem;">
    {!! view('filament.logo', ['theme' => 'light'])->render() !!}
</a>

{{-- Language dropdown, pinned top-right (shared with the legal pages). --}}
<div style="position:fixed; top:1.25rem; right:1.5rem; z-index:20;">
    @include('partials.locale-dropdown')
</div>

{{-- Legal consent line under the auth card. --}}
<div style="max-width:26rem; margin:1.25rem auto 0; text-align:center;">
    <p style="font-size:.75rem; line-height:1.5; color:#9ca3af;">
        {!! __('auth.legal', [
            'terms' => $link('legal.terms', __('auth.terms')),
            'privacy' => $link('legal.privacy', __('auth.privacy')),
            'cookie' => $link('legal.cookies', __('auth.cookie')),
        ]) !!}
    </p>
</div>
