<?php

namespace Codeplugtech\DodoPayments\Events;

use Codeplugtech\DodoPayments\Subscription;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SubscriptionPlanChanged
{

    use Dispatchable, SerializesModels;

    public function __construct(public Model $billable, public Subscription $subscription, public array $payload)
    {

    }
}
