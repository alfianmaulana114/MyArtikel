<?php

namespace App\Http\Controllers;

use App\Services\AdvancedPdfExportService;
use App\Models\Article;
use App\Models\User;
use App\Models\Export;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Exception;

class PdfExportController extends Controller
{
    private AdvancedPdfExportService $pdfService;

    public function __construct(AdvancedPdfExportService $pdfService)
    {
        $this->pdfService = $pdfService;
    }

    /**
     * Show PDF export form
     */
    public function create()
    {
        $user = Auth::user();
        
        // Get user's articles
        $articles = Article::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get();
        
        // Get available templates
        $templates = $this->pdfService->getAvailableTemplates();
        
        // Get recent exports
        $recentExports = Export::where('user_id', $user->id)
            ->where('type', 'pdf')
            ->where('status', 'completed')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return view('pdf.export-form', [
            'articles' => $articles,
            'templates' => $templates,
            'recentExports' => $recentExports
        ]);
    }

    /**
     * Export single article to PDF
     */
    public function exportSingle(Request $request, int $articleId)
    {
        try {
            $request->validate([
                'template' => 'nullable|string|in:default,academic,magazine,minimal,business',
                'format' => 'nullable|string|in:A4,A3,Letter,Legal',
                'orientation' => 'nullable|string|in:portrait,landscape',
                'include_images' => 'nullable|boolean',
                'include_summaries' => 'nullable|boolean',
                'include_metadata' => 'nullable|boolean',
                'font_size' => 'nullable|integer|min:8|max:20',
                'page_numbers' => 'nullable|boolean',
                'watermark' => 'nullable|boolean',
            ]);

            $user = Auth::user();
            $article = Article::where('id', $articleId)
                ->where('user_id', $user->id)
                ->firstOrFail();

            // Prepare export options
            $options = [
                'template' => $request->input('template', 'default'),
                'format' => $request->input('format', 'A4'),
                'orientation' => $request->input('orientation', 'portrait'),
                'include_images' => $request->boolean('include_images', false),
                'include_summaries' => $request->boolean('include_summaries', false),
                'include_metadata' => $request->boolean('include_metadata', true),
                'font_size' => $request->input('font_size', 12),
                'page_numbers' => $request->boolean('page_numbers', true),
                'watermark' => $request->boolean('watermark', false),
                'clean_reader_format' => true,
                'filename_prefix' => 'article_' . $article->id,
            ];

            // Export to PDF
            $result = $this->pdfService->exportSingleArticle($article, $options);

            if (!$result['success']) {
                throw new Exception($result['error']);
            }

            // Create export record
            $export = Export::create([
                'user_id' => $user->id,
                'type' => 'pdf',
                'file_path' => $result['file_path'],
                'file_size' => $result['file_size'],
                'status' => 'completed',
                'metadata' => [
                    'article_id' => $article->id,
                    'template' => $options['template'],
                    'options' => $options,
                    'processing_time' => $result['processing_time'],
                ],
                'processing_started_at' => now(),
                'processing_completed_at' => now(),
                'processing_time_ms' => intval($result['processing_time'] * 1000),
                'expires_at' => now()->addDays(30),
            ]);

            // Log successful export
            Log::info('PDF export completed', [
                'user_id' => $user->id,
                'article_id' => $article->id,
                'export_id' => $export->id,
                'file_size' => $result['file_size'],
                'processing_time' => $result['processing_time']
            ]);

            // Return download URL or redirect
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'PDF exported successfully',
                    'data' => [
                        'export_id' => $export->id,
                        'download_url' => route('pdf.download', $export->id),
                        'preview_url' => route('pdf.preview', $export->id),
                        'file_size' => $result['file_size'],
                        'processing_time' => $result['processing_time']
                    ]
                ]);
            }

            // Redirect to download
            return redirect(route('pdf.download', $export->id));

        } catch (Exception $e) {
            Log::error('PDF export failed', [
                'user_id' => Auth::id(),
                'article_id' => $articleId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'error' => $e->getMessage()
                ], 500);
            }

            return back()->with('error', 'Failed to export PDF: ' . $e->getMessage());
        }
    }

    /**
     * Export multiple articles to PDF
     */
    public function exportMultiple(Request $request)
    {
        try {
            $request->validate([
                'article_ids' => 'required|array|min:1|max:50',
                'article_ids.*' => 'integer|exists:articles,id',
                'template' => 'nullable|string|in:default,academic,magazine,minimal,business',
                'format' => 'nullable|string|in:A4,A3,Letter,Legal',
                'orientation' => 'nullable|string|in:portrait,landscape',
                'include_images' => 'nullable|boolean',
                'include_summaries' => 'nullable|boolean',
                'include_metadata' => 'nullable|boolean',
                'include_toc' => 'nullable|boolean',
                'font_size' => 'nullable|integer|min:8|max:20',
                'page_numbers' => 'nullable|boolean',
                'watermark' => 'nullable|boolean',
            ]);

            $user = Auth::user();
            $articleIds = $request->input('article_ids');

            // Validate articles belong to user
            $articles = Article::where('user_id', $user->id)
                ->whereIn('id', $articleIds)
                ->with(['user', 'tags', 'summaries'])
                ->orderBy('created_at', 'desc')
                ->get();

            if ($articles->isEmpty()) {
                throw new Exception('No valid articles found for export');
            }

            // Prepare export options
            $options = [
                'template' => $request->input('template', 'default'),
                'format' => $request->input('format', 'A4'),
                'orientation' => $request->input('orientation', 'portrait'),
                'include_images' => $request->boolean('include_images', false),
                'include_summaries' => $request->boolean('include_summaries', false),
                'include_metadata' => $request->boolean('include_metadata', true),
                'include_toc' => $request->boolean('include_toc', true),
                'font_size' => $request->input('font_size', 12),
                'page_numbers' => $request->boolean('page_numbers', true),
                'watermark' => $request->boolean('watermark', false),
                'clean_reader_format' => true,
                'filename_prefix' => 'articles_collection',
            ];

            // Export to PDF
            $result = $this->pdfService->exportMultipleArticles($articles, $options);

            if (!$result['success']) {
                throw new Exception($result['error']);
            }

            // Create export record
            $export = Export::create([
                'user_id' => $user->id,
                'type' => 'pdf',
                'file_path' => $result['file_path'],
                'file_size' => $result['file_size'],
                'status' => 'completed',
                'metadata' => [
                    'article_ids' => $articles->pluck('id')->toArray(),
                    'article_count' => $articles->count(),
                    'template' => $options['template'],
                    'options' => $options,
                    'processing_time' => $result['processing_time'],
                ],
                'processing_started_at' => now(),
                'processing_completed_at' => now(),
                'processing_time_ms' => intval($result['processing_time'] * 1000),
                'expires_at' => now()->addDays(30),
            ]);

            // Log successful export
            Log::info('Multiple articles PDF export completed', [
                'user_id' => $user->id,
                'article_count' => $articles->count(),
                'export_id' => $export->id,
                'file_size' => $result['file_size'],
                'processing_time' => $result['processing_time']
            ]);

            // Return download URL or redirect
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'PDF collection exported successfully',
                    'data' => [
                        'export_id' => $export->id,
                        'download_url' => route('pdf.download', $export->id),
                        'preview_url' => route('pdf.preview', $export->id),
                        'file_size' => $result['file_size'],
                        'processing_time' => $result['processing_time'],
                        'article_count' => $result['article_count']
                    ]
                ]);
            }

            // Redirect to download
            return redirect(route('pdf.download', $export->id));

        } catch (Exception $e) {
            Log::error('Multiple articles PDF export failed', [
                'user_id' => Auth::id(),
                'article_ids' => $request->input('article_ids', []),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'error' => $e->getMessage()
                ], 500);
            }

            return back()->with('error', 'Failed to export PDF collection: ' . $e->getMessage());
        }
    }

    /**
     * Export with custom template
     */
    public function exportWithTemplate(Request $request, string $template)
    {
        try {
            $request->validate([
                'article_ids' => 'required|array|min:1|max:50',
                'article_ids.*' => 'integer|exists:articles,id',
                'format' => 'nullable|string|in:A4,A3,Letter,Legal',
                'orientation' => 'nullable|string|in:portrait,landscape',
                'include_images' => 'nullable|boolean',
                'include_summaries' => 'nullable|boolean',
                'include_metadata' => 'nullable|boolean',
                'font_size' => 'nullable|integer|min:8|max:20',
                'page_numbers' => 'nullable|boolean',
                'watermark' => 'nullable|boolean',
            ]);

            $user = Auth::user();
            $articleIds = $request->input('article_ids');

            // Validate template exists
            $templates = $this->pdfService->getAvailableTemplates();
            $templateExists = collect($templates)->contains('name', $template);
            
            if (!$templateExists) {
                throw new Exception("Template '{$template}' not found");
            }

            // Validate articles belong to user
            $articles = Article::where('user_id', $user->id)
                ->whereIn('id', $articleIds)
                ->with(['user', 'tags', 'summaries'])
                ->orderBy('created_at', 'desc')
                ->get();

            if ($articles->isEmpty()) {
                throw new Exception('No valid articles found for export');
            }

            // Prepare export options
            $options = [
                'format' => $request->input('format', 'A4'),
                'orientation' => $request->input('orientation', 'portrait'),
                'include_images' => $request->boolean('include_images', false),
                'include_summaries' => $request->boolean('include_summaries', false),
                'include_metadata' => $request->boolean('include_metadata', true),
                'font_size' => $request->input('font_size', 12),
                'page_numbers' => $request->boolean('page_numbers', true),
                'watermark' => $request->boolean('watermark', false),
                'clean_reader_format' => true,
                'filename_prefix' => 'template_' . $template . '_export',
            ];

            // Export to PDF with template
            $result = $this->pdfService->exportWithTemplate($articles, $template, $options);

            if (!$result['success']) {
                throw new Exception($result['error']);
            }

            // Create export record
            $export = Export::create([
                'user_id' => $user->id,
                'type' => 'pdf',
                'file_path' => $result['file_path'],
                'file_size' => $result['file_size'],
                'status' => 'completed',
                'metadata' => [
                    'article_ids' => $articles->pluck('id')->toArray(),
                    'article_count' => $articles->count(),
                    'template' => $template,
                    'options' => $options,
                    'processing_time' => $result['processing_time'],
                ],
                'processing_started_at' => now(),
                'processing_completed_at' => now(),
                'processing_time_ms' => intval($result['processing_time'] * 1000),
                'expires_at' => now()->addDays(30),
            ]);

            // Log successful export
            Log::info('Template-based PDF export completed', [
                'user_id' => $user->id,
                'template' => $template,
                'article_count' => $articles->count(),
                'export_id' => $export->id,
                'file_size' => $result['file_size'],
                'processing_time' => $result['processing_time']
            ]);

            // Return download URL or redirect
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'PDF exported successfully with template',
                    'data' => [
                        'export_id' => $export->id,
                        'template' => $template,
                        'download_url' => route('pdf.download', $export->id),
                        'preview_url' => route('pdf.preview', $export->id),
                        'file_size' => $result['file_size'],
                        'processing_time' => $result['processing_time'],
                        'article_count' => $articles->count()
                    ]
                ]);
            }

            // Redirect to download
            return redirect(route('pdf.download', $export->id));

        } catch (Exception $e) {
            Log::error('Template-based PDF export failed', [
                'user_id' => Auth::id(),
                'template' => $template,
                'article_ids' => $request->input('article_ids', []),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'error' => $e->getMessage()
                ], 500);
            }

            return back()->with('error', 'Failed to export PDF with template: ' . $e->getMessage());
        }
    }

    /**
     * Download exported PDF
     */
    public function download(int $exportId)
    {
        try {
            $user = Auth::user();
            
            $export = Export::where('id', $exportId)
                ->where('user_id', $user->id)
                ->where('type', 'pdf')
                ->firstOrFail();

            if ($export->isExpired()) {
                throw new Exception('Export has expired');
            }

            if ($export->status !== 'completed') {
                throw new Exception('Export is not ready');
            }

            $filePath = $export->file_path;
            
            if (!Storage::disk('local')->exists($filePath)) {
                throw new Exception('Export file not found');
            }

            // Update download count
            $metadata = $export->metadata ?? [];
            $metadata['download_count'] = ($metadata['download_count'] ?? 0) + 1;
            $metadata['last_downloaded_at'] = now()->toIso8601String();
            $export->update(['metadata' => $metadata]);

            // Log download
            Log::info('PDF export downloaded', [
                'user_id' => $user->id,
                'export_id' => $export->id,
                'file_path' => $filePath,
                'file_size' => $export->file_size
            ]);

            return Storage::disk('local')->download($filePath, $this->generateDownloadFilename($export));

        } catch (Exception $e) {
            Log::error('PDF download failed', [
                'user_id' => Auth::id(),
                'export_id' => $exportId,
                'error' => $e->getMessage()
            ]);

            return back()->with('error', 'Failed to download PDF: ' . $e->getMessage());
        }
    }

    /**
     * Preview exported PDF
     */
    public function preview(int $exportId)
    {
        try {
            $user = Auth::user();
            
            $export = Export::where('id', $exportId)
                ->where('user_id', $user->id)
                ->where('type', 'pdf')
                ->firstOrFail();

            if ($export->isExpired()) {
                throw new Exception('Export has expired');
            }

            if ($export->status !== 'completed') {
                throw new Exception('Export is not ready');
            }

            $filePath = $export->file_path;
            
            if (!Storage::disk('local')->exists($filePath)) {
                throw new Exception('Export file not found');
            }

            // Return file for inline viewing
            return Storage::disk('local')->response($filePath, null, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="' . $this->generateDownloadFilename($export) . '"'
            ]);

        } catch (Exception $e) {
            Log::error('PDF preview failed', [
                'user_id' => Auth::id(),
                'export_id' => $exportId,
                'error' => $e->getMessage()
            ]);

            return back()->with('error', 'Failed to preview PDF: ' . $e->getMessage());
        }
    }

    /**
     * Get export history
     */
    public function history(Request $request)
    {
        try {
            $user = Auth::user();
            
            $request->validate([
                'limit' => 'nullable|integer|min:1|max:100',
                'status' => 'nullable|string|in:pending,processing,completed,failed',
                'template' => 'nullable|string',
            ]);

            $limit = $request->input('limit', 20);
            
            $exports = Export::where('user_id', $user->id)
                ->where('type', 'pdf')
                ->when($request->status, function ($query) use ($request) {
                    return $query->where('status', $request->status);
                })
                ->when($request->template, function ($query) use ($request) {
                    return $query->whereJsonContains('metadata->template', $request->template);
                })
                ->orderBy('created_at', 'desc')
                ->paginate($limit);

            // Add additional metadata
            $exports->getCollection()->transform(function ($export) {
                $export->is_downloadable = $export->isReady();
                $export->download_url = $export->isReady() ? route('pdf.download', $export->id) : null;
                $export->preview_url = $export->isReady() ? route('pdf.preview', $export->id) : null;
                
                // Extract article count from metadata
                $export->article_count = $export->metadata['article_count'] ?? 
                    (isset($export->metadata['article_ids']) ? count($export->metadata['article_ids']) : 1);
                
                return $export;
            });

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'data' => $exports
                ]);
            }

            return view('pdf.export-history', [
                'exports' => $exports,
                'templates' => $this->pdfService->getAvailableTemplates()
            ]);

        } catch (Exception $e) {
            Log::error('Export history retrieval failed', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'error' => $e->getMessage()
                ], 500);
            }

            return back()->with('error', 'Failed to retrieve export history: ' . $e->getMessage());
        }
    }

    /**
     * Delete export
     */
    public function delete(int $exportId)
    {
        try {
            $user = Auth::user();
            
            $export = Export::where('id', $exportId)
                ->where('user_id', $user->id)
                ->where('type', 'pdf')
                ->firstOrFail();

            // Delete file
            if (Storage::disk('local')->exists($export->file_path)) {
                Storage::disk('local')->delete($export->file_path);
            }

            // Delete record
            $export->delete();

            Log::info('PDF export deleted', [
                'user_id' => $user->id,
                'export_id' => $exportId,
                'file_path' => $export->file_path
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Export deleted successfully'
            ]);

        } catch (Exception $e) {
            Log::error('PDF export deletion failed', [
                'user_id' => Auth::id(),
                'export_id' => $exportId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get available templates
     */
    public function templates()
    {
        try {
            $templates = $this->pdfService->getAvailableTemplates();
            
            return response()->json([
                'success' => true,
                'data' => $templates
            ]);

        } catch (Exception $e) {
            Log::error('Template retrieval failed', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate download filename
     */
    private function generateDownloadFilename(Export $export): string
    {
        $metadata = $export->metadata ?? [];
        $template = $metadata['template'] ?? 'default';
        $articleCount = $metadata['article_count'] ?? 1;
        
        $timestamp = now()->format('Y-m-d_H-i-s');
        
        if ($articleCount === 1 && isset($metadata['article_id'])) {
            return "article_{$metadata['article_id']}_{$template}_{$timestamp}.pdf";
        } else {
            return "articles_collection_{$articleCount}_{$template}_{$timestamp}.pdf";
        }
    }

    /**
     * Background export (for large collections)
     */
    public function exportBackground(Request $request)
    {
        try {
            $request->validate([
                'article_ids' => 'required|array|min:1|max:200',
                'article_ids.*' => 'integer|exists:articles,id',
                'template' => 'nullable|string|in:default,academic,magazine,minimal,business',
                'format' => 'nullable|string|in:A4,A3,Letter,Legal',
                'orientation' => 'nullable|string|in:portrait,landscape',
                'include_images' => 'nullable|boolean',
                'include_summaries' => 'nullable|boolean',
                'include_metadata' => 'nullable|boolean',
                'font_size' => 'nullable|integer|min:8|max:20',
                'page_numbers' => 'nullable|boolean',
                'watermark' => 'nullable|boolean',
            ]);

            $user = Auth::user();
            $articleIds = $request->input('article_ids');

            // Validate articles belong to user
            $articles = Article::where('user_id', $user->id)
                ->whereIn('id', $articleIds)
                ->get();

            if ($articles->isEmpty()) {
                throw new Exception('No valid articles found for export');
            }

            // Create export record with pending status
            $export = Export::create([
                'user_id' => $user->id,
                'type' => 'pdf',
                'file_path' => '',
                'file_size' => 0,
                'status' => 'pending',
                'metadata' => [
                    'article_ids' => $articles->pluck('id')->toArray(),
                    'article_count' => $articles->count(),
                    'template' => $request->input('template', 'default'),
                    'format' => $request->input('format', 'A4'),
                    'orientation' => $request->input('orientation', 'portrait'),
                    'include_images' => $request->boolean('include_images', false),
                    'include_summaries' => $request->boolean('include_summaries', false),
                    'include_metadata' => $request->boolean('include_metadata', true),
                    'font_size' => $request->input('font_size', 12),
                    'page_numbers' => $request->boolean('page_numbers', true),
                    'watermark' => $request->boolean('watermark', false),
                    'background_export' => true,
                ],
                'processing_started_at' => now(),
                'expires_at' => now()->addDays(30),
            ]);

            // Dispatch background job
            dispatch(new \App\Jobs\ProcessPdfExport(
                $user->id,
                $articles->pluck('id')->toArray(),
                $request->all()
            ))->onQueue('exports');

            Log::info('Background PDF export dispatched', [
                'user_id' => $user->id,
                'export_id' => $export->id,
                'article_count' => $articles->count(),
                'template' => $request->input('template', 'default')
            ]);

            return response()->json([
                'success' => true,
                'message' => 'PDF export queued for background processing',
                'data' => [
                    'export_id' => $export->id,
                    'status' => 'pending',
                    'check_status_url' => route('pdf.status', $export->id)
                ]
            ]);

        } catch (Exception $e) {
            Log::error('Background PDF export dispatch failed', [
                'user_id' => Auth::id(),
                'article_ids' => $request->input('article_ids', []),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Check export status
     */
    public function status(int $exportId)
    {
        try {
            $user = Auth::user();
            
            $export = Export::where('id', $exportId)
                ->where('user_id', $user->id)
                ->where('type', 'pdf')
                ->firstOrFail();

            $response = [
                'export_id' => $export->id,
                'status' => $export->status,
                'is_expired' => $export->isExpired(),
                'is_ready' => $export->isReady(),
                'file_size' => $export->file_size,
                'processing_time' => $export->processing_time_ms ? ($export->processing_time_ms / 1000) . 's' : null,
                'created_at' => $export->created_at->toIso8601String(),
                'expires_at' => $export->expires_at ? $export->expires_at->toIso8601String() : null,
            ];

            if ($export->isReady()) {
                $response['download_url'] = route('pdf.download', $export->id);
                $response['preview_url'] = route('pdf.preview', $export->id);
            }

            if ($export->status === 'failed' && $export->error_message) {
                $response['error_message'] = $export->error_message;
            }

            return response()->json([
                'success' => true,
                'data' => $response
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 404);
        }
    }
}
