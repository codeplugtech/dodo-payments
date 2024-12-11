<?php

namespace Codeplugtech\DodoPayments\Tests\Unit;

use Codeplugtech\DodoPayments\Enum\SubscriptionStatusEnum;
use Codeplugtech\DodoPayments\Subscription;
use Codeplugtech\DodoPayments\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);
it('has a name attribute', function () {
    $subscription = new Subscription([
        'product_id' => 'prd_12344',
        'user_id' => 1
    ]);
    expect($subscription->product_id)->toBe('prd_12344')
        ->and($subscription->user_id)->toBe(1);
});

it('it can determine if the subscription is on grace period', function () {
    $subscription = new Subscription([
        'product_id' => 'prd_12344',
        'type' => 'basic',
        'user_id' => 1,
        'status' => SubscriptionStatusEnum::ACTIVE->value,
        'ends_at' => now()->addDay()
    ]);
    $this->assertTrue($subscription->onGracePeriod());
});

it('it can determine if the subscription is cancelled', function () {
    $subscription = new Subscription([
        'product_id' => 'prd_12344',
        'type' => 'basic',
        'user_id' => 1,
        'status' => SubscriptionStatusEnum::CANCELLED->value,
        'ends_at' => now()->subDay()
    ]);
    $this->assertTrue($subscription->cancelled());
});

it('it can determine if the subscription is active', function () {
    $subscription = new Subscription([
        'product_id' => 'prd_12344',
        'type' => 'basic',
        'user_id' => 1,
        'status' => SubscriptionStatusEnum::ACTIVE->value,
        'ends_at' => now()->subDay()
    ]);
    $this->assertTrue($subscription->active());
});

it('creates a model instance using the factory', function () {
    $model = Transaction::factory()->create();
    expect($model)->toBeInstanceOf(Transaction::class);
    $this->assertDatabaseHas('transactions', [
        'id' => $model->id,
    ]);
});
