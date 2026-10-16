<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\PasswordResetController;
use App\Http\Controllers\Api\V1\QuizAttemptController;
use App\Http\Controllers\Api\V1\RegistrationController;
use App\Http\Controllers\Api\V1\SubjectController;
use App\Http\Controllers\Api\V1\TopicController;
use App\Http\Middleware\EnsureAccountActive;
use Illuminate\Support\Facades\Route;

// Vor dem Login holt die Vue-App einmal GET /sanctum/csrf-cookie.
Route::prefix('v1')->group(function () {
    Route::post('login', [AuthController::class, 'login'])->middleware('throttle:login');

    // Registrierung (E-025): Code anfordern → Code prüfen → Konto anlegen
    Route::post('register/code', [RegistrationController::class, 'sendCode'])->middleware('throttle:email-code');
    Route::post('register/verify', [RegistrationController::class, 'verify'])->middleware('throttle:email-code-verify');
    Route::post('register', [RegistrationController::class, 'register'])->middleware('throttle:email-code-verify');

    // Passwort vergessen: Code anfordern → mit Code neues Passwort setzen
    Route::post('password/code', [PasswordResetController::class, 'sendCode'])->middleware('throttle:email-code');
    Route::post('password/reset', [PasswordResetController::class, 'reset'])->middleware('throttle:email-code-verify');

    Route::middleware(['auth:sanctum', EnsureAccountActive::class])->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::get('me', [AuthController::class, 'me']);
        // Eigenes Konto löschen (E-027), mit Passwort, deshalb wie der Login begrenzt
        Route::delete('me', [AuthController::class, 'destroy'])->middleware('throttle:5,1');

        Route::get('subjects', [SubjectController::class, 'index']);
        Route::get('subjects/{subject}', [SubjectController::class, 'show']);
        Route::get('subjects/{subject}/topics/{topic}', [TopicController::class, 'show']);

        Route::post('topics/{topic}/attempts', [QuizAttemptController::class, 'store']);
        Route::post('attempts/{attempt}/answers', [QuizAttemptController::class, 'answer']);
        Route::post('attempts/{attempt}/finish', [QuizAttemptController::class, 'finish']);
    });
});
