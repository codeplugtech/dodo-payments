<?php

namespace Codeplugtech\DodoPayments\Events;

use Codeplugtech\DodoPayments\Transaction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PaymentSucceeded
{
    use Dispatchable, SerializesModels;


    public function __construct(Model $billable,  Transaction $transaction, array $payload)
    {

    }
}
