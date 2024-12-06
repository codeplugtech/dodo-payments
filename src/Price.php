<?php

namespace Codeplugtech\DodoPayments;

class Price
{
    public string $type;
    public int $amount; // Stored as an integer in cents
    public string $currency;
    public float $discount;
    public bool $purchasingPowerParity;
    public ?int $paymentFrequencyCount;
    public ?string $paymentFrequencyInterval;
    public ?int $subscriptionPeriodCount;
    public ?string $subscriptionPeriodInterval;
    public ?int $trialPeriodDays;

    public function __construct(array $data)
    {
        $this->type = $data['type'];
        $this->amount = $data['price'];
        $this->currency = $data['currency'];
        $this->discount = $data['discount'];
        $this->purchasingPowerParity = $data['purchasing_power_parity'];
        $this->paymentFrequencyCount = $data['payment_frequency_count']??null;
        $this->paymentFrequencyInterval = $data['payment_frequency_interval']??null;
        $this->subscriptionPeriodCount = $data['subscription_period_count']??null;
        $this->subscriptionPeriodInterval = $data['subscription_period_interval']??null;
        $this->trialPeriodDays = $data['trial_period_days']??null;
    }

    public function amount(): string
    {
        return DodoPayments::formatAmount($this->amount, $this->currency);
    }

    /**
     * Get the raw amount.
     *
     * @return string
     */
    public function rawAmount()
    {
        return $this->amount;
    }

    /**
     * Get the currency code.
     *
     * @return string
     */
    public function getCurrency()
    {
        return $this->currency;
    }

    /**
     * Get the payment frequency interval as a readable string.
     *
     * @return string
     */
    public function getPaymentInterval()
    {
        return $this->paymentFrequencyInterval;
    }

    /**
     * Dynamically get values from the Product price.
     *
     * @param string $key
     * @return mixed
     */
    public function __get($key)
    {
        return $this->price[$key];
    }


}
