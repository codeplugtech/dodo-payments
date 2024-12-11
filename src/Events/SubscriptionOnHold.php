<?php

namespace Codeplugtech\DodoPayments\Events;

use Codeplugtech\DodoPayments\Subscription;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
class SubscriptionOnHold
{
    use Dispatchable, SerializesModels;

    public function __construct(Model $billable,Subscription $subscription, array $payload)
    {

    }
}
