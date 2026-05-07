<?php

use Fujiba\LiveChat\Http\Controllers\Admin\LiveChatController;
use Fujiba\LiveChat\Http\Controllers\Admin\SettingsController;

Route::group([
    'prefix'     => config('app.admin_path', 'admin') . '/fujiba-livechat',
    'middleware' => ['web', 'admin'],
    'as'         => 'admin.fujiba.livechat.',
], function () {
    // Main livechat UI
    Route::get('/',                           [LiveChatController::class, 'index'])->name('index');

    // Conversation proxies (all return JSON — called by Blade JS)
    Route::get('/conversations',              [LiveChatController::class, 'conversations'])->name('conversations');
    Route::get('/conversation',               [LiveChatController::class, 'conversation'])->name('conversation');
    Route::post('/reply',                     [LiveChatController::class, 'sendReply'])->name('reply');
    Route::post('/bot-flow',                  [LiveChatController::class, 'sendBotFlow'])->name('bot-flow');
    Route::get('/bot-flows',                  [LiveChatController::class, 'botFlows'])->name('bot-flows');
    Route::get('/canned-responses',            [LiveChatController::class, 'cannedResponses'])->name('canned-responses');
    Route::post('/mark-as',                   [LiveChatController::class, 'markAs'])->name('mark-as');
    Route::get('/subscriber',                 [LiveChatController::class, 'subscriberInfo'])->name('subscriber');
    Route::post('/upload',                    [LiveChatController::class, 'uploadFile'])->name('upload');
    Route::get('/health',                     [LiveChatController::class, 'serverHealth'])->name('health');

    // Settings
    Route::get('/settings',                   [SettingsController::class, 'index'])->name('settings.index');
    Route::get('/settings/{channelId}',       [SettingsController::class, 'show'])->name('settings.show');
    Route::post('/settings/{channelId}',      [SettingsController::class, 'save'])->name('settings.save');
    Route::post('/settings/{channelId}/test', [SettingsController::class, 'testConnection'])->name('settings.test');
});
