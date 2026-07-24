<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Workspace;
use App\Services\Reviews\ZernioConnectionManager;
use Filament\Notifications\Notification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * OAuth flow for connecting a Google Business account to the current workspace
 * through Zernio (single platform key). connect() sends the client to Google;
 * callback() links whatever account they authorized.
 */
class ZernioConnectController extends Controller
{
    private const ACCOUNTS_URL = '/locations';

    /**
     * Where errors (and plain-redirect exits) send the user back to: the
     * onboarding wizard when the flow started there, Locations otherwise.
     * Remembered in the session so it survives the Google round-trip.
     */
    private function returnUrl(): string
    {
        return (string) session('zernio_return', self::ACCOUNTS_URL);
    }

    public function connect(ZernioConnectionManager $manager): RedirectResponse
    {
        session(['zernio_return' => str_contains((string) url()->previous(), '/onboarding')
            ? '/onboarding'
            : self::ACCOUNTS_URL]);

        $workspace = $this->workspace();
        if ($workspace === null) {
            return redirect($this->returnUrl());
        }

        try {
            $result = $manager->connectUrl($workspace, route('zernio.google.callback'));
        } catch (Throwable $e) {
            Notification::make()->title(__('errors.google_connect_start_failed'))->body($e->getMessage())->danger()->send();

            return redirect($this->returnUrl());
        }

        session(['zernio_oauth_state' => $result['state']]);

        return redirect()->away($result['authUrl']);
    }

    public function callback(Request $request, ZernioConnectionManager $manager): RedirectResponse
    {
        Log::info('Zernio GMB OAuth callback', ['query' => $request->query()]);

        $workspace = $this->workspace();
        if ($workspace === null) {
            return redirect($this->returnUrl());
        }

        // Headless flow: Zernio returns a token + step=select_page; the client
        // picks which Business location to connect in our own UI. The token may
        // arrive as pendingDataToken or tempToken/connect_token depending on the
        // API version — capture whatever is present.
        $q = $request->query();
        $profileId = $q['profileId'] ?? $q['profile_id'] ?? $workspace->zernio_profile_id;
        $pendingDataToken = $q['pendingDataToken'] ?? $q['pending_data_token'] ?? null;
        $tempToken = $q['tempToken'] ?? $q['temp_token'] ?? null;
        $connectToken = $q['connect_token'] ?? $q['connectToken'] ?? null;
        $isSelectStep = in_array($q['step'] ?? null, ['select_page', 'select_location'], true);

        if ($pendingDataToken || $tempToken || $connectToken || $isSelectStep) {
            session(['zernio_pending' => [
                'profileId' => (string) $profileId,
                'pendingDataToken' => $pendingDataToken ? (string) $pendingDataToken : null,
                'tempToken' => $tempToken ? (string) $tempToken : null,
                'connectToken' => $connectToken ? (string) $connectToken : null,
            ]]);

            return redirect('/connect-location');
        }

        // Fallback: account already finalized — link whatever is present.
        try {
            $linked = $manager->linkConnectedAccounts($workspace);
        } catch (Throwable $e) {
            Notification::make()->title(__('errors.google_connect_failed'))->body($e->getMessage())->danger()->send();

            return redirect($this->returnUrl());
        }

        Notification::make()
            ->title($linked > 0 ? "Connected {$linked} Google account(s)" : 'Authorized, but no location was selected')
            ->success()
            ->send();

        return redirect(self::ACCOUNTS_URL);
    }

    private function workspace(): ?Workspace
    {
        return Workspace::find(session('current_workspace_id'));
    }
}
