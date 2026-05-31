<?php

use App\Http\Controllers\SummaryController;
use App\Models\Summary;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes for Summary Generation
|--------------------------------------------------------------------------
|
| These routes provide API endpoints for the summarization system
| with Gemini AI integration and local fallback.
|
*/

Route::middleware(['auth:sanctum'])->group(function () {
    // Summary generation endpoints
    Route::prefix('summaries')->group(function () {
        // Generate new summary
        Route::post('/', [SummaryController::class, 'store'])
            ->name('api.summaries.store');

        // Get user's summaries
        Route::get('/', [SummaryController::class, 'index'])
            ->name('api.summaries.index');

        // Get specific summary
        Route::get('/{id}', [SummaryController::class, 'show'])
            ->name('api.summaries.show');

        // Check summary status (for async processing)
        Route::get('/{id}/status', [SummaryController::class, 'status'])
            ->name('api.summaries.status');

        // Update summary (manual editing)
        Route::put('/{id}', [SummaryController::class, 'update'])
            ->name('api.summaries.update');

        // Delete summary
        Route::delete('/{id}', [SummaryController::class, 'destroy'])
            ->name('api.summaries.destroy');

        // Regenerate summary
        Route::post('/{articleId}/regenerate', [SummaryController::class, 'regenerate'])
            ->name('api.summaries.regenerate');

        // Get quota status
        Route::get('/quota', [SummaryController::class, 'quota'])
            ->name('api.summaries.quota');
    });

    // Bulk summary operations
    Route::prefix('bulk-summaries')->group(function () {
        // Generate summaries for multiple articles
        Route::post('/generate', function (Request $request) {
            $request->validate([
                'article_ids' => 'required|array',
                'article_ids.*' => 'exists:articles,id',
                'max_words' => 'integer|min:50|max:500',
                'prefer_ai' => 'boolean',
            ]);

            $results = [];
            foreach ($request->article_ids as $articleId) {
                try {
                    $summaryController = app(SummaryController::class);
                    $fakeRequest = new Request([
                        'article_id' => $articleId,
                        'max_words' => $request->max_words ?? 150,
                        'prefer_ai' => $request->prefer_ai ?? true,
                        'async' => true, // Always async for bulk operations
                    ]);

                    $result = $summaryController->store($fakeRequest);
                    $results[] = [
                        'article_id' => $articleId,
                        'success' => $result->getStatusCode() === 200 || $result->getStatusCode() === 202,
                        'status_code' => $result->getStatusCode(),
                        'data' => json_decode($result->getContent(), true),
                    ];
                } catch (Exception $e) {
                    $results[] = [
                        'article_id' => $articleId,
                        'success' => false,
                        'error' => $e->getMessage(),
                    ];
                }
            }

            return response()->json([
                'success' => true,
                'data' => $results,
            ]);
        })->name('api.bulk-summaries.generate');

        // Get bulk generation status
        Route::get('/status', function (Request $request) {
            $request->validate([
                'summary_ids' => 'required|array',
                'summary_ids.*' => 'exists:summaries,id',
            ]);

            $statuses = Summary::whereIn('id', $request->summary_ids)
                ->where('user_id', $request->user()->id)
                ->select(['id', 'status', 'source', 'processing_time_ms', 'error_message', 'updated_at'])
                ->get();

            return response()->json([
                'success' => true,
                'data' => $statuses,
            ]);
        })->name('api.bulk-summaries.status');
    });
});

// Public summary endpoints (if needed)
Route::prefix('public')->group(function () {
    // Get public summary (if sharing is enabled)
    Route::get('/summaries/{id}', function ($id) {
        $summary = Summary::with(['article' => function ($query) {
            $query->select(['id', 'title', 'created_at']);
        }])->findOrFail($id);

        // Check if summary is public (you might want to add a 'is_public' field)
        return response()->json([
            'success' => true,
            'data' => [
                'id' => $summary->id,
                'content' => $summary->content,
                'key_points' => $summary->key_points,
                'word_count' => $summary->word_count,
                'source' => $summary->source,
                'created_at' => $summary->created_at,
                'article' => $summary->article,
            ],
        ]);
    })->name('api.public.summaries.show');
});
