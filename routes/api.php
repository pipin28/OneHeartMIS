<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\MemberController;
use App\Http\Controllers\Api\TestController;

Route::get('/test', [TestController::class, 'index']);

Route::prefix('v1')->group(function () {
    Route::get('/members', [MemberController::class, 'index']);
    Route::post('/members', [MemberController::class, 'store']);
});
