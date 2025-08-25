<?php

namespace Einenlum\CashierPaddleWebhooks;

use Einenlum\CashierPaddleWebhooks\Commands\CashierPaddleWebhooksCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

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
            ->hasCommand(CashierPaddleWebhooksCommand::class);
    }
}
