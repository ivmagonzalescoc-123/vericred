<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\RegistrarController;
use App\Http\Controllers\VerifyController;
use Illuminate\Support\Facades\Route;

// Public: anyone (employers, embassies, agencies) can verify.
Route::get('/', [VerifyController::class, 'home'])->name('home');
Route::post('/verify', [VerifyController::class, 'check'])->name('verify.check');
Route::get('/v', [VerifyController::class, 'link'])->name('verify.link');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'show'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
});

// Registrar portal: issue and revoke.
Route::middleware('auth')->prefix('registrar')->name('registrar.')->group(function () {
    Route::get('/', [RegistrarController::class, 'index'])->name('index');
    Route::get('/credentials/new', [RegistrarController::class, 'create'])->name('create');
    Route::post('/credentials', [RegistrarController::class, 'store'])->name('store');
    Route::get('/credentials/{credential}', [RegistrarController::class, 'show'])->name('show');
    Route::get('/credentials/{credential}/download', [RegistrarController::class, 'download'])->name('download');
    Route::post('/credentials/{credential}/revoke', [RegistrarController::class, 'revoke'])->name('revoke');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
});
