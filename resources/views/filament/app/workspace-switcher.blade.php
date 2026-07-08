@php
    $user = auth()->user();
    $workspaces = $user ? $user->workspaces()->orderBy('name')->get() : collect();
    $currentId = session('current_workspace_id');
    $current = $workspaces->firstWhere('id', $currentId) ?? $workspaces->first();

    $circle = function ($ws, float $size = 1.6) {
        if ($url = $ws->logoUrl()) {
            return '<img src="'.e($url).'" alt="" style="width:'.$size.'rem;height:'.$size.'rem;border-radius:9999px;object-fit:cover;flex:none;">';
        }

        return '<span style="width:'.$size.'rem;height:'.$size.'rem;border-radius:9999px;flex:none;background:'.e($ws->avatarColor()).';color:#fff;display:inline-flex;align-items:center;justify-content:center;font-size:'.($size * 0.44).'rem;font-weight:700;font-family:sans-serif;">'.e($ws->initials()).'</span>';
    };
@endphp

@if ($current)
    {{-- Light/dark aware: the panel is teleported to <body>, so the usual
         inherited colors don't apply — every surface needs an explicit value
         per theme (Filament toggles the `dark` class on <html>). --}}
    <style>
        .ws-dd-btn { display: flex; align-items: center; gap: .5rem; padding: .3rem .55rem; border: 1px solid rgba(0,0,0,.08); border-radius: .65rem; background: transparent; cursor: pointer; max-width: 15rem; }
        .dark .ws-dd-btn { border-color: rgba(255,255,255,.16); }
        .ws-dd-panel { min-width: 16rem; background: #fff; border: 1px solid #e5e7eb; border-radius: .75rem; box-shadow: 0 14px 36px -8px rgba(0,0,0,.22); padding: .35rem; z-index: 70; color: #111827; }
        .dark .ws-dd-panel { background: #18181b; border-color: rgba(255,255,255,.12); box-shadow: 0 14px 36px -8px rgba(0,0,0,.6); color: #f4f4f5; }
        .ws-dd-item { display: flex; align-items: center; gap: .55rem; width: 100%; padding: .45rem .55rem; border: 0; border-radius: .55rem; cursor: pointer; text-align: start; background: transparent; color: inherit; }
        .ws-dd-item:hover, .ws-dd-item--active { background: #f3f4f6; }
        .dark .ws-dd-item:hover, .dark .ws-dd-item--active { background: rgba(255,255,255,.08); }
        .ws-dd-create { display: flex; align-items: center; gap: .55rem; padding: .5rem .55rem; margin-top: .25rem; border-top: 1px solid #f3f4f6; text-decoration: none; color: #1800ff; font-size: .85rem; font-weight: 600; border-radius: .55rem; }
        .ws-dd-create:hover { background: #f5f3ff; }
        .dark .ws-dd-create { border-top-color: rgba(255,255,255,.1); color: #a5b4fc; }
        .dark .ws-dd-create:hover { background: rgba(99,102,241,.15); }
        .ws-dd-check { stroke: #1800ff; }
        .dark .ws-dd-check { stroke: #a5b4fc; }
    </style>

    <div x-data="{ open: false, x: 0, y: 0, place() { const r = $refs.btn.getBoundingClientRect(); this.y = r.bottom + 6; this.x = window.innerWidth - r.right; }, toggle() { if (! this.open) { this.place(); } this.open = ! this.open; } }"
        @keydown.escape.window="open = false" style="position:relative; margin-inline-end:10px;">
        <button type="button" x-ref="btn" @click.stop="toggle()" class="ws-dd-btn">
            {!! $circle($current) !!}
            <span style="font-weight:600; font-size:.85rem; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">{{ $current->name }}</span>
            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="opacity:.5; flex:none;"><path stroke-linecap="round" stroke-linejoin="round" d="m19 9-7 7-7-7"/></svg>
        </button>

        <template x-teleport="body">
                <div x-show="open" x-cloak @click.outside="open = false" x-transition.opacity class="ws-dd-panel"
                    :style="`position:fixed; top:${y}px; inset-inline-end:${x}px;`">
                    <div style="font-size:.7rem; text-transform:uppercase; letter-spacing:.04em; color:#9ca3af; padding:.4rem .55rem .25rem;">{{ __('nav.switch_workspace') }}</div>
                    @foreach ($workspaces as $ws)
                        <form method="POST" action="{{ route('workspace.switch') }}" style="margin:0;">
                            @csrf
                            <input type="hidden" name="workspace" value="{{ $ws->id }}">
                            <button type="submit" class="ws-dd-item {{ $ws->id === $current->id ? 'ws-dd-item--active' : '' }}">
                                {!! $circle($ws) !!}
                                <span style="font-size:.85rem; font-weight:{{ $ws->id === $current->id ? '700' : '500' }}; flex:1; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">{{ $ws->name }}</span>
                                @if ($ws->id === $current->id)
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke-width="2.5" class="ws-dd-check" style="flex:none;"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                                @endif
                            </button>
                        </form>
                    @endforeach

                    <a href="{{ route('workspace.create') }}" class="ws-dd-create">
                        <span style="width:1.6rem; height:1.6rem; border-radius:9999px; flex:none; background:#eef0ff; color:#1800ff; display:inline-flex; align-items:center; justify-content:center;"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14"/></svg></span>
                        {{ __('nav.create_workspace') }}
                    </a>
                </div>
            </template>
    </div>
@endif
