<?php

use Einenlum\CashierPaddleWebhooks\CashierPaddleWebhooks;
use Illuminate\Support\Facades\Cache;

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
});
