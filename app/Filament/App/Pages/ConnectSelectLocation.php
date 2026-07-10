<?php

namespace App\Filament\App\Pages;

use App\Jobs\ResolveLocationPlaceId;
use App\Jobs\SyncWorkspaceReviews;
use App\Mail\LocationConnectedMail;
use App\Models\GoogleAccount;
use App\Models\Location;
use App\Models\Workspace;
use App\Services\ActivityLog\ActivityLogger;
use App\Services\Billing\LocationBilling;
use App\Services\Notifications\NotificationCategory;
use App\Services\Notifications\NotificationDispatcher;
use App\Services\Reviews\ZernioConnectionManager;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * In-app "Select business location" step of the headless Zernio connect flow.
 * Lists the Google Business locations available under the authorized account and
 * lets the client connect one or more of them. Each connected location becomes a
 * tracked Location (reviews synced for it). The Google account is finalized once,
 * on the first selection.
 */
class ConnectSelectLocation extends Page
{
    protected string $view = 'filament.app.pages.connect-select-location';

    protected static ?string $slug = 'connect-location';

    public function getTitle(): string
    {
        return __('onboarding.select_location_title');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    /** @var array<int, array{id:string, name:string, address:?string, accountName:?string}> */
    public array $locations = [];

    /** @var array<int, string> external ids of locations already connected */
    public array $connectedIds = [];

    public ?string $error = null;

    /** The OAuth pending token expired — offer a one-click reconnect. */
    public bool $pendingExpired = false;

    public function mount(): void
    {
        $pending = session('zernio_pending');

        if (! $pending || (empty($pending['pendingDataToken']) && empty($pending['tempToken']))) {
            $this->redirect('/locations');

            return;
        }

        $this->connectedIds = Location::query()->pluck('external_id')->all();

        try {
            $this->locations = app(ZernioConnectionManager::class)->pendingLocations(
                $pending['profileId'],
                $pending['pendingDataToken'] ?? null,
                $pending['tempToken'] ?? null,
            );
        } catch (Throwable $e) {
            // Zernio keeps the pending OAuth data only briefly; a stale page
            // (or a consumed token) gets a friendly "reconnect" instead of raw JSON.
            if (str_contains($e->getMessage(), 'Pending OAuth data not found')) {
                $this->pendingExpired = true;
                $this->error = __('onboarding.pending_expired');
            } else {
                $this->error = $e->getMessage();
            }
        }
    }

    public function isConnected(string $externalId): bool
    {
        return in_array($externalId, $this->connectedIds, true);
    }

    public function select(string $locationId): void
    {
        $pending = session('zernio_pending');
        $workspace = Workspace::find(session('current_workspace_id'));

        if (! $pending || $workspace === null) {
            $this->redirect('/locations');

            return;
        }

        $picked = collect($this->locations)->firstWhere('id', $locationId);
        $manager = app(ZernioConnectionManager::class);

        try {
            // Finalize the Google account once (the first selection consumes the
            // pending token); later picks just add more tracked locations.
            if (! GoogleAccount::query()->where('workspace_id', $workspace->id)->exists()) {
                $manager->selectLocation(
                    $pending['profileId'],
                    $pending['pendingDataToken'] ?? null,
                    $pending['tempToken'] ?? null,
                    $locationId,
                    url('/locations'),
                );
                $manager->linkConnectedAccounts($workspace);
            }

            $accountId = GoogleAccount::query()->where('workspace_id', $workspace->id)->value('zernio_account_id');

            $location = Location::updateOrCreate(
                ['external_id' => $locationId],
                [
                    'zernio_account_id' => $accountId,
                    'name' => $picked['name'] ?? 'Location',
                    'address' => $picked['address'] ?? null,
                    'status' => 'active',
                ],
            );

            // Resolve the Google Maps place_id off the request so competitor
            // snapshots can reuse this location's synced data instead of a paid
            // Places call (see LocationPlaceResolver).
            if (blank($location->place_id)) {
                ResolveLocationPlaceId::dispatch((string) $workspace->id, (int) $location->id);
            }

            if ($location->wasRecentlyCreated) {
                ActivityLogger::log('location.connected', ['location' => $location->name], $location);

                // "Connected, import is running" email; the reviews-are-in email
                // follows from ReviewSync once the first import finishes.
                try {
                    app(NotificationDispatcher::class)->dispatch(
                        $workspace,
                        NotificationCategory::OPERATIONS,
                        fn (string $name, string $lang) => new LocationConnectedMail(
                            name: $name,
                            location: (string) $location->name,
                            lang: $lang,
                        ),
                    );
                } catch (Throwable $e) {
                    Log::warning('Location connected email failed', ['error' => $e->getMessage()]);
                }
            }

            // Pulling reviews can be hundreds of records, do it off the request
            // so the picker stays responsive. The Locations page fills in once
            // the worker finishes.
            SyncWorkspaceReviews::dispatch((string) $workspace->id);

            // Keep the per-location subscription quantity in sync (no-op until
            // Stripe is configured and the workspace is subscribed).
            app(LocationBilling::class)->syncQuantity($workspace);
        } catch (Throwable $e) {
            Notification::make()->title(__('onboarding.connect_failed'))->body($e->getMessage())->danger()->send();

            return;
        }

        $this->connectedIds[] = $locationId;

        Notification::make()
            ->title(__('onboarding.connected_title', ['name' => $picked['name'] ?? __('onboarding.location_fallback')]))
            ->body(__('onboarding.connected_body'))
            ->success()
            ->send();
    }

    public function finish(): void
    {
        session()->forget('zernio_pending');
        $this->redirect('/locations');
    }
}
