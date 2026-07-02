<?php

declare(strict_types=1);

namespace App\Support;

/**
 * ISO 3166-1 alpha-2 country codes → names for the billing-address picker.
 * Focused on the EU + major markets; extend as needed.
 */
class Countries
{
    /**
     * @return array<string, string>
     */
    public static function list(): array
    {
        return [
            'AT' => 'Austria', 'BE' => 'Belgium', 'BG' => 'Bulgaria', 'HR' => 'Croatia',
            'CY' => 'Cyprus', 'CZ' => 'Czechia', 'DK' => 'Denmark', 'EE' => 'Estonia',
            'FI' => 'Finland', 'FR' => 'France', 'DE' => 'Germany', 'GR' => 'Greece',
            'HU' => 'Hungary', 'IE' => 'Ireland', 'IT' => 'Italy', 'LV' => 'Latvia',
            'LT' => 'Lithuania', 'LU' => 'Luxembourg', 'MT' => 'Malta', 'NL' => 'Netherlands',
            'PL' => 'Poland', 'PT' => 'Portugal', 'RO' => 'Romania', 'SK' => 'Slovakia',
            'SI' => 'Slovenia', 'ES' => 'Spain', 'SE' => 'Sweden',
            'GB' => 'United Kingdom', 'CH' => 'Switzerland', 'NO' => 'Norway',
            'IS' => 'Iceland', 'LI' => 'Liechtenstein',
            'US' => 'United States', 'CA' => 'Canada', 'AU' => 'Australia', 'NZ' => 'New Zealand',
            'AE' => 'United Arab Emirates', 'SG' => 'Singapore', 'JP' => 'Japan',
        ];
    }
}
