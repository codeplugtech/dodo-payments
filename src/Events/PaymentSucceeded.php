<?php

namespace Codeplugtech\DodoPayments\Events;

use App\Models\User;
use Codeplugtech\DodoPayments\Transaction;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PaymentSucceeded
{
    use Dispatchable, SerializesModels;


    public User $user;

    public Transaction $transaction;

    /**
     * The webhook payload.
     *
     * @var array
     */
    public array $payload;

    public function __construct(User $billable, Transaction $transaction, array $payload)
    {
        $this->user = $billable;
        $this->transaction = $transaction;
        $this->payload = $payload;
    }
}
