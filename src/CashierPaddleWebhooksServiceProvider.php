<?php

namespace Einenlum\CashierPaddleWebhooks;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Einenlum\CashierPaddleWebhooks\Commands\CashierPaddleWebhooksCommand;

class CashierPaddleWebhooksServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('cashier-paddle-webhooks')
            ->hasConfigFile()
            ->hasViews()
            ->hasMigration('create_cashier_paddle_webhooks_table')
            ->hasCommand(CashierPaddleWebhooksCommand::class);
    }
}
