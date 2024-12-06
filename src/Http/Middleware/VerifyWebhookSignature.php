<?php

namespace Codeplugtech\DodoPayments\Http\Middleware;

use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;


/**
 * @see https://docs.dodopayments.com/features/webhooks#verifying-signatures
 */
class VerifyWebhookSignature
{

    public function handle($request, \Closure $next)
    {
        $isValid = $this->verify(
            $request->getContent(),
            $request->header('webhook-signature'),
            $request->header('webhook-id'),
            $request->header('webhook-timestamp'),
            config('dodo.webhook_secret')
        );

        if ($isValid) {
            return $next($request);
        }
        throw new AccessDeniedHttpException('Webhook signature verification failed');
    }

    /**
     * Verify the webhook signature from DodoPayments
     *
     * @param string $payload The raw webhook payload
     * @param string $webhookSignature The webhook-signature header value
     * @param string $webhookId The webhook-id header value
     * @param string $webhookTimestamp The webhook-timestamp header value
     * @param string $webhookSecret The secret key from DodoPayments Dashboard (with whsec_ prefix)
     * @return bool
     */
    public function verify(
        string $payload,
        string $webhookSignature,
        string $webhookId,
        string $webhookTimestamp,
        string $webhookSecret
    ): bool
    {
        try {
            // Remove the whsec_ prefix if present
            $cleanSecret = str_starts_with($webhookSecret, 'whsec_')
                ? substr($webhookSecret, 6)
                : $webhookSecret;

            // Decode the secret from Base64
            $decodedSecret = base64_decode($cleanSecret);

            // Extract the signature from the header (after the v1, prefix)
            $signatureParts = explode(',', $webhookSignature);
            $receivedSignature = $signatureParts[1] ?? '';

            // Construct the signed payload string
            $signedPayload = "{$webhookId}.{$webhookTimestamp}.{$payload}";

            // Compute the HMAC SHA256 signature
            $computedSignature = base64_encode(
                hash_hmac('sha256', $signedPayload, $decodedSecret, true)
            );

            // Compare the signatures
            $isValid = hash_equals($receivedSignature, $computedSignature);

            // Extensive logging for debugging
            Log::info('Webhook Signature Verification', [
                'payload_length' => strlen($payload),
                'is_valid' => $isValid,
                'signedPayload' => $signedPayload,
                'computedSignature' => $computedSignature,
                'receivedSignature' => $receivedSignature,
                'webhook_id' => $webhookId
            ]);

            return $isValid;
        } catch (\Exception $e) {
            // Log any errors during verification
            Log::error('Webhook Signature Verification Failed', [
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }


}
