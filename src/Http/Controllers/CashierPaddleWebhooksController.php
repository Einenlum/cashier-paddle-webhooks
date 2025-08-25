<?php

declare(strict_types=1);

namespace Einenlum\CashierPaddleWebhooks\Http\Controllers;

use Einenlum\CashierPaddleWebhooks\Http\Middlewares\VerifyPaddleWebhookMiddleware;
use Laravel\Paddle\Http\Controllers\WebhookController;

class CashierPaddleWebhooksController extends WebhookController
{
    public function __construct()
    {
        // Here the only change is that we use our own middleware
        // which extends the original one
        if (config('cashier.webhook_secret')) {
            $this->middleware(VerifyPaddleWebhookMiddleware::class);
        }
    }
}
