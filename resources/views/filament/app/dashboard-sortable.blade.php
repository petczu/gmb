{{--
    Drag-and-drop widget reordering + per-widget hide on the dashboard grid.
    No JS deps: hovering a widget reveals a grip (drag to rearrange, the CSS
    grid reflows left/right/up/down on its own) and a trash icon (hide; the
    Customize modal is the restore path). The controls are re-injected after
    EVERY Livewire morph — a widget refresh (filters, chart selects, polls)
    strips injected nodes from its snapshot, which used to kill the dragging.
    DOM order maps to order keys via window.__wgtKeys (kept in sync with the
    server after each save).
--}}
<style>
    /* Controls only exist in arrange mode (the Customize button toggles it). */
    .wgt-ctl {
        position: absolute; top: .45rem; right: .45rem; z-index: 20;
        display: none; align-items: center; gap: .15rem;
        background: rgb(255 255 255 / .85); border-radius: .5rem; padding: .1rem;
    }
    .dark .wgt-ctl { background: rgb(24 24 27 / .85); }
    body.wgt-arranging .wgt-ctl { display: flex; }
    body.wgt-arranging .wgt-sortable { outline: 1px dashed rgb(45 25 236 / .35); outline-offset: 3px; border-radius: .75rem; }
    .wgt-ctl button {
        display: flex; align-items: center; justify-content: center;
        width: 1.6rem; height: 1.6rem; border-radius: .4rem; border: 0;
        background: transparent; color: rgb(156 163 175); padding: 0;
    }
    .wgt-ctl button:hover { background: rgb(0 0 0 / .06); color: rgb(75 85 99); }
    .dark .wgt-ctl button:hover { background: rgb(255 255 255 / .1); color: #d4d4d8; }
    .wgt-grip-btn { cursor: grab; }
    .wgt-grip-btn:active { cursor: grabbing; }
    .wgt-hide-btn:hover { color: rgb(220 38 38) !important; }
    .wgt-sortable { position: relative; }
    .wgt-dragging { opacity: .45; outline: 2px dashed #2d19ec; outline-offset: 4px; border-radius: .75rem; }
</style>

<script>
(() => {
    window.__wgtKeys = @json($orderKeys);

    if (window.__wgtSortableBooted) return; // survive SPA re-execution
    window.__wgtSortableBooted = true;

    const TITLE_MOVE = @json(__('pages/dashboard.drag_to_move'));
    const TITLE_HIDE = @json(__('pages/dashboard.hide_widget'));
    const TITLE_SPAN = @json(__('pages/dashboard.toggle_width'));
    const CONFIRM_HIDE = @json(__('pages/dashboard.hide_widget_confirm'));
    const GRIP_SVG = '<svg width="14" height="14" viewBox="0 0 20 20" fill="currentColor"><circle cx="7" cy="4" r="1.5"/><circle cx="13" cy="4" r="1.5"/><circle cx="7" cy="10" r="1.5"/><circle cx="13" cy="10" r="1.5"/><circle cx="7" cy="16" r="1.5"/><circle cx="13" cy="16" r="1.5"/></svg>';
    const SPAN_SVG = '<svg width="13" height="13" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M7 6 3 10l4 4M13 6l4 4-4 4M3 10h14"/></svg>';
    const TRASH_SVG = '<svg width="13" height="13" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h14M8 6V4a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v2m3 0v11a1 1 0 0 1-1 1H6a1 1 0 0 1-1-1V6M8.5 10v5M11.5 10v5"/></svg>';

    const PAGE_PATH = new URL(@json(\App\Filament\App\Pages\Dashboard::getUrl()), window.location.origin).pathname;

    const init = () => {
        // The morph hook fires on every page; only touch the dashboard grid.
        if (window.location.pathname !== PAGE_PATH) return;

        const keys = window.__wgtKeys;
        if (!Array.isArray(keys) || keys.length < 1) return;

        // The widgets grid = the parent holding the most nested Livewire
        // components (each widget is one). Bail quietly on layout mismatch.
        const byParent = new Map();
        document.querySelectorAll('.fi-page [wire\\:id]').forEach((el) => {
            const p = el.parentElement;
            if (p) byParent.set(p, (byParent.get(p) ?? 0) + 1);
        });
        let grid = null, best = 0;
        byParent.forEach((count, parent) => { if (count > best) { best = count; grid = parent; } });
        if (!grid || best !== keys.length) return;

        const pageEl = grid.closest('[wire\\:id]');
        if (!pageEl) return;
        const pageId = pageEl.getAttribute('wire:id');

        [...grid.children].filter((el) => el.matches('[wire\\:id]')).forEach((el, i) => {
            // Always re-map: a morph may have reordered or re-created nodes.
            el.dataset.wgtKey = keys[i];
            if (el.querySelector(':scope > .wgt-ctl')) return; // controls intact
            el.classList.add('wgt-sortable');

            const ctl = document.createElement('div');
            ctl.className = 'wgt-ctl';
            ctl.innerHTML = '<button type="button" class="wgt-grip-btn" title="' + TITLE_MOVE + '">' + GRIP_SVG + '</button>'
                + '<button type="button" class="wgt-span-btn" title="' + TITLE_SPAN + '">' + SPAN_SVG + '</button>'
                + '<button type="button" class="wgt-hide-btn" title="' + TITLE_HIDE + '">' + TRASH_SVG + '</button>';
            el.appendChild(ctl);

            // Drag only by the grip: enable draggable while it is held.
            ctl.querySelector('.wgt-grip-btn').addEventListener('mousedown', () => el.setAttribute('draggable', 'true'));
            el.addEventListener('mouseup', () => el.removeAttribute('draggable'));

            // Full ↔ half width: half-width widgets pair up on one row, which
            // is what makes left/right dragging meaningful.
            ctl.querySelector('.wgt-span-btn').addEventListener('click', () => {
                window.Livewire?.find(pageId)?.call('toggleWidgetSpan', el.dataset.wgtKey);
            });

            ctl.querySelector('.wgt-hide-btn').addEventListener('click', () => {
                if (!window.confirm(CONFIRM_HIDE)) return;
                const key = el.dataset.wgtKey;
                window.Livewire?.find(pageId)?.call('hideWidget', key)?.then(() => {
                    window.__wgtKeys = window.__wgtKeys.filter((k) => k !== key);
                });
            });

            el.addEventListener('dragstart', (e) => {
                e.dataTransfer.effectAllowed = 'move';
                el.classList.add('wgt-dragging');
                grid.dataset.wgtDragging = el.dataset.wgtKey;
            });

            el.addEventListener('dragend', () => {
                el.classList.remove('wgt-dragging');
                el.removeAttribute('draggable');
                delete grid.dataset.wgtDragging;

                const order = [...grid.children]
                    .filter((c) => c.dataset && c.dataset.wgtKey)
                    .map((c) => c.dataset.wgtKey);

                window.Livewire?.find(pageId)?.call('reorderWidgets', order)?.then(() => {
                    // Keep the JS key map in sync with the saved order.
                    window.__wgtKeys = order;
                });
            });

            el.addEventListener('dragover', (e) => {
                const draggingKey = grid.dataset.wgtDragging;
                if (!draggingKey || draggingKey === el.dataset.wgtKey) return;
                e.preventDefault();
                e.dataTransfer.dropEffect = 'move';

                const dragged = grid.querySelector('[data-wgt-key="' + CSS.escape(draggingKey) + '"]');
                if (!dragged) return;

                // Live reorder: insert before/after depending on DOM position.
                const children = [...grid.children];
                const after = children.indexOf(dragged) < children.indexOf(el);
                grid.insertBefore(dragged, after ? el.nextSibling : el);
            });
        });
    };

    let bootTimer = null;
    const boot = () => { clearTimeout(bootTimer); bootTimer = setTimeout(init, 80); };

    document.readyState === 'loading' ? document.addEventListener('DOMContentLoaded', boot) : boot();
    document.addEventListener('livewire:navigated', () => {
        document.body.classList.remove('wgt-arranging'); // mode resets per visit
        boot();
    });

    // Customize toggles arrange mode server-side; mirror it as a body class.
    window.addEventListener('wgt-arranging', (e) => {
        document.body.classList.toggle('wgt-arranging', !!(e.detail?.state));
    });

    // Any component update (filters, chart selects, polls) morphs injected
    // controls away — put them back after every round trip.
    const hookMorphs = () => window.Livewire?.hook('morphed', () => boot());
    window.Livewire ? hookMorphs() : document.addEventListener('livewire:init', hookMorphs);
})();
</script>
