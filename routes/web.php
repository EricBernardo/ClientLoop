<?php

use App\Http\Controllers\ImportTemplateController;
use App\Http\Controllers\RegistrationController;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::get('/register', [RegistrationController::class, 'create'])->name('register');
Route::post('/register', [RegistrationController::class, 'store'])->name('register.store');
Route::redirect('/cadastro', '/register');
Route::redirect('/app', '/admin');
Route::redirect('/super', '/platform');
Route::get('/admin/imports/template/{type}', ImportTemplateController::class)->middleware(['auth', 'verified'])->name('imports.template');
Route::get('/admin/importacoes/modelo/{type}', fn (string $type) => redirect()->route('imports.template', ['type' => $type]))->middleware(['auth', 'verified']);
Route::get('/verify-email', fn () => view('auth.verify'))->middleware('auth')->name('verification.notice');
Route::get('/email/verify/{id}/{hash}', function (EmailVerificationRequest $request) {
    $request->fulfill();

    return redirect('/admin');
})->middleware(['auth', 'signed'])->name('verification.verify');
Route::post('/email/verification-notification', function (Request $request) {
    $request->user()->sendEmailVerificationNotification();

    return back();
})->middleware(['auth', 'throttle:6,1'])->name('verification.send');
