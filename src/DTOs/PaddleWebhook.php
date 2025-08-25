<?php

declare(strict_types=1);

namespace Einenlum\CashierPaddleWebhooks\DTOs;

class PaddleWebhook
{
    public function __construct(
        public string $id,
        public string $secret,
    ) {}
}
