@php
    /** @var \App\Models\ReviewWidget $widget */
    $s = fn (string $k, $d = null) => $widget->setting($k, $d);
    $token = $widget->token;
    $root = 'reviews-widget-'.$token;
    // CSS is scoped to a class on the content wrapper itself (not the mount id),
    // so the styles apply in the builder preview, the iframe page and the
    // JS-injected embed alike.
    $scope = 'rw-w-'.$token;
    $layout = $widget->layout();
    $reviews = $widget->snapshotReviews();
    $summary = $widget->snapshotSummary();

    $dark = $s('theme') === 'dark';
    $accent = $s('accent', '#2d19ec');
    $cardBg = $s('card_background') ?: ($dark ? '#1f2430' : '#ffffff');
    $textColor = $s('text_color') ?: ($dark ? '#e5e7eb' : '#1f2937');
    $mutedColor = $dark ? '#9ca3af' : '#6b7280';
    $borderColor = $dark ? '#333a48' : '#e9e9ee';
    $colW = (int) $s('target_column_width', 320);
    $gap = (int) $s('gap', 16);
    $radius = (int) $s('rounded', 12);
    $clamp = (int) $s('text_max_lines', 6);

    $stars = function (int $rating) use ($accent): string {
        $out = '';
        for ($i = 1; $i <= 5; $i++) {
            $out .= '<span'.($i <= $rating ? ' style="color:'.$accent.'"' : '').'>'.($i <= $rating ? '★' : '☆').'</span>';
        }

        return $out;
    };

    $title = $s('header_title') ?: ($widget->workspace?->name ?? '');
@endphp
<style>
.{{ $scope }} { --rw-accent: {{ $accent }}; --rw-card: {{ $cardBg }}; --rw-text: {{ $textColor }}; --rw-muted: {{ $mutedColor }}; --rw-border: {{ $borderColor }}; --rw-radius: {{ $radius }}px; --rw-gap: {{ $gap }}px; box-sizing: border-box; color: var(--rw-text); font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; line-height: 1.45; }
.{{ $scope }} * { box-sizing: border-box; }
.{{ $scope }} .rw-header { display: flex; align-items: center; gap: 10px; margin-bottom: var(--rw-gap); }
.{{ $scope }} .rw-header h3 { margin: 0; font-size: 16px; font-weight: 700; }
.{{ $scope }} .rw-summary { display: inline-flex; align-items: center; gap: 6px; font-size: 14px; color: var(--rw-muted); }
.{{ $scope }} .rw-summary b { color: var(--rw-text); font-size: 15px; }
.{{ $scope }} .rw-stars { letter-spacing: 1px; font-size: 15px; color: #d1d5db; white-space: nowrap; }
.{{ $scope }} .rw-card { background: var(--rw-card); border: 1px solid var(--rw-border); border-radius: var(--rw-radius); padding: 16px; display: flex; flex-direction: column; gap: 8px; break-inside: avoid; }
.{{ $scope }} .rw-card-top { display: flex; align-items: center; gap: 10px; }
.{{ $scope }} .rw-avatar { width: 38px; height: 38px; border-radius: 999px; background: var(--rw-accent); color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 700; flex: 0 0 auto; }
.{{ $scope }} .rw-who { min-width: 0; }
.{{ $scope }} .rw-name { font-weight: 600; font-size: 14px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.{{ $scope }} .rw-date { font-size: 12px; color: var(--rw-muted); }
.{{ $scope }} .rw-text { font-size: 14px; margin: 0; @if($clamp > 0) display: -webkit-box; -webkit-line-clamp: {{ $clamp }}; -webkit-box-orient: vertical; overflow: hidden; @endif }
.{{ $scope }} .rw-reply { font-size: 13px; color: var(--rw-muted); border-left: 2px solid var(--rw-border); padding-left: 10px; margin-top: 4px; }
.{{ $scope }} .rw-reply b { color: var(--rw-text); }
.{{ $scope }} .rw-g { margin-left: auto; flex: 0 0 auto; }
.{{ $scope }} .rw-branding { text-align: center; font-size: 12px; color: var(--rw-muted); margin-top: var(--rw-gap); }
.{{ $scope }} .rw-branding a { color: var(--rw-muted); text-decoration: none; }
/* Grid */
.{{ $scope }} .rw-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(min({{ $colW }}px, 100%), 1fr)); gap: var(--rw-gap); }
/* List */
.{{ $scope }} .rw-list { display: flex; flex-direction: column; gap: var(--rw-gap); }
/* Masonry */
.{{ $scope }} .rw-masonry { column-width: {{ $colW }}px; column-gap: var(--rw-gap); }
.{{ $scope }} .rw-masonry .rw-card { margin-bottom: var(--rw-gap); width: 100%; }
/* Slider */
.{{ $scope }} .rw-slider { position: relative; }
.{{ $scope }} .rw-track { display: flex; gap: var(--rw-gap); overflow-x: auto; scroll-snap-type: x mandatory; scroll-behavior: smooth; -webkit-overflow-scrolling: touch; padding-bottom: 4px; }
.{{ $scope }} .rw-track::-webkit-scrollbar { height: 6px; }
.{{ $scope }} .rw-track::-webkit-scrollbar-thumb { background: var(--rw-border); border-radius: 999px; }
.{{ $scope }} .rw-track .rw-card { flex: 0 0 {{ $colW }}px; max-width: 85%; scroll-snap-align: start; }
.{{ $scope }} .rw-nav { position: absolute; top: 50%; transform: translateY(-50%); width: 38px; height: 38px; border-radius: 999px; border: 1px solid var(--rw-border); background: var(--rw-card); color: var(--rw-text); cursor: pointer; font-size: 18px; line-height: 1; display: flex; align-items: center; justify-content: center; box-shadow: 0 2px 8px rgba(0,0,0,.12); z-index: 2; }
.{{ $scope }} .rw-nav:hover { border-color: var(--rw-accent); }
.{{ $scope }} .rw-prev { left: -12px; }
.{{ $scope }} .rw-next { right: -12px; }
.{{ $scope }} .rw-empty { color: var(--rw-muted); font-size: 14px; padding: 24px; text-align: center; }
</style>

