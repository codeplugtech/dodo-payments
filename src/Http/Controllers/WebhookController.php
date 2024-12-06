<?php

namespace Codeplugtech\DodoPayments\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Codeplugtech\DodoPayments\DodoPayments;
use Codeplugtech\DodoPayments\Events\PaymentSucceeded;
use Codeplugtech\DodoPayments\Events\SubscriptionActive;
use Codeplugtech\DodoPayments\Events\SubscriptionFailed;
use Codeplugtech\DodoPayments\Events\SubscriptionOnHold;
use Codeplugtech\DodoPayments\Http\Middleware\VerifyWebhookSignature as DodoPaymentsWebhookSignature;
use Illuminate\Support\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class WebhookController extends Controller
{
    /**
     * Create a new WebhookController instance.
     *
     * @return void
     */
    public function __construct()
    {
        if (config('dodo.webhook_secret')) {
             $this->middleware(DodoPaymentsWebhookSignature::class);
        }
    }

    /**
     * Handle a Paddle webhook call.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function __invoke(Request $request)
    {
        $payload = $request->all();

        $method = 'handle' . Str::studly(Str::replace('.', ' ', $payload['type']));
        Log::info(json_encode($payload));
        if (method_exists($this, $method)) {
            $this->{$method}($payload);

            return new Response('Webhook Handled');
        }

        return new Response();
    }

    /**
     * Handle transaction completed.
     *
     * @param array $payload
     * @return void
     */
    protected function handlePaymentSucceeded(array $payload): void
    {
        $data = $payload['data'];

        //ignore Mandate Transactions
        if ($data['subscription_id'] && (int)$data['total_amount'] === 0) {
            return;
        }

        if (!$subscription = $this->findSubscription($data['subscription_id'])) {
            return;
        }
        $billable = User::whereEmail($data['customer']['email'])->first();

        $transaction = $billable->transactions()->create([
            'payment_id' => $data['payment_id'],
            'subscription_id' => $data['subscription_id'],
            'status' => $data['status'],
            'total' => $data['total_amount'],
            'tax' => $data['tax'] ?? 0,
            'currency' => $data['currency'],
            'billed_at' => Carbon::parse($data['created_at'], 'UTC'),
        ]);
        $response = DodoPayments::api('get', "subscriptions/$subscription->subscription_id");
        if ($response->successful()) {
            Log::info('GetSubscription:' . $response->json('next_billing_date'));
            $subscription->update([
                'next_billing_at' => Carbon::parse($response->json('next_billing_date'), 'UTC')
            ]);
        }
        PaymentSucceeded::dispatch($billable, $transaction, $payload);
    }

    /**
     * Handle Subscription Active.
     *
     * @param array $payload
     * @return void
     */
    protected function handleSubscriptionActive(array $payload): void
    {
        $data = $payload['data'];
        if (!$subscription = $this->findSubscription($data['subscription_id'])) {
            return;
        }
        $subscription->update([
            'status' => $data['status'],
        ]);
        $billable = User::whereEmail($data['customer']['email'])->first();
        SubscriptionActive::dispatch($billable, $subscription, $payload);
    }


    /**
     * Handle Subscription Failed.
     *
     * @param array $payload
     * @return void
     */
    protected function handleSubscriptionFailed(array $payload): void
    {
        $data = $payload['data'];
        if (!$subscription = $this->findSubscription($data['subscription_id'])) {
            return;
        }
        $subscription->update([
            'status' => $data['status'],
        ]);
        $billable = User::whereEmail($data['customer']['email'])->first();
        SubscriptionFailed::dispatch($billable,$subscription, $payload);
    }

    /**
     * Handle Subscription Failed.
     *
     * @param array $payload
     * @return void
     */
    protected function handleSubscriptionOnHold(array $payload): void
    {
        $data = $payload['data'];
        if (!$subscription = $this->findSubscription($data['subscription_id'])) {
            return;
        }
        $subscription->update([
            'status' => $data['status'],
        ]);
        $billable = User::whereEmail($data['customer']['email'])->first();
        SubscriptionOnHold::dispatch($billable,$subscription, $payload);
    }

    /**
     * Handle Subscription Failed.
     *
     * @param array $payload
     * @return void
     */
    protected function handleSubscriptionPaused(array $payload): void
    {
        $data = $payload['data'];
        if (!$subscription = $this->findSubscription($data['subscription_id'])) {
            return;
        }
        $subscription->update([
            'status' => $data['status'],
            'paused_at' => Carbon::parse($data['created_at'], 'UTC')
        ]);
        $billable = User::whereEmail($data['customer']['email'])->first();
        SubscriptionOnHold::dispatch($billable,$subscription, $payload);
    }

    /**
     * Find the first subscription matching a Dodo subscription ID.
     *
     * @param string $subscriptionId
     * @return
     */
    protected function findSubscription(string $subscriptionId)
    {
        return DodoPayments::$subscriptionModel::firstWhere('subscription_id', $subscriptionId);
    }


}
