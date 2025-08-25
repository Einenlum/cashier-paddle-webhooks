<?php

namespace Einenlum\CashierPaddleWebhooks;

use Einenlum\CashierPaddleWebhooks\DTOs\PaddleWebhook;
use Einenlum\CashierPaddleWebhooks\Exceptions\CashierPaddleWebhooksException;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class CashierPaddleWebhooks
{
    public function __construct(private Cache $cache) {}

    public function setSecret(string $secret): void
    {
        $this->cache->put(config('cashier-paddle-webhooks.cache_key'), $secret);
    }

    public function getSecret(): ?string
    {
        return $this->cache->get(config('cashier-paddle-webhooks.cache_key'));
    }

    public function forgetSecret(): void
    {
        $this->cache->forget(config('cashier-paddle-webhooks.cache_key'));
    }

    public function fetchWebhooks(): array
    {
        $fetch = true;
        $page = 1;
        $webhooks = [];

        while ($fetch) {
            $result = Http::withToken(config('cashier.api_key'))
                ->withHeaders([
                    'Accept' => 'application/json',
                ])
                ->retry(3, 250)
                ->get($this->getPaddleApiUrl().'/notification-settings', [
                    'per_page' => 50,
                    'page' => $page,
                ])
                ->json();

            $page++;

            $meta = $result['meta'] ?? [];
            $pagination = $meta['pagination'] ?? [];

            if (isset($pagination['has_more']) && ! $pagination['has_more']) {
                $fetch = false;
            }

            foreach ($result['data'] ?? [] as $webhook) {
                $webhooks[] = $webhook;
            }
        }

        return $webhooks;
    }

    public function setupWebhook(string $tunnel, string $service): PaddleWebhook
    {
        $path = config('cashier-paddle-webhooks.webhook_path') ?: config('cashier.path').'/webhook';
        $path = ltrim($path, '/');

        $webhookUrl = $tunnel.'/'.$path;

        $data = [
            'description' => 'Laravel Paddle Listen - '.$service,
            'destination' => $webhookUrl,
            'type' => 'url',
            'subscribed_events' => config('cashier-paddle-webhooks.subscribed_events'),
        ];

        $result = Http::withToken(config('cashier.api_key'))
            ->withHeaders([
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ])
            ->retry(3, 250)
            ->post($this->getPaddleApiUrl().'/notification-settings', $data);

        if (! $result->successful()) {
            throw new CashierPaddleWebhooksException('❌ Failed to set up webhook: '.$result->body());
        }

        $responseData = $result->json();

        $webhookId = $responseData['data']['id'];
        $webhookSecret = $responseData['data']['endpoint_secret_key'];

        return new PaddleWebhook($webhookId, $webhookSecret);
    }

    public function deleteWebhook(string $webhookId): Response
    {
        return Http::withToken(config('cashier.api_key'))
            ->withHeaders([
                'Accept' => 'application/json',
            ])
            ->retry(3, 250)
            ->delete($this->getPaddleApiUrl()."/notification-settings/{$webhookId}");
    }

    protected function getPaddleApiUrl(): string
    {
        return 'https://sandbox-api.paddle.com';
    }
}
