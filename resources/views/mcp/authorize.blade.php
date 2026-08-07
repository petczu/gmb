<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ \App\Support\Locales::direction(app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Authorize Application - {{ config('app.name', 'MCP Server') }}</title>

    <link rel="icon" type="image/png" href="/favicon-96x96.png" sizes="96x96" />
    <link rel="icon" type="image/svg+xml" href="/favicon.svg" />
    <link rel="shortcut icon" href="/favicon.ico" />
    <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png" />
    <meta name="apple-mobile-web-app-title" content="Authorize MCP" />

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

    {{-- Self-contained styles: this page renders in a bare OAuth popup, so it
         must not depend on the app's compiled CSS (whose semantic tokens don't
         exist here) or on a Vite dev server. --}}
    <style>
        :root {
            --bg: #f4f5f7;
            --card: #ffffff;
            --border: #e4e4e7;
            --text: #18181b;
            --muted: #6b7280;
            --primary: #2563eb;
            --primary-hover: #1d4ed8;
            --primary-soft: rgba(37, 99, 235, 0.12);
            --cancel-hover: #f4f4f5;
            --panel: #f9fafb;
        }

        @media (prefers-color-scheme: dark) {
            :root {
                --bg: #101014;
                --card: #1b1b21;
                --border: #33333c;
                --text: #f4f4f5;
                --muted: #a1a1aa;
                --primary: #3b82f6;
                --primary-hover: #2563eb;
                --primary-soft: rgba(59, 130, 246, 0.18);
                --cancel-hover: #26262e;
                --panel: #23232b;
            }
        }

        * { box-sizing: border-box; margin: 0; }

        body {
            font-family: 'Instrument Sans', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: var(--bg);
            color: var(--text);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
            -webkit-font-smoothing: antialiased;
        }

        .card {
            width: 100%;
            max-width: 28rem;
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 0.75rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            padding: 1.5rem;
        }

        .icon-wrap { display: flex; justify-content: center; margin-bottom: 1rem; }

        .icon-wrap svg { height: 3rem; width: 3rem; color: var(--primary); }

        /* Monochrome brand marks (fill: currentColor) follow the text color. */
        .icon-wrap .client-mark { color: var(--text); }

        h1 {
            font-size: 1.5rem;
            font-weight: 600;
            text-align: center;
            letter-spacing: -0.02em;
            margin-bottom: 0.5rem;
        }

        .subtitle {
            font-size: 0.875rem;
            color: var(--muted);
            text-align: center;
            margin-bottom: 1.25rem;
            line-height: 1.5;
        }

        .panel {
            background: var(--panel);
            border: 1px solid var(--border);
            border-radius: 0.5rem;
            padding: 1rem;
            margin-bottom: 1rem;
        }

        .panel .label { font-size: 0.875rem; color: var(--muted); margin-bottom: 0.375rem; }

        .panel .value { font-weight: 500; word-break: break-all; }

        .permissions { margin-bottom: 1.25rem; }

        .permissions .label { font-size: 0.875rem; font-weight: 500; margin-bottom: 0.5rem; }

        .permissions ul { list-style: none; padding: 0; }

        .permissions li {
            display: flex;
            align-items: flex-start;
            gap: 0.5rem;
            font-size: 0.875rem;
            color: var(--muted);
            margin-bottom: 0.5rem;
        }

        .dot-wrap {
            background: var(--primary-soft);
            border-radius: 9999px;
            padding: 0.25rem;
            margin-top: 0.2rem;
            display: inline-flex;
        }

        .dot { height: 0.375rem; width: 0.375rem; border-radius: 9999px; background: var(--primary); }

        .workspaces { margin-bottom: 1.25rem; }

        .workspaces .label { font-size: 0.875rem; font-weight: 500; margin-bottom: 0.5rem; }

        .workspace-option {
            display: flex;
            align-items: center;
            gap: 0.625rem;
            border: 1px solid var(--border);
            border-radius: 0.5rem;
            padding: 0.625rem 0.75rem;
            margin-bottom: 0.5rem;
            cursor: pointer;
            transition: border-color 0.15s ease, background-color 0.15s ease;
        }

        .workspace-option:last-child { margin-bottom: 0; }

        .workspace-option:hover { background: var(--panel); }

        .workspace-option:has(input:checked) {
            border-color: var(--primary);
            background: var(--primary-soft);
        }

        .workspace-option input { accent-color: var(--primary); flex: none; }
        .workspace-hint { color: #f87171 !important; margin-top: 8px; }

        .workspace-option .name { font-size: 0.875rem; font-weight: 500; }

        .actions { display: flex; gap: 0.75rem; }

        .actions form { flex: 1; }

        button {
            width: 100%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            height: 2.5rem;
            padding: 0 1rem;
            border-radius: 0.5rem;
            font-size: 0.875rem;
            font-weight: 500;
            font-family: inherit;
            cursor: pointer;
            transition: background-color 0.15s ease;
        }

        button:disabled { opacity: 0.5; pointer-events: none; }

        button svg { height: 1rem; width: 1rem; }

        .btn-cancel {
            background: var(--card);
            border: 1px solid var(--border);
            color: var(--text);
        }

        .btn-cancel:hover { background: var(--cancel-hover); }

        .btn-authorize {
            background: var(--primary);
            border: 1px solid transparent;
            color: #ffffff;
        }

        .btn-authorize:hover { background: var(--primary-hover); }

        .spinner { animation: spin 1s linear infinite; }

        .hidden { display: none; }

        @keyframes spin { to { transform: rotate(360deg); } }
    </style>
</head>
<body>
@php
    // Show the connecting client's own mark when we recognize it (the name
    // arrives via dynamic client registration); otherwise a neutral shield.
    // Icons: lobehub/lobe-icons (MIT), see resources/views/mcp/marks.
    $clientLabel = mb_strtolower($client->name ?? '');
    $clientMark = match (true) {
        str_contains($clientLabel, 'claude') || str_contains($clientLabel, 'anthropic') => 'claude',
        str_contains($clientLabel, 'chatgpt') || str_contains($clientLabel, 'openai') => 'openai',
        str_contains($clientLabel, 'gemini') => 'gemini',
        str_contains($clientLabel, 'perplexity') => 'perplexity',
        str_contains($clientLabel, 'mistral') || str_contains($clientLabel, 'le chat') => 'mistral',
        str_contains($clientLabel, 'copilot') => 'copilot',
        str_contains($clientLabel, 'cursor') => 'cursor',
        default => null,
    };
@endphp
<div class="card">
    <div class="icon-wrap">
        @if ($clientMark !== null)
            @include("mcp.marks.{$clientMark}")
        @else
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.618 5.984A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.031 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
            </svg>
        @endif
    </div>

    <h1>Authorize {{ $client->name }}</h1>

    <p class="subtitle">This application will be able to:<br/>Use available MCP functionality.</p>

    <div class="panel">
        <p class="label">Logged in as:</p>
        <p class="value">{{ $user->email }}</p>
    </div>

    {{-- No "Permissions" list: the subtitle already says what access this
         grants, and the single OAuth scope only repeated it. --}}

    @php($workspaces = $workspaces ?? collect())
    @php($selectedWorkspaceIds = $selectedWorkspaceIds ?? array_filter([$selectedWorkspaceId ?? null]))
    @if($workspaces->count() > 1)
        {{-- The user belongs to several Pro workspaces: bind this connection to
             any subset (at least one). Inputs use form="authorizeForm" so they
             submit with Approve. --}}
        <div class="workspaces">
            <p class="label">Workspaces:</p>
            @foreach($workspaces as $workspace)
                <label class="workspace-option">
                    <input type="checkbox" form="authorizeForm" name="workspace_ids[]"
                           value="{{ $workspace->getKey() }}"
                           @checked(in_array($workspace->getKey(), $selectedWorkspaceIds, true))>
                    <span class="name">{{ $workspace->name }}</span>
                </label>
            @endforeach
            <p class="label workspace-hint hidden" id="workspaceHint">Select at least one workspace.</p>
        </div>
    @endif

    <div class="actions">
        <form method="POST" action="{{ route('passport.authorizations.deny') }}">
            @csrf
            @method('DELETE')
            <input type="hidden" name="state" value="">
            <input type="hidden" name="client_id" value="{{ $client->id }}">
            <input type="hidden" name="auth_token" value="{{ $authToken }}">
            <button type="submit" class="btn-cancel">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
                Cancel
            </button>
        </form>

        <form method="POST" action="{{ route('mcp.oauth.approve') }}" id="authorizeForm">
            @csrf
            <input type="hidden" name="state" value="">
            <input type="hidden" name="client_id" value="{{ $client->id }}">
            <input type="hidden" name="auth_token" value="{{ $authToken }}">
            @if($workspaces->count() === 1)
                <input type="hidden" name="workspace_ids[]" value="{{ $workspaces->first()->getKey() }}">
            @endif
            <button type="submit" class="btn-authorize" id="authorizeButton">
                <span id="authorizeText">Authorize</span>

                <svg id="loadingSpinner" class="spinner hidden" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle style="opacity: 0.25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path style="opacity: 0.75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
            </button>
        </form>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('authorizeForm');
        const button = document.getElementById('authorizeButton');
        const authorizeText = document.getElementById('authorizeText');
        const loadingSpinner = document.getElementById('loadingSpinner');

        form.addEventListener('submit', function(e) {
            // At least one workspace must stay selected.
            const boxes = document.querySelectorAll('input[name="workspace_ids[]"][type="checkbox"]');
            const hint = document.getElementById('workspaceHint');
            if (boxes.length > 0 && ![...boxes].some(function(b) { return b.checked; })) {
                e.preventDefault();
                if (hint) hint.classList.remove('hidden');
                return;
            }
            if (hint) hint.classList.add('hidden');

            // Show loading state...
            button.disabled = true;
            authorizeText.textContent = 'Authorizing...';
            loadingSpinner.classList.remove('hidden');

            // After form submission, watch for redirect and close window...
            setTimeout(function() {
                const checkRedirect = setInterval(function() {
                    // If URL changed or we have OAuth params, redirect happened...
                    if (!window.location.href.includes('/oauth/authorize') ||
                        window.location.search.includes('code=') ||
                        window.location.search.includes('error=')) {
                        clearInterval(checkRedirect);
                        window.close();
                    }
                }, 100);

                // Fallback: Close after five seconds...
                setTimeout(function() {
                    clearInterval(checkRedirect);
                    window.close();
                }, 5000);
            }, 200);
        });

        // Handle cancel button...
        const cancelForm = document.querySelector('form[method="POST"]:has(input[name="_method"][value="DELETE"])');
        if (cancelForm) {
            cancelForm.addEventListener('submit', function(e) {
                setTimeout(function() {
                    window.close();
                }, 200);
            });
        }
    });
</script>
</body>
</html>
