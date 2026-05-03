<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ArticleController;
use App\Http\Controllers\TagController;
use App\Http\Controllers\NoteController;
use App\Http\Controllers\BookmarkController;
use App\Http\Controllers\SummaryController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::post('/dashboard/ingest', [DashboardController::class, 'ingest'])->name('dashboard.ingest');
    Route::post('/dashboard/articles/{article}/retry', [DashboardController::class, 'retry'])->name('dashboard.retry');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    
    // Articles Routes
    Route::get('articles/data', [ArticleController::class, 'data'])->name('articles.data');
    Route::resource('articles', ArticleController::class);
    
    // Tags Routes
    Route::get('tags/data', [TagController::class, 'data'])->name('tags.data');
    Route::resource('tags', TagController::class)->except(['show']);
    Route::get('tags/autocomplete', [TagController::class, 'autocomplete'])->name('tags.autocomplete');
    Route::get('tags/suggest', [TagController::class, 'suggest'])->name('tags.suggest');
    Route::post('tags/bulk-tag', [TagController::class, 'bulkTag'])->name('tags.bulk-tag');
    Route::post('tags/{tag}/remove-from-articles', [TagController::class, 'removeFromArticles'])->name('tags.remove-from-articles');
    
    // Notes Routes
    Route::get('notes/data', [NoteController::class, 'data'])->name('notes.data');
    Route::resource('notes', NoteController::class);
    Route::post('notes/sync', [NoteController::class, 'sync'])->name('notes.sync');
    Route::get('notes/pending-sync', [NoteController::class, 'getPendingSync'])->name('notes.pending-sync');
    Route::post('notes/search', [NoteController::class, 'search'])->name('notes.search');
    
    // Include bookmark routes
    require __DIR__.'/bookmarks.php';
    
    // Include job monitoring routes
    require __DIR__.'/jobs.php';
    
    // Include PDF export routes
    require __DIR__.'/pdf.php';
    
    // Summaries (page + JSON endpoints)
    Route::view('summaries', 'summaries.index')->name('summaries.page');
    Route::get('summaries/data', [SummaryController::class, 'index'])->name('summaries.data');
    Route::post('summaries/generate', [SummaryController::class, 'store'])->name('summaries.generate');
    Route::get('summaries/quota', [SummaryController::class, 'quota'])->name('summaries.quota');
    Route::get('summaries/{id}', [SummaryController::class, 'show'])->name('summaries.show');
    Route::put('summaries/{id}', [SummaryController::class, 'update'])->name('summaries.update');
    Route::delete('summaries/{id}', [SummaryController::class, 'destroy'])->name('summaries.destroy');
    Route::get('summaries/{id}/status', [SummaryController::class, 'status'])->name('summaries.status');
    Route::post('summaries/{articleId}/regenerate', [SummaryController::class, 'regenerate'])->name('summaries.regenerate');
    
    // Search Routes (commented out for now)
    // Route::prefix('search')->name('search.')->group(function () {
    //     Route::get('/', [SearchController::class, 'search'])->name('index');
    //     Route::get('/suggestions', [SearchController::class, 'suggestions'])->name('suggestions');
    //     Route::get('/history', [SearchController::class, 'history'])->name('history');
    //     Route::get('/analytics', [SearchController::class, 'analytics'])->name('analytics');
    //     Route::post('/click', [SearchController::class, 'recordClick'])->name('click');
    //     Route::get('/quick', [SearchController::class, 'quickSearch'])->name('quick');
    //     Route::post('/advanced', [SearchController::class, 'advancedSearch'])->name('advanced');
    // });
});

require __DIR__.'/auth.php';
