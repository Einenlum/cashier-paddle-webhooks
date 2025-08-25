<?php

declare(strict_types=1);

namespace Einenlum\CashierPaddleWebhooks\Http\Middlewares;

use Einenlum\CashierPaddleWebhooks\Facades\CashierPaddleWebhooks;
use Illuminate\Http\Request;
use Laravel\Paddle\Http\Middleware\VerifyWebhookSignature;

class VerifyPaddleWebhookMiddleware extends VerifyWebhookSignature
{
    protected function isInvalidSignature(Request $request, $signature)
    {
        if (empty($signature)) {
            return true;
        }

        [$timestamp, $hashes] = $this->parseSignature($signature);

        if ($this->maximumVariance > 0 && time() > $timestamp + $this->maximumVariance) {
            return true;
        }

        // This is the only thing we change from the original method
        $secret = $this->getWebhookSecret();

        $data = $request->getContent();

        foreach ($hashes as $hashAlgorithm => $possibleHashes) {
            /** @phpstan-ignore-next-line */
            $hash = match ($hashAlgorithm) {
                'h1' => hash_hmac('sha256', "{$timestamp}:{$data}", $secret),
            };

            foreach ($possibleHashes as $possibleHash) {
                if (hash_equals($hash, $possibleHash)) {
                    return false;
                }
            }
        }

        return true;
    }

    private function getWebhookSecret(): string
    {
        // If we are in production, always use the config value
        if (app()->environment('production')) {
            return config('cashier.webhook_secret');
        }

        // In other environments, try to get it from the cache first
        $secret = CashierPaddleWebhooks::getSecret();

        return $secret ?? config('cashier.webhook_secret');
    }
}
