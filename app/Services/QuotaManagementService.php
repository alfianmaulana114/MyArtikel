<?php

namespace App\Services;

use App\Models\SummaryQuotaTracking;
use Carbon\Carbon;
use Exception;

class QuotaManagementService
{
    private int $dailyGeminiLimit;

    private int $dailyLocalLimit;

    private int $maxTokensPerRequest;

    public function __construct()
    {
        $this->dailyGeminiLimit = config('services.quota.gemini_daily_limit', 100);
        $this->dailyLocalLimit = config('services.quota.local_daily_limit', 1000);
        $this->maxTokensPerRequest = config('services.quota.max_tokens_per_request', 8000);
    }

    /**
     * Check if user can use Gemini API
     */
    public function canUseGemini(int $userId, int $estimatedTokens = 1000): bool
    {
        try {
            $today = Carbon::today();

            $quota = SummaryQuotaTracking::firstOrCreate([
                'user_id' => $userId,
                'service' => 'gemini',
                'quota_date' => $today,
            ], [
                'requests_count' => 0,
                'tokens_used' => 0,
            ]);

            // Check daily request limit
            if ($quota->requests_count >= $this->dailyGeminiLimit) {
                return false;
            }

            // Check daily token limit
            if (($quota->tokens_used + $estimatedTokens) > $this->maxTokensPerRequest * 10) {
                return false;
            }

            return true;

        } catch (Exception $e) {
            // If quota tracking fails, allow usage but log the error
            \Log::error('Quota check failed for user '.$userId.': '.$e->getMessage());

            return true; // Fail open for better user experience
        }
    }

    /**
     * Check if user can use local summarization
     */
    public function canUseLocal(int $userId): bool
    {
        try {
            $today = Carbon::today();

            $quota = SummaryQuotaTracking::firstOrCreate([
                'user_id' => $userId,
                'service' => 'local',
                'quota_date' => $today,
            ], [
                'requests_count' => 0,
                'tokens_used' => 0,
            ]);

            return $quota->requests_count < $this->dailyLocalLimit;

        } catch (Exception $e) {
            \Log::error('Local quota check failed for user '.$userId.': '.$e->getMessage());

            return true; // Fail open
        }
    }

    /**
     * Record Gemini API usage
     */
    public function recordGeminiUsage(int $userId, int $tokensUsed, array $metadata = []): void
    {
        try {
            $today = Carbon::today();

            $quota = SummaryQuotaTracking::firstOrCreate([
                'user_id' => $userId,
                'service' => 'gemini',
                'quota_date' => $today,
            ], [
                'requests_count' => 0,
                'tokens_used' => 0,
            ]);

            $quota->increment('requests_count');
            $quota->increment('tokens_used', $tokensUsed);

            if (! empty($metadata)) {
                $quota->update(['metadata' => $metadata]);
            }

        } catch (Exception $e) {
            \Log::error('Failed to record Gemini usage for user '.$userId.': '.$e->getMessage());
        }
    }

    /**
     * Record local summarization usage
     */
    public function recordLocalUsage(int $userId, int $contentLength, array $metadata = []): void
    {
        try {
            $today = Carbon::today();

            $quota = SummaryQuotaTracking::firstOrCreate([
                'user_id' => $userId,
                'service' => 'local',
                'quota_date' => $today,
            ], [
                'requests_count' => 0,
                'tokens_used' => 0,
            ]);

            $quota->increment('requests_count');
            $quota->increment('tokens_used', intval($contentLength / 4));

            if (! empty($metadata)) {
                $quota->update(['metadata' => $metadata]);
            }

        } catch (Exception $e) {
            \Log::error('Failed to record local usage for user '.$userId.': '.$e->getMessage());
        }
    }

    /**
     * Get quota status for user
     */
    public function getQuotaStatus(int $userId): array
    {
        try {
            $today = Carbon::today();

            $geminiQuota = SummaryQuotaTracking::where([
                'user_id' => $userId,
                'service' => 'gemini',
                'quota_date' => $today,
            ])->first();

            $localQuota = SummaryQuotaTracking::where([
                'user_id' => $userId,
                'service' => 'local',
                'quota_date' => $today,
            ])->first();

            return [
                'gemini' => [
                    'used_today' => $geminiQuota->requests_count ?? 0,
                    'limit' => $this->dailyGeminiLimit,
                    'remaining' => max(0, $this->dailyGeminiLimit - ($geminiQuota->requests_count ?? 0)),
                    'tokens_used' => $geminiQuota->tokens_used ?? 0,
                    'can_use' => $this->canUseGemini($userId),
                ],
                'local' => [
                    'used_today' => $localQuota->requests_count ?? 0,
                    'limit' => $this->dailyLocalLimit,
                    'remaining' => max(0, $this->dailyLocalLimit - ($localQuota->requests_count ?? 0)),
                    'tokens_used' => $localQuota->tokens_used ?? 0,
                    'can_use' => $this->canUseLocal($userId),
                ],
            ];
        } catch (Exception $e) {
            \Log::error('Failed to get quota status for user '.$userId.': '.$e->getMessage());

            return [
                'gemini' => [
                    'used_today' => 0,
                    'limit' => $this->dailyGeminiLimit,
                    'remaining' => $this->dailyGeminiLimit,
                    'tokens_used' => 0,
                    'can_use' => true,
                ],
                'local' => [
                    'used_today' => 0,
                    'limit' => $this->dailyLocalLimit,
                    'remaining' => $this->dailyLocalLimit,
                    'tokens_used' => 0,
                    'can_use' => true,
                ],
            ];
        }
    }

    /**
     * Get quota usage statistics
     */
    public function getUsageStats(int $userId, int $days = 7): array
    {
        $startDate = Carbon::today()->subDays($days - 1);

        $stats = SummaryQuotaTracking::where('user_id', $userId)
            ->where('quota_date', '>=', $startDate)
            ->orderBy('quota_date', 'desc')
            ->get()
            ->groupBy('service')
            ->map(function ($records) {
                return $records->map(function ($record) {
                    return [
                        'date' => $record->quota_date->format('Y-m-d'),
                        'requests' => $record->requests_count,
                        'tokens' => $record->tokens_used,
                    ];
                });
            });

        return [
            'period_days' => $days,
            'gemini' => $stats->get('gemini', collect())->toArray(),
            'local' => $stats->get('local', collect())->toArray(),
        ];
    }

    /**
     * Reset quota for user (admin function)
     */
    public function resetQuota(int $userId, ?string $service = null): void
    {
        $query = SummaryQuotaTracking::where('user_id', $userId);

        if ($service) {
            $query->where('service', $service);
        }

        $query->delete();
    }

    /**
     * Get system-wide quota statistics
     */
    public function getSystemStats(int $days = 30): array
    {
        $startDate = Carbon::today()->subDays($days - 1);

        $stats = SummaryQuotaTracking::where('quota_date', '>=', $startDate)
            ->selectRaw('service, SUM(requests_count) as total_requests, SUM(tokens_used) as total_tokens')
            ->groupBy('service')
            ->get()
            ->pluck('total_requests', 'service')
            ->toArray();

        return [
            'period_days' => $days,
            'total_gemini_requests' => $stats['gemini'] ?? 0,
            'total_local_requests' => $stats['local'] ?? 0,
            'total_tokens_used' => SummaryQuotaTracking::where('quota_date', '>=', $startDate)->sum('tokens_used'),
        ];
    }
}
