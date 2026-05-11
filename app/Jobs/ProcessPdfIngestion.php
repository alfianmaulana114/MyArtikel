<?php

namespace App\Jobs;

use App\Models\Article;
use App\Models\User;
use App\Models\Summary;
use App\Services\PdfExtractionService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Exception;
use Illuminate\Database\QueryException;

class ProcessPdfIngestion extends BaseJob
{
    private int $userId;
    private string $filePath;
    private array $options;
    private ?int $articleId;

    /**
     * Create a new job instance.
     */
    public function __construct(int $userId, string $filePath, array $options = [])
    {
        $this->userId = $userId;
        $this->filePath = $filePath;
        $this->options = $options;
        $this->articleId = isset($options['article_id']) ? (int) $options['article_id'] : null;

        $this->onQueue('low-priority');

        $this->setJobMetadata([
            'user_id' => $userId,
            'file_path' => $filePath,
            'options' => $options,
            'type' => 'pdf_ingestion'
        ]);
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('Starting PDF ingestion', [
            'user_id' => $this->userId,
            'file_path' => $this->filePath,
            'options' => $this->options
        ]);

        try {
            $article = Article::where('user_id', $this->userId)->findOrFail($this->articleId);

            $article->update([
                'processing_status' => 'fetching',
                'processing_error' => null,
            ]);

            // Extract text from PDF
            $pdfService = app(PdfExtractionService::class);
            $extractedData = $pdfService->extractFromPath($this->filePath);

            if (!$extractedData['success']) {
                throw new Exception('Failed to extract PDF: ' . $extractedData['error']);
            }

            $article->update([
                'processing_status' => 'extracting',
            ]);

            $textExtracted = trim(preg_replace('/\s+/', ' ', $extractedData['text']));
            $contentHash = $textExtracted !== '' ? hash('sha256', $textExtracted) : null;

            $title = $article->title;
            if ($title === '' || $title === 'Memproses artikel…') {
                $pdfMeta = $extractedData['metadata'] ?? [];
                $title = $pdfMeta['title'] ?? 'Jurnal PDF';
            }

            $slugBase = Str::slug($title);
            $slug = $slugBase !== '' ? ($slugBase . '-' . $article->id) : ('pdf-' . $article->id);

            $contentForDb = Str::limit($textExtracted, 60000, '');
            $excerptForDb = $textExtracted !== '' ? Str::limit($textExtracted, 300, '...') : '';

            $meta = $article->metadata ?? [];
            if (!empty($extractedData['metadata'])) {
                $meta = array_merge($meta, $extractedData['metadata']);
            }

            $article->update([
                'title' => $title,
                'slug' => $slug,
                'content' => $contentForDb,
                'content_sanitized' => null,
                'text_extracted' => $textExtracted,
                'content_hash' => $contentHash,
                'excerpt' => $excerptForDb,
                'fetched_at' => now(),
                'processing_status' => 'ready',
                'metadata' => $meta,
            ]);

            Log::info('PDF ingestion completed successfully', [
                'user_id' => $this->userId,
                'article_id' => $article->id,
                'title' => $article->title,
                'word_count' => str_word_count($article->content)
            ]);

            $this->addMetadata('article_id', $article->id);
            $this->addMetadata('word_count', str_word_count($article->content));

            $this->autoGenerateSummaryWithResearch($article);

        } catch (\Throwable $e) {
            Log::error('PDF ingestion failed', [
                'user_id' => $this->userId,
                'file_path' => $this->filePath,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            if ($this->articleId) {
                $message = $e instanceof QueryException
                    ? (($e->getPrevious() ? $e->getPrevious()->getMessage() : null) ?? $e->getMessage())
                    : $e->getMessage();
                $message = Str::limit(trim((string) $message), 1000, '…');

                Article::where('user_id', $this->userId)
                    ->where('id', $this->articleId)
                    ->update([
                        'processing_status' => 'failed',
                        'processing_error' => $message,
                    ]);
            }

            throw $e;
        }
    }

    /**
     * Auto-generate summary with research context
     */
    private function autoGenerateSummaryWithResearch(Article $article): void
    {
        try {
            if ($article->processing_status !== 'ready') {
                return;
            }

            $textExtracted = $article->text_extracted;
            if (empty($textExtracted) || str_word_count($textExtracted) < 50) {
                Log::info('Article content too short for summarization', [
                    'article_id' => $article->id,
                    'word_count' => str_word_count($textExtracted ?? '')
                ]);
                return;
            }

            $existingSummary = Summary::where('article_id', $article->id)
                ->where('user_id', $this->userId)
                ->where('status', 'completed')
                ->first();

            if ($existingSummary) {
                return;
            }

            $summary = Summary::create([
                'article_id' => $article->id,
                'user_id' => $this->userId,
                'content' => '',
                'word_count' => 0,
                'type' => 'ai_generated',
                'source' => 'gemini',
                'status' => 'pending',
                'processing_started_at' => now()
            ]);

            $summarizationService = app(\App\Services\SummarizationService::class);
            $options = [
                'max_words' => 150,
                'language' => 'id',
                'prefer_ai' => true,
                'force_regenerate' => false,
            ];

            $result = $summarizationService->generateSummary(
                $article->id,
                $this->userId,
                $options
            );

            if ($result['success']) {
                Log::info('Auto summary generated successfully for PDF', [
                    'article_id' => $article->id,
                    'summary_id' => $summary->id
                ]);

                // If there's a research_title, also generate citation suggestions
                if (!empty($article->research_title)) {
                    $this->generateCitationSuggestions($article);
                }
            } else {
                $summary->update([
                    'status' => 'failed',
                    'error_message' => $result['error'] ?? 'Generation failed'
                ]);
            }

        } catch (Exception $e) {
            Log::warning('Auto summary generation error for PDF', [
                'article_id' => $article->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Generate AI quotation/citation suggestions
     */
    private function generateCitationSuggestions(Article $article): void
    {
        try {
            $textExtracted = $article->text_extracted;
            $researchTitle = $article->research_title;

            if (empty($textExtracted) || empty($researchTitle)) {
                return;
            }

            $geminiService = app(\App\Services\GeminiSummarizationService::class);

            if (!$geminiService->isAvailable()) {
                return;
            }

            $result = $geminiService->generateResearchCitations($textExtracted, $researchTitle);

            if ($result['success']) {
                $article->update([
                    'ai_quotation_suggestions' => $result['citations'],
                ]);

                Log::info('Citation suggestions generated', [
                    'article_id' => $article->id,
                    'suggestions_count' => count($result['citations'])
                ]);
            }

        } catch (Exception $e) {
            Log::warning('Citation suggestion generation failed', [
                'article_id' => $article->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get created article ID
     */
    public function getArticleId(): ?int
    {
        return $this->articleId;
    }

    /**
     * Check if this is a business logic exception
     */
    protected function isBusinessLogicException(Exception $exception): bool
    {
        return str_contains($exception->getMessage(), 'already exists');
    }
}
