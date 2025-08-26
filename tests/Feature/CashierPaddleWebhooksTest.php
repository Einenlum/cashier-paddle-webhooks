<?php

use Einenlum\CashierPaddleWebhooks\CashierPaddleWebhooks;
use Einenlum\CashierPaddleWebhooks\DTOs\PaddleWebhook;
use Einenlum\CashierPaddleWebhooks\Exceptions\CashierPaddleWebhooksException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

describe('CashierPaddleWebhooks', function () {
    beforeEach(function () {
        $this->service = app(CashierPaddleWebhooks::class);
    });

    describe('Cache operations', function () {
        it('stores secret correctly', function () {
            $secret = 'test_secret_key_123';

            Cache::expects('put')
                ->once()
                ->with('cashier-paddle-webhooks.secret', $secret);

            $this->service->setSecret($secret);
        });

        it('retrieves secret from cache', function () {
            $expectedSecret = 'test_secret_key_123';

            Cache::expects('get')
                ->once()
                ->with('cashier-paddle-webhooks.secret')
                ->andReturn($expectedSecret);

            $result = $this->service->getSecret();

            expect($result)->toBe($expectedSecret);
        });

        it('removes secret from cache', function () {
            Cache::expects('forget')
                ->once()
                ->with('cashier-paddle-webhooks.secret');

            $this->service->forgetSecret();
        });

        it('returns null when no secret exists', function () {
            Cache::expects('get')
                ->once()
                ->with('cashier-paddle-webhooks.secret')
                ->andReturn(null);

            $result = $this->service->getSecret();

            expect($result)->toBeNull();
        });
    });

    describe('Webhook API operations', function () {
        it('creates webhook with correct payload', function () {
            $tunnel = 'https://example-tunnel.com';
            $service = 'tunnelmole';
            $expectedPath = config('cashier.path').'/webhook';
            $expectedUrl = $tunnel.'/'.$expectedPath;

            $expectedData = [
                'description' => 'Laravel Paddle Listen - '.$service,
                'destination' => $expectedUrl,
                'type' => 'url',
                'subscribed_events' => config('cashier-paddle-webhooks.subscribed_events'),
            ];

            $responseData = [
                'data' => [
                    'id' => 'wh_test_123',
                    'endpoint_secret_key' => 'secret_key_123',
                ],
            ];

            Http::fake([
                'https://sandbox-api.paddle.com/notification-settings' => Http::response($responseData, 201),
            ]);

            $result = $this->service->setupWebhook($tunnel, $service);

            expect($result)->toBeInstanceOf(PaddleWebhook::class);
            expect($result->id)->toBe('wh_test_123');
            expect($result->secret)->toBe('secret_key_123');
        });

        it('handles API errors properly on setup', function () {
            $tunnel = 'https://example-tunnel.com';
            $service = 'tunnelmole';

            Http::fake([
                'https://sandbox-api.paddle.com/notification-settings' => Http::response(['error' => 'Invalid request'], 400),
            ]);

            $thrown = false;
            try {
                $this->service->setupWebhook($tunnel, $service);
            } catch (CashierPaddleWebhooksException) {
                $thrown = true;
            }

            expect($thrown)->toBeTrue();
        });

        it('calls correct API endpoint for delete', function () {
            $webhookId = 'wh_test_123';

            Http::fake([
                "sandbox-api.paddle.com/notification-settings/{$webhookId}" => Http::response([], 200),
            ]);

            $result = $this->service->deleteWebhook($webhookId);

            expect($result)->toBeInstanceOf(Response::class);
            expect($result->successful())->toBeTrue();

            Http::assertSent(function ($request) use ($webhookId) {
                return $request->url() === "https://sandbox-api.paddle.com/notification-settings/{$webhookId}" &&
                       $request->method() === 'DELETE';
            });
        });
    });

    describe('API URL generation', function () {
        it('returns correct sandbox URL', function () {
            $reflection = new \ReflectionClass($this->service);
            $method = $reflection->getMethod('getPaddleApiUrl');
            $method->setAccessible(true);

            $result = $method->invoke($this->service);

            expect($result)->toBe('https://sandbox-api.paddle.com');
        });
    });
});
