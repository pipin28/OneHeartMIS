<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\PercentageController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/login');

Route::view('/login', 'auth.login')->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.submit');

Route::middleware(['auth'])->group(function () {
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
Route::get('/activity-ping', function () {
    return response()->noContent();
})->name('activity.ping');

Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
Route::get('/profile', [ProfileController::class, 'show'])->name('profile');

Route::middleware(['role:admin,encoder'])->group(function () {
    Route::get('/add-members/staff', [MemberController::class, 'showStaff'])->name('add-members.staff');
    Route::post('/add-members/staff', [MemberController::class, 'storeStaff'])->name('add-members.staff.store');
    Route::get('/add-members', [MemberController::class, 'create'])->name('add-members');
    Route::post('/add-members', [MemberController::class, 'storePart1'])->name('add-members.store');
    Route::get('/add-members/{part1}/edit', [MemberController::class, 'editPart1'])->name('add-members.edit');
    Route::get('/add-members/part2/{part1}', [MemberController::class, 'showPart2'])->name('add-members.part2');
    Route::post('/add-members/part2/{part1}', [MemberController::class, 'storePart2'])->name('add-members.part2.store');
    Route::get('/add-members/part2/{part1}/{part2}/address', [MemberController::class, 'showAddress'])->name('add-members.part2.address');
    Route::post('/add-members/part2/{part1}/{part2}/address', [MemberController::class, 'storeAddress'])->name('add-members.part2.address.store');
    Route::get('/add-members/part2/{part1}/{part2}/beneficiaries', [MemberController::class, 'showBeneficiaries'])->name('add-members.part2.beneficiaries');
    Route::post('/add-members/part2/{part1}/{part2}/beneficiaries', [MemberController::class, 'storeBeneficiaries'])->name('add-members.part2.beneficiaries.store');
    Route::get('/add-members/draft/staff', [MemberController::class, 'draftStaff'])->name('add-members.draft.staff');
    Route::get('/add-members/draft/enrollment', [MemberController::class, 'draftEnrollment'])->name('add-members.draft.enrollment');
    Route::get('/add-members/draft/details', [MemberController::class, 'draftPart2'])->name('add-members.draft.part2');
    Route::get('/add-members/draft/address', [MemberController::class, 'draftAddress'])->name('add-members.draft.address');
    Route::get('/add-members/draft/beneficiaries', [MemberController::class, 'draftBeneficiaries'])->name('add-members.draft.beneficiaries');
    Route::post('/add-members/draft/submit', [MemberController::class, 'storeDraft'])->name('add-members.draft.submit');
});

Route::get('/show-members', [MemberController::class, 'index'])->name('show-members');
Route::post('/members/{part2}/update', [MemberController::class, 'update'])->middleware('role:admin')->name('members.update');
Route::delete('/members/{part2}', [MemberController::class, 'destroy'])->middleware('role:admin')->name('members.destroy');

Route::view('/customer', 'customer')->name('customer');
Route::view('/supplier', 'supplier')->name('supplier');
Route::view('/purchases', 'purchases')->name('purchases');
Route::get('/payment', [PaymentController::class, 'index'])->name('payment');
Route::post('/payments/{payment}/pay', [PaymentController::class, 'pay'])->name('payments.pay');
Route::middleware(['role:admin,manager'])->group(function () {
    Route::get('/settings', [PercentageController::class, 'index'])->name('settings');
    Route::post('/settings', [PercentageController::class, 'update'])->name('settings.update');
    Route::post('/settings/plan', [PercentageController::class, 'storePlan'])->name('settings.plan.store');
    Route::post('/settings/plan/delete', [PercentageController::class, 'deletePlan'])->name('settings.plan.delete');
    Route::post('/settings/plan/update', [PercentageController::class, 'updatePlan'])->name('settings.plan.update');
    Route::post('/settings/percentages', [PercentageController::class, 'updatePercentages'])->name('settings.percentages.update');
    Route::post('/settings/insurance', [PercentageController::class, 'updateInsurancePartners'])->name('settings.insurance.update');
});
Route::middleware(['role:manager'])->group(function () {
    Route::view('/register', 'auth.register')->name('register');
    Route::post('/register', [AuthController::class, 'register'])->name('register.submit');
    Route::get('/report', [ReportController::class, 'index'])->name('report');
    Route::get('/report/export', [ReportController::class, 'exportCsv'])->name('report.export');
});
Route::view('/sales', 'sales')->name('sales');
Route::get('/users', [\App\Http\Controllers\UsersController::class, 'index'])->name('users');
Route::post('/users/{user}/update', [\App\Http\Controllers\UsersController::class, 'update'])->name('users.update');
Route::post('/users/{user}/delete', [\App\Http\Controllers\UsersController::class, 'destroy'])->name('users.delete');
});
