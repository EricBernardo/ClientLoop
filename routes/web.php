<?php

use App\Http\Controllers\AppointmentConfirmationController;
use App\Http\Controllers\ImportTemplateController;
use App\Http\Controllers\PublicBookingController;
use App\Http\Controllers\RegistrationController;
use App\Support\FirstVisitGuide;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::get('/book/{token}', [PublicBookingController::class, 'show'])->name('booking.show');
Route::post('/book/{token}', [PublicBookingController::class, 'store'])->name('booking.store');
Route::get('/confirm/{token}', [AppointmentConfirmationController::class, 'show'])->name('appointment.confirm.show');
Route::post('/confirm/{token}', [AppointmentConfirmationController::class, 'confirm'])->name('appointment.confirm.submit');
Route::post('/confirm/{token}/cancel', [AppointmentConfirmationController::class, 'cancel'])->name('appointment.confirm.cancel');

Route::get('/register', [RegistrationController::class, 'create'])->name('register');
Route::post('/register', [RegistrationController::class, 'store'])->name('register.store');
Route::redirect('/app', '/admin');
Route::redirect('/super', '/platform');
Route::get('/admin/imports/template/{type}', ImportTemplateController::class)->middleware(['auth', 'verified.when_required'])->name('imports.template');
Route::get('/verify-email', fn () => view('auth.verify'))->middleware('auth')->name('verification.notice');
Route::get('/email/verify/{id}/{hash}', function (EmailVerificationRequest $request) {
    $request->fulfill();

    return redirect()->to(FirstVisitGuide::url($request->user()->company));
})->middleware(['auth', 'signed'])->name('verification.verify');
Route::post('/email/verification-notification', function (Request $request) {
    $request->user()->sendEmailVerificationNotification();

    return back()->with('status', 'verification-link-sent');
})->middleware(['auth', 'throttle:6,1'])->name('verification.send');
