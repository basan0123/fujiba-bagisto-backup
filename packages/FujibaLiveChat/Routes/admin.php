<?php

use Illuminate\Support\Facades\Route;

Route::group([
    'prefix'     => config('app.admin_url').'/fujiba-livechat',
    'middleware' => ['web', 'admin'],
], function () {
    Route::get('/',              [\Modules\FujibaLiveChat\Http\Controllers\LiveChatController::class, 'index'])->name('fujiba.livechat.index');
    Route::get('/conversations', [\Modules\FujibaLiveChat\Http\Controllers\LiveChatController::class, 'getConversationList']);
    Route::get('/conversation',  [\Modules\FujibaLiveChat\Http\Controllers\LiveChatController::class, 'getConversation']);
    Route::post('/reply',        [\Modules\FujibaLiveChat\Http\Controllers\LiveChatController::class, 'sendReply']);
    Route::post('/bot-flow',     [\Modules\FujibaLiveChat\Http\Controllers\LiveChatController::class, 'sendBotFlow']);
    Route::get('/bot-flows',     [\Modules\FujibaLiveChat\Http\Controllers\LiveChatController::class, 'getBotFlows']);
    Route::get('/subscriber',    [\Modules\FujibaLiveChat\Http\Controllers\LiveChatController::class, 'getSubscriberInfo']);
    Route::post('/notify-order', [\Modules\FujibaLiveChat\Http\Controllers\LiveChatController::class, 'sendOrderNotification']);
    Route::get('/settings/{ch}', [\Modules\FujibaLiveChat\Http\Controllers\LiveChatController::class, 'getSettings']);
    Route::post('/settings/{ch}',[\Modules\FujibaLiveChat\Http\Controllers\LiveChatController::class, 'saveSettings']);

    Route::get('/health', function () {
        return response()->json(
            app(\Modules\FujibaLiveChat\Services\ChatpionService::class)->healthCheck()
        );
    });
});
