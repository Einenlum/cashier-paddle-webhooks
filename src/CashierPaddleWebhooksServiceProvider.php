<?php

namespace Einenlum\CashierPaddleWebhooks;

use Einenlum\CashierPaddleWebhooks\Commands\ListenPaddleWebhooksCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class CashierPaddleWebhooksServiceProvider extends PackageServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/cashier-paddle-webhooks.php', 'cashier-paddle-webhooks'
        );
    }

    public function boot()
    {
        $this->configureRoutes();
        $this->bootPublishing();
    }

    public function configureRoutes(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
    }

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
            ->hasCommand(ListenPaddleWebhooksCommand::class);
    }

    /**
     * Boot the package's publishable resources.
     *
     * @return void
     */
    protected function bootPublishing()
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/cashier-paddle-webhooks.php' => $this->app->configPath('cashier-paddle-webhooks.php'),
            ], 'cashier-paddle-webhooks-config');
        }
    }
}
