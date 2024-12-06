<?php

namespace Codeplugtech\DodoPayments\Concerns;

use Codeplugtech\DodoPayments\DodoPayments;
use Codeplugtech\DodoPayments\Enum\SubscriptionStatusEnum;
use Codeplugtech\DodoPayments\SubscriptionBuilder;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 *
 */
trait ManageSubscriptions
{

    /**
     * @param string $type
     * @param string $productId
     * @return SubscriptionBuilder
     */
    public function newSubscription(string $type, string $productId): SubscriptionBuilder
    {
        return new SubscriptionBuilder($type, $productId,$this);
    }

    /**
     * Get all of the subscriptions for the Dodo model.
     *
     * @return HasMany
     */
    public function subscriptions()
    {
        return $this->hasMany(DodoPayments::$subscriptionModel, $this->getForeignKey())->orderBy('created_at', 'desc');
    }

    /**
     * Get a active subscription
     *
     * @param string $type
     *
     */
    public function subscription()
    {
        return $this->subscriptions?->where('status',SubscriptionStatusEnum::ACTIVE->value)->first();
    }
}
