<?php

// config for Einenlum/CashierPaddleWebhooks
return [
    // Key used to store the Paddle webhook secret in the cache.
    'cache_key' => 'cashier-paddle-webhooks.secret',

    // Default is config('cashier.pash').'/webhook' if null
    // but you can override it here.
    'webhook_path' => null,

    /*
     * Whether to automatically register the webhook route
     * from this package.
     *
     * Check the documentation as to why it is enabled by default.
     *
     * If you disable this, you will have to take care yourself
     * of overriding the default Cashier Paddle middleware.
     *
     * @see VerifyPaddleWebhookMiddleware
     */
    'register_routes' => true,

    'subscribed_events' => [
        // Transaction events
        'transaction.billed',
        'transaction.canceled',
        'transaction.completed',
        'transaction.created',
        'transaction.paid',
        'transaction.past_due',
        'transaction.payment_failed',
        'transaction.ready',
        'transaction.updated',
        'transaction.revised',

        // Subscription events
        'subscription.activated',
        'subscription.canceled',
        'subscription.created',
        'subscription.imported',
        'subscription.past_due',
        'subscription.paused',
        'subscription.resumed',
        'subscription.trialing',
        'subscription.updated',

        // Product events
        'product.created',
        'product.imported',
        'product.updated',

        // Price events
        'price.created',
        'price.imported',
        'price.updated',

        // Customer events
        'customer.created',
        'customer.imported',
        'customer.updated',

        // Payment method events
        'payment_method.saved',
        'payment_method.deleted',

        // Address events
        'address.created',
        'address.imported',
        'address.updated',

        // Business events
        'business.created',
        'business.imported',
        'business.updated',

        // Adjustment events
        'adjustment.created',
        'adjustment.updated',

        // Payout events
        'payout.created',
        'payout.paid',

        // Discount events
        'discount.created',
        'discount.imported',
        'discount.updated',

        // Discount group events
        'discount_group.created',
        'discount_group.updated',

        // Report events
        'report.created',
        'report.updated',

        // API key events
        'api_key.created',
        'api_key.expired',
        'api_key.expiring',
        'api_key.revoked',
        'api_key.updated',

        // Client token events
        'client_token.created',
        'client_token.updated',
        'client_token.revoked',
    ],
];
