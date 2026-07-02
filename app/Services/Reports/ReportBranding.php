<?php

declare(strict_types=1);

namespace App\Services\Reports;

use App\Billing\Plans;
use App\Models\Workspace;
use App\Services\Billing\LocationBilling;
use Illuminate\Support\Facades\Storage;

/**
 * Branding for the performance report. Defaults to Repunio (system brand).
 * Workspaces on a plan that allows white-labelling may replace the logo and
 * accent colour with their own (set on the Company page).
 */
class ReportBranding
{
    private const DEFAULT_NAME = 'Repunio';

    private const DEFAULT_COLOR = '#2d19ec';

    /**
     * @return array{name: string, color: string, logo: ?string, whiteLabel: bool}
     */
    public static function for(?Workspace $workspace): array
    {
        $default = [
            'name' => self::DEFAULT_NAME,
            'color' => self::DEFAULT_COLOR,
            'logo' => asset('logo/repunio-full-light.png'),
            'whiteLabel' => false,
        ];

        if ($workspace === null) {
            return $default;
        }

        if (! app(LocationBilling::class)->allows($workspace, Plans::WHITE_LABEL)) {
            return $default;
        }

        $color = $workspace->brand_color;
        $logo = $workspace->logo_path ? Storage::disk('uploads')->url($workspace->logo_path) : null;

        // Only override once the client has actually set their branding.
        if (! $color && ! $logo) {
            return $default;
        }

        return [
            'name' => $workspace->name ?: self::DEFAULT_NAME,
            'color' => $color ?: self::DEFAULT_COLOR,
            'logo' => $logo ?: $default['logo'],
            'whiteLabel' => true,
        ];
    }
}
