<?php

use Codeplugtech\DodoPayments\SubscriptionBuilder;
use Codeplugtech\DodoPayments\Exceptions\DodoPaymentsException;
use Illuminate\Http\RedirectResponse;

it('sets billing information correctly', function () {
    $subscriptionBuilder = new SubscriptionBuilder('monthly', 'prod_12345');

    // Setting billing information
    $subscriptionBuilder->setBilling('123 Main St', 'New York', 'NY', 'US', '10001');

    $reflection = new ReflectionClass(SubscriptionBuilder::class);
    $property = $reflection->getProperty('data');
    $property->setAccessible(true);
    $data = $property->getValue($subscriptionBuilder);

    expect($data['billing'])->toBe([
        'street' => '123 Main St',
        'city' => 'New York',
        'state' => 'NY',
        'country' => 'US',
        'zipcode' => 10001
    ]);
});

it('throws an exception for invalid country code', function () {
    $subscriptionBuilder = new SubscriptionBuilder('monthly', 'prod_12345');

    // Setting invalid country code
    $this->expectException(DodoPaymentsException::class);
    $subscriptionBuilder->setBilling('123 Main St', 'New York', 'NY', 'USA', '10001');
});

it('sets customer information correctly', function () {

    $subscriptionBuilder = new SubscriptionBuilder('monthly', 'prod_12345');
    $subscriptionBuilder->setCustomer('satz','satz@gmail.com');


    $reflection = new ReflectionClass(SubscriptionBuilder::class);
    $property = $reflection->getProperty('data');
    $property->setAccessible(true);
    $data = $property->getValue($subscriptionBuilder);

    expect($data['customer'])->toBe([
        'name' => 'satz',
        'email' => 'satz@gmail.com'
    ]);
});

it('creates a subscription and redirects correctly', function () {

    $subscriptionBuilder = Mockery::mock(SubscriptionBuilder::class)
        ->makePartial()
        ->shouldAllowMockingProtectedMethods();

    $redirectResponse = Mockery::mock(RedirectResponse::class);
    $subscriptionBuilder->shouldReceive('create')
        ->once()
        ->andReturn($redirectResponse);

    $subscriptionBuilder->setProduct('prod_12345')
        ->setCustomer('John Doe', 'john.doe@example.com')
        ->setBilling('123 Main St', 'New York', 'NY', 'US', '10001');

    $response = $subscriptionBuilder->create();

    expect($response)->toBeInstanceOf(RedirectResponse::class);
});

