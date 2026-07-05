<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Docs' }} — Repunio API</title>
    <meta name="description" content="{{ $description ?? 'Repunio API Documentation' }}">

    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        accent: '#2d19ec',
                    }
                }
            }
        }
    </script>

    <script>
        if (localStorage.theme === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        }
    </script>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">

    <style>
        body { font-family: 'Inter', sans-serif; }

        .prose-docs h1 { font-size: 2rem; font-weight: 700; margin-top: 0; margin-bottom: 1rem; }
        .prose-docs h2 { font-size: 1.5rem; font-weight: 600; margin-top: 2.5rem; margin-bottom: 0.75rem; padding-bottom: 0.5rem; border-bottom: 1px solid #e5e7eb; }
        .prose-docs h3 { font-size: 1.25rem; font-weight: 600; margin-top: 2rem; margin-bottom: 0.5rem; }
        .prose-docs p { margin-bottom: 1rem; line-height: 1.75; }
        .prose-docs p:last-child { margin-bottom: 0; }
        .prose-docs p:only-child { margin-bottom: 0; }
        .prose-docs blockquote p:last-child { margin-bottom: 0; }
        .prose-docs ul, .prose-docs ol { margin-bottom: 1rem; padding-left: 1.5rem; }
        .prose-docs li { margin-bottom: 0.25rem; line-height: 1.75; }
        .prose-docs a { color: #2d19ec; text-decoration: underline; }
        .prose-docs code:not(pre code) {
            background: #f3f4f6; padding: 0.15rem 0.4rem; border-radius: 0.25rem;
            font-size: 0.875rem; font-family: 'JetBrains Mono', monospace;
        }
        .dark .prose-docs code:not(pre code) { background: #374151; color: #e5e7eb; }
        .prose-docs pre {
            background: #1e1e2e; color: #cdd6f4; padding: 1.25rem; border-radius: 0.5rem;
            overflow-x: auto; margin-bottom: 1.5rem; font-size: 0.875rem;
            font-family: 'JetBrains Mono', monospace; line-height: 1.6;
        }
        .prose-docs table { width: 100%; border-collapse: collapse; margin-bottom: 1.5rem; font-size: 0.9rem; }
        .prose-docs th, .prose-docs td { border: 1px solid #e5e7eb; padding: 0.5rem 0.75rem; text-align: left; }
        .dark .prose-docs th, .dark .prose-docs td { border-color: #374151; }
        .prose-docs th { background: #f9fafb; font-weight: 600; }
        .dark .prose-docs th { background: #1f2937; }
        .dark .prose-docs h2 { border-color: #374151; }
        .prose-docs blockquote {
            border-left: 4px solid #2d19ec; padding: 0.75rem 1rem; margin-bottom: 1rem;
            background: #f5f3ff; border-radius: 0 0.375rem 0.375rem 0;
        }
        .dark .prose-docs blockquote { background: #1e1b4b; color: #c7d2fe; }
        .dark .prose-docs a { color: #a5b4fc; }

        /* ── Code Language Tabs ── */
        .code-tab-group {
            margin-bottom: 1.5rem;
            border-radius: 0.5rem;
            overflow: hidden;
            border: 1px solid #e5e7eb;
        }
        .dark .code-tab-group { border-color: #374151; }

        .code-tab-bar {
            display: flex;
            background: #f3f4f6;
            border-bottom: 1px solid #e5e7eb;
            padding: 0 0.5rem;
            overflow-x: auto;
        }
        .dark .code-tab-bar { background: #1f2937; border-color: #374151; }

        .code-tab {
            padding: 0.5rem 1rem;
            font-size: 0.8125rem;
            font-weight: 500;
            color: #6b7280;
            background: none;
            border: none;
            border-bottom: 2px solid transparent;
            cursor: pointer;
            transition: color 0.15s, border-color 0.15s;
            font-family: inherit;
            white-space: nowrap;
            flex-shrink: 0;
        }
        .code-tab:hover { color: #111827; }
        .dark .code-tab:hover { color: #e5e7eb; }
        .code-tab.active { color: #2d19ec; border-bottom-color: #2d19ec; }

        .code-tab-group pre {
            margin: 0 !important;
            border-radius: 0 !important;
            border: none !important;
        }

        /* ── Response Tabs ── */
        .response-tabs {
            margin-bottom: 1.5rem;
            border-radius: 0.5rem;
            overflow: hidden;
            border: 1px solid #e5e7eb;
        }
        .dark .response-tabs { border-color: #374151; }

        .response-tab-bar {
            display: flex;
            background: #f9fafb;
            border-bottom: 1px solid #e5e7eb;
            padding: 0 0.5rem;
            overflow-x: auto;
        }
        .dark .response-tab-bar { background: #111827; border-color: #374151; }

        .response-tab {
            padding: 0.5rem 0.75rem;
            font-size: 0.8125rem;
            font-weight: 600;
            font-family: 'JetBrains Mono', monospace;
            color: #6b7280;
            background: none;
            border: none;
            border-bottom: 2px solid transparent;
            cursor: pointer;
            transition: color 0.15s, border-color 0.15s;
            white-space: nowrap;
            flex-shrink: 0;
        }
        .response-tab:hover { color: #111827; }
        .dark .response-tab:hover { color: #e5e7eb; }
        .response-tab.active { color: #2d19ec; border-bottom-color: #2d19ec; }

        .response-tab.status-2xx.active .response-status-code { color: #22c55e; }
        .response-tab.status-4xx.active .response-status-code { color: #f59e0b; }
        .response-tab.status-5xx.active .response-status-code { color: #ef4444; }

        .response-tabs pre {
            margin: 0 !important;
            border-radius: 0 !important;
            border: none !important;
        }

        .response-label {
            padding: 0.5rem 1rem;
            font-size: 0.8125rem;
            color: #6b7280;
            background: #f9fafb;
            border-top: 1px solid #e5e7eb;
        }
        .dark .response-label { background: #111827; border-color: #374151; color: #9ca3af; }
    </style>
</head>
<body class="bg-white dark:bg-gray-950 text-gray-900 dark:text-gray-100 antialiased">
<div class="flex min-h-screen">

    {{-- Sidebar --}}
    <aside id="sidebar"
           class="fixed inset-y-0 left-0 w-64 bg-gray-50 dark:bg-gray-900 border-r border-gray-200 dark:border-gray-800 overflow-y-auto z-30 transform -translate-x-full md:translate-x-0 transition-transform duration-200">

        {{-- Brand --}}
        <div class="px-5 py-5 border-b border-gray-200 dark:border-gray-800">
            <a href="{{ route('docs.index') }}" class="text-lg font-bold text-accent">
                Repunio API
            </a>
            <div class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">API Documentation</div>
        </div>

        {{-- Navigation --}}
        <nav class="px-3 py-4 space-y-5">
            @foreach($navigation as $group)
                <div>
                    @if($group['group'])
                        <div class="px-3 mb-1.5 text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider">
                            {{ $group['group'] }}
                        </div>
                    @else
                        <div class="border-t border-gray-200 dark:border-gray-800 pt-4 mb-1.5"></div>
                    @endif

                    @foreach($group['items'] as $item)
                        @php
                            $href = isset($item['route'])
                                ? route($item['route'])
                                : route('docs.show', $item['slug']);
                            $active = ($currentSlug ?? '') === $item['slug'];
                        @endphp
                        <a href="{{ $href }}"
                           class="block px-3 py-1.5 rounded-md text-sm transition-colors
                                  {{ $active
                                      ? 'bg-indigo-50 dark:bg-indigo-950/50 text-accent dark:text-indigo-400 font-medium'
                                      : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-800' }}">
                            {{ $item['title'] }}
                        </a>
                    @endforeach
                </div>
            @endforeach
        </nav>

        {{-- Theme toggle --}}
        <div class="absolute bottom-0 left-0 right-0 px-5 py-3 border-t border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-900">
            <button onclick="
                document.documentElement.classList.toggle('dark');
                localStorage.theme = document.documentElement.classList.contains('dark') ? 'dark' : 'light';
            " class="text-xs text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 flex items-center gap-1.5 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
                </svg>
                Toggle theme
            </button>
        </div>
    </aside>

    {{-- Mobile hamburger --}}
    <button onclick="document.getElementById('sidebar').classList.toggle('-translate-x-full')"
            class="md:hidden fixed top-4 left-4 z-40 p-2 bg-white dark:bg-gray-900 rounded-lg shadow-lg border border-gray-200 dark:border-gray-700">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
        </svg>
    </button>

    {{-- Main content --}}
    <main class="flex-1 md:ml-64 min-w-0">
        <div class="max-w-3xl mx-auto px-6 md:px-12 py-12">
            @yield('content')
        </div>
    </main>

</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const langLabels = {
        curl: 'cURL', javascript: 'JavaScript', js: 'JavaScript',
        python: 'Python', php: 'PHP', go: 'Go', java: 'Java',
        ruby: 'Ruby', csharp: 'C#', bash: 'Shell', shell: 'Shell',
        json: 'JSON', typescript: 'TypeScript',
    };

    const article = document.querySelector('.prose-docs');
    if (!article) return;

    // ── 1. Code Language Tabs ──
    // Group consecutive <pre> blocks (no element between them) into tabbed interfaces.
    const pres = Array.from(article.querySelectorAll('pre'));
    const groups = [];
    let current = [];

    pres.forEach((pre, i) => {
        current.push(pre);
        const next = pres[i + 1];
        if (next && pre.nextElementSibling === next) {
            // Adjacent — keep accumulating
        } else {
            if (current.length > 1) groups.push([...current]);
            current = [];
        }
    });

    groups.forEach(group => {
        if (group[0].closest('.response-tabs')) return;

        const wrapper = document.createElement('div');
        wrapper.className = 'code-tab-group';

        const tabBar = document.createElement('div');
        tabBar.className = 'code-tab-bar';

        group.forEach((pre, i) => {
            const code = pre.querySelector('code');
            const lang = (code?.className.match(/language-(\S+)/)?.[1] || 'text').toLowerCase();
            const label = langLabels[lang] || lang;

            const tab = document.createElement('button');
            tab.className = 'code-tab' + (i === 0 ? ' active' : '');
            tab.textContent = label;
            tab.addEventListener('click', () => {
                tabBar.querySelectorAll('.code-tab').forEach(t => t.classList.remove('active'));
                tab.classList.add('active');
                wrapper.querySelectorAll(':scope > pre').forEach((p, j) => {
                    p.style.display = j === i ? '' : 'none';
                });
            });
            tabBar.appendChild(tab);
            if (i > 0) pre.style.display = 'none';
        });

        group[0].parentNode.insertBefore(wrapper, group[0]);
        wrapper.appendChild(tabBar);
        group.forEach(pre => wrapper.appendChild(pre));
    });

    // ── 2. Response Tabs ──
    // Each response is wrapped in <div data-status="200" data-label="Success">…</div>
    // inside a <div class="response-tabs"> container.
    document.querySelectorAll('.response-tabs').forEach(container => {
        const panels = Array.from(container.querySelectorAll(':scope > div[data-status]'));
        if (panels.length === 0) return;

        const tabBar = document.createElement('div');
        tabBar.className = 'response-tab-bar';

        panels.forEach((panel, i) => {
            const statusCode = panel.dataset.status || '';
            const label      = panel.dataset.label  || '';

            const statusClass = statusCode.startsWith('2') ? 'status-2xx'
                : statusCode.startsWith('4') ? 'status-4xx'
                : statusCode.startsWith('5') ? 'status-5xx' : '';

            const tab = document.createElement('button');
            tab.className = ['response-tab', statusClass, i === 0 ? 'active' : ''].filter(Boolean).join(' ');
            tab.innerHTML = `<span class="response-status-code">${statusCode || (i + 1)}</span>`;
            tab.addEventListener('click', () => {
                tabBar.querySelectorAll('.response-tab').forEach(t => t.classList.remove('active'));
                tab.classList.add('active');
                panels.forEach((p, j) => { p.style.display = j === i ? '' : 'none'; });
            });
            tabBar.appendChild(tab);

            if (label) {
                const labelEl = document.createElement('div');
                labelEl.className = 'response-label';
                labelEl.textContent = label;
                panel.appendChild(labelEl);
            }

            if (i > 0) panel.style.display = 'none';
        });

        container.insertBefore(tabBar, container.firstChild);
    });
});
</script>

</body>
</html>
