<?php

namespace App\Jobs;

use App\Models\Article;
use App\Models\User;
use App\Models\Summary;
use App\Services\ArticleExtractionService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Exception;
use Illuminate\Database\QueryException;

class ProcessArticleIngestion extends BaseJob
{
    private int $userId;
    private string $url;
    private array $options;
    private ?int $articleId;

    /**
     * Create a new job instance.
     */
    public function __construct(int $userId, string $url, array $options = [])
    {
        $this->userId = $userId;
        $this->url = $url;
        $this->options = $options;
        $this->articleId = isset($options['article_id']) ? (int) $options['article_id'] : null;
        
        // Set queue for article ingestion jobs (low priority)
        $this->onQueue('low-priority');
        
        $this->setJobMetadata([
            'user_id' => $userId,
            'url' => $url,
            'options' => $options,
            'type' => 'article_ingestion'
        ]);
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('Starting article ingestion', [
            'user_id' => $this->userId,
            'url' => $this->url,
            'options' => $this->options
        ]);

        try {
            $user = User::findOrFail($this->userId);
            $canonicalUrl = $this->url;
            $sourceDomain = parse_url($canonicalUrl, PHP_URL_HOST) ?: null;

            if ($this->articleId) {
                $article = Article::where('user_id', $this->userId)->findOrFail($this->articleId);
            } else {
                $existingArticle = Article::where('user_id', $this->userId)
                    ->where(function ($q) use ($canonicalUrl) {
                        $q->where('canonical_url', $canonicalUrl)->orWhere('source_url', $canonicalUrl);
                    })
                    ->first();

                if ($existingArticle) {
                    Log::info('Article already exists for URL', [
                        'user_id' => $this->userId,
                        'url' => $this->url,
                        'article_id' => $existingArticle->id
                    ]);
                    
                    $this->articleId = $existingArticle->id;
                    $this->addMetadata('existing_article_id', $existingArticle->id);
                    return;
                }

                $article = Article::create([
                    'user_id' => $this->userId,
                    'title' => 'Memproses artikel…',
                    'slug' => 'processing-' . uniqid(),
                    'source_url' => $this->url,
                    'canonical_url' => $canonicalUrl,
                    'source_domain' => $sourceDomain,
                    'content' => '',
                    'processing_status' => 'queued',
                    'status' => 'draft',
                ]);

                $this->articleId = $article->id;
            }

            $article->update([
                'processing_status' => 'fetching',
                'processing_error' => null,
            ]);

            // Extract article content
            $extractionService = app(ArticleExtractionService::class);
            $extractedData = $extractionService->extractFromUrl($this->url);

            if (!$extractedData['success']) {
                throw new Exception('Failed to extract article: ' . $extractedData['error']);
            }

            $article->update([
                'processing_status' => 'extracting',
            ]);

            $content = $extractedData['content'] ?? '';
            $textExtracted = trim(preg_replace('/\s+/', ' ', strip_tags($content)));
            $contentHash = $textExtracted !== '' ? hash('sha256', $textExtracted) : null;

            $title = $extractedData['title'] ?? 'Untitled Article';
            $slugBase = Str::slug($title);
            $slug = $slugBase !== '' ? ($slugBase . '-' . $article->id) : ('article-' . $article->id);

            $contentForDb = Str::limit($textExtracted, 60000, '');
            $excerptForDb = $textExtracted !== '' ? Str::limit($textExtracted, 300, '...') : '';

            $article->update([
                'title' => $title,
                'slug' => $slug,
                'content' => $contentForDb,
                'content_sanitized' => null,
                'text_extracted' => $textExtracted,
                'content_hash' => $contentHash,
                'excerpt' => $excerptForDb,
                'featured_image' => $extractedData['image'] ?? null,
                'source_url' => $this->url,
                'canonical_url' => $canonicalUrl,
                'source_domain' => $sourceDomain,
                'fetched_at' => now(),
                'processing_status' => 'ready',
            ]);

            // Process tags if available
            if (!empty($extractedData['tags'])) {
                $this->processTags($article, $extractedData['tags']);
            }

            // Extract and save metadata
            if (!empty($extractedData['metadata'])) {
                $this->saveMetadata($article, $extractedData['metadata']);
            }

            Log::info('Article ingestion completed successfully', [
                'user_id' => $this->userId,
                'article_id' => $article->id,
                'title' => $article->title,
                'word_count' => str_word_count($article->content)
            ]);

            $this->addMetadata('article_id', $article->id);
            $this->addMetadata('word_count', str_word_count($article->content));

            $this->autoGenerateSummary($article);

        } catch (Exception $e) {
            Log::error('Article ingestion failed', [
                'user_id' => $this->userId,
                'url' => $this->url,
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
     * Process and attach tags to article
     */
    private function processTags(Article $article, array $tags): void
    {
        try {
            $tagModels = [];
            
            foreach ($tags as $tagName) {
                $tag = \App\Models\Tag::firstOrCreate(
                    ['name' => trim($tagName)],
                    ['user_id' => $this->userId]
                );
                $tagModels[] = $tag->id;
            }
            
            $article->tags()->sync($tagModels);
            
            Log::info('Tags processed for article', [
                'article_id' => $article->id,
                'tags_count' => count($tagModels)
            ]);
            
        } catch (Exception $e) {
            Log::warning('Failed to process tags', [
                'article_id' => $article->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Auto-generate summary for the article
     */
    private function autoGenerateSummary(Article $article): void
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
                Log::info('Summary already exists for article', [
                    'article_id' => $article->id,
                    'summary_id' => $existingSummary->id
                ]);
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
                'force_regenerate' => false
            ];

            $result = $summarizationService->generateSummary(
                $article->id,
                $this->userId,
                $options
            );

            if ($result['success']) {
                Log::info('Auto summary generated successfully', [
                    'article_id' => $article->id,
                    'summary_id' => $summary->id
                ]);
            } else {
                Log::warning('Auto summary generation failed', [
                    'article_id' => $article->id,
                    'error' => $result['error'] ?? 'Unknown error'
                ]);
                $summary->update([
                    'status' => 'failed',
                    'error_message' => $result['error'] ?? 'Generation failed'
                ]);
            }

        } catch (Exception $e) {
            Log::warning('Auto summary generation error', [
                'article_id' => $article->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Save additional metadata
     */
    private function saveMetadata(Article $article, array $metadata): void
    {
        try {
            $article->update([
                'metadata' => array_merge($article->metadata ?? [], $metadata)
            ]);
            
            Log::info('Metadata saved for article', [
                'article_id' => $article->id,
                'metadata_keys' => array_keys($metadata)
            ]);
            
        } catch (Exception $e) {
            Log::warning('Failed to save metadata', [
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

