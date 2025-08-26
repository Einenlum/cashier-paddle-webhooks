<?php

use Illuminate\Validation\ValidationException;

describe('ListenPaddleWebhooksCommand', function () {
    describe('Argument validation', function () {
        it('validates required PADDLE_API_KEY', function () {
            config(['cashier.api_key' => null]);
            config(['cashier.sandbox' => true]);

            expect(function () {
                $this->artisan('cashier-paddle-webhooks:listen', ['service' => 'tunnelmole']);
            })->toThrow(ValidationException::class);
        });

        it('validates service parameter must be valid option', function () {
            config(['cashier.api_key' => 'test_api_key']);
            config(['cashier.sandbox' => true]);

            expect(function () {
                $this->artisan('cashier-paddle-webhooks:listen', ['service' => 'invalid']);
            })->toThrow(ValidationException::class);
        });

        it('provides proper error message for missing PADDLE_API_KEY', function () {
            config(['cashier.api_key' => null]);
            config(['cashier.sandbox' => true]);

            try {
                $this->artisan('cashier-paddle-webhooks:listen', ['service' => 'tunnelmole']);
                $this->fail('Expected ValidationException was not thrown');
            } catch (ValidationException $e) {
                expect($e->getMessage())->toContain('The PADDLE_API_KEY environment variable is required.');
            }
        });

        it('prevents running in production mode', function () {
            config(['cashier.api_key' => 'test_api_key']);
            config(['cashier.sandbox' => false]);

            expect(function () {
                $this->artisan('cashier-paddle-webhooks:listen', ['service' => 'tunnelmole']);
            })->toThrow(Exception::class, 'You cannot use cashier-paddle-webhooks:listen in production mode.');
        });
    });
});
