<?php

use Backtik\Impersonate\Http\Controllers\ImpersonateController;
use Illuminate\Support\Facades\Route;

$prefix = config('impersonate.route_prefix', 'impersonate');
$middleware = config('impersonate.route_middleware', ['web', 'auth']);
$withoutMiddleware = config('impersonate.route_without_middleware', []);

Route::middleware($middleware)->withoutMiddleware($withoutMiddleware)->prefix($prefix)->group(function () {
    Route::post('/leave', [ImpersonateController::class, 'leave'])
        ->name('impersonate.leave');

    Route::post('/{id}', [ImpersonateController::class, 'start'])
        ->name('impersonate.start')
        ->where('id', '[0-9]+');
});
