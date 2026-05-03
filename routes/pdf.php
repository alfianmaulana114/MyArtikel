<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PdfExportController;

/*
|--------------------------------------------------------------------------
| PDF Export Routes
|--------------------------------------------------------------------------
|
| These routes handle PDF export functionality for articles and collections.
|
*/

Route::middleware(['auth'])->prefix('pdf')->name('pdf.')->group(function () {
    
    // Main export form
    Route::get('/export', [PdfExportController::class, 'create'])
        ->name('export.form');
    
    // Single article export
    Route::post('/export/single/{article}', [PdfExportController::class, 'exportSingle'])
        ->name('export.single');
    
    // Multiple articles export
    Route::post('/export/multiple', [PdfExportController::class, 'exportMultiple'])
        ->name('export.multiple');
    
    // Template-based export
    Route::post('/export/template/{template}', [PdfExportController::class, 'exportWithTemplate'])
        ->name('export.template');
    
    // Background export (for large collections)
    Route::post('/export/background', [PdfExportController::class, 'exportBackground'])
        ->name('export.background');
    
    // Download exported PDF
    Route::get('/download/{export}', [PdfExportController::class, 'download'])
        ->name('download');
    
    // Preview exported PDF
    Route::get('/preview/{export}', [PdfExportController::class, 'preview'])
        ->name('preview');
    
    // Export history
    Route::get('/history', [PdfExportController::class, 'history'])
        ->name('history');
    
    // Check export status
    Route::get('/status/{export}', [PdfExportController::class, 'status'])
        ->name('status');
    
    // Delete export
    Route::delete('/export/{export}', [PdfExportController::class, 'delete'])
        ->name('delete');
    
    // Available templates
    Route::get('/templates', [PdfExportController::class, 'templates'])
        ->name('templates');
    
});