<?php

namespace App\Jobs;

use App\Models\Article;
use App\Models\User;
use App\Services\AdvancedPdfExportService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Exception;

class ProcessPdfExport extends BaseJob
{
    private int $userId;
    private array $articleIds;
    private array $exportOptions;
    private ?string $filePath;

    /**
     * Create a new job instance.
     */
    public function __construct(int $userId, array $articleIds, array $exportOptions = [])
    {
        $this->userId = $userId;
        $this->articleIds = $articleIds;
        $this->exportOptions = array_merge([
            'format' => 'A4',
            'orientation' => 'portrait',
            'include_images' => true,
            'include_metadata' => true,
            'template' => 'default',
            'filename_prefix' => 'articles_export',
        ], $exportOptions);
        
        // Set queue for PDF export jobs
        $this->onQueue('exports');
        
        $this->setJobMetadata([
            'user_id' => $userId,
            'article_count' => count($articleIds),
            'export_options' => $this->exportOptions,
            'type' => 'pdf_export'
        ]);
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $user = User::findOrFail($this->userId);
        
        Log::info('Starting PDF export', [
            'user_id' => $this->userId,
            'article_count' => count($this->articleIds),
            'export_options' => $this->exportOptions
        ]);

        try {
            // Fetch articles with related data
            $articles = Article::with(['user', 'tags', 'summaries'])
                ->where('user_id', $this->userId)
                ->whereIn('id', $this->articleIds)
                ->orderBy('created_at', 'desc')
                ->get();

            if ($articles->isEmpty()) {
                throw new Exception('No articles found for export');
            }

            // Generate PDF
            $pdfService = app(AdvancedPdfExportService::class);
            $exportResult = $pdfService->exportMultipleArticles($articles, $this->exportOptions);

            if (!$exportResult['success']) {
                throw new Exception('PDF generation failed: ' . $exportResult['error']);
            }

            $this->filePath = $exportResult['file_path'];
            
            // Create export record
            $this->createExportRecord($exportResult);

            Log::info('PDF export completed successfully', [
                'user_id' => $this->userId,
                'file_path' => $this->filePath,
                'file_size' => Storage::disk('local')->size($this->filePath),
                'article_count' => $articles->count()
            ]);

            $this->addMetadata('file_path', $this->filePath);
            $this->addMetadata('file_size', Storage::disk('local')->size($this->filePath));
            $this->addMetadata('article_count', $articles->count());

        } catch (Exception $e) {
            Log::error('PDF export failed', [
                'user_id' => $this->userId,
                'article_ids' => $this->articleIds,
                'error' => $e->getMessage()
            ]);
            
            throw $e;
        }
    }

    /**
     * Create export record in database
     */
    private function createExportRecord(array $exportResult): void
    {
        try {
            \App\Models\Export::create([
                'user_id' => $this->userId,
                'type' => 'pdf',
                'file_path' => $exportResult['file_path'],
                'file_size' => Storage::disk('local')->size($exportResult['file_path']),
                'metadata' => [
                    'article_ids' => $this->articleIds,
                    'export_options' => $this->exportOptions,
                    'processing_time' => $exportResult['processing_time'] ?? null,
                ],
                'status' => 'completed',
                'expires_at' => now()->addDays(7) // Keep for 7 days
            ]);
            
        } catch (Exception $e) {
            Log::warning('Failed to create export record', [
                'user_id' => $this->userId,
                'file_path' => $exportResult['file_path'],
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get exported file path
     */
    public function getFilePath(): ?string
    {
        return $this->filePath;
    }

    /**
     * Check if this is a business logic exception
     */
    protected function isBusinessLogicException(Exception $exception): bool
    {
        return str_contains($exception->getMessage(), 'No articles found');
    }
}
