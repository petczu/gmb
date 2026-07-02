<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Tenant Routes
|--------------------------------------------------------------------------
|
| This app uses SESSION-based tenancy (not domain identification), so tenant
| routes are NOT registered here. Tenant-scoped pages live under the Filament
| `app` panel (routes/web.php), initialized by the SetCurrentWorkspace
| middleware. Leaving the default domain route in place would register a `/`
| route guarded by InitializeTenancyByDomain and throw
| TenantCouldNotBeIdentifiedOnDomainException — see gmb-gotchas #5.
|
| Intentionally left without route definitions.
*/
