<?php

declare(strict_types=1);

namespace App\Support;

/**
 * The app's permission catalog: sections with granular sub-permissions. The
 * Roles editor renders one checkbox group per section; roles:sync seeds these
 * for every workspace. Owner bypasses all checks via Gate::before.
 */
class PermissionCatalog
{
    /** Section key => granular permissions, in display order. */
    public const GROUPS = [
        'reviews' => ['view_reviews', 'manage_reviews', 'delete_replies', 'manage_review_pages'],
        'locations' => ['manage_locations', 'edit_business_info', 'view_competitors'],
        'publishing' => ['publish_posts'],
        'automations' => ['manage_automations', 'manage_ai_agents'],
        'reports' => ['view_reports', 'generate_reports', 'manage_reports'],
        'team' => ['manage_team', 'manage_roles', 'manage_company', 'manage_notifications', 'manage_integrations'],
        'billing' => ['manage_billing'],
    ];

    /**
     * @return list<string>
     */
    public static function all(): array
    {
        return array_values(array_merge(...array_values(self::GROUPS)));
    }

    /**
     * @return array<string, list<string>>
     */
    public static function groups(): array
    {
        return self::GROUPS;
    }
}
