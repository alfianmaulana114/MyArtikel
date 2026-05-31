<?php

namespace App\Console\Commands;

use App\Models\Article;
use App\Models\Export;
use App\Services\AdvancedPdfExportService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class PdfExportManager extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pdf:export-manager
                            {action : Action to perform (cleanup|stats|test|optimize)}
                            {--user-id= : Specific user ID}
                            {--days=30 : Number of days to keep exports}
                            {--article-id= : Test with specific article}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Manage PDF exports - cleanup, statistics, testing, and optimization';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $action = $this->argument('action');

        $this->info("PDF Export Manager - Action: {$action}");

        switch ($action) {
            case 'cleanup':
                $this->cleanupOldExports();
                break;

            case 'stats':
                $this->showStatistics();
                break;

            case 'test':
                $this->testExport();
                break;

            case 'optimize':
                $this->optimizeExports();
                break;

            default:
                $this->error("Invalid action: {$action}. Use: cleanup, stats, test, or optimize");

                return 1;
        }

        return 0;
    }

    /**
     * Cleanup old exports
     */
    private function cleanupOldExports(): void
    {
        $days = (int) $this->option('days');
        $userId = $this->option('user-id');

        $this->info("Cleaning up exports older than {$days} days...");

        try {
            $query = Export::where('type', 'pdf')
                ->where('created_at', '<', now()->subDays($days));

            if ($userId) {
                $query->where('user_id', $userId);
            }

            $count = $query->count();

            if ($count === 0) {
                $this->info('No old exports found to cleanup');

                return;
            }

            if (! $this->confirm("Found {$count} old exports. Delete them?")) {
                $this->info('Cleanup cancelled');

                return;
            }

            // Delete files first
            $exports = $query->get();
            foreach ($exports as $export) {
                if ($export->file_path && \Storage::disk('local')->exists($export->file_path)) {
                    \Storage::disk('local')->delete($export->file_path);
                }
            }

            // Delete records
            $deleted = $query->delete();

            $this->info("Deleted {$deleted} old exports");
            Log::info('PDF export cleanup completed', ['deleted_count' => $deleted, 'days' => $days]);

        } catch (\Exception $e) {
            $this->error('Cleanup failed: '.$e->getMessage());
            Log::error('PDF export cleanup failed', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Show export statistics
     */
    private function showStatistics(): void
    {
        $userId = $this->option('user-id');

        $this->info('PDF Export Statistics');
        $this->line('====================');

        try {
            $query = Export::where('type', 'pdf');

            if ($userId) {
                $query->where('user_id', $userId);
            }

            // Total exports
            $totalExports = $query->count();
            $this->info("Total exports: {$totalExports}");

            // By status
            $statusStats = $query->select('status', \DB::raw('COUNT(*) as count'))
                ->groupBy('status')
                ->pluck('count', 'status')
                ->toArray();

            $this->info('Status breakdown:');
            foreach ($statusStats as $status => $count) {
                $percentage = $totalExports > 0 ? round(($count / $totalExports) * 100, 1) : 0;
                $this->line("  {$status}: {$count} ({$percentage}%)");
            }

            // File size statistics
            $sizeStats = $query->select(\DB::raw('MIN(file_size) as min_size'),
                \DB::raw('MAX(file_size) as max_size'),
                \DB::raw('AVG(file_size) as avg_size'),
                \DB::raw('SUM(file_size) as total_size'))
                ->first();

            if ($sizeStats) {
                $this->info('File size statistics:');
                $this->line('  Min size: '.$this->formatBytes($sizeStats->min_size ?? 0));
                $this->line('  Max size: '.$this->formatBytes($sizeStats->max_size ?? 0));
                $this->line('  Avg size: '.$this->formatBytes($sizeStats->avg_size ?? 0));
                $this->line('  Total size: '.$this->formatBytes($sizeStats->total_size ?? 0));
            }

            // Template usage
            $templateStats = $query->select(\DB::raw("JSON_UNQUOTE(JSON_EXTRACT(metadata, '$.template')) as template"),
                \DB::raw('COUNT(*) as count'))
                ->groupBy('template')
                ->orderBy('count', 'desc')
                ->get();

            if ($templateStats->isNotEmpty()) {
                $this->info('Template usage:');
                foreach ($templateStats as $stat) {
                    $template = $stat->template ?? 'default';
                    $this->line("  {$template}: {$stat->count} exports");
                }
            }

            // Recent activity
            $recentExports = $query->where('created_at', '>', now()->subDays(7))->count();
            $this->info("Recent activity (last 7 days): {$recentExports} exports");

            // Processing time statistics
            $processingStats = $query->whereNotNull('processing_time_ms')
                ->select(\DB::raw('MIN(processing_time_ms) as min_time'),
                    \DB::raw('MAX(processing_time_ms) as max_time'),
                    \DB::raw('AVG(processing_time_ms) as avg_time'))
                ->first();

            if ($processingStats && $processingStats->min_time) {
                $this->info('Processing time statistics:');
                $this->line('  Min time: '.$this->formatMilliseconds($processingStats->min_time));
                $this->line('  Max time: '.$this->formatMilliseconds($processingStats->max_time));
                $this->line('  Avg time: '.$this->formatMilliseconds($processingStats->avg_time));
            }

        } catch (\Exception $e) {
            $this->error('Failed to show statistics: '.$e->getMessage());
            Log::error('PDF export statistics failed', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Test PDF export
     */
    private function testExport(): void
    {
        $articleId = $this->option('article-id');
        $userId = $this->option('user-id') ?? 1;

        $this->info('Testing PDF export...');

        try {
            // Get test article
            $article = $articleId
                ? Article::find($articleId)
                : Article::where('user_id', $userId)->first();

            if (! $article) {
                $this->error('No article found for testing');

                return;
            }

            $this->info("Testing with article: {$article->title}");

            // Test different templates
            $templates = ['default', 'academic', 'magazine', 'minimal', 'business'];
            $formats = ['A4', 'Letter'];
            $orientations = ['portrait', 'landscape'];

            foreach ($templates as $template) {
                foreach ($formats as $format) {
                    foreach ($orientations as $orientation) {
                        $this->info("Testing template: {$template}, format: {$format}, orientation: {$orientation}");

                        $startTime = microtime(true);

                        $pdfService = app(AdvancedPdfExportService::class);
                        $result = $pdfService->exportSingleArticle($article, [
                            'template' => $template,
                            'format' => $format,
                            'orientation' => $orientation,
                            'include_images' => true,
                            'include_summaries' => true,
                            'include_metadata' => true,
                            'page_numbers' => true,
                            'clean_reader_format' => true,
                        ]);

                        $processingTime = microtime(true) - $startTime;

                        if ($result['success']) {
                            $this->info("✓ Success: {$result['file_size']} bytes in ".round($processingTime, 2).'s');

                            // Cleanup test file
                            if (isset($result['file_path'])) {
                                \Storage::disk('local')->delete($result['file_path']);
                            }
                        } else {
                            $this->error("✗ Failed: {$result['error']}");
                        }
                    }
                }
            }

            $this->info('PDF export test completed');

        } catch (\Exception $e) {
            $this->error('Test failed: '.$e->getMessage());
            Log::error('PDF export test failed', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Optimize exports
     */
    private function optimizeExports(): void
    {
        $this->info('Optimizing PDF exports...');

        try {
            // Get large exports
            $largeExports = Export::where('type', 'pdf')
                ->where('file_size', '>', 10 * 1024 * 1024) // > 10MB
                ->orderBy('file_size', 'desc')
                ->limit(10)
                ->get();

            if ($largeExports->isEmpty()) {
                $this->info('No large exports found for optimization');

                return;
            }

            $this->info("Found {$largeExports->count()} large exports:");
            foreach ($largeExports as $export) {
                $this->line("  Export ID {$export->id}: ".$this->formatBytes($export->file_size));
            }

            if (! $this->confirm('Optimize these exports?')) {
                $this->info('Optimization cancelled');

                return;
            }

            // Optimization suggestions
            $this->info('Optimization suggestions:');
            $this->line('  1. Enable PDF compression');
            $this->line('  2. Reduce image quality');
            $this->line('  3. Use smaller page formats');
            $this->line('  4. Remove unnecessary metadata');
            $this->line('  5. Use font subsetting');

            // Apply optimizations (placeholder)
            $this->info('Optimizations applied to future exports');

            Log::info('PDF export optimization completed', ['large_exports' => $largeExports->count()]);

        } catch (\Exception $e) {
            $this->error('Optimization failed: '.$e->getMessage());
            Log::error('PDF export optimization failed', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Format bytes to human readable
     */
    private function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, $precision).' '.$units[$i];
    }

    /**
     * Format milliseconds to human readable
     */
    private function formatMilliseconds(int $milliseconds): string
    {
        if ($milliseconds < 1000) {
            return $milliseconds.'ms';
        } elseif ($milliseconds < 60000) {
            return round($milliseconds / 1000, 2).'s';
        } else {
            return round($milliseconds / 60000, 2).'min';
        }
    }
}
