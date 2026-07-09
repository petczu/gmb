<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Performance report, {{ $data['businessName'] }}</title>
    @include('partials.favicons')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
    <style>
        * { box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; color: #1f2937; margin: 0; background: #fff; font-size: 13px; line-height: 1.5; }
        .page { max-width: 880px; margin: 0 auto; padding: 28px 32px 40px; }
        .head { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 3px solid var(--brand); padding-bottom: 14px; margin-bottom: 22px; }
        .head h1 { margin: 0 0 2px; font-size: 22px; }
        .head .sub { color: #6b7280; font-size: 12px; }
        .head .brand { text-align: right; color: var(--brand); font-weight: 700; letter-spacing: .04em; font-size: 12px; }
        h2 { font-size: 14px; text-transform: uppercase; letter-spacing: .05em; color: #374151; margin: 26px 0 12px; border-bottom: 1px solid #e5e7eb; padding-bottom: 5px; }
        h2:first-of-type { margin-top: 6px; }
        .lead { color: #4b5563; margin: 0 0 12px; }
        .kpis { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; }
        .kpi { border: 1px solid #e5e7eb; border-radius: 10px; padding: 12px 14px; }
        .kpi .label { color: #6b7280; font-size: 11px; text-transform: uppercase; letter-spacing: .04em; }
        .kpi .value { font-size: 24px; font-weight: 700; margin: 4px 0 2px; }
        .kpi .sub { color: #9ca3af; font-size: 11px; }
        .kpi .delta { font-size: 11px; font-weight: 600; }
        .up { color: #16a34a; } .down { color: #dc2626; } .flat { color: #9ca3af; }
        .grid2 { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; align-items: start; }
        .card { border: 1px solid #e5e7eb; border-radius: 10px; padding: 14px 16px; }
        .summary { background: #fffbeb; border: 1px solid #fde68a; border-radius: 10px; padding: 14px 16px; }
        ul.recs { margin: 6px 0 0; padding-left: 18px; } ul.recs li { margin-bottom: 5px; }
        ul.notes { margin: 0; padding-left: 18px; color: #4b5563; } ul.notes li { margin-bottom: 6px; }
        table { width: 100%; border-collapse: collapse; font-size: 12px; }
        th, td { text-align: left; padding: 7px 8px; border-bottom: 1px solid #eef2f7; vertical-align: top; }
        thead th { background: #1f2937; color: #fff; text-transform: uppercase; font-size: 10px; letter-spacing: .04em; }
        thead th:first-child { border-top-left-radius: 8px; } thead th:last-child { border-top-right-radius: 8px; }
        tr.total td { font-weight: 700; background: #f9fafb; }
        .num { color: #b91c1c; font-weight: 700; }
        .pill { display: inline-block; padding: 1px 8px; border-radius: 999px; font-size: 11px; font-weight: 600; }
        .pos { background: #dcfce7; color: #166534; } .neg { background: #fee2e2; color: #991b1b; } .mix { background: #fef9c3; color: #854d0e; }
        .flag-high { color: #b91c1c; font-weight: 700; } .flag-medium { color: #b45309; font-weight: 700; }
        .review { border-left: 3px solid #e5e7eb; padding: 4px 0 4px 12px; margin-bottom: 12px; }
        .review.good { border-color: #16a34a; } .review.bad { border-color: #dc2626; }
        .review .meta { color: #6b7280; font-size: 11px; margin-bottom: 2px; }
        .stars { color: #f59e0b; letter-spacing: 1px; }
        .foot { margin-top: 30px; border-top: 1px solid #e5e7eb; padding-top: 10px; color: #9ca3af; font-size: 11px; text-align: center; }
        .chartbox { height: 220px; }
        /* Cadence heatmap */
        .heat { display: flex; flex-wrap: wrap; gap: 4px; margin: 4px 0 10px; }
        .heat .cell { width: 34px; text-align: center; }
        .heat .cell .d { background: #1f2937; color: #fff; font-size: 10px; font-weight: 700; line-height: 1.1; padding: 2px 0; border-radius: 4px 4px 0 0; }
        .heat .cell .d .dow { display: block; font-size: 7px; font-weight: 600; color: #dedede; letter-spacing: .03em; padding-bottom: 1px; }
        .heat .cell .c { font-size: 12px; font-weight: 700; padding: 4px 0; border-radius: 0 0 4px 4px; }
        .lvl-none .c { background: #f3f4f6; color: #c0c4cc; } .lvl-low .c { background: #dcfce7; color: #166534; }
        .lvl-mid .c { background: #fef3c7; color: #92400e; } .lvl-high .c { background: #fee2e2; color: #991b1b; }
        .legend { font-size: 11px; color: #6b7280; margin-bottom: 10px; }
        .legend b.g { color: #166534; } .legend b.a { color: #92400e; } .legend b.r { color: #991b1b; }
        .callout { background: #fef2f2; border-left: 4px solid #ef4444; border-radius: 8px; padding: 12px 14px; margin-top: 12px; }
        .callout strong { color: #b91c1c; }
        .themes { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
        .themes ul { margin: 6px 0 0; padding-left: 18px; } .themes li { margin-bottom: 4px; }
        .statgrid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; }
        @media print { .page { padding: 0; } .card, .kpi { break-inside: avoid; } h2 { break-after: avoid; } }
    </style>
</head>
@php
    use App\Support\ReportBlocks;
    $brand = $brand ?? ['name' => 'Repunio', 'color' => '#2d19ec', 'logo' => null];
@endphp
<body style="--brand: {{ $brand['color'] }};">
@php
    $blocks = $blocks ?? ReportBlocks::ORDER;
    $has = fn (string $k): bool => in_array($k, $blocks, true);
    $k = $data['kpis'];
    $deltaClass = fn ($d) => $d > 0 ? 'up' : ($d < 0 ? 'down' : 'flat');
    $deltaStr = fn ($d, $suffix = '') => ($d > 0 ? '▲ +' : ($d < 0 ? '▼ ' : '')) . $d . $suffix;
    $sentClass = ['positive' => 'pos', 'negative' => 'neg', 'mixed' => 'mix'];
    $distLabels = [];
    foreach ([5, 4, 3, 2, 1] as $sIdx) {
        $distLabels[] = $sIdx.'★ ('.$data['distribution'][$sIdx].')';
    }
@endphp
<div class="page">
    <div class="head">
        <div>
            <h1>{{ $data['businessName'] }}</h1>
            <div class="sub">{{ __('report.performance_report') }} · {{ $data['periodLabel'] }}@if($data['compare']) · {{ __('report.footer_compared', ['period' => $data['previousLabel']]) }}@endif</div>
        </div>
        <div class="brand">
            @if(!empty($brand['logo']))
                <img src="{{ $brand['logo'] }}" alt="{{ $brand['name'] }}" style="height:26px; width:auto; display:inline-block;">
            @else
                {{ strtoupper($brand['name']) }}
            @endif
            <br><span style="color:#9ca3af;font-weight:400;">{{ __('report.generated', ['date' => $generatedAt]) }}</span>
        </div>
    </div>

    @if($has('glance'))
        <div class="kpis">
            <div class="kpi">
                <div class="label">{{ __('report.reviews_received') }}</div>
                <div class="value">{{ $k['total']['value'] }}</div>
                @if($data['compare'])<div class="delta {{ $deltaClass($k['total']['delta']) }}">{{ $deltaStr($k['total']['delta']) }} {{ __('report.vs_prev') }}</div>@else<div class="sub">{{ $data['periodLabel'] }}</div>@endif
            </div>
            <div class="kpi">
                <div class="label">{{ __('report.average_rating') }}</div>
                <div class="value">{{ number_format((float)$k['avg']['value'], 2) }}★</div>
                @if($data['compare'])<div class="delta {{ $deltaClass($k['avg']['delta']) }}">{{ $deltaStr($k['avg']['delta']) }} {{ __('report.vs_prev') }}</div>@else<div class="sub">{{ __('report.out_of_5') }}</div>@endif
            </div>
            <div class="kpi">
                <div class="label">{{ __('report.five_star_share') }}</div>
                <div class="value">{{ $data['fiveStarShare'] }}%</div>
                <div class="sub">{{ $data['distribution'][5] }} {{ __('report.of') }} {{ $k['total']['value'] }}</div>
            </div>
            <div class="kpi">
                <div class="label">{{ __('report.response_rate') }}</div>
                <div class="value">{{ $k['responseRate']['value'] }}%</div>
                @if($data['compare'])<div class="delta {{ $deltaClass($k['responseRate']['delta']) }}">{{ $deltaStr($k['responseRate']['delta'], ' pp') }} {{ __('report.vs_prev') }}</div>@else<div class="sub">{{ $k['replied']['value'] }} {{ __('report.replies_sent') }}</div>@endif
            </div>
        </div>
    @endif

    @if($has('performance') && ! empty($data['performance']))
        @php $perf = $data['performance']; @endphp
        <h2 style="margin-top:22px;">{{ __('report.perf_title') }}</h2>
        <div class="kpis" style="grid-template-columns: repeat(5, 1fr);">
            @foreach($perf['kpis'] as $pk)
                <div class="kpi">
                    <div class="label">{{ __('report.perf_'.$pk['key']) }}</div>
                    <div class="value">{{ number_format($pk['value']) }}</div>
                    @if($pk['pct'] !== null && $pk['pct'] !== 0)
                        <div class="delta {{ $deltaClass($pk['pct']) }}">{{ $deltaStr($pk['pct'], '%') }} {{ __('report.vs_prev') }}</div>
                    @else
                        <div class="sub">{{ __('report.perf_'.$pk['key'].'_sub') }}</div>
                    @endif
                </div>
            @endforeach
        </div>
        <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-top: 12px;">
            <div class="kpi">
                <div class="label">{{ __('report.perf_breakdown') }}</div>
                @php
                    $perfColors = ['search_desktop' => '#2563eb', 'search_mobile' => '#ef4444', 'maps_desktop' => '#f59e0b', 'maps_mobile' => '#22c55e'];
                    $perfRadius = 38;
                    $perfCircumference = 2 * M_PI * $perfRadius;
                    $perfOffset = 0.0;
                @endphp
                <div style="display:flex; align-items:center; gap:16px; margin-top:8px;">
                    <svg viewBox="0 0 100 100" width="110" height="110" style="flex:none;">
                        <circle cx="50" cy="50" r="{{ $perfRadius }}" fill="none" stroke="#f3f4f6" stroke-width="15"/>
                        @foreach($perfColors as $bk => $color)
                            @php
                                $pct = $perf['views'] > 0 ? ($perf['breakdown'][$bk] ?? 0) / $perf['views'] * 100 : 0;
                                $len = max(0, min(100, $pct)) / 100 * $perfCircumference;
                            @endphp
                            @if($len > 0)
                                <circle cx="50" cy="50" r="{{ $perfRadius }}" fill="none"
                                    stroke="{{ $color }}" stroke-width="15"
                                    stroke-dasharray="{{ round($len, 2) }} {{ round($perfCircumference - $len, 2) }}"
                                    stroke-dashoffset="{{ round(-$perfOffset, 2) }}"
                                    transform="rotate(-90 50 50)"/>
                            @endif
                            @php $perfOffset += $len; @endphp
                        @endforeach
                        <text x="50" y="54" text-anchor="middle" font-size="14" font-weight="700" fill="#111827">{{ $perf['views'] >= 10000 ? round($perf['views'] / 1000, 1).'K' : number_format($perf['views']) }}</text>
                    </svg>
                    <table style="flex:1; border-collapse:collapse; font-size:11px;">
                        @foreach(array_keys($perfColors) as $bk)
                            <tr>
                                <td style="padding:3px 6px 3px 0;"><span style="display:inline-block; width:8px; height:8px; border-radius:99px; background:{{ $perfColors[$bk] }};"></span></td>
                                <td style="padding:3px 0; color:#6b7280;">{{ __('report.perf_'.$bk) }}</td>
                                <td style="padding:3px 0; text-align:right; font-weight:600;">{{ number_format($perf['breakdown'][$bk] ?? 0) }}</td>
                                <td style="padding:3px 0 3px 8px; text-align:right; color:#9ca3af; width:44px;">{{ $perf['views'] > 0 ? round(($perf['breakdown'][$bk] ?? 0) / $perf['views'] * 100, 1) : 0 }}%</td>
                            </tr>
                        @endforeach
                    </table>
                </div>
            </div>
            <div class="kpi">
                <div class="label">{{ __('report.perf_searches') }}</div>
                @if(!empty($perf['keywords']))
                    <table style="width:100%; border-collapse:collapse; font-size:11px; margin-top:6px;">
                        @foreach($perf['keywords'] as $i => $kw)
                            <tr>
                                <td style="padding:3px 4px 3px 0; color:#9ca3af; width:16px;">{{ $i + 1 }}.</td>
                                <td style="padding:3px 0;">{{ $kw['keyword'] }}</td>
                                <td style="padding:3px 0; text-align:right; font-weight:600;">{{ number_format($kw['impressions']) }}</td>
                            </tr>
                        @endforeach
                    </table>
                @else
                    <div class="sub" style="margin-top:6px;">—</div>
                @endif
            </div>
        </div>
        <p style="font-size:10px; color:#9ca3af; margin:8px 0 2px;">{{ __('report.perf_note') }}</p>
    @endif

    @if($has('summary'))
        <h2>{{ __('report.executive_summary') }}</h2>
        <div class="summary"><p style="margin:0;">{{ $insights['summary'] }}</p></div>
    @endif

    @if($has('topics') && !empty($insights['topics']))
        <h2>{{ __('report.topics_title') }}</h2>
        @if(!empty($insights['topicsSummary']))<p class="lead">{{ $insights['topicsSummary'] }}</p>@endif
        <div style="display:flex; flex-wrap:wrap; gap:8px;">
            @foreach($insights['topics'] as $t)
                <span class="pill {{ $sentClass[$t['sentiment']] ?? 'mix' }}" style="font-size:12px; padding:5px 12px;">
                    {{ $t['label'] }}@if(!empty($t['mentions'])) · {{ $t['mentions'] }}@endif
                </span>
            @endforeach
        </div>
    @endif

    @if($has('staff') && !empty($insights['staff']))
        @php $totalCredits = array_sum(array_map(fn ($s) => $s['mentions'] ?? 0, $insights['staff'])); @endphp
        <h2>{{ __('report.staff_mentions') }}</h2>
        <p class="lead">{{ __('report.staff_intro') }}</p>
        <table>
            <thead><tr><th>{{ __('report.name') }}</th><th>{{ __('report.mentions') }}</th><th>{{ __('report.share_credits') }}</th><th>{{ __('report.sentiment') }}</th><th>{{ __('report.notes') }}</th></tr></thead>
            <tbody>
            @foreach($insights['staff'] as $s)
                <tr>
                    <td><strong>{{ $s['name'] ?? '—' }}</strong></td>
                    <td class="num">{{ $s['mentions'] ?? 0 }}</td>
                    <td>{{ $totalCredits > 0 ? round(($s['mentions'] ?? 0) / $totalCredits * 100) : 0 }}%</td>
                    <td><span class="pill {{ $sentClass[$s['sentiment'] ?? 'mixed'] ?? 'mix' }}">{{ __('report.'.($s['sentiment'] ?? 'mixed')) }}</span></td>
                    <td style="color:#6b7280;">{{ $s['note'] ?? '' }}</td>
                </tr>
            @endforeach
                <tr class="total"><td>{{ __('report.total_credits') }}</td><td>{{ $totalCredits }}</td><td>100%</td><td></td><td></td></tr>
            </tbody>
        </table>
    @endif

    @if($has('cadence'))
        @php $cad = $data['cadence']; @endphp
        <h2>{{ __('report.cadence_title') }}</h2>
        <p class="lead">{{ __('report.cadence_intro', ['active' => $cad['activeDays'], 'total' => $cad['totalDays'], 'avg' => $cad['perActiveDay']]) }}</p>
        <div class="heat">
            @foreach($cad['daily'] as $cell)
                <div class="cell lvl-{{ $cell['level'] }}"><div class="d">{{ $cell['label'] }}<span class="dow">{{ strtoupper($cell['dow']) }}</span></div><div class="c">{{ $cell['count'] ?: '·' }}</div></div>
            @endforeach
        </div>
        <div class="legend">{{ __('report.legend') }}: <b class="g">{{ __('report.legend_low') }}</b> · <b class="a">{{ __('report.legend_mid') }}</b> · <b class="r">{{ __('report.legend_high') }}</b></div>

        @if(!empty($cad['bursts']))
            <table>
                <thead><tr><th>{{ __('report.day') }}</th><th>{{ __('report.time_window') }}</th><th>{{ __('report.volume') }}</th><th>{{ __('report.flag') }}</th></tr></thead>
                <tbody>
                @foreach($cad['bursts'] as $b)
                    <tr>
                        <td><strong>{{ $b['date'] }}</strong></td>
                        <td>{{ $b['window'] }}</td>
                        <td>{{ $b['count'] }} {{ __('report.reviews_lc') }}</td>
                        <td class="flag-{{ $b['flag'] }}">{{ __('report.flag_'.$b['flag']) }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
            <div class="callout"><strong>{{ __('report.why_matters') }}</strong><br>{{ __('report.cadence_why') }}</div>
        @else
            <p style="color:#16a34a;">{{ __('report.cadence_clean') }}</p>
        @endif
    @endif

    @if($has('themes') && (!empty($insights['themes']['praise']) || !empty($insights['themes']['complaints'])))
        <h2>{{ __('report.themes_title') }}</h2>
        <div class="themes">
            <div class="card">
                <strong style="color:#166534;">{{ __('report.praised') }}</strong>
                <ul>@forelse($insights['themes']['praise'] as $t)<li>{{ $t }}</li>@empty<li style="color:#9ca3af;">—</li>@endforelse</ul>
            </div>
            <div class="card">
                <strong style="color:#991b1b;">{{ __('report.complaints') }}</strong>
                <ul>@forelse($insights['themes']['complaints'] as $t)<li>{{ $t }}</li>@empty<li style="color:#9ca3af;">—</li>@endforelse</ul>
            </div>
        </div>
    @endif

    @if($has('responses'))
        @php $resp = $data['responses']; @endphp
        <h2>{{ __('report.responses_title') }}</h2>
        <div class="statgrid">
            <div class="kpi"><div class="label">{{ __('report.reply_rate') }}</div><div class="value">{{ $resp['rate'] }}%</div><div class="sub">{{ $resp['replied'] }} {{ __('report.of') }} {{ $resp['total'] }}</div></div>
            <div class="kpi"><div class="label">{{ __('report.unanswered') }}</div><div class="value">{{ $resp['unanswered'] }}</div><div class="sub">{{ __('report.reviews_lc') }}</div></div>
            <div class="kpi"><div class="label">{{ __('report.avg_response') }}</div><div class="value">{{ $resp['avgResponseHours'] !== null ? $resp['avgResponseHours'].'h' : '—' }}</div><div class="sub">{{ __('report.to_reply') }}</div></div>
            <div class="kpi"><div class="label">{{ __('report.within_24h') }}</div><div class="value">{{ $resp['within24hPct'] !== null ? $resp['within24hPct'].'%' : '—' }}</div><div class="sub">{{ __('report.of_replies') }}</div></div>
        </div>
    @endif

    @if($has('distribution') || $has('volume'))
        <div class="grid2">
            @if($has('distribution'))
                <div>
                    <h2>{{ __('report.star_distribution') }}</h2>
                    <div class="card"><div class="chartbox"><canvas id="dist"></canvas></div></div>
                </div>
            @endif
            @if($has('volume'))
                <div>
                    <h2>{{ __('report.reviews_per_'.$data['series']['granularity']) }}</h2>
                    <div class="card"><div class="chartbox"><canvas id="vol"></canvas></div></div>
                </div>
            @endif
        </div>
    @endif

    @if($has('competitors') && ! empty($data['competitors']))
        <h2>{{ __('report.competitors_title') }}</h2>
        <div class="card">
            <table style="width:100%; border-collapse:collapse; font-size:12px;">
                <thead>
                    <tr style="text-align:left; color:#6b7280;">
                        <th style="padding:6px 8px;">{{ __('report.competitors_col_business') }}</th>
                        <th style="padding:6px 8px; text-align:right;">{{ __('report.competitors_col_rating') }}</th>
                        <th style="padding:6px 8px; text-align:right;">{{ __('report.competitors_col_reviews') }}</th>
                        <th style="padding:6px 8px; text-align:right;">{{ __('report.competitors_col_new') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @php $own = $data['competitors']['own']; @endphp
                    <tr style="border-top:1px solid #eef0f4; background:#f6f5ff; font-weight:700;">
                        <td style="padding:6px 8px;">{{ $own['name'] }} · {{ __('report.competitors_you') }}</td>
                        <td style="padding:6px 8px; text-align:right;">{{ $own['rating'] !== null ? number_format($own['rating'], 1).' ★' : '—' }}</td>
                        <td style="padding:6px 8px; text-align:right;">{{ number_format($own['reviews']) }}</td>
                        <td style="padding:6px 8px; text-align:right;">+{{ number_format($own['new_reviews']) }}</td>
                    </tr>
                    @foreach($data['competitors']['rows'] as $row)
                        <tr style="border-top:1px solid #eef0f4;">
                            <td style="padding:6px 8px;">{{ $row['name'] }}</td>
                            <td style="padding:6px 8px; text-align:right;">{{ $row['rating'] !== null ? number_format($row['rating'], 1).' ★' : '—' }}</td>
                            <td style="padding:6px 8px; text-align:right;">{{ number_format($row['reviews']) }}</td>
                            <td style="padding:6px 8px; text-align:right;">{{ $row['new_reviews'] !== null ? ($row['new_reviews'] > 0 ? '+' : '').number_format($row['new_reviews']) : '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <p style="font-size:10px; color:#9ca3af; margin:8px 8px 2px;">{{ __('report.competitors_note') }}</p>
        </div>
    @endif

    @if($has('highlights'))
        <div class="grid2">
            <div>
                <h2>{{ __('report.highlights_positive') }}</h2>
                @forelse($data['highlightsPositive'] as $r)
                    <div class="review good">
                        <div class="meta"><span class="stars">{{ str_repeat('★', $r->rating) }}</span> · {{ $r->author_name ?: __('report.anonymous') }}@if($data['allLocations'] && $r->location) · {{ $r->location->name }}@endif · {{ optional($r->created_at_external)->format('D, M j') }}</div>
                        <div>{{ $r->originalText() ?? $r->text }}</div>
                    </div>
                @empty
                    <p style="color:#9ca3af;">{{ __('report.no_positive') }}</p>
                @endforelse
            </div>
            <div>
                <h2>{{ __('report.highlights_attention') }}</h2>
                @forelse($data['highlightsCritical'] as $r)
                    <div class="review bad">
                        <div class="meta"><span class="stars">{{ str_repeat('★', $r->rating) }}</span> · {{ $r->author_name ?: __('report.anonymous') }}@if($data['allLocations'] && $r->location) · {{ $r->location->name }}@endif · {{ optional($r->created_at_external)->format('D, M j') }}</div>
                        <div>{{ $r->originalText() ?? $r->text }}</div>
                    </div>
                @empty
                    <p style="color:#9ca3af;">{{ __('report.no_critical') }}</p>
                @endforelse
            </div>
        </div>
    @endif

    @if($has('recommendations') && !empty($insights['recommendations']))
        <h2>{{ __('report.recommendations') }}</h2>
        <ul class="recs">@foreach($insights['recommendations'] as $rec)<li>{{ $rec }}</li>@endforeach</ul>
    @endif

    @if($has('methodology'))
        <h2>{{ __('report.methodology') }}</h2>
        <ul class="notes">
            <li>{{ __('report.method_scope', ['business' => $data['businessName'], 'period' => $data['periodLabel'], 'count' => $k['total']['value']]) }}</li>
            <li>{{ __('report.method_ratings', ['five' => $data['distribution'][5], 'four' => $data['distribution'][4], 'three' => $data['distribution'][3], 'two' => $data['distribution'][2], 'one' => $data['distribution'][1]]) }}</li>
            <li>{{ __('report.method_cadence') }}</li>
            <li>{{ __('report.method_source') }}</li>
        </ul>
    @endif

    <div class="foot">
        {{ $data['businessName'] }} · {{ $data['periodLabel'] }}@if($data['compare']) · {{ __('report.footer_compared', ['period' => $data['previousLabel']]) }}@endif · {{ __('report.footer_positive', ['pct' => $data['positivePct']]) }}, {{ __('report.footer_critical', ['pct' => $data['negativePct']]) }}
    </div>
</div>

<script>
    // Charts must survive the CDN script arriving late (cold cache, SPA
    // navigation): poll for Chart instead of assuming it's loaded, so a slow
    // first visit doesn't leave the chart boxes empty until a manual reload.
    function initReportCharts() {
    const common = { responsive: true, maintainAspectRatio: false, animation: false, plugins: { legend: { display: false } } };
    // Draw the value on top of each bar so counts are readable in the PDF
    // (no hover available there).
    const barValues = {
        id: 'barValues',
        afterDatasetsDraw(chart) {
            const ctx = chart.ctx;
            chart.data.datasets.forEach((ds, di) => {
                chart.getDatasetMeta(di).data.forEach((bar, i) => {
                    const v = ds.data[i];
                    if (!v) return;
                    ctx.save();
                    ctx.fillStyle = '#374151';
                    ctx.font = '700 9px -apple-system, Arial, sans-serif';
                    ctx.textAlign = 'center';
                    ctx.textBaseline = 'bottom';
                    ctx.fillText(v, bar.x, bar.y - 2);
                    ctx.restore();
                });
            });
        }
    };
    @if($has('distribution'))
    new Chart(document.getElementById('dist'), {
        type: 'bar',
        data: { labels: @json($distLabels), datasets: [{ data: @json(array_values($data['distribution'])), backgroundColor: ['#16a34a','#65a30d','#ca8a04','#ea580c','#dc2626'] }] },
        options: { ...common, scales: { y: { beginAtZero: true, ticks: { precision: 0 } } } }
    });
    @endif
    @if($has('volume'))
    const vol = @json($data['series']);
    new Chart(document.getElementById('vol'), {
        type: 'bar',
        data: { labels: vol.labels, datasets: [{ data: vol.data, backgroundColor: @json($brand['color']).concat('cc'), borderColor: @json($brand['color']) }] },
        options: {
            responsive: true, maintainAspectRatio: false, animation: false,
            layout: { padding: { top: 14 } },
            plugins: { legend: { display: false }, tooltip: { callbacks: { title: (items) => (vol.titles && vol.titles[items[0].dataIndex]) || items[0].label } } },
            scales: { y: { beginAtZero: true, ticks: { precision: 0 } } },
        },
        plugins: [barValues]
    });
    @endif
    window.__chartsReady = true;
    }

    (function bootReportCharts(attempt) {
        if (window.Chart) {
            initReportCharts();
        } else if (attempt < 200) { // keep trying for ~10s, then give up quietly
            setTimeout(function () { bootReportCharts(attempt + 1); }, 50);
        }
    })(0);
</script>
</body>
</html>
