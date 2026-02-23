<?php

use App\Http\Controllers\Api\AutomationApiController;
use App\Http\Controllers\Api\PaymentWebhookController;
use App\Http\Controllers\WhatsAppWebhookController;
use Illuminate\Support\Facades\Route;

Route::get('/health', fn () => response()->json(['status' => 'ok']));

Route::prefix('webhooks/whatsapp/{tenantUuid}')
    ->middleware('throttle:webhook-whatsapp')
    ->group(function () {
        Route::get('/', [WhatsAppWebhookController::class, 'verify'])
            ->name('webhooks.whatsapp.verify');
        Route::post('/', [WhatsAppWebhookController::class, 'receive'])
            ->name('webhooks.whatsapp.receive');
    });

Route::post('webhooks/payments/mercadopago', [PaymentWebhookController::class, 'mercadopago'])
    ->middleware('throttle:webhook-payments')
    ->name('webhooks.payments.mercadopago');

Route::prefix('v1')->middleware(['auth:sanctum', 'tenant.api', 'throttle:tenant-api'])->group(function () {
    Route::post('messages/send', [AutomationApiController::class, 'sendMessage'])->name('api.v1.messages.send');
    Route::put('leads/{lead}', [AutomationApiController::class, 'updateLead'])->name('api.v1.leads.update');
    Route::post('conversations/{conversation}/close', [AutomationApiController::class, 'closeConversation'])->name('api.v1.conversations.close');
    Route::post('escalations/{escalation}/note', [AutomationApiController::class, 'addEscalationNote'])->name('api.v1.escalations.note');
    Route::get('conversations', [AutomationApiController::class, 'listConversations'])->name('api.v1.conversations.index');
    Route::get('leads', [AutomationApiController::class, 'listLeads'])->name('api.v1.leads.index');
});
