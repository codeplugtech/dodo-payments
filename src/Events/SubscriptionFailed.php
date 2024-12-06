<?php

namespace Codeplugtech\DodoPayments\Events;

use App\Models\User;
use Codeplugtech\DodoPayments\Subscription;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SubscriptionFailed
{
    use Dispatchable, SerializesModels;

    public function __construct(User $billable,Subscription $subscription, array $payload)
    {

    }
}
