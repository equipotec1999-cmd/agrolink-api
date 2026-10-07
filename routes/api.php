<?php

use App\Http\Controllers\Api\AccountController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CatalogController;
use App\Http\Controllers\Api\ComplianceRuleController;
use App\Http\Controllers\Api\ConversationController;
use App\Http\Controllers\Api\SavedSearchController;
use App\Http\Controllers\Api\DeviceController;
use App\Http\Controllers\Api\FavoriteController;
use App\Http\Controllers\Api\ListingController;
use App\Http\Controllers\Api\ModerationController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\OfferController;
use App\Http\Controllers\Api\OperationController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\TwoFactorController;
use App\Http\Controllers\Api\VerificationController;
use App\Http\Controllers\Api\VerificationReviewController;
use Illuminate\Support\Facades\Route;

// Auth (Fase 1 §21: rate limiting en login/registro contra fuerza bruta).
Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:6,1');
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:6,1');
Route::post('/verify-contact', [AuthController::class, 'verifyContact'])->middleware('throttle:10,1');
Route::post('/resend-verification', [AuthController::class, 'resendVerification'])->middleware('throttle:3,1');

Route::middleware(['auth:sanctum', 'full.session'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    Route::get('/me/stats', [AccountController::class, 'stats']);
    Route::patch('/me', [AccountController::class, 'update']);
    Route::post('/me/password', [AccountController::class, 'password'])->middleware('throttle:6,1');
    Route::post('/me/avatar', [AccountController::class, 'avatar'])->middleware('throttle:12,1');
    Route::delete('/me/avatar', [AccountController::class, 'deleteAvatar']);
});

// Verificación en dos pasos, paso 2 del login: SOLO acepta el token pendiente.
Route::post('/two-factor/challenge', [TwoFactorController::class, 'challenge'])
    ->middleware(['auth:sanctum', 'ability:two-factor-pending', 'throttle:6,1']);

// Catálogo: lectura pública, no requiere sesión (se necesita para poder buscar sin cuenta).
Route::get('/categories', [CatalogController::class, 'categories']);
Route::get('/product-types/{productType}/attributes', [CatalogController::class, 'productTypeAttributes']);

// Listings: lectura pública; escritura requiere sesión.
Route::get('/listings', [ListingController::class, 'index']);
Route::get('/listings/{listing}', [ListingController::class, 'show']);

