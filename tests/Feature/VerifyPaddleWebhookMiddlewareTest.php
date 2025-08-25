<?php

use Einenlum\CashierPaddleWebhooks\Facades\CashierPaddleWebhooks;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;

describe('VerifyPaddleWebhookMiddleware', function () {
    beforeEach(function () {
        config([
            'cashier.webhook_secret' => 'config-secret-123',
            'cashier-paddle-webhooks.cache_key' => 'test-cache-key',
        ]);
        
        Route::post('/test-webhook', function () {
            return response()->json(['success' => true]);
        })->middleware(\Einenlum\CashierPaddleWebhooks\Http\Middlewares\VerifyPaddleWebhookMiddleware::class);
    });

    describe('Secret retrieval logic', function () {
        it('uses config secret in production environment', function () {
            app()->detectEnvironment(fn() => 'production');
            
            // Set a different secret in cache to verify production uses config
            Cache::put('test-cache-key', 'cached-secret-456');
            
            $timestamp = time();
            $data = '{"test":"data"}';
            $signature = hash_hmac('sha256', "{$timestamp}:{$data}", 'config-secret-123');
            
            $response = $this->postJson('/test-webhook', json_decode($data, true), [
                'Paddle-Signature' => "ts={$timestamp};h1={$signature}",
            ]);
            
            $response->assertStatus(200);
        });

        it('uses cached secret in non-production environment', function () {
            app()->detectEnvironment(fn() => 'local');
            
            Cache::put('test-cache-key', 'cached-secret-456');
            
            $timestamp = time();
            $data = '{"test":"data"}';
            $signature = hash_hmac('sha256', "{$timestamp}:{$data}", 'cached-secret-456');
            
            $response = $this->postJson('/test-webhook', json_decode($data, true), [
                'Paddle-Signature' => "ts={$timestamp};h1={$signature}",
            ]);
            
            $response->assertStatus(200);
        });

        it('falls back to config when cache is empty in non-production environment', function () {
            app()->detectEnvironment(fn() => 'local');
            
            Cache::forget('test-cache-key');
            
            $timestamp = time();
            $data = '{"test":"data"}';
            $signature = hash_hmac('sha256', "{$timestamp}:{$data}", 'config-secret-123');
            
            $response = $this->postJson('/test-webhook', json_decode($data, true), [
                'Paddle-Signature' => "ts={$timestamp};h1={$signature}",
            ]);
            
            $response->assertStatus(200);
        });

        it('rejects request with wrong signature in production', function () {
            app()->detectEnvironment(fn() => 'production');
            
            Cache::put('test-cache-key', 'cached-secret-456');
            
            $timestamp = time();
            $data = '{"test":"data"}';
            $signature = hash_hmac('sha256', "{$timestamp}:{$data}", 'wrong-secret');
            
            $response = $this->postJson('/test-webhook', json_decode($data, true), [
                'Paddle-Signature' => "ts={$timestamp};h1={$signature}",
            ]);
            
            $response->assertStatus(403);
        });

        it('rejects request with wrong signature in non-production when using cache', function () {
            app()->detectEnvironment(fn() => 'local');
            
            Cache::put('test-cache-key', 'cached-secret-456');
            
            $timestamp = time();
            $data = '{"test":"data"}';
            $signature = hash_hmac('sha256', "{$timestamp}:{$data}", 'wrong-secret');
            
            $response = $this->postJson('/test-webhook', json_decode($data, true), [
                'Paddle-Signature' => "ts={$timestamp};h1={$signature}",
            ]);
            
            $response->assertStatus(403);
        });
    });
});