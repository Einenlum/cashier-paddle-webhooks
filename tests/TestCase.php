<?php

namespace Einenlum\CashierPaddleWebhooks\Tests;

use Einenlum\CashierPaddleWebhooks\CashierPaddleWebhooksServiceProvider;
use Illuminate\Database\Eloquent\Factories\Factory;
use Laravel\Paddle\Cashier;
use Orchestra\Testbench\Bootstrap\HandleExceptions;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        Cashier::ignoreRoutes();
        Factory::guessFactoryNamesUsing(
            fn (string $modelName) => 'Einenlum\\CashierPaddleWebhooks\\Database\\Factories\\'.class_basename($modelName).'Factory'
        );
    }

    protected function getPackageProviders($app)
    {
        return [
            \Laravel\Paddle\CashierServiceProvider::class,
            CashierPaddleWebhooksServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app)
    {
        config()->set('database.default', 'testing');
        config()->set('cashier.sandbox', true);
        config()->set('app.env', 'local');
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        /**
         * Try to fix the following error:
         * Test code or tested code did not remove its own exception handlers
         *
         * @see https://github.com/laravel/framework/issues/49502
         */
        HandleExceptions::flushState();
    }
}
