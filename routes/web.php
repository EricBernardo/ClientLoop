<?php

use App\Http\Controllers\AppointmentConfirmationController;
use App\Http\Controllers\ImportTemplateController;
use App\Http\Controllers\PublicBookingController;
use App\Http\Controllers\RegistrationController;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::get('/agendar/{token}', [PublicBookingController::class, 'show'])->name('booking.show');
Route::post('/agendar/{token}', [PublicBookingController::class, 'store'])->name('booking.store');
Route::get('/confirmar/{token}', [AppointmentConfirmationController::class, 'show'])->name('appointment.confirm.show');
Route::post('/confirmar/{token}', [AppointmentConfirmationController::class, 'confirm'])->name('appointment.confirm.submit');
Route::post('/confirmar/{token}/cancelar', [AppointmentConfirmationController::class, 'cancel'])->name('appointment.confirm.cancel');

Route::get('/register', [RegistrationController::class, 'create'])->name('register');
Route::post('/register', [RegistrationController::class, 'store'])->name('register.store');
Route::redirect('/cadastro', '/register');
Route::redirect('/app', '/admin');
Route::redirect('/super', '/platform');
Route::get('/admin/imports/template/{type}', ImportTemplateController::class)->middleware(['auth', 'verified.when_required'])->name('imports.template');
Route::get('/admin/importacoes/modelo/{type}', fn (string $type) => redirect()->route('imports.template', ['type' => $type]))->middleware(['auth', 'verified.when_required']);
Route::get('/verify-email', fn () => view('auth.verify'))->middleware('auth')->name('verification.notice');
Route::get('/email/verify/{id}/{hash}', function (EmailVerificationRequest $request) {
    $request->fulfill();

    return redirect('/admin');
})->middleware(['auth', 'signed'])->name('verification.verify');
Route::post('/email/verification-notification', function (Request $request) {
    $request->user()->sendEmailVerificationNotification();

    return back()->with('status', 'verification-link-sent');
})->middleware(['auth', 'throttle:6,1'])->name('verification.send');
