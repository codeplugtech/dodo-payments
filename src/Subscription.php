<?php

namespace Codeplugtech\DodoPayments;

use Illuminate\Support\Facades\Response;
use Codeplugtech\DodoPayments\Enum\SubscriptionStatusEnum;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class Subscription extends Model
{

    use HasFactory;

    protected $guarded = [];


    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'trial_ends_at' => 'datetime',
        'paused_at' => 'datetime',
        'ends_at' => 'datetime',
    ];

    /**
     * Get the model related to the subscription.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        $model = DodoPayments::$customerModel;

        return $this->belongsTo($model, (new $model)->getForeignKey());
    }

    /**
     * Get all of the transactions for the Billable model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function transactions()
    {
        return $this->hasMany(DodoPayments::$transactionModel, 'subscription_id', 'subscription_id')
            ->orderByDesc('created_at');
    }

    /**
     * Check if the subscription is active.
     */
    public function active(): bool
    {
        return $this->status === SubscriptionStatusEnum::ACTIVE->value;
    }

    /**
     * Filter query by active.
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('status', SubscriptionStatusEnum::ACTIVE->value);
    }

    /**
     * Check if the subscription is paused.
     */
    public function cancelled(): bool
    {
        return $this->status === SubscriptionStatusEnum::CANCELLED->value;
    }

    /**
     * Filter query by cancelled.
     */
    public function scopeCancelled(Builder $query): void
    {
        $query->where('status', SubscriptionStatusEnum::CANCELLED->value);
    }

    /**
     * Cancel the subscription.
     */
    public function cancel(bool $cancelNow = false): self
    {
        $response = DodoPayments::api('PATCH', "subscriptions/$this->subscription_id", [
            'status' => SubscriptionStatusEnum::CANCELLED->value,
        ]);
        $endsAt = $cancelNow ? $response['created_at'] : $this->next_billing_at;
        $this->forceFill([
            'ends_at' => Carbon::parse($endsAt, 'UTC')
        ])->save();
        return $this;
    }

    /**
     * Cancel the subscription.
     */
    public function pause(): self
    {
        $response = DodoPayments::api('PATCH', "subscriptions/{$this->subscription_id}", [
            'status' => SubscriptionStatusEnum::PAUSED->value,
        ]);

        $this->sync($response->collect()->toArray());

        return $this;
    }

    /**
     * Filter query by cancelled.
     */
    public function scopePaused(Builder $query): void
    {
        $query->where('status', SubscriptionStatusEnum::PAUSED->value);
    }

    /**
     * Get the last payment for the subscription.
     *
     * @return Payment|null
     */
    public function lastPayment(): ?Payment
    {
        if ($transaction = $this->transactions()->orderByDesc('billed_at')->first()) {
            return new Payment($transaction->total, $transaction->currency, $transaction->billed_at);
        }

        return null;
    }

    /**
     * Get the next payment for the subscription.
     *
     * @return Payment|null
     */
    public function nextPayment(): ?Payment
    {
        if ($transaction = $this->transactions()->orderByDesc('billed_at')->first()) {
            return new Payment(
                $transaction->total,
                $transaction->currency,
                Carbon::parse($this->next_billing_at, 'UTC'),
            );
        }
        return null;
    }

    /**
     * @param string $productId
     * @param string $type
     * @return void
     * @throws Exceptions\DodoPaymentsException
     */
    public function swapPlan(string $productId, string $type): void
    {
        $response = DodoPayments::api('POST', "subscriptions/{$this->subscription_id}/change-plan", [
            'product_id' => $productId,
            'proration_billing_mode' => 'prorated_immediately',
            'quantity' => 1
        ],
        );
        if ($response->successful()) {
            $this->update([
                'product_id' => $productId,
                'type' => $type,
            ]);
        }
    }
    /**
     * Determine if the subscription is within its grace period after cancellation.
     *
     * @return bool
     */
    public function onGracePeriod()
    {
        return $this->ends_at && $this->ends_at->isFuture();
    }

    /**
     * Determine if the subscription is within its grace period after being paused.
     *
     * @return bool
     */
    public function onPausedGracePeriod()
    {
        return $this->paused_at && $this->paused_at->isFuture();
    }

    /**
     * Filter query by on grace period.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return void
     */
    public function scopeOnGracePeriod($query)
    {
        $query->whereNotNull('ends_at')->where('ends_at', '>', Carbon::now());
    }

    /**
     * Filter query by not on grace period.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return void
     */
    public function scopeNotOnGracePeriod($query)
    {
        $query->whereNull('ends_at')->orWhere('ends_at', '<=', Carbon::now());
    }

    /**
     * Filter query by on trial grace period.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return void
     */
    public function scopeOnPausedGracePeriod($query)
    {
        $query->whereNotNull('paused_at')->where('paused_at', '>', Carbon::now());
    }

    /**
     * Filter query by not on trial grace period.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return void
     */
    public function scopeNotOnPausedGracePeriod($query)
    {
        $query->whereNull('paused_at')->orWhere('paused_at', '<=', Carbon::now());
    }


    /**
     * Sync the subscription with the given attributes.
     */
    public function sync(array $attributes): self
    {
        $this->update([
            'status' => $attributes['status']
        ]);
        return $this;
    }


}
