<?php

namespace App\Services;

use App\Models\Summary;
use App\Models\Article;
use Illuminate\Support\Facades\Log;
use Exception;

class SummarizationService
{
    private GeminiSummarizationService $geminiService;
    private LocalSummarizationService $localService;
    private QuotaManagementService $quotaService;
    
    public function __construct(
        GeminiSummarizationService $geminiService,
        LocalSummarizationService $localService,
        QuotaManagementService $quotaService
    ) {
        $this->geminiService = $geminiService;
        $this->localService = $localService;
        $this->quotaService = $quotaService;
    }

    /**
     * Generate summary for article with fallback strategy
     */
    public function generateSummary(int $articleId, int $userId, array $options = []): array
    {
        $options = array_merge([
            'max_words' => 150,
            'language' => 'id',
            'prefer_ai' => true,
            'force_regenerate' => false
        ], $options);
        
        try {
            // Get article
            $article = Article::findOrFail($articleId);
            $content = $this->prepareContent($article);
            
            if (empty($content)) {
                throw new Exception('Article content is empty');
            }
            
            // Check existing summary
            if (!$options['force_regenerate']) {
                $existingSummary = $this->getExistingSummary($articleId, $userId, $content);
                if ($existingSummary) {
                    return [
                        'success' => true,
                        'summary' => $existingSummary,
                        'source' => 'cache',
                        'message' => 'Summary retrieved from cache'
                    ];
                }
            }
            
            // Try AI summarization first if preferred and available
            if ($options['prefer_ai'] && $this->geminiService->isAvailable()) {
                $quotaCheck = $this->quotaService->canUseGemini($userId, strlen($content));
                
                if ($quotaCheck) {
                    try {
                        $aiSummary = $this->generateAiSummary($articleId, $userId, $content, $options);
                        
                        return [
                            'success' => true,
                            'summary' => $aiSummary,
                            'source' => 'gemini',
                            'message' => 'Summary generated using Gemini AI'
                        ];
                    } catch (Exception $e) {
                        Log::warning('AI summarization failed, falling back to local: ' . $e->getMessage());
                    }
                }
            }
            
            // Fallback to local summarization
            if ($this->quotaService->canUseLocal($userId)) {
                $localSummary = $this->generateLocalSummary($articleId, $userId, $content, $options);
                
                return [
                    'success' => true,
                    'summary' => $localSummary,
                    'source' => 'local',
                    'message' => 'Summary generated using local algorithm'
                ];
            }
            
            throw new Exception('Quota exceeded for all summarization services');
            
        } catch (Exception $e) {
            Log::error('Summarization failed for article ' . $articleId . ': ' . $e->getMessage());
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'source' => 'error',
                'message' => 'Failed to generate summary'
            ];
        }
    }

    /**
     * Generate summary using AI service
     */
    private function generateAiSummary(int $articleId, int $userId, string $content, array $options): Summary
    {
        // Create processing record
        $summary = Summary::create([
            'article_id' => $articleId,
            'user_id' => $userId,
            'content' => '',
            'word_count' => 0,
            'type' => 'ai_generated',
            'source' => 'gemini',
            'status' => 'processing',
            'processing_started_at' => now(),
            'cache_key' => $this->generateCacheKey($articleId, $userId, $content, 'gemini')
        ]);
        
        try {
            // Generate summary
            $aiResult = $this->geminiService->generateSummary(
                $content,
                $options['max_words'],
                $options['language']
            );
            
            // Update summary
            $summary->update([
                'content' => $aiResult['summary'],
                'word_count' => str_word_count($aiResult['summary']),
                'key_points' => $aiResult['key_points'],
                'status' => 'completed',
                'processing_completed_at' => now(),
                'processing_time_ms' => $this->calculateProcessingTime($summary->processing_started_at)
            ]);
            
            // Record quota usage
            $this->quotaService->recordGeminiUsage(
                $userId,
                $aiResult['tokens_used'] ?? strlen($content),
                [
                    'article_id' => $articleId,
                    'summary_id' => $summary->id,
                    'confidence' => 0.9
                ]
            );
            
            return $summary;
            
        } catch (Exception $e) {
            $summary->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'processing_completed_at' => now(),
                'processing_time_ms' => $this->calculateProcessingTime($summary->processing_started_at)
            ]);
            
            throw $e;
        }
    }

    /**
     * Generate summary using local service
     */
    private function generateLocalSummary(int $articleId, int $userId, string $content, array $options): Summary
    {
        // Create processing record
        $summary = Summary::create([
            'article_id' => $articleId,
            'user_id' => $userId,
            'content' => '',
            'word_count' => 0,
            'type' => 'ai_generated',
            'source' => 'local',
            'status' => 'processing',
            'processing_started_at' => now(),
            'cache_key' => $this->generateCacheKey($articleId, $userId, $content, 'local')
        ]);
        
        try {
            // Generate summary
            $localResult = $this->localService->generateSummary(
                $content,
                $options['max_words'],
                $options['language']
            );
            
            // Update summary
            $summary->update([
                'content' => $localResult['summary'],
                'word_count' => str_word_count($localResult['summary']),
                'key_points' => $localResult['key_points'],
                'status' => 'completed',
                'processing_completed_at' => now(),
                'processing_time_ms' => $this->calculateProcessingTime($summary->processing_started_at)
            ]);
            
            // Record quota usage
            $this->quotaService->recordLocalUsage(
                $userId,
                strlen($content),
                [
                    'article_id' => $articleId,
                    'summary_id' => $summary->id,
                    'method' => $localResult['method'] ?? 'extractive',
                    'confidence' => $localResult['confidence'] ?? 0.7
                ]
            );
            
            return $summary;
            
        } catch (Exception $e) {
            $summary->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'processing_completed_at' => now(),
                'processing_time_ms' => $this->calculateProcessingTime($summary->processing_started_at)
            ]);
            
            throw $e;
        }
    }

    /**
     * Check for existing summary
     */
    private function getExistingSummary(int $articleId, int $userId, string $content): ?Summary
    {
        $cacheKeys = [
            $this->generateCacheKey($articleId, $userId, $content, 'local'),
            $this->generateCacheKey($articleId, $userId, $content, 'gemini'),
            $this->generateCacheKey($articleId, $userId, $content, 'any'),
        ];
        
        return Summary::where('article_id', $articleId)
            ->where('user_id', $userId)
            ->where('status', 'completed')
            ->where(function($query) use ($cacheKeys) {
                $query->whereIn('cache_key', $cacheKeys)
                      ->orWhere('source', 'manual');
            })
            ->where('updated_at', '>', now()->subHours(24))
            ->first();
    }

    /**
     * Prepare content from article
     */
    private function prepareContent(Article $article): string
    {
        $content = '';
        
        if ($article->title) {
            $content .= $article->title . '. ';
        }
        
        if ($article->content) {
            $content .= strip_tags($article->content);
        }
        
        return trim($content);
    }

    /**
     * Generate cache key
     */
    private function generateCacheKey(int $articleId, int $userId, string $content, string $source): string
    {
        $contentHash = md5($content);
        return "summary:{$articleId}:{$userId}:{$contentHash}:{$source}";
    }

    /**
     * Calculate processing time in milliseconds
     */
    private function calculateProcessingTime($startTime): int
    {
        return intval((microtime(true) - strtotime($startTime)) * 1000);
    }

    /**
     * Get quota status for user
     */
    public function getQuotaStatus(int $userId): array
    {
        return $this->quotaService->getQuotaStatus($userId);
    }

    /**
     * Get usage statistics
     */
    public function getUsageStats(int $userId, int $days = 7): array
    {
        return $this->quotaService->getUsageStats($userId, $days);
    }
}
