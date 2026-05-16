<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\JobMonitoringController;

/*
|--------------------------------------------------------------------------
| Job Monitoring Routes
|--------------------------------------------------------------------------
|
| These routes provide monitoring and management capabilities for
| background jobs and queue processing.
|
*/

Route::middleware(['auth', 'redirect.admin'])->prefix('jobs')->group(function () {
    // Job monitoring dashboard
    Route::get('/monitoring', [JobMonitoringController::class, 'index'])
        ->name('jobs.monitoring');
    
    // API endpoints for job statistics
    Route::get('/stats', [JobMonitoringController::class, 'queueStats'])
        ->name('jobs.stats');
    
    Route::get('/job-types', [JobMonitoringController::class, 'jobTypeStats'])
        ->name('jobs.types');
    
    Route::get('/user-stats', [JobMonitoringController::class, 'userStats'])
        ->name('jobs.user-stats');
    
    Route::get('/health', [JobMonitoringController::class, 'health'])
        ->name('jobs.health');
    
    // Failed jobs management
    Route::get('/failed', [JobMonitoringController::class, 'failedJobs'])
        ->name('jobs.failed');
    
    Route::get('/failed/{id}', [JobMonitoringController::class, 'jobDetails'])
        ->name('jobs.failed.details');
    
    Route::post('/retry', [JobMonitoringController::class, 'retryFailed'])
        ->name('jobs.retry');
    
    Route::delete('/failed', [JobMonitoringController::class, 'deleteFailed'])
        ->name('jobs.failed.delete');
    
    // Queue worker management
    Route::post('/restart-workers', [JobMonitoringController::class, 'restartWorkers'])
        ->name('jobs.restart-workers');
    
    Route::post('/clear-cache', [JobMonitoringController::class, 'clearCache'])
        ->name('jobs.clear-cache');
});