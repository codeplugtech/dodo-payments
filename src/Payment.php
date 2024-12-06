<?php

namespace Codeplugtech\DodoPayments;

use Carbon\Carbon;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use JsonSerializable;
use Money\Currency;
class Payment implements Arrayable, Jsonable, JsonSerializable
{
    /**
     * The amount of the payment.
     *
     * @var string
     */
    public string $amount;

    /**
     * The currency of the payment.
     *
     * @var string
     */
    public string $currency;

    /**
     * The payment date.
     *
     * @var \Carbon\Carbon
     */
    public Carbon $date;

    /**
     * Create a new Payment instance.
     *
     * @param  string  $amount
     * @param  string  $currency
     * @param  \Carbon\Carbon  $date
     * @return void
     */
    public function __construct(string $amount,string $currency, Carbon $date)
    {
        $this->amount = $amount;
        $this->currency = $currency;
        $this->date = $date;
    }

    /**
     * Get the total amount of the payment.
     *
     * @return string
     */
    public function amount():string
    {
        return DodoPayments::formatAmount($this->amount, $this->currency);
    }

    /**
     * Get the raw total of the payment.
     *
     * @return string
     */
    public function rawAmount():string
    {
        return $this->amount;
    }

    /**
     * Get the currency used for the payment.
     *
     * @return \Money\Currency
     */
    public function currency(): Currency
    {
        return new Currency($this->currency);
    }

    /**
     * Get the date of the payment as a Carbon instance.
     *
     * @return \Carbon\Carbon
     */
    public function date()
    {
        return $this->date;
    }

    /**
     * Get the instance as an array.
     *
     * @return array
     */
    public function toArray()
    {
        return [
            'amount' => $this->amount(),
            'currency' => $this->currency,
            'date' => $this->date()->toIso8601String(),
        ];
    }

    /**
     * Convert the object to its JSON representation.
     *
     * @param  int  $options
     * @return string
     */
    public function toJson($options = 0):string
    {
        return json_encode($this->jsonSerialize(), $options);
    }

    /**
     * Convert the object into something JSON serializable.
     *
     * @return array
     */
    #[\ReturnTypeWillChange]
    public function jsonSerialize():array
    {
        return $this->toArray();
    }
}

