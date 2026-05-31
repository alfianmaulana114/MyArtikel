<?php

use App\Http\Controllers\BookmarkAnalyticsController;
use App\Http\Controllers\BookmarkCategoryController;
use App\Http\Controllers\BookmarkController;
use App\Http\Controllers\BookmarkedArticlesController;
use App\Http\Controllers\BookmarkSyncController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Bookmark Routes
|--------------------------------------------------------------------------
|
| Routes for bookmark management system
|
*/

Route::middleware(['auth', 'redirect.admin'])->group(function () {
    // Bookmark routes
    Route::prefix('bookmarks')->group(function () {
        Route::get('/', [BookmarkController::class, 'index'])->name('bookmarks.index');
        Route::get('/data', [BookmarkController::class, 'data'])->name('bookmarks.data');
        Route::post('/', [BookmarkController::class, 'store'])->name('bookmarks.store');
        Route::get('/check', [BookmarkController::class, 'checkArticle'])->name('bookmarks.check');
        Route::post('/bulk', [BookmarkController::class, 'bulkOperation'])->name('bookmarks.bulk');

        Route::prefix('{bookmark}')->group(function () {
            Route::get('/', [BookmarkController::class, 'show'])->name('bookmarks.show');
            Route::put('/', [BookmarkController::class, 'update'])->name('bookmarks.update');
            Route::delete('/', [BookmarkController::class, 'destroy'])->name('bookmarks.destroy');
            Route::post('/favorite', [BookmarkController::class, 'toggleFavorite'])->name('bookmarks.favorite');
            Route::post('/read', [BookmarkController::class, 'markAsRead'])->name('bookmarks.read');
            Route::post('/unread', [BookmarkController::class, 'markAsUnread'])->name('bookmarks.unread');
            Route::post('/archive', [BookmarkController::class, 'archive'])->name('bookmarks.archive');
            Route::post('/unarchive', [BookmarkController::class, 'unarchive'])->name('bookmarks.unarchive');
        });
    });

    // Bookmark category routes
    Route::prefix('bookmark-categories')->group(function () {
        Route::get('/', [BookmarkCategoryController::class, 'index'])->name('bookmark-categories.index');
        Route::post('/', [BookmarkCategoryController::class, 'store'])->name('bookmark-categories.store');
        Route::post('/reorder', [BookmarkCategoryController::class, 'reorder'])->name('bookmark-categories.reorder');

        Route::prefix('{category}')->group(function () {
            Route::get('/', [BookmarkCategoryController::class, 'show'])->name('bookmark-categories.show');
            Route::put('/', [BookmarkCategoryController::class, 'update'])->name('bookmark-categories.update');
            Route::delete('/', [BookmarkCategoryController::class, 'destroy'])->name('bookmark-categories.destroy');
            Route::get('/bookmarks', [BookmarkCategoryController::class, 'bookmarks'])->name('bookmark-categories.bookmarks');
        });
    });

    // Bookmark sync routes
    Route::prefix('bookmark-sync')->group(function () {
        Route::get('/devices', [BookmarkSyncController::class, 'devices'])->name('bookmark-sync.devices');
        Route::post('/register-device', [BookmarkSyncController::class, 'registerDevice'])->name('bookmark-sync.register-device');
        Route::post('/unregister-device', [BookmarkSyncController::class, 'unregisterDevice'])->name('bookmark-sync.unregister-device');
        Route::get('/sync', [BookmarkSyncController::class, 'sync'])->name('bookmark-sync.sync');
        Route::post('/sync', [BookmarkSyncController::class, 'processSync'])->name('bookmark-sync.process');
        Route::get('/conflicts', [BookmarkSyncController::class, 'conflicts'])->name('bookmark-sync.conflicts');
        Route::post('/resolve-conflict', [BookmarkSyncController::class, 'resolveConflict'])->name('bookmark-sync.resolve-conflict');
    });

    // Bookmark analytics routes
    Route::prefix('bookmark-analytics')->group(function () {
        Route::get('/overview', [BookmarkAnalyticsController::class, 'overview'])->name('bookmark-analytics.overview');
        Route::get('/reading-stats', [BookmarkAnalyticsController::class, 'readingStats'])->name('bookmark-analytics.reading-stats');
        Route::get('/category-stats', [BookmarkAnalyticsController::class, 'categoryStats'])->name('bookmark-analytics.category-stats');
        Route::get('/device-stats', [BookmarkAnalyticsController::class, 'deviceStats'])->name('bookmark-analytics.device-stats');
        Route::get('/trends', [BookmarkAnalyticsController::class, 'trends'])->name('bookmark-analytics.trends');
        Route::get('/export', [BookmarkAnalyticsController::class, 'export'])->name('bookmark-analytics.export');
    });

    // Bookmarked articles filter routes
    Route::prefix('bookmarked-articles')->group(function () {
        Route::get('/', [BookmarkedArticlesController::class, 'index'])->name('bookmarked-articles.index');
        Route::get('/favorites', [BookmarkedArticlesController::class, 'favorites'])->name('bookmarked-articles.favorites');
        Route::get('/archived', [BookmarkedArticlesController::class, 'archived'])->name('bookmarked-articles.archived');
        Route::get('/due-reminders', [BookmarkedArticlesController::class, 'dueReminders'])->name('bookmarked-articles.due-reminders');
        Route::get('/search', [BookmarkedArticlesController::class, 'search'])->name('bookmarked-articles.search');
        Route::get('/category/{category}', [BookmarkedArticlesController::class, 'byCategory'])->name('bookmarked-articles.by-category');
    });
});
