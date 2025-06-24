<?php

use Illuminate\Support\Facades\Event;
use Codeplugtech\DodoPayments\Events\SubscriptionRenewed;
use Codeplugtech\DodoPayments\Subscription;
use Codeplugtech\DodoPayments\Tests\Models\User;

beforeEach(function () {
    config()->set('dodo.user_model', \Codeplugtech\DodoPayments\Tests\Models\User::class);
    Route::post('/webhook/dodo', \Codeplugtech\DodoPayments\Http\Controllers\WebhookController::class);
});

it('dispatches SubscriptionRenewed event when webhook receives subscription.renewed', function () {
    Event::fake();
    $user = User::factory()->create(['email' => 'test@example.com']);
    $subscription = Subscription::factory()->create([
        'subscription_id' => 'sub_123',
        'user_id' => $user->id,
    ]);
    $payload = [
        'type' => 'subscription.renewed',
        'data' => [
            'subscription_id' => $subscription->subscription_id,
            'customer' => ['email' => $user->email],
            'status' => 'active',
            'next_billing_date' => now()->addMonth()->toISOString(),
        ],
    ];
    $this->withoutExceptionHandling();
    $this->postJson('/webhook/dodo', $payload)
        ->assertSuccessful();
    Event::assertDispatched(SubscriptionRenewed::class);
});
