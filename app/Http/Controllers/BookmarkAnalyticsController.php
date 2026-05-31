<?php

namespace App\Http\Controllers;

use App\Models\Bookmark;
use App\Models\BookmarkAnalytics;
use App\Models\BookmarkCategory;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class BookmarkAnalyticsController extends Controller
{
    /**
     * Get bookmark analytics overview.
     */
    public function overview(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'period' => 'nullable|in:7d,30d,90d,1y,all',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after:start_date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $userId = auth()->id();
        $dateRange = $this->getDateRange($request);

        $stats = [
            'total_bookmarks' => Bookmark::forUser($userId)->count(),
            'total_read' => Bookmark::forUser($userId)->read()->count(),
            'total_favorites' => Bookmark::forUser($userId)->favorites()->count(),
            'total_archived' => Bookmark::forUser($userId)->archived()->count(),
            'reading_rate' => $this->calculateReadingRate($userId, $dateRange),
            'average_bookmarks_per_week' => $this->calculateAverageBookmarksPerWeek($userId),
            'most_active_day' => $this->getMostActiveDay($userId, $dateRange),
            'category_distribution' => $this->getCategoryDistribution($userId),
            'recent_activity' => $this->getRecentActivity($userId, 10),
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'overview' => $stats,
                'period' => $dateRange,
                'generated_at' => now()->toISOString(),
            ],
        ]);
    }

    /**
     * Get reading statistics.
     */
    public function readingStats(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'period' => 'nullable|in:7d,30d,90d,1y,all',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after:start_date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $userId = auth()->id();
        $dateRange = $this->getDateRange($request);

        $stats = [
            'total_reading_time' => $this->calculateTotalReadingTime($userId, $dateRange),
            'average_reading_time' => $this->calculateAverageReadingTime($userId),
            'articles_read' => Bookmark::forUser($userId)->read()->whereBetween('read_at', $dateRange)->count(),
            'reading_streak' => $this->calculateReadingStreak($userId),
            'longest_streak' => $this->getLongestReadingStreak($userId),
            'daily_reading_goal' => $this->getDailyReadingGoal($userId),
            'goal_completion_rate' => $this->calculateGoalCompletionRate($userId, $dateRange),
            'reading_velocity' => $this->calculateReadingVelocity($userId, $dateRange),
            'preferred_reading_times' => $this->getPreferredReadingTimes($userId, $dateRange),
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'reading_stats' => $stats,
                'period' => $dateRange,
            ],
        ]);
    }

    /**
     * Get category statistics.
     */
    public function categoryStats(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'period' => 'nullable|in:7d,30d,90d,1y,all',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after:start_date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $userId = auth()->id();
        $dateRange = $this->getDateRange($request);

        $categories = BookmarkCategory::forUser($userId)
            ->withCount(['bookmarks', 'bookmarks as read_bookmarks_count' => function ($query) use ($dateRange) {
                $query->read()->whereBetween('read_at', $dateRange);
            }])
            ->get()
            ->map(function ($category) use ($dateRange) {
                return [
                    'id' => $category->id,
                    'name' => $category->name,
                    'color' => $category->color,
                    'total_bookmarks' => $category->bookmarks_count,
                    'read_bookmarks' => $category->read_bookmarks_count,
                    'unread_bookmarks' => $category->bookmarks_count - $category->read_bookmarks_count,
                    'completion_rate' => $category->bookmarks_count > 0
                        ? round(($category->read_bookmarks_count / $category->bookmarks_count) * 100, 2)
                        : 0,
                    'average_reading_time' => $this->calculateAverageReadingTimeForCategory($category->id, $dateRange),
                    'most_bookmarked_topic' => $this->getMostBookmarkedTopicInCategory($category->id, $dateRange),
                ];
            });

        return response()->json([
            'success' => true,
            'data' => [
                'category_stats' => $categories,
                'period' => $dateRange,
            ],
        ]);
    }

    /**
     * Get device statistics.
     */
    public function deviceStats(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'period' => 'nullable|in:7d,30d,90d,1y,all',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after:start_date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $userId = auth()->id();
        $dateRange = $this->getDateRange($request);

        $deviceStats = BookmarkAnalytics::forUser($userId)
            ->whereBetween('occurred_at', $dateRange)
            ->select('device_id', DB::raw('COUNT(*) as action_count'))
            ->groupBy('device_id')
            ->get()
            ->map(function ($stat) use ($userId, $dateRange) {
                return [
                    'device_id' => $stat->device_id,
                    'action_count' => $stat->action_count,
                    'bookmarks_created' => $this->getBookmarksCreatedOnDevice($userId, $stat->device_id, $dateRange),
                    'bookmarks_read' => $this->getBookmarksReadOnDevice($userId, $stat->device_id, $dateRange),
                    'most_used_browser' => $this->getMostUsedBrowser($stat->device_id, $dateRange),
                ];
            });

        return response()->json([
            'success' => true,
            'data' => [
                'device_stats' => $deviceStats,
                'period' => $dateRange,
            ],
        ]);
    }

    /**
     * Get bookmark trends over time.
     */
    public function trends(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'period' => 'nullable|in:7d,30d,90d,1y',
            'granularity' => 'nullable|in:daily,weekly,monthly',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $userId = auth()->id();
        $period = $request->get('period', '30d');
        $granularity = $request->get('granularity', 'daily');

        $dateRange = $this->getDateRangeFromPeriod($period);
        $trends = $this->calculateTrends($userId, $dateRange, $granularity);

        return response()->json([
            'success' => true,
            'data' => [
                'trends' => $trends,
                'period' => $dateRange,
                'granularity' => $granularity,
            ],
        ]);
    }

    /**
     * Export analytics data.
     */
    public function export(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'format' => 'required|in:json,csv,pdf',
            'period' => 'nullable|in:7d,30d,90d,1y,all',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after:start_date',
            'include_categories' => 'nullable|boolean',
            'include_devices' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $userId = auth()->id();
        $dateRange = $this->getDateRange($request);
        $format = $request->get('format', 'json');

        $exportData = $this->prepareExportData(
            $userId,
            $dateRange,
            $request->get('include_categories', true),
            $request->get('include_devices', true)
        );

        // Here you would implement the actual export logic
        // For now, return the data that would be exported
        return response()->json([
            'success' => true,
            'data' => [
                'export_data' => $exportData,
                'format' => $format,
                'generated_at' => now()->toISOString(),
            ],
        ]);
    }

    /**
     * Get date range based on request parameters.
     */
    private function getDateRange(Request $request): array
    {
        if ($request->start_date && $request->end_date) {
            return [
                Carbon::parse($request->start_date)->startOfDay(),
                Carbon::parse($request->end_date)->endOfDay(),
            ];
        }

        $period = $request->get('period', '30d');

        return $this->getDateRangeFromPeriod($period);
    }

    /**
     * Get date range from period string.
     */
    private function getDateRangeFromPeriod(string $period): array
    {
        $endDate = now();

        switch ($period) {
            case '7d':
                $startDate = now()->subDays(7);
                break;
            case '30d':
                $startDate = now()->subDays(30);
                break;
            case '90d':
                $startDate = now()->subDays(90);
                break;
            case '1y':
                $startDate = now()->subYear();
                break;
            case 'all':
                $startDate = Bookmark::forUser(auth()->id())->oldest()->first()?->created_at ?? now();
                break;
            default:
                $startDate = now()->subDays(30);
        }

        return [$startDate, $endDate];
    }

    /**
     * Calculate reading rate.
     */
    private function calculateReadingRate(int $userId, array $dateRange): float
    {
        $totalBookmarks = Bookmark::forUser($userId)->count();
        $readBookmarks = Bookmark::forUser($userId)->read()->whereBetween('read_at', $dateRange)->count();

        return $totalBookmarks > 0 ? round(($readBookmarks / $totalBookmarks) * 100, 2) : 0;
    }

    /**
     * Calculate average bookmarks per week.
     */
    private function calculateAverageBookmarksPerWeek(int $userId): float
    {
        $totalBookmarks = Bookmark::forUser($userId)->count();
        $weeksSinceFirstBookmark = Bookmark::forUser($userId)->oldest()->first()?->created_at->diffInWeeks(now()) ?? 1;

        return round($totalBookmarks / max(1, $weeksSinceFirstBookmark), 2);
    }

    /**
     * Get most active day.
     */
    private function getMostActiveDay(int $userId, array $dateRange): array
    {
        $activity = BookmarkAnalytics::forUser($userId)
            ->whereBetween('occurred_at', $dateRange)
            ->select(DB::raw('DAYOFWEEK(occurred_at) as day_of_week'), DB::raw('COUNT(*) as count'))
            ->groupBy('day_of_week')
            ->orderByDesc('count')
            ->first();

        $days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

        return [
            'day' => $activity ? $days[$activity->day_of_week - 1] : 'Unknown',
            'activity_count' => $activity?->count ?? 0,
        ];
    }

    /**
     * Get category distribution.
     */
    private function getCategoryDistribution(int $userId): array
    {
        return BookmarkCategory::forUser($userId)
            ->withCount('bookmarks')
            ->get()
            ->map(function ($category) {
                return [
                    'name' => $category->name,
                    'count' => $category->bookmarks_count,
                    'color' => $category->color,
                ];
            })
            ->toArray();
    }

    /**
     * Get recent activity.
     */
    private function getRecentActivity(int $userId, int $limit = 10): array
    {
        return BookmarkAnalytics::forUser($userId)
            ->with(['bookmark.article'])
            ->latest('occurred_at')
            ->limit($limit)
            ->get()
            ->map(function ($activity) {
                return [
                    'action' => $activity->action,
                    'article_title' => $activity->bookmark->article->title ?? 'Unknown',
                    'occurred_at' => $activity->occurred_at->diffForHumans(),
                ];
            })
            ->toArray();
    }

    /**
     * Calculate total reading time.
     */
    private function calculateTotalReadingTime(int $userId, array $dateRange): int
    {
        // This is a simplified calculation - you might want to implement more sophisticated tracking
        $readBookmarks = Bookmark::forUser($userId)
            ->read()
            ->whereBetween('read_at', $dateRange)
            ->with('article')
            ->get();

        return $readBookmarks->sum(function ($bookmark) {
            return $bookmark->article->reading_time ?? 5; // Default 5 minutes
        });
    }

    /**
     * Calculate average reading time.
     */
    private function calculateAverageReadingTime(int $userId): float
    {
        $bookmarks = Bookmark::forUser($userId)
            ->read()
            ->with('article')
            ->get();

        if ($bookmarks->isEmpty()) {
            return 0;
        }

        $totalTime = $bookmarks->sum(function ($bookmark) {
            return $bookmark->article->reading_time ?? 5;
        });

        return round($totalTime / $bookmarks->count(), 2);
    }

    /**
     * Calculate reading streak.
     */
    private function calculateReadingStreak(int $userId): int
    {
        $streak = 0;
        $currentDate = now()->startOfDay();

        while (true) {
            $hasReadingActivity = Bookmark::forUser($userId)
                ->read()
                ->whereDate('read_at', $currentDate)
                ->exists();

            if ($hasReadingActivity) {
                $streak++;
                $currentDate->subDay();
            } else {
                break;
            }
        }

        return $streak;
    }

    /**
     * Get longest reading streak.
     */
    private function getLongestReadingStreak(int $userId): int
    {
        // This is a simplified implementation
        // You might want to implement a more sophisticated algorithm
        return $this->calculateReadingStreak($userId); // Placeholder
    }

    /**
     * Get daily reading goal.
     */
    private function getDailyReadingGoal(int $userId): int
    {
        // This could be user-configurable, for now return a default
        return 3; // 3 articles per day
    }

    /**
     * Calculate goal completion rate.
     */
    private function calculateGoalCompletionRate(int $userId, array $dateRange): float
    {
        $dailyGoal = $this->getDailyReadingGoal($userId);
        $daysInRange = Carbon::parse($dateRange[0])->diffInDays($dateRange[1]) + 1;
        $totalGoal = $dailyGoal * $daysInRange;

        $actualReading = Bookmark::forUser($userId)
            ->read()
            ->whereBetween('read_at', $dateRange)
            ->count();

        return $totalGoal > 0 ? round(($actualReading / $totalGoal) * 100, 2) : 0;
    }

    /**
     * Calculate reading velocity.
     */
    private function calculateReadingVelocity(int $userId, array $dateRange): float
    {
        $articlesRead = Bookmark::forUser($userId)
            ->read()
            ->whereBetween('read_at', $dateRange)
            ->count();

        $daysInRange = Carbon::parse($dateRange[0])->diffInDays($dateRange[1]) + 1;

        return round($articlesRead / $daysInRange, 2);
    }

    /**
     * Get preferred reading times.
     */
    private function getPreferredReadingTimes(int $userId, array $dateRange): array
    {
        $hourlyActivity = Bookmark::forUser($userId)
            ->read()
            ->whereBetween('read_at', $dateRange)
            ->select(DB::raw('HOUR(read_at) as hour'), DB::raw('COUNT(*) as count'))
            ->groupBy('hour')
            ->orderByDesc('count')
            ->limit(3)
            ->get();

        return $hourlyActivity->map(function ($activity) {
            return [
                'hour' => $activity->hour,
                'time_range' => sprintf('%02d:00-%02d:00', $activity->hour, $activity->hour + 1),
                'count' => $activity->count,
            ];
        })->toArray();
    }

    /**
     * Calculate average reading time for category.
     */
    private function calculateAverageReadingTimeForCategory(int $categoryId, array $dateRange): float
    {
        $bookmarks = Bookmark::where('category_id', $categoryId)
            ->read()
            ->whereBetween('read_at', $dateRange)
            ->with('article')
            ->get();

        if ($bookmarks->isEmpty()) {
            return 0;
        }

        $totalTime = $bookmarks->sum(function ($bookmark) {
            return $bookmark->article->reading_time ?? 5;
        });

        return round($totalTime / $bookmarks->count(), 2);
    }

    /**
     * Get most bookmarked topic in category.
     */
    private function getMostBookmarkedTopicInCategory(int $categoryId, array $dateRange): ?string
    {
        // This would require additional topic/tag analysis
        // For now, return null as placeholder
        return null;
    }

    /**
     * Get bookmarks created on device.
     */
    private function getBookmarksCreatedOnDevice(int $userId, string $deviceId, array $dateRange): int
    {
        return Bookmark::forUser($userId)
            ->where('source_device', $deviceId)
            ->whereBetween('created_at', $dateRange)
            ->count();
    }

    /**
     * Get bookmarks read on device.
     */
    private function getBookmarksReadOnDevice(int $userId, string $deviceId, array $dateRange): int
    {
        return BookmarkAnalytics::forUser($userId)
            ->forDevice($deviceId)
            ->action('read')
            ->whereBetween('occurred_at', $dateRange)
            ->count();
    }

    /**
     * Get most used browser.
     */
    private function getMostUsedBrowser(string $deviceId, array $dateRange): ?string
    {
        $browser = BookmarkAnalytics::forDevice($deviceId)
            ->whereBetween('occurred_at', $dateRange)
            ->select('user_agent', DB::raw('COUNT(*) as count'))
            ->groupBy('user_agent')
            ->orderByDesc('count')
            ->first();

        return $browser?->user_agent;
    }

    /**
     * Calculate trends over time.
     */
    private function calculateTrends(int $userId, array $dateRange, string $granularity): array
    {
        $trends = [];
        $currentDate = Carbon::parse($dateRange[0]);
        $endDate = Carbon::parse($dateRange[1]);

        while ($currentDate <= $endDate) {
            $periodStart = $currentDate->copy();

            switch ($granularity) {
                case 'daily':
                    $periodEnd = $currentDate->copy()->endOfDay();
                    $label = $currentDate->format('Y-m-d');
                    $currentDate->addDay();
                    break;
                case 'weekly':
                    $periodEnd = $currentDate->copy()->endOfWeek();
                    $label = $currentDate->format('Y-W');
                    $currentDate->addWeek();
                    break;
                case 'monthly':
                    $periodEnd = $currentDate->copy()->endOfMonth();
                    $label = $currentDate->format('Y-m');
                    $currentDate->addMonth();
                    break;
                default:
                    $periodEnd = $currentDate->copy()->endOfDay();
                    $label = $currentDate->format('Y-m-d');
                    $currentDate->addDay();
            }

            $bookmarksCreated = Bookmark::forUser($userId)
                ->whereBetween('created_at', [$periodStart, $periodEnd])
                ->count();

            $bookmarksRead = Bookmark::forUser($userId)
                ->read()
                ->whereBetween('read_at', [$periodStart, $periodEnd])
                ->count();

            $trends[] = [
                'period' => $label,
                'bookmarks_created' => $bookmarksCreated,
                'bookmarks_read' => $bookmarksRead,
                'timestamp' => $periodStart->toISOString(),
            ];
        }

        return $trends;
    }

    /**
     * Prepare export data.
     */
    private function prepareExportData(int $userId, array $dateRange, bool $includeCategories, bool $includeDevices): array
    {
        $data = [
            'overview' => $this->getOverviewData($userId, $dateRange),
            'reading_stats' => $this->getReadingStatsData($userId, $dateRange),
            'generated_at' => now()->toISOString(),
            'period' => [
                'start' => $dateRange[0]->toISOString(),
                'end' => $dateRange[1]->toISOString(),
            ],
        ];

        if ($includeCategories) {
            $data['category_stats'] = $this->getCategoryStatsData($userId, $dateRange);
        }

        if ($includeDevices) {
            $data['device_stats'] = $this->getDeviceStatsData($userId, $dateRange);
        }

        return $data;
    }

    /**
     * Get overview data for export.
     */
    private function getOverviewData(int $userId, array $dateRange): array
    {
        return [
            'total_bookmarks' => Bookmark::forUser($userId)->count(),
            'total_read' => Bookmark::forUser($userId)->read()->count(),
            'total_favorites' => Bookmark::forUser($userId)->favorites()->count(),
            'reading_rate' => $this->calculateReadingRate($userId, $dateRange),
        ];
    }

    /**
     * Get reading stats data for export.
     */
    private function getReadingStatsData(int $userId, array $dateRange): array
    {
        return [
            'total_reading_time' => $this->calculateTotalReadingTime($userId, $dateRange),
            'average_reading_time' => $this->calculateAverageReadingTime($userId),
            'articles_read' => Bookmark::forUser($userId)->read()->whereBetween('read_at', $dateRange)->count(),
            'reading_streak' => $this->calculateReadingStreak($userId),
        ];
    }

    /**
     * Get category stats data for export.
     */
    private function getCategoryStatsData(int $userId, array $dateRange): array
    {
        return BookmarkCategory::forUser($userId)
            ->withCount(['bookmarks', 'bookmarks as read_bookmarks_count' => function ($query) use ($dateRange) {
                $query->read()->whereBetween('read_at', $dateRange);
            }])
            ->get()
            ->map(function ($category) {
                return [
                    'name' => $category->name,
                    'total_bookmarks' => $category->bookmarks_count,
                    'read_bookmarks' => $category->read_bookmarks_count,
                    'completion_rate' => $category->bookmarks_count > 0
                        ? round(($category->read_bookmarks_count / $category->bookmarks_count) * 100, 2)
                        : 0,
                ];
            })
            ->toArray();
    }

    /**
     * Get device stats data for export.
     */
    private function getDeviceStatsData(int $userId, array $dateRange): array
    {
        return BookmarkAnalytics::forUser($userId)
            ->whereBetween('occurred_at', $dateRange)
            ->select('device_id', DB::raw('COUNT(*) as action_count'))
            ->groupBy('device_id')
            ->get()
            ->map(function ($stat) {
                return [
                    'device_id' => $stat->device_id,
                    'action_count' => $stat->action_count,
                ];
            })
            ->toArray();
    }
}
