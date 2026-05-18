<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ArticleController;
use App\Http\Controllers\TagController;
use App\Http\Controllers\NoteController;
use App\Http\Controllers\BookmarkController;
use App\Http\Controllers\SummaryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\Admin\AdminController;
use Illuminate\Support\Facades\Route;

Route::middleware('redirect.admin')->group(function () {
    Route::get('/', function () {
        return view('welcome');
    });
});

Route::middleware(['auth', 'verified', 'redirect.admin'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/ingest', fn () => redirect()->route('dashboard'));
    Route::post('/dashboard/ingest', [DashboardController::class, 'ingest'])->name('dashboard.ingest');
    Route::post('/dashboard/articles/{article}/retry', [DashboardController::class, 'retry'])->name('dashboard.retry');
});

// Profile routes — accessible by both admin and regular users
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware(['auth', 'redirect.admin'])->group(function () {
    // Articles Routes
    Route::get('articles/data', [ArticleController::class, 'data'])->name('articles.data');
    Route::post('articles/{article}/generate-citations', [ArticleController::class, 'generateCitations'])->name('articles.generate-citations');
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

    // Summaries (JSON endpoints for article page)
    Route::get('summaries/data', [SummaryController::class, 'index'])->name('summaries.data');
    Route::post('summaries/generate', [SummaryController::class, 'store'])->name('summaries.generate');
    Route::get('summaries/quota', [SummaryController::class, 'quota'])->name('summaries.quota');
    Route::get('summaries/{id}', [SummaryController::class, 'show'])->name('summaries.show');
    Route::get('summaries/{id}/status', [SummaryController::class, 'status'])->name('summaries.status');
    Route::post('summaries/{articleId}/regenerate', [SummaryController::class, 'regenerate'])->name('summaries.regenerate');

    // Projects / Workspace Routes
    Route::get('projects/data', [ProjectController::class, 'data'])->name('projects.data');
    Route::post('projects/{project}/duplicate', [ProjectController::class, 'duplicate'])->name('projects.duplicate');
    Route::post('projects/{project}/add-article', [ProjectController::class, 'addArticle'])->name('projects.add-article');
    Route::delete('projects/{project}/remove-article/{article}', [ProjectController::class, 'removeArticle'])->name('projects.remove-article');
    Route::get('projects/{project}/bibliography', [ProjectController::class, 'generateBibliography'])->name('projects.bibliography');
    Route::get('projects/{project}/bibliography/export', [ProjectController::class, 'exportBibliography'])->name('projects.bibliography-export');
    Route::resource('projects', ProjectController::class);
    
    });

// Admin Routes
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('dashboard');
    Route::get('/users', [AdminController::class, 'users'])->name('users');
    Route::post('/users/{user}/toggle', [AdminController::class, 'toggleUser'])->name('users.toggle');
    Route::delete('/users/{user}/delete', [AdminController::class, 'deleteUser'])->name('users.delete');
    Route::get('/articles', [AdminController::class, 'articles'])->name('articles');
    Route::get('/system', [AdminController::class, 'system'])->name('system');
});

require __DIR__.'/auth.php';
