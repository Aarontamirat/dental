<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AppearanceController;
use App\Http\Controllers\AppointmentAdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ContentController;
use App\Http\Controllers\MediaController;
use App\Http\Controllers\MessageAdminController;
use App\Http\Controllers\PatientController;
use App\Http\Controllers\PublicController;
use App\Http\Controllers\SeoController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PublicController::class, 'home'])->name('home');
Route::get('/about', [PublicController::class, 'about']);
foreach (['services' => 'service', 'doctors' => 'doctor', 'articles' => 'article', 'branches' => 'branch'] as $plural => $singular) {
    Route::get('/'.$plural, [PublicController::class, $plural]);
    Route::get('/'.$plural.'/{slug}', [PublicController::class, $singular]);
}
Route::get('/gallery', [PublicController::class, 'gallery']);
Route::get('/testimonials', [PublicController::class, 'testimonials']);
Route::get('/faq', [PublicController::class, 'faq']);
Route::get('/appointments', [PublicController::class, 'appointments']);
Route::post('/appointments', [PatientController::class, 'appointment'])->middleware('throttle:patient');
Route::get('/contact', [PublicController::class, 'contact']);
Route::post('/contact', [PatientController::class, 'contact'])->middleware('throttle:patient');
Route::get('/pages/{slug}', [PublicController::class, 'page']);
Route::get('/media/{media}', [MediaController::class, 'serve'])->whereNumber('media');
Route::get('/sitemap.xml', [SeoController::class, 'sitemap']);
Route::get('/robots.txt', [SeoController::class, 'robots']);

Route::middleware('guest')->group(function () {
    Route::view('/login', 'auth.login')->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login');
    Route::view('/forgot-password', 'auth.forgot')->name('password.request');
    Route::post('/forgot-password', [AuthController::class, 'forgot'])->middleware('throttle:password-reset')->name('password.email');
    Route::get('/reset-password/{token}', fn ($token) => view('auth.reset', ['token' => $token]))->name('password.reset');
    Route::post('/reset-password', [AuthController::class, 'reset'])->middleware('throttle:password-reset')->name('password.update');
});
Route::middleware(['auth', 'active'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::view('/admin', 'admin');
    Route::prefix('admin/api')->group(function () {
        Route::get('/bootstrap', [AdminController::class, 'bootstrap']);
        Route::get('/dashboard', [AdminController::class, 'dashboard']);
        Route::get('/settings', [AdminController::class, 'settings']);
        Route::put('/settings', [AdminController::class, 'updateSettings']);
        Route::get('/appearance', [AppearanceController::class, 'show']);
        Route::post('/appearance/preview', [AppearanceController::class, 'preview'])->block(10,10);
        Route::put('/appearance', [AppearanceController::class, 'publish']);
        Route::get('/sections', [AdminController::class, 'sections']);
        Route::put('/sections', [AdminController::class, 'updateSections']);
        Route::get('/activities', [AdminController::class, 'activities']);
        Route::get('/appointments/export', [AppointmentAdminController::class, 'export']);
        Route::get('/appointments', [AppointmentAdminController::class, 'index']);
        Route::get('/appointments/{appointment}', [AppointmentAdminController::class, 'show']);
        Route::put('/appointments/{appointment}', [AppointmentAdminController::class, 'update']);
        Route::get('/messages', [MessageAdminController::class, 'index']);
        Route::get('/messages/{message}', [MessageAdminController::class, 'show']);
        Route::put('/messages/{message}', [MessageAdminController::class, 'update']);
        Route::get('/users', [UserController::class, 'index']);
        Route::post('/users', [UserController::class, 'save']);
        Route::put('/users/{id}', [UserController::class, 'save'])->whereNumber('id');
        Route::get('/media', [MediaController::class, 'index']);
        Route::post('/media', [MediaController::class, 'save']);
        Route::post('/media/{media}', [MediaController::class, 'save']);
        Route::delete('/media/{media}', [MediaController::class, 'destroy']);
        Route::get('/content/{module}', [ContentController::class, 'index']);
        Route::post('/content/{module}', [ContentController::class, 'save']);
        Route::get('/content/{module}/{id}', [ContentController::class, 'show'])->whereNumber('id');
        Route::put('/content/{module}/{id}',[ContentController::class, 'save'])->whereNumber('id');
        Route::delete('/content/{module}/{id}',[ContentController::class, 'destroy'])->whereNumber('id');
    });
});
