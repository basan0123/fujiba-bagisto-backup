<?php

use Fujiba\AutoPoster\Http\Controllers\Admin\DashboardController;
use Fujiba\AutoPoster\Http\Controllers\Admin\MobileController;
use Fujiba\AutoPoster\Http\Controllers\Admin\PostController;
use Fujiba\AutoPoster\Http\Controllers\Admin\PreviewApprovalController;
use Fujiba\AutoPoster\Http\Controllers\Admin\SettingsController;
use Fujiba\AutoPoster\Http\Controllers\Admin\TemplateController;
use Fujiba\AutoPoster\Http\Controllers\Admin\TikTokController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'admin', 'auth:admin'])
    ->prefix(config('app.admin_url') . '/autoposter')
    ->name('admin.autoposter.')
    ->group(function () {
        Route::get('/', [DashboardController::class, 'index'])->name('index');

        // Phone planner (week view + bottom-sheet editor, JSON actions).
        Route::get('/m',                       [MobileController::class, 'index'])->name('mobile');
        Route::get('/m/posts',                 [MobileController::class, 'posts'])->name('mobile.posts');
        Route::post('/m/posts/{id}',           [MobileController::class, 'update'])->name('mobile.update');
        Route::post('/m/posts/{id}/recaption', [MobileController::class, 'recaption'])->name('mobile.recaption');
        Route::post('/m/posts/{id}/shuffle',   [MobileController::class, 'shuffle'])->name('mobile.shuffle');
        Route::post('/m/posts/{id}/skip',      [MobileController::class, 'skip'])->name('mobile.skip');
        Route::post('/m/posts/{id}/publish',   [MobileController::class, 'publish'])->name('mobile.publish');
        Route::post('/m/toggle',               [MobileController::class, 'toggle'])->name('mobile.toggle');
        Route::post('/preflight', [DashboardController::class, 'preflight'])->name('preflight');
        Route::post('/toggle',    [DashboardController::class, 'toggle'])->name('toggle');
        Route::post('/plan-now',  [DashboardController::class, 'planNow'])->name('plan');

        // v2 preview owner approval — records the decision only (approvals.json), never posts/queues.
        Route::get('/previews',                      [PreviewApprovalController::class, 'index'])->name('previews');
        Route::get('/previews/{dir}/media/{file}',   [PreviewApprovalController::class, 'media'])->name('previews.media');
        Route::post('/previews/{dir}/{key}/decide',  [PreviewApprovalController::class, 'decide'])->name('previews.decide');

        Route::get('/settings',  [SettingsController::class, 'index'])->name('settings');
        Route::post('/settings', [SettingsController::class, 'save'])->name('settings.save');

        Route::get('/templates',            [TemplateController::class, 'index'])->name('templates');
        Route::post('/templates',           [TemplateController::class, 'save'])->name('templates.save');
        Route::delete('/templates/{id}',    [TemplateController::class, 'destroy'])->name('templates.destroy');

        Route::get('/tiktok',                [TikTokController::class, 'index'])->name('tiktok');
        Route::get('/tiktok/connect',        [TikTokController::class, 'connect'])->name('tiktok.connect');
        Route::post('/tiktok/disconnect',    [TikTokController::class, 'disconnect'])->name('tiktok.disconnect');
        Route::get('/tiktok/post/{id}',      [TikTokController::class, 'compose'])->name('tiktok.compose');
        Route::post('/tiktok/post/{id}',     [TikTokController::class, 'publish'])->name('tiktok.publish');
        Route::get('/tiktok/status/{id}',    [TikTokController::class, 'status'])->name('tiktok.status');

        Route::get('/queue',   [PostController::class, 'queue'])->name('queue');
        Route::get('/history', [PostController::class, 'history'])->name('history');

        Route::get('/create',  [PostController::class, 'create'])->name('posts.create');
        Route::post('/create', [PostController::class, 'store'])->name('posts.store');

        Route::get('/bulk',  [PostController::class, 'bulkForm'])->name('bulk');
        Route::post('/bulk', [PostController::class, 'bulkStore'])->name('bulk.store');

        Route::get('/posts/{id}',                   [PostController::class, 'edit'])->name('posts.edit');
        Route::put('/posts/{id}',                   [PostController::class, 'update'])->name('posts.update');
        Route::post('/posts/{id}/reorder',          [PostController::class, 'reorder'])->name('posts.reorder');
        Route::post('/posts/{id}/remove-product',   [PostController::class, 'removeProduct'])->name('posts.remove_product');
        Route::post('/posts/{id}/regenerate',       [PostController::class, 'regenerateCaption'])->name('posts.regenerate');
        Route::post('/posts/{id}/publish',          [PostController::class, 'publishNow'])->name('posts.publish');
        Route::post('/posts/{id}/schedule',         [PostController::class, 'schedule'])->name('posts.schedule');
        Route::post('/posts/{id}/retry',            [PostController::class, 'retry'])->name('posts.retry');
        Route::post('/posts/{id}/cancel',           [PostController::class, 'cancel'])->name('posts.cancel');
        Route::post('/posts/{id}/duplicate',        [PostController::class, 'duplicate'])->name('posts.duplicate');
        Route::delete('/posts/{id}',                [PostController::class, 'destroy'])->name('posts.destroy');
    });

// Short address for phones: /admin/posts
Route::middleware(['web', 'admin', 'auth:admin'])
    ->get(config('app.admin_url').'/posts', fn () => redirect()->route('admin.autoposter.mobile'))
    ->name('admin.autoposter.short');

// TikTok OAuth redirect URI registered in the developer portal: /admin/tiktok/callback
Route::middleware(['web', 'admin', 'auth:admin'])
    ->get(config('app.admin_url').'/tiktok/callback', [TikTokController::class, 'callback'])
    ->name('admin.autoposter.tiktok.callback');
