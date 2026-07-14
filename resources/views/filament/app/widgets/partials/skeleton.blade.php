{{-- Lazy-widget placeholder: a shimmer skeleton shaped like the real content,
     so the dashboard doesn't pop from empty boxes to data. --}}
@php
    $height ??= null;
    $variant ??= 'chart';
@endphp

<div
    {{
        ($attributes ?? new \Filament\Support\View\ComponentAttributeBag)
            ->gridColumn($columnSpan ?? [], $columnStart ?? [])
            ->class(['fi-section wsk'])
            ->style(['min-height: '.e($height ?? '10rem')])
    }}
>
    <style>
        .wsk { padding: 1.25rem; }
        .wsk .b { border-radius: .45rem; background: linear-gradient(90deg, rgb(0 0 0 / .05) 25%, rgb(0 0 0 / .1) 45%, rgb(0 0 0 / .05) 65%); background-size: 200% 100%; animation: wsk-shimmer 1.4s ease-in-out infinite; }
        .dark .wsk .b { background: linear-gradient(90deg, rgb(255 255 255 / .05) 25%, rgb(255 255 255 / .11) 45%, rgb(255 255 255 / .05) 65%); background-size: 200% 100%; }
        @keyframes wsk-shimmer { 0% { background-position: 200% 0; } 100% { background-position: -200% 0; } }
        .wsk .row { display: flex; align-items: center; justify-content: space-between; gap: 1rem; margin-bottom: .9rem; }
        .wsk .cardgrid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .9rem; }
        .wsk .card { border: 1px solid rgb(0 0 0 / .06); border-radius: .75rem; padding: 1rem; }
        .dark .wsk .card { border-color: rgb(255 255 255 / .08); }
    </style>

    @switch($variant)
        @case('stats')
            <div class="b" style="height: .9rem; width: 10rem; margin-bottom: 1rem;"></div>
            <div class="cardgrid">
                @for ($i = 0; $i < 4; $i++)
                    <div class="card">
                        <div class="b" style="height: .7rem; width: 55%; margin-bottom: .8rem;"></div>
                        <div class="b" style="height: 1.6rem; width: 40%; margin-bottom: .6rem;"></div>
                        <div class="b" style="height: .6rem; width: 70%;"></div>
                    </div>
                @endfor
            </div>
            @break

        @case('list')
            <div class="b" style="height: .9rem; width: 11rem; margin-bottom: 1.2rem;"></div>
            @for ($i = 0; $i < 6; $i++)
                <div class="row">
                    <div class="b" style="height: .75rem; width: {{ 62 - $i * 6 }}%;"></div>
                    <div class="b" style="height: .75rem; width: 3.5rem;"></div>
                </div>
            @endfor
            @break

        @case('table')
            <div class="b" style="height: .9rem; width: 11rem; margin-bottom: 1.2rem;"></div>
            @for ($i = 0; $i < 4; $i++)
                <div class="row">
                    <div class="b" style="height: .75rem; width: 30%;"></div>
                    <div class="b" style="height: .75rem; width: 12%;"></div>
                    <div class="b" style="height: .75rem; width: 12%;"></div>
                    <div class="b" style="height: .75rem; width: 12%;"></div>
                </div>
            @endfor
            @break

        @default {{-- chart --}}
            <div class="row" style="margin-bottom: 1.1rem;">
                <div class="b" style="height: .9rem; width: 10rem;"></div>
                <div class="b" style="height: .9rem; width: 6rem;"></div>
            </div>
            <div class="b" style="height: calc(100% - 3.4rem); min-height: 8rem;"></div>
    @endswitch
</div>
