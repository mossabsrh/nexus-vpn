<?php

use App\Http\Controllers\Api\AccountController;
use App\Http\Controllers\Api\AdminBillingController;
use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ContactController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\PricingController;
use App\Http\Controllers\Api\TeamController;
use App\Http\Controllers\Api\TestimonialController;
use Illuminate\Support\Facades\Route;

Route::get('pricing', [PricingController::class, 'index'])->middleware('throttle:api');
Route::get('testimonials', [TestimonialController::class, 'index'])->middleware('throttle:api');

Route::post('webhooks/chargily', [PaymentController::class, 'webhook'])->middleware('throttle:webhooks');

/*
| Session (cookie) authenticated routes. All state-changing requests here are CSRF-protected.
*/
Route::middleware(['web', 'throttle:api'])->group(function () {
    Route::get('csrf-cookie', fn () => response()->noContent());

    Route::post('contact', [ContactController::class, 'store'])->middleware('throttle:contact');

    Route::post('auth/register', [AuthController::class, 'register'])->middleware('throttle:register');
    Route::post('auth/login', [AuthController::class, 'login'])->middleware('throttle:login');
    Route::post('auth/logout', [AuthController::class, 'logout'])->middleware('auth');
    Route::post('auth/forgot-password', [AuthController::class, 'forgotPassword'])->middleware('throttle:password-reset');
    Route::post('auth/reset-password', [AuthController::class, 'resetPassword'])->middleware('throttle:password-reset');
    Route::get('email/verify/{id}/{hash}', [AuthController::class, 'verifyEmail'])
        ->middleware(['signed:relative', 'throttle:verification'])
        ->name('api.verification.verify');

    Route::middleware('auth')->group(function () {
        Route::get('me', [AuthController::class, 'me']);
        Route::post('email/verification-notification', [AuthController::class, 'resendVerification'])->middleware('throttle:verification');
        Route::put('account/email', [AuthController::class, 'updateEmail'])->middleware('throttle:account');
        Route::put('account/password', [AuthController::class, 'updatePassword'])->middleware('throttle:account');
        Route::delete('account', [AuthController::class, 'deleteAccount'])->middleware('throttle:account');

        Route::get('account/subscriptions', [AccountController::class, 'subscriptions']);
        Route::get('account/payments', [AccountController::class, 'payments']);
        Route::get('account/payments/{payment}', [AccountController::class, 'payment']);
        Route::get('vpn/credentials', [AccountController::class, 'vpnStatus']);
        Route::get('vpn/config', [AccountController::class, 'vpnConfig'])->middleware('throttle:vpn');

        Route::post('testimonials', [TestimonialController::class, 'store'])->middleware('throttle:testimonials');

        Route::post('checkout', [PaymentController::class, 'createCheckout'])->middleware('throttle:checkout');
        Route::get('payments/{payment}/verify', [PaymentController::class, 'verifyPayment']);
        Route::get('checkout/{checkoutId}/verify', [PaymentController::class, 'verify']);
        Route::post('subscriptions/trial', [PaymentController::class, 'startTrial'])->middleware('throttle:trial');
        Route::post('subscriptions/cancel', [PaymentController::class, 'cancelSubscription']);

        Route::get('team', [TeamController::class, 'show']);
        Route::patch('team', [TeamController::class, 'update']);
        Route::post('team/members', [TeamController::class, 'addMember']);
        Route::delete('team/members/{member}', [TeamController::class, 'removeMember']);
    });

    Route::prefix('admin')->middleware(['auth', 'admin'])->group(function () {
        Route::get('users', [AdminController::class, 'users']);
        Route::patch('users/{user}', [AdminController::class, 'updateUser']);
        Route::delete('users/{user}', [AdminController::class, 'destroyUser']);
        Route::get('plans', [AdminController::class, 'plans']);
        Route::post('plans', [AdminController::class, 'storePlan']);
        Route::patch('plans/{plan}', [AdminController::class, 'updatePlan']);
        Route::delete('plans/{plan}', [AdminController::class, 'destroyPlan']);
        Route::get('testimonials', [AdminController::class, 'testimonials']);
        Route::patch('testimonials/{testimonial}', [AdminController::class, 'moderateTestimonial']);
        Route::delete('testimonials/{testimonial}', [AdminController::class, 'destroyTestimonial']);
        Route::get('audit-logs', [AdminController::class, 'auditLogs']);
        Route::get('contact-requests', [ContactController::class, 'index']);
        Route::patch('contact-requests/{contactRequest}', [ContactController::class, 'update']);

        Route::get('stats', [AdminBillingController::class, 'stats']);
        Route::get('payments', [AdminBillingController::class, 'payments']);
        Route::post('payments/{payment}/refund', [AdminBillingController::class, 'refund']);
        Route::get('subscriptions', [AdminBillingController::class, 'subscriptions']);
        Route::post('subscriptions/team', [AdminBillingController::class, 'createTeamSubscription']);
        Route::post('subscriptions/{subscription}/cancel', [AdminBillingController::class, 'cancelSubscription']);
    });
});
