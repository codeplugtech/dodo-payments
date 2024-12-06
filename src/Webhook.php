<?php

namespace Codeplugtech\DodoPayments;

class Webhook
{
    private const SECRET_PREFIX = "whsec_";
    private const TOLERANCE = 5 * 60; // 5 minutes
    private $secret;

    public function __construct($secret)
    {
        if (str_starts_with($secret, self::SECRET_PREFIX)) {
            $secret = substr($secret, strlen(self::SECRET_PREFIX));
        }
        $this->secret = base64_decode($secret);
    }

    public static function fromRaw($secret): Webhook
    {
        $obj = new self('');
        $obj->secret = base64_decode($secret); // Decode Base64 secret
        return $obj;
    }

    public function verify($payload, $headers)
    {
        if (
            isset($headers['webhook-id'])
            && isset($headers['webhook-timestamp'])
            && isset($headers['webhook-signature'])
        ) {
            $msgId = $headers['webhook-id'];
            $msgTimestamp = $headers['webhook-timestamp'];
            $msgSignature = $headers['webhook-signature'];
        } else {
            throw new \Exception("Missing required headers for webhook verification");
        }

        // Verify timestamp to ensure the request isn't too old or too new
        $timestamp = $this->verifyTimestamp($msgTimestamp);

        // Generate the expected signature
        $expectedSignature = $this->sign($msgId, $timestamp, $payload);

        // Extract versioned signature
        $passedSignatures = explode(' ', $msgSignature);
        foreach ($passedSignatures as $versionedSignature) {
            [$version, $passedSignature] = explode(',', $versionedSignature, 2);

            // Validate version and signature
            if ($version === "v1" && hash_equals($expectedSignature, $passedSignature)) {
                return json_decode($payload, true);
            }
        }

        throw new \Exception("No matching signature found");
    }

    public function sign($msgId, $timestamp, $payload): string
    {
        $timestamp = (string) $timestamp;

        if (!$this->isPositiveInteger($timestamp)) {
            throw new \Exception("Invalid timestamp");
        }

        // Concatenate data to sign
        $toSign = "{$msgId}.{$timestamp}.{$payload}";

        // Generate HMAC SHA256 hash
        $hexHash = hash_hmac('sha256', $toSign, $this->secret);

        // Encode signature to Base64
        $signature = base64_encode(pack('H*', $hexHash));

        return "v1,{$signature}";
    }

    private function verifyTimestamp($timestampHeader): int
    {
        $now = time();

        try {
            $timestamp = intval($timestampHeader, 10);
        } catch (\Exception $e) {
            throw new \Exception("Invalid Signature Headers");
        }

        // Validate the timestamp
        if ($timestamp < ($now - self::TOLERANCE)) {
            throw new \Exception("Message timestamp too old");
        }

        if ($timestamp > ($now + self::TOLERANCE)) {
            throw new \Exception("Message timestamp too new");
        }

        return $timestamp;
    }

    private function isPositiveInteger($value): bool
    {
        return is_numeric($value) && (int)$value == $value && (int)$value > 0;
    }
}
