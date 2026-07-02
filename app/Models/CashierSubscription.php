<?php

declare(strict_types=1);

namespace App\Models;

use Laravel\Cashier\Subscription as CashierBaseSubscription;

/**
 * Cashier Subscription pinned to the CENTRAL connection — billing data lives in
 * the central DB, so it must not be redirected to a tenant DB while tenancy is
 * initialized. Registered via Cashier::useSubscriptionModel().
 */
class CashierSubscription extends CashierBaseSubscription
{
    protected $connection = 'mysql';

    protected $table = 'subscriptions';

    /**
     * Subclassing Cashier's Subscription changes the derived foreign key to
     * `cashier_subscription_id`; force the real column so items()/relations work.
     */
    public function getForeignKey(): string
    {
        return 'subscription_id';
    }
}
