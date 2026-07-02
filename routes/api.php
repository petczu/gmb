<?php

declare(strict_types=1);

use App\Api\ApiAbilities;
use App\Http\Controllers\Api\V1\LocationController;
use App\Http\Controllers\Api\V1\ReviewController;
use App\Http\Controllers\Api\V1\StatsController;
use App\Http\Middleware\AuthenticateApiKey;
use App\Http\Middleware\RequireApiAbility;
use Illuminate\Support\Facades\Route;

/*
 * Public REST API v1. Authenticated by a workspace API key (Bearer) which
 * resolves + initializes tenancy and gates the Pro plan; each route then
 * requires the key to hold the relevant scope (ApiAbilities).
 */
Route::prefix('v1')
    ->middleware(AuthenticateApiKey::class)
    ->group(function (): void {
        Route::get('locations', [LocationController::class, 'index'])
            ->middleware(RequireApiAbility::class.':'.ApiAbilities::LOCATIONS_READ);

        Route::get('reviews', [ReviewController::class, 'index'])
            ->middleware(RequireApiAbility::class.':'.ApiAbilities::REVIEWS_READ);

        Route::get('reviews/{review}', [ReviewController::class, 'show'])
            ->whereNumber('review')
            ->middleware(RequireApiAbility::class.':'.ApiAbilities::REVIEWS_READ);

        Route::post('reviews/{review}/reply', [ReviewController::class, 'reply'])
            ->whereNumber('review')
            ->middleware(RequireApiAbility::class.':'.ApiAbilities::REVIEWS_REPLY);

        Route::get('stats', [StatsController::class, 'index'])
            ->middleware(RequireApiAbility::class.':'.ApiAbilities::ANALYTICS_READ);
    });
