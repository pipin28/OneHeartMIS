<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\PercentageController;
use App\Http\Controllers\PaymentController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/login');

Route::view('/login', 'auth.login')->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.submit');

Route::view('/register', 'auth.register')->name('register');
Route::post('/register', [AuthController::class, 'register'])->name('register.submit');

Route::view('/dashboard', 'dashboard')->name('dashboard');

Route::get('/add-members', [MemberController::class, 'create'])->name('add-members');
Route::post('/add-members', [MemberController::class, 'storePart1'])->name('add-members.store');
Route::get('/add-members/{part1}/edit', [MemberController::class, 'editPart1'])->name('add-members.edit');
Route::get('/add-members/part2/{part1}', [MemberController::class, 'showPart2'])->name('add-members.part2');
Route::post('/add-members/part2/{part1}', [MemberController::class, 'storePart2'])->name('add-members.part2.store');
Route::get('/add-members/part2/{part1}/{part2}/address', [MemberController::class, 'showAddress'])->name('add-members.part2.address');
Route::post('/add-members/part2/{part1}/{part2}/address', [MemberController::class, 'storeAddress'])->name('add-members.part2.address.store');
Route::get('/add-members/part2/{part1}/{part2}/beneficiaries', [MemberController::class, 'showBeneficiaries'])->name('add-members.part2.beneficiaries');
Route::post('/add-members/part2/{part1}/{part2}/beneficiaries', [MemberController::class, 'storeBeneficiaries'])->name('add-members.part2.beneficiaries.store');

Route::get('/show-members', [MemberController::class, 'index'])->name('show-members');
Route::post('/members/{part2}/update', [MemberController::class, 'update'])->name('members.update');
Route::delete('/members/{part2}', [MemberController::class, 'destroy'])->name('members.destroy');

Route::view('/customer', 'customer')->name('customer');
Route::view('/supplier', 'supplier')->name('supplier');
Route::view('/purchases', 'purchases')->name('purchases');
Route::get('/payment', [PaymentController::class, 'index'])->name('payment');
Route::post('/payments/{payment}/pay', [PaymentController::class, 'pay'])->name('payments.pay');
Route::get('/settings', [PercentageController::class, 'index'])->name('settings');
Route::post('/settings', [PercentageController::class, 'update'])->name('settings.update');
Route::post('/settings/plan', [PercentageController::class, 'storePlan'])->name('settings.plan.store');
Route::post('/settings/plan/delete', [PercentageController::class, 'deletePlan'])->name('settings.plan.delete');
Route::post('/settings/plan/update', [PercentageController::class, 'updatePlan'])->name('settings.plan.update');
Route::post('/settings/percentages', [PercentageController::class, 'updatePercentages'])->name('settings.percentages.update');
Route::view('/sales', 'sales')->name('sales');
Route::get('/users', [\App\Http\Controllers\UsersController::class, 'index'])->name('users');
Route::post('/users/{user}/update', [\App\Http\Controllers\UsersController::class, 'update'])->name('users.update');
Route::post('/users/{user}/delete', [\App\Http\Controllers\UsersController::class, 'destroy'])->name('users.delete');
Route::view('/report', 'report')->name('report');
