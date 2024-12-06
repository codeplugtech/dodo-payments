<?php

namespace Codeplugtech\DodoPayments;


use Illuminate\Database\Eloquent\Model;
use Money\Currency;
use Illuminate\Database\Eloquent\Casts\Attribute;
class Transaction extends Model
{
    /**
     * The attributes that are not mass assignable.
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'billed_at' => 'datetime',
    ];

    /**
     * Get the subscription related to the transaction.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function subscription()
    {
        return $this->belongsTo(DodoPayments::$subscriptionModel, 'subscription_id', 'subscription_id');
    }

    /**
     * Get the total amount that was paid.
     *
     * @return string
     */
    public function total():string
    {
        return DodoPayments::formatAmount($this->total, $this->currency());
    }

    protected function formatTotal(): Attribute
    {
        return Attribute::make(
            get: fn (string $value) => $this->total(),
        );
    }

    /**
     * Get the total amount that was paid.
     *
     * @return string
     */
    public function tax():string
    {
        return DodoPayments::formatAmount($this->tax, $this->currency());
    }

    /**
     * Get the used currency for the transaction.
     *
     * @return \Money\Currency
     */
    public function currency(): Currency
    {
        return new Currency($this->currency);
    }

}