Route::middleware(['auth:sanctum', 'full.session'])->group(function () {
    Route::get('/my/listings', [ListingController::class, 'mine']);
    Route::post('/listings', [ListingController::class, 'store']);
    Route::patch('/listings/{listing}', [ListingController::class, 'update']);
    Route::delete('/listings/{listing}', [ListingController::class, 'destroy']);
    Route::post('/listings/{listing}/publish', [ListingController::class, 'publish']);
    Route::post('/listings/{listing}/resubmit', [ListingController::class, 'resubmit']);
    Route::post('/listings/{listing}/archive', [ListingController::class, 'archive']);
    Route::post('/listings/{listing}/media', [ListingController::class, 'uploadMedia']);
    Route::delete('/listings/{listing}/media/{media}', [ListingController::class, 'destroyMedia']);

    // Búsquedas guardadas.
    Route::get('/saved-searches', [SavedSearchController::class, 'index']);
    Route::post('/saved-searches', [SavedSearchController::class, 'store']);
    Route::delete('/saved-searches/{search}', [SavedSearchController::class, 'destroy']);

    // Favoritos (Fase 4). PUT/DELETE son idempotentes a propósito: la app los
    // reintenta sin riesgo si la red falla a medias.
    Route::get('/favorites', [FavoriteController::class, 'index']);
    Route::get('/favorites/ids', [FavoriteController::class, 'ids']);
    Route::put('/favorites/{listing}', [FavoriteController::class, 'store']);
    Route::delete('/favorites/{listing}', [FavoriteController::class, 'destroy']);

    // Chat (Fase 5). Mensajes por polling (`after_id`); sin WebSockets por ahora.
    Route::get('/conversations', [ConversationController::class, 'index']);
    Route::post('/listings/{listing}/conversation', [ConversationController::class, 'start']);
    Route::get('/conversations/{conversation}/messages', [ConversationController::class, 'messages']);
    Route::post('/conversations/{conversation}/messages', [ConversationController::class, 'send'])
        ->middleware('throttle:60,1');

    // Ofertas (Fase 5, bloque 2). Una oferta es un mensaje especial de la conversación.
    Route::post('/conversations/{conversation}/offers', [OfferController::class, 'store'])->middleware('throttle:30,1');
    Route::post('/offers/{offer}/accept', [OfferController::class, 'accept']);
    Route::post('/offers/{offer}/reject', [OfferController::class, 'reject']);
    Route::post('/offers/{offer}/cancel', [OfferController::class, 'cancel']);

    // Operaciones (Fase 5, bloque 3): lectura. Los cambios de estado llegan con pagos/entrega.
    Route::get('/operations', [OperationController::class, 'index']);
    Route::get('/operations/{operation}', [OperationController::class, 'show']);

    // Notificaciones dentro de la app (Fase 5, bloque 4).
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::post('/notifications/read-all', [NotificationController::class, 'readAll']);
    Route::post('/notifications/{id}/read', [NotificationController::class, 'read'])
        ->whereUuid('id');

    // Push (Fase 5, bloque 5): token FCM del celular.
    Route::post('/devices', [DeviceController::class, 'store']);
    Route::post('/devices/unregister', [DeviceController::class, 'destroy']);

    // Moderación (Fase 6). Reportar: cualquier usuario con sesión.
    Route::post('/listings/{listing}/report', [ReportController::class, 'store'])->middleware('throttle:10,1');
    Route::post('/users/{user}/report', [ReportController::class, 'storeUser'])->middleware('throttle:10,1');

    // Verificación de vendedor (quien la pide).
    Route::get('/verification', [VerificationController::class, 'show']);
    Route::post('/verification', [VerificationController::class, 'store'])->middleware('throttle:5,60');

    // Solo con permiso (no por nombre de rol).
    Route::prefix('moderation')->middleware('two-factor')->group(function () {
        Route::middleware('can:moderate listings')->group(function () {
            Route::get('/listings', [ModerationController::class, 'listings']);
            Route::post('/listings/{listing}/approve', [ModerationController::class, 'approve']);
            Route::post('/listings/{listing}/reject', [ModerationController::class, 'reject']);
        });
        Route::middleware('can:moderate documents')->group(function () {
            Route::get('/verifications', [VerificationReviewController::class, 'index']);
            Route::get('/verifications/{verification}/documents/{document}', [VerificationReviewController::class, 'document']);
            Route::post('/verifications/{verification}/approve', [VerificationReviewController::class, 'approve']);
            Route::post('/verifications/{verification}/reject', [VerificationReviewController::class, 'reject']);
        });
        Route::middleware('can:resolve reports')->group(function () {
            Route::get('/reports', [ModerationController::class, 'reports']);
            Route::post('/reports/{report}/resolve', [ModerationController::class, 'resolveReport']);
        });
    });

    // Administración (permiso + verificación en dos pasos, siempre).
    Route::prefix('admin')->middleware(['two-factor', 'can:manage compliance rules'])->group(function () {
        Route::get('/compliance-rules', [ComplianceRuleController::class, 'index']);
        Route::post('/compliance-rules', [ComplianceRuleController::class, 'store']);
        Route::patch('/compliance-rules/{rule}', [ComplianceRuleController::class, 'update']);
        Route::delete('/compliance-rules/{rule}', [ComplianceRuleController::class, 'destroy']);
    });

    // Activar/desactivar la verificación en dos pasos (sesión completa).
    Route::post('/two-factor/setup', [TwoFactorController::class, 'setup'])->middleware('throttle:10,1');
    Route::post('/two-factor/confirm', [TwoFactorController::class, 'confirm'])->middleware('throttle:10,1');
    Route::post('/two-factor/disable', [TwoFactorController::class, 'disable'])->middleware('throttle:6,1');
});
