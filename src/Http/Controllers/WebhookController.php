<?php

namespace Codeplugtech\DodoPayments\Http\Controllers;

use Codeplugtech\DodoPayments\Enum\SubscriptionStatusEnum;
use Codeplugtech\DodoPayments\Events\SubscriptionPlanChanged;
use Codeplugtech\DodoPayments\Events\SubscriptionRenewed;
use Codeplugtech\DodoPayments\Subscription;
use Illuminate\Routing\Controller;
use App\Models\User;
use Codeplugtech\DodoPayments\DodoPayments;
use Codeplugtech\DodoPayments\Events\PaymentSucceeded;
use Codeplugtech\DodoPayments\Events\SubscriptionActive;
use Codeplugtech\DodoPayments\Events\SubscriptionFailed;
use Codeplugtech\DodoPayments\Events\SubscriptionOnHold;
use Codeplugtech\DodoPayments\Http\Middleware\VerifyWebhookSignature as DodoPaymentsWebhookSignature;
use Illuminate\Support\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
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
        $billable = User::whereEmail($data['customer']['email'])->first();

        SubscriptionActive::dispatch($billable, $subscription, $payload);
    }

    /**
     * Handle Subscription Renewed.
     *
     * @param array $payload
     * @return void
     */
    protected function handleSubscriptionRenewed(array $payload): void
    {
        $data = $payload['data'];
        if (!$subscription = $this->findSubscription($data['subscription_id'])) {
            return;
        }
        $subscription->update([
            'status' => $data['status'],
            'next_billing_at' => Carbon::parse(Carbon::parse($data['next_billing_date'], 'UTC'))
        ]);
        $billable = User::whereEmail($data['customer']['email'])->first();

        SubscriptionRenewed::dispatch($billable, $subscription, $payload);
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
        SubscriptionFailed::dispatch($billable, $subscription, $payload);
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
        SubscriptionOnHold::dispatch($billable, $subscription, $payload);
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
        SubscriptionOnHold::dispatch($billable, $subscription, $payload);
    }

    protected function handleSubscriptionPlanChanged(array $payload): void
    {
        $data = $payload['data'];
        if (!$subscription = $this->findSubscription($data['subscription_id'])) {
            return;
        }
        $subscription->update([
            'status' => $data['status'],
            'next_billing_at' => Carbon::parse($data['next_billing_date'], 'UTC'),
            'product_id' => $data['product_id'],
        ]);
        $billable = User::whereEmail($data['customer']['email'])->first();
        SubscriptionPlanChanged::dispatch($billable, $subscription, $payload);
    }

    /**
     * Find the first subscription matching a Dodo subscription ID.
     *
     * @param string $subscriptionId
     * @return Subscription
     */
    protected function findSubscription(string $subscriptionId): Subscription
    {

        return DB::transaction(function () use ($subscriptionId) {
            $model = (DodoPayments::$subscriptionModel)::where('subscription_id', $subscriptionId)
                ->lockForUpdate()
                ->first();

            Log::info('Model ' . $model);
            if ($model) {
                return $model;
            }

            if (!Config::get('dodo.overlay_checkout')) {
                throw new \RuntimeException("Overlay checkout is disabled, and subscription {$subscriptionId} not found locally.");
            }
            $response = DodoPayments::api('GET', "subscriptions/{$subscriptionId}");
            if (!$response->successful()) {
                throw new \RuntimeException("Failed to fetch subscription {$subscriptionId} from Dodo.");
            }

            $data = $response->collect()->toArray();

            if (is_null($data['metadata']) || is_null($data['metadata']['type'])) {
                throw new \RuntimeException("There is no type in metadata for subscription {$subscriptionId}");
            }

            $user = $this->findCustomer($data['customer']['email']);

            $previous = $user->subscriptions()
                ->where('status', SubscriptionStatusEnum::ACTIVE->value)
                ->lockForUpdate()
                ->latest()
                ->first();

            if ($previous) {
                $previous->status = SubscriptionStatusEnum::CANCELLED->value;
                $previous->ends_at = now();
                $previous->save();
            }

            $existingSubscription = (DodoPayments::$subscriptionModel)::where('subscription_id', $subscriptionId)
                ->lockForUpdate()
                ->first();

            if ($existingSubscription) {
                return $existingSubscription;
            }



            return $user->subscriptions()->create([
                'type' => $data['metadata']['type'],
                'subscription_id' => $subscriptionId,
                'product_id' => $data['product_id'],
                'status' => $data['status'],
                'next_billing_at' => $data['next_billing_at'] ?? null
            ]);
        }, 3);

    }

    protected function findCustomer(string $email): User
    {
        return User::whereEmail($email)->firstOrFail();
    }


}
