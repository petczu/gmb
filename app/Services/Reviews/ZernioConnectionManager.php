<?php

declare(strict_types=1);

namespace App\Services\Reviews;

use App\Models\GoogleAccount;
use App\Models\Workspace;
use GuzzleHttp\Client as GuzzleClient;
use Zernio\Api\AccountsApi;
use Zernio\Api\ConnectApi;
use Zernio\Api\ProfilesApi;
use Zernio\Configuration;
use Zernio\Model\CreateProfileRequest;
use Zernio\Model\SelectGoogleBusinessLocationRequest;

/**
 * Manages a workspace's connected Google Business accounts using the single
 * platform-wide Zernio key (config services.reviews.zernio_key).
 *
 * Each workspace gets its own Zernio "profile" (accounts attach to a profile);
 * the profile id is stored on the workspace (stancl `data` column). The connect
 * flow is OAuth: ensure profile -> getConnectUrl -> the client authorizes Google
 * -> Zernio redirects back -> we link the now-connected accounts to the workspace.
 */
class ZernioConnectionManager
{
    public const PLATFORM = 'googlebusiness';

    /**
     * Ensure the workspace has a LIVE Zernio profile. The stored id is verified
     * against Zernio (profiles can be deleted over there); a stale id falls back
     * to adopting an existing profile with the same name (names are unique on
     * Zernio) and only then to creating a fresh one.
     */
    public function ensureProfile(Workspace $workspace): string
    {
        $name = $workspace->name ?: 'Workspace '.$workspace->id;
        $profiles = $this->profilesApi()->listProfiles()->getProfiles() ?? [];

        // Stored id still exists on Zernio's side?
        if ($workspace->zernio_profile_id) {
            $stored = (string) $workspace->zernio_profile_id;

            foreach ($profiles as $profile) {
                if ((string) $profile->getId() === $stored) {
                    return $stored;
                }
            }
            // Deleted on Zernio — fall through and re-resolve.
        }

        $profileId = null;
        foreach ($profiles as $profile) {
            if ((string) $profile->getName() === $name) {
                $profileId = (string) $profile->getId();
                break;
            }
        }

        if ($profileId === null) {
            try {
                $created = $this->profilesApi()->createProfile((new CreateProfileRequest)->setName($name));
                $profileId = (string) $created->getProfile()?->getId();
            } catch (\Throwable $e) {
                // Race: "a profile with this name already exists" → adopt it
                // instead of failing the whole connect flow.
                $profileId = $this->profileIdByName($name);

                if ($profileId === null) {
                    throw $e;
                }
            }
        }

        $workspace->zernio_profile_id = $profileId;
        $workspace->save();

        return $profileId;
    }

    /** Id of the existing Zernio profile with this exact name, or null. */
    private function profileIdByName(string $name): ?string
    {
        foreach ($this->profilesApi()->listProfiles()->getProfiles() ?? [] as $profile) {
            if ((string) $profile->getName() === $name) {
                return (string) $profile->getId();
            }
        }

        return null;
    }

    /**
     * Google Business accounts connected under the workspace's profile.
     *
     * @return array<int, array{id:string, name:string, username:?string}>
     */
    public function availableAccounts(?string $profileId): array
    {
        $accounts = $this->accountsApi()->listAccounts($profileId, self::PLATFORM);

        return array_map(fn ($account): array => [
            'id' => (string) $account->getId(),
            'name' => (string) ($account->getDisplayName() ?? $account->getUsername() ?? $account->getId()),
            'username' => $account->getUsername(),
        ], $accounts->getAccounts() ?? []);
    }

    /**
     * OAuth connect URL for adding a Google account to the workspace's profile.
     *
     * @return array{authUrl:string, state:?string, profileId:string}
     */
    public function connectUrl(Workspace $workspace, string $redirectUrl): array
    {
        $profileId = $this->ensureProfile($workspace);

        // headless=true → after Google auth, Zernio redirects back to OUR
        // redirectUrl with a pendingDataToken (instead of showing Zernio's own
        // hosted location-selection page), so the flow stays in our app.
        //
        // NOTE: the SDK serializes the boolean as `headless=1`, which Zernio
        // treats as false (it only honors the string `true`). So we call the
        // endpoint directly with `headless=true`.
        $response = $this->httpClient()->get($this->host().'/v1/connect/'.self::PLATFORM, [
            'headers' => [
                'Authorization' => 'Bearer '.(string) config('services.reviews.zernio_key'),
                'Accept' => 'application/json',
            ],
            'query' => [
                'profileId' => $profileId,
                'redirect_url' => $redirectUrl,
                'headless' => 'true',
            ],
        ]);

        $json = json_decode((string) $response->getBody(), true) ?: [];

        return [
            'authUrl' => (string) ($json['authUrl'] ?? ''),
            'state' => $json['state'] ?? null,
            'profileId' => $profileId,
        ];
    }

