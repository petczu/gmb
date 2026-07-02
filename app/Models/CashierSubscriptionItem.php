<?php

declare(strict_types=1);

namespace App\Models;

use Laravel\Cashier\SubscriptionItem as CashierBaseSubscriptionItem;

/**
 * Cashier SubscriptionItem pinned to the CENTRAL connection (see
 * [[CashierSubscription]]). Registered via Cashier::useSubscriptionItemModel().
 */
class CashierSubscriptionItem extends CashierBaseSubscriptionItem
{
    protected $connection = 'mysql';

    protected $table = 'subscription_items';
}
