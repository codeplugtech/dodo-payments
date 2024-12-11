<?php

namespace Codeplugtech\DodoPayments;

use Codeplugtech\DodoPayments\Exceptions\DodoPaymentsException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Money\Currencies\ISOCurrencies;
use Money\Currency;
use Money\Formatter\IntlMoneyFormatter;
use Money\Money;
use NumberFormatter;

class DodoPayments
{

    /**
     * The custom currency formatter.
     *
     * @var callable
     */
    protected static $formatCurrencyUsing;
    public static $customerModel = 'App\\Models\\User';

    /**
     * The transaction model class name.
     *
     * @var string
     */
    public static $transactionModel = Transaction::class;

    /**
     * The subscription model class name.
     */
    public static string $subscriptionModel = Subscription::class;


    /**
     * Perform a Lemon Squeezy API call.
     *
     * @throws DodoPaymentsException
     */
    public static function api(string $method, string $uri, array $payload = []): Response
    {
        if (empty($apiKey = config('dodo.api_key'))) {
            throw new \Exception('Dodo Payments API key not set.');
        }
        $host = static::apiUrl();
        /** @var \Illuminate\Http\Client\Response $response */
        $response = Http::withToken($apiKey)
            ->accept('application/json')
            ->contentType('application/json')
            ->$method("$host/$uri", $payload);

        if ($response->failed()) {
            throw new DodoPaymentsException($response->body());
        }

        return $response;
    }

    /**
     * Get the Dodo Payments API url.
     *
     * @return string
     */
    public static function apiUrl()
    {
        return 'https://' . (config('dodo.sandbox') ? 'test' : 'live') . '.dodopayments.com';
    }


    /**
     * Set the custom currency formatter.
     *
     * @param callable $callback
     * @return void
     */
    public static function formatCurrencyUsing(callable $callback): void
    {
        static::$formatCurrencyUsing = $callback;
    }

    /**
     * Format the given amount into a displayable currency.
     * From Laravel Cashier
     *
     * @param int $amount
     * @param string $currency
     * @param string|null $locale
     * @param array $options
     * @return string
     */
    public static function formatAmount(int $amount, string $currency, string $locale = null, array $options = []): string
    {
        if (static::$formatCurrencyUsing) {
            return call_user_func(static::$formatCurrencyUsing, $amount, $currency, $locale, $options);
        }

        $money = new Money($amount, new Currency(strtoupper($currency)));

        $locale = $locale ?? config('dodo.currency_locale');

        $numberFormatter = new NumberFormatter($locale, NumberFormatter::CURRENCY);

        if (isset($options['min_fraction_digits'])) {
            $numberFormatter->setAttribute(NumberFormatter::MIN_FRACTION_DIGITS, $options['min_fraction_digits']);
        }

        $moneyFormatter = new IntlMoneyFormatter($numberFormatter, new ISOCurrencies());

        // Format the money
        $formattedAmount = $moneyFormatter->format($money);

        // Remove fractional digits if they are zero
        // Assuming cents are represented as an integer amount (e.g., 1500 = $15.00)
        if (fmod($amount / 100, 1) === 0.0) {
            $formattedAmount = preg_replace('/(\.\d+)?(?=\D|$)/', '', $formattedAmount);
        }

        return $formattedAmount;
    }


    /**
     * Get List Of Products
     */
    public static function products(): Collection
    {
        $response = static::api('get', 'products');
        $items = $response->json('items');
        return collect($items)->map(function (array $item) {
            return new Product($item);
        });
    }

    /**
     * Get Product Details
     */
    public static function productPrice(string $id): Product
    {
        $response = static::api('get', "products/$id",);
        return new Product($response->json());
    }


}