    /**
     * Google Business locations available to connect after OAuth (headless).
     *
     * @return array<int, array{id:string, name:string, address:?string, accountName:?string}>
     */
    public function pendingLocations(string $profileId, ?string $pendingDataToken, ?string $tempToken = null): array
    {
        // listGoogleBusinessLocations($profileId, $pendingDataToken, $tempToken)
        $response = $this->connectApi()->listGoogleBusinessLocations($profileId, $pendingDataToken, $tempToken);

        return array_map(fn ($loc): array => [
            'id' => (string) $loc->getId(),
            'name' => (string) ($loc->getName() ?? 'Untitled location'),
            'address' => $loc->getAddress(),
            'accountName' => $loc->getAccountName(),
        ], $response->getLocations() ?? []);
    }

    /**
     * Finalize: connect the chosen Google Business location to the profile.
     */
    public function selectLocation(string $profileId, ?string $pendingDataToken, ?string $tempToken, string $locationId, string $redirectUrl): void
    {
        $request = (new SelectGoogleBusinessLocationRequest)
            ->setProfileId($profileId)
            ->setLocationId($locationId)
            ->setRedirectUrl($redirectUrl)
            // The SDK request only exposes pendingDataToken; fall back to the
            // tempToken when that's what the headless callback returned.
            ->setPendingDataToken($pendingDataToken ?? $tempToken);

        $this->connectApi()->selectGoogleBusinessLocation($request);
    }

    /**
     * After the OAuth round-trip, link every Google account now under the
     * workspace's profile that isn't linked yet. Returns the number newly linked.
     */
    public function linkConnectedAccounts(Workspace $workspace): int
    {
        $profileId = $this->ensureProfile($workspace);
        $linked = 0;

        foreach ($this->availableAccounts($profileId) as $account) {
            $row = GoogleAccount::query()
                ->where('workspace_id', $workspace->id)
                ->where('zernio_account_id', $account['id'])
                ->first();

            if ($row === null) {
                $this->link($workspace, $account['id'], $account['name']);
                $linked++;
            }
        }

        return $linked;
    }

    public function link(Workspace $workspace, string $accountId, ?string $name = null): GoogleAccount
    {
        return GoogleAccount::updateOrCreate(
            ['workspace_id' => $workspace->id, 'zernio_account_id' => $accountId],
            ['name' => $name, 'status' => 'connected'],
        );
    }

    public function unlink(GoogleAccount $account): void
    {
        $account->delete();
    }

    /**
     * Delete the workspace's Zernio profile (used by the GDPR purge so stale
     * profiles don't pile up on Zernio and block future name reuse).
     */
    public function deleteProfile(Workspace $workspace): void
    {
        if (! $workspace->zernio_profile_id) {
            return;
        }

        $this->profilesApi()->deleteProfile((string) $workspace->zernio_profile_id);

        $workspace->zernio_profile_id = null;
        $workspace->save();
    }

    private function accountsApi(): AccountsApi
    {
        return new AccountsApi($this->httpClient(), $this->config());
    }

    private function connectApi(): ConnectApi
    {
        return new ConnectApi($this->httpClient(), $this->config());
    }

    private function profilesApi(): ProfilesApi
    {
        return new ProfilesApi($this->httpClient(), $this->config());
    }

    private function httpClient(): GuzzleClient
    {
        return new GuzzleClient(['timeout' => 15, 'connect_timeout' => 5]);
    }

    private function host(): string
    {
        if ($base = config('services.reviews.zernio_base_url')) {
            return rtrim((string) preg_replace('#/v1/?$#', '', (string) $base), '/');
        }

        return Configuration::getDefaultConfiguration()->getHost();
    }

    private function config(): Configuration
    {
        $config = Configuration::getDefaultConfiguration()
            ->setAccessToken((string) config('services.reviews.zernio_key'));

        // ZERNIO_BASE_URL ends with /v1; SDK paths already include /v1, so strip
        // a trailing /v1 before setting the host.
        if ($base = config('services.reviews.zernio_base_url')) {
            $config->setHost(rtrim((string) preg_replace('#/v1/?$#', '', (string) $base), '/'));
        }

        return $config;
    }
}
