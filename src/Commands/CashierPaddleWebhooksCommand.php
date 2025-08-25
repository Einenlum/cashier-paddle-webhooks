<?php

namespace Einenlum\CashierPaddleWebhooks\Commands;

use Illuminate\Console\Command;

class CashierPaddleWebhooksCommand extends Command
{
    public $signature = 'cashier-paddle-webhooks';

    public $description = 'My command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
