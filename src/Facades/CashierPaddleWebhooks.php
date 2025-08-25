<?php

namespace Einenlum\CashierPaddleWebhooks\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Einenlum\CashierPaddleWebhooks\CashierPaddleWebhooks
 */
class CashierPaddleWebhooks extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Einenlum\CashierPaddleWebhooks\CashierPaddleWebhooks::class;
    }
}
