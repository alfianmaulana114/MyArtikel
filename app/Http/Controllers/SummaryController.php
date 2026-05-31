<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessSummaryGeneration;
use App\Models\Article;
use App\Models\Summary;
use App\Services\QuotaManagementService;
use App\Services\SummarizationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class SummaryController extends Controller
{
    private SummarizationService $summarizationService;

    private QuotaManagementService $quotaService;

    public function __construct(
        SummarizationService $summarizationService,
        QuotaManagementService $quotaService
    ) {
        $this->summarizationService = $summarizationService;
        $this->quotaService = $quotaService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $query = Summary::with(['article', 'user'])
            ->where('user_id', $user->id)
            ->where('status', 'completed')
            ->orderBy('created_at', 'desc');

        if ($request->has('article_id')) {
            $query->where('article_id', $request->article_id);
        }

        $summaries = $query->paginate(10);

        return response()->json([
            'success' => true,
            'data' => $summaries,
        ]);
    }

    /**
     * Store a newly created resource in storage (generate summary).
     */
    public function store(Request $request)
    {
        $request->validate([
            'article_id' => 'required|exists:articles,id',
            'max_words' => 'integer|min:50|max:500',
            'language' => 'string|in:id,en',
            'prefer_ai' => 'boolean',
            'async' => 'boolean',
        ]);

        $user = Auth::user();
        $articleId = $request->article_id;

        // Check quota status
        $quotaStatus = $this->quotaService->getQuotaStatus($user->id);

        if ($request->prefer_ai && ! $quotaStatus['gemini']['can_use'] && ! $quotaStatus['local']['can_use']) {
            return response()->json([
                'success' => false,
                'error' => 'Quota exceeded',
                ...($user->is_admin ? ['quota_status' => $quotaStatus] : []),
            ], 429);
        }

        if (! $quotaStatus['local']['can_use']) {
            return response()->json([
                'success' => false,
                'error' => 'Daily quota exceeded',
                ...($user->is_admin ? ['quota_status' => $quotaStatus] : []),
            ], 429);
        }

        try {
            $options = [
                'max_words' => $request->max_words ?? 150,
                'language' => $request->language ?? 'id',
                'prefer_ai' => $request->prefer_ai ?? true,
                'force_regenerate' => false,
            ];

            // Check if async processing is requested
            if ($request->async ?? false) {
                // Create summary record with pending status
                $summary = Summary::create([
                    'article_id' => $articleId,
                    'user_id' => $user->id,
                    'content' => '',
                    'word_count' => 0,
                    'type' => 'ai_generated',
                    'source' => 'pending',
                    'status' => 'pending',
                    'processing_started_at' => now(),
                ]);

                // Dispatch job
                ProcessSummaryGeneration::dispatch($articleId, $user->id, $summary->id, $options);

                return response()->json([
                    'success' => true,
                    'data' => [
                        'summary_id' => $summary->id,
                        'status' => 'pending',
                        'message' => 'Summary generation queued for processing',
                    ],
                    ...($user->is_admin ? ['quota_status' => $quotaStatus] : []),
                ], 202);
            }

            // Synchronous processing
            $result = $this->summarizationService->generateSummary($articleId, $user->id, $options);

            if (! $result['success']) {
                return response()->json([
                    'success' => false,
                    'error' => $result['error'],
                    ...($user->is_admin ? ['quota_status' => $quotaStatus] : []),
                ], 500);
            }

            return response()->json([
                'success' => true,
                'data' => $result['summary'],
                ...($user->is_admin ? ['source' => $result['source']] : []),
                'message' => $result['message'],
                ...($user->is_admin ? ['quota_status' => $this->quotaService->getQuotaStatus($user->id)] : []),
            ]);

        } catch (\Exception $e) {
            Log::error('Summary generation failed: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'error' => 'Failed to generate summary',
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $user = Auth::user();

        $summary = Summary::with(['article', 'user'])
            ->where('user_id', $user->id)
            ->findOrFail($id);

        if (! $user->is_admin) {
            $summary->makeHidden(['source', 'type']);
        }

        return response()->json([
            'success' => true,
            'data' => $summary,
        ]);
    }

    /**
     * Check summary generation status.
     */
    public function status(string $id)
    {
        $user = Auth::user();

        $summary = Summary::with(['article'])
            ->where('user_id', $user->id)
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $summary->id,
                'status' => $summary->status,
                ...($user->is_admin ? ['source' => $summary->source] : []),
                'processing_time_ms' => $summary->processing_time_ms,
                'error_message' => $summary->error_message,
                'created_at' => $summary->created_at,
                'updated_at' => $summary->updated_at,
            ],
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $request->validate([
            'content' => 'required|string',
            'key_points' => 'array',
            'key_points.*' => 'string',
        ]);

        $user = Auth::user();

        $summary = Summary::where('user_id', $user->id)->findOrFail($id);

        $summary->update([
            'content' => $request->content,
            'word_count' => str_word_count($request->content),
            'key_points' => $request->key_points ?? [],
            'type' => 'manual',
            'source' => 'manual',
            'status' => 'completed',
        ]);

        return response()->json([
            'success' => true,
            'data' => $summary,
            'message' => 'Summary updated successfully',
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $user = Auth::user();

        $summary = Summary::where('user_id', $user->id)->findOrFail($id);
        $summary->delete();

        return response()->json([
            'success' => true,
            'message' => 'Summary deleted successfully',
        ]);
    }

    /**
     * Get quota status for current user.
     */
    public function quota()
    {
        $user = Auth::user();

        $quotaStatus = $this->quotaService->getQuotaStatus($user->id);
        $usageStats = $this->summarizationService->getUsageStats($user->id, 7);

        return response()->json([
            'success' => true,
            'data' => [
                'quota_status' => $quotaStatus,
                'usage_stats' => $usageStats,
            ],
        ]);
    }

    /**
     * Regenerate summary for article.
     */
    public function regenerate(Request $request, string $articleId)
    {
        $request->validate([
            'max_words' => 'integer|min:50|max:500',
            'language' => 'string|in:id,en',
            'prefer_ai' => 'boolean',
        ]);

        $user = Auth::user();

        // Check if article exists and belongs to user
        $article = Article::where('user_id', $user->id)->findOrFail($articleId);

        $options = [
            'max_words' => $request->max_words ?? 150,
            'language' => $request->language ?? 'id',
            'prefer_ai' => $request->prefer_ai ?? true,
            'force_regenerate' => true,
        ];

        try {
            $result = $this->summarizationService->generateSummary($articleId, $user->id, $options);

            if (! $result['success']) {
                return response()->json([
                    'success' => false,
                    'error' => $result['error'],
                ], 500);
            }

            return response()->json([
                'success' => true,
                'data' => $result['summary'],
                ...($user->is_admin ? ['source' => $result['source']] : []),
                'message' => $result['message'],
            ]);

        } catch (\Exception $e) {
            Log::error('Summary regeneration failed: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'error' => 'Failed to regenerate summary',
            ], 500);
        }
    }
}
