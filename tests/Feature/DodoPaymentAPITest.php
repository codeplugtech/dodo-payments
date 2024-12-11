<?php

use Codeplugtech\DodoPayments\DodoPayments;
use Codeplugtech\DodoPayments\Exceptions\DodoPaymentsException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    config(['dodo.api_key' => 'test_api_key']);
    config(['dodo.sandbox' => true]);
});


test('api method throws exception if API key is not set', function () {
    config(['dodo.api_key' => null]);

    $this->expectException(\Exception::class);
    $this->expectExceptionMessage('Dodo Payments API key not set.');

    DodoPayments::api('get', 'test');
});

test('api method makes a successful API call', function () {
    Http::fake([
        'https://test.dodopayments.com/*' => Http::response(['success' => true], 200),
    ]);

    $response = DodoPayments::api('get', 'test');

    expect($response->json('success'))->toBeTrue();
    Http::assertSent(function (Request $request) {
        return $request->url() === 'https://test.dodopayments.com/test' &&
            $request->hasHeader('Authorization', 'Bearer test_api_key');
    });
});

test('api method throws DodoPaymentsException on failure', function () {
    Http::fake([
        'https://test.dodopayments.com/*' => Http::response(['error' => 'Invalid request'], 400),
    ]);

    $this->expectException(DodoPaymentsException::class);
    $this->expectExceptionMessage('Invalid request');

    DodoPayments::api('get', 'test');
});

test('products method returns a collection of products', function () {
    $data = [
        "items" => [
            [
                "business_id" => "biz_12345", // Example value
                "created_at" => "2023-11-07T05:31:56Z",
                "description" => "This is a sample product description.", // Example value
                "image" => "https://example.com/images/product.jpg", // Example value
                "is_recurring" => true,
                "name" => "Sample Digital Product", // Example value
                "price" => 123,
                "product_id" => "prod_67890", // Example value
                "tax_category" => "digital_products",
                "updated_at" => "2023-11-07T05:31:56Z"
            ],
            [
                "business_id" => "biz_12345", // Example value
                "created_at" => "2023-11-07T05:31:56Z",
                "description" => "This is a sample product description.", // Example value
                "image" => "https://example.com/images/product.jpg", // Example value
                "is_recurring" => true,
                "name" => "Sample Digital Product", // Example value
                "price" => 123,
                "product_id" => "prod_67899", // Example value
                "tax_category" => "digital_products",
                "updated_at" => "2023-11-07T05:31:56Z"
            ]
        ]
    ];
    Http::fake([
        'https://test.dodopayments.com/products' => Http::response($data, 200),
    ]);

    $products = DodoPayments::products();

    expect($products)->toBeInstanceOf(Collection::class)
        ->and($products->count())->toBe(2)
        ->and($products->first())->toBeInstanceOf(\Codeplugtech\DodoPayments\Product::class);
});

test('productPrice method retrieves product details', function () {
    $product = [
        "business_id" => "biz_12345",
        "created_at" => "2023-11-07T05:31:56Z",
        "description" => "This is a premium digital product for enhanced productivity.",
        "image" => "https://example.com/images/product.jpg",
        "is_recurring" => true,
        "name" => "Premium Digital Toolkit",
        "price" => [
            "currency" => "AED",
            "discount" => 50,
            "price" => 500,
            "purchasing_power_parity" => true,
            "type" => "one_time_price"
        ],
        "product_id" => "prod_67890",
        "tax_category" => "digital_products",
        "updated_at" => "2023-11-07T05:31:56Z"
    ];
    Http::fake([
        'https://test.dodopayments.com/products/prod_67890' => Http::response($product, 200),
    ]);

    $product = DodoPayments::productPrice('prod_67890');

    expect($product)->toBeInstanceOf(\Codeplugtech\DodoPayments\Product::class)
        ->and($product->productId)->toBe('prod_67890')
        ->and($product->price->amount)->toBe(500);
});
