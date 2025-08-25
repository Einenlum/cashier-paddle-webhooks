<?php

use Einenlum\CashierPaddleWebhooks\Http\Controllers\CashierPaddleWebhooksController;
use Illuminate\Support\Facades\Route;

Route::post('webhook', CashierPaddleWebhooksController::class)->name('webhook');