<div class="rw-root {{ $scope }}" dir="{{ \App\Support\Locales::isRtl(app()->getLocale()) ? 'rtl' : 'ltr' }}">
    @if($s('show_header') && ($title || $s('show_summary')))
        <div class="rw-header">
            @if($title)<h3>{{ $title }}</h3>@endif
            @if($s('show_summary') && ($summary['count'] ?? 0) > 0)
                <span class="rw-summary">
                    <span class="rw-stars">{!! $stars((int) round($summary['average'])) !!}</span>
                    <b>{{ number_format((float) $summary['average'], 1) }}</b>
                    <span>({{ (int) $summary['count'] }})</span>
                </span>
            @endif
        </div>
    @endif

    @if(count($reviews) === 0)
        <div class="rw-empty">{{ __('pages/review_widgets.embed_empty') }}</div>
    @else
        @php
            $wrapClass = match ($layout) {
                'slider' => 'rw-slider',
                'list' => 'rw-list',
                'masonry' => 'rw-masonry',
                default => 'rw-grid',
            };
        @endphp

        @if($layout === 'slider')
            <div class="rw-slider">
                <button type="button" class="rw-nav rw-prev" aria-label="Previous"
                    onclick="this.parentNode.querySelector('.rw-track').scrollBy({left:-{{ $colW + $gap }},behavior:'smooth'})">‹</button>
                <div class="rw-track">
                    @foreach($reviews as $r) @include('widgets.partials.card', ['r' => $r]) @endforeach
                </div>
                <button type="button" class="rw-nav rw-next" aria-label="Next"
                    onclick="this.parentNode.querySelector('.rw-track').scrollBy({left:{{ $colW + $gap }},behavior:'smooth'})">›</button>
            </div>
        @else
            <div class="{{ $wrapClass }}">
                @foreach($reviews as $r) @include('widgets.partials.card', ['r' => $r]) @endforeach
            </div>
        @endif
    @endif

    @if($s('branding'))
        <div class="rw-branding"><a href="https://{{ parse_url(config('app.url'), PHP_URL_HOST) }}" target="_blank" rel="noopener">{{ __('pages/review_widgets.embed_branding') }}</a></div>
    @endif
</div>

@if(count($reviews) > 0)
    <script type="application/ld+json">{!! json_encode($jsonLd ?? [], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
@endif
