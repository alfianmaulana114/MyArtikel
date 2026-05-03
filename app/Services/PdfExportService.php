<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Log;
use Exception;

class PdfExportService
{
    private string $storageDisk = 'local';
    private string $exportPath = 'exports/pdf';
    
    /**
     * Export articles to PDF
     */
    public function exportArticles($articles, array $options = []): array
    {
        try {
            $startTime = microtime(true);
            
            // Prepare data for PDF
            $pdfData = $this->preparePdfData($articles, $options);
            
            // Generate HTML content
            $htmlContent = $this->generateHtmlContent($pdfData, $options);
            
            // Convert HTML to PDF
            $pdfContent = $this->convertHtmlToPdf($htmlContent, $options);
            
            // Save PDF file
            $filePath = $this->savePdfFile($pdfContent, $options);
            
            $processingTime = microtime(true) - $startTime;
            
            Log::info('PDF export completed', [
                'article_count' => $articles->count(),
                'file_path' => $filePath,
                'file_size' => strlen($pdfContent),
                'processing_time' => round($processingTime, 2) . 's'
            ]);
            
            return [
                'success' => true,
                'file_path' => $filePath,
                'file_size' => strlen($pdfContent),
                'processing_time' => $processingTime,
                'download_url' => $this->generateDownloadUrl($filePath)
            ];
            
        } catch (Exception $e) {
            Log::error('PDF export failed', [
                'article_count' => $articles->count(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Prepare PDF data
     */
    private function preparePdfData($articles, array $options): array
    {
        $data = [
            'articles' => $articles,
            'export_date' => now()->format('Y-m-d H:i:s'),
            'total_articles' => $articles->count(),
            'options' => $options
        ];
        
        // Add statistics
        $data['statistics'] = [
            'total_words' => $articles->sum(function ($article) {
                return str_word_count(strip_tags($article->content));
            }),
            'total_tags' => $articles->pluck('tags')->flatten()->unique('id')->count(),
            'date_range' => [
                'oldest' => $articles->min('created_at')->format('Y-m-d'),
                'newest' => $articles->max('created_at')->format('Y-m-d')
            ]
        ];
        
        return $data;
    }

    /**
     * Generate HTML content for PDF
     */
    private function generateHtmlContent(array $data, array $options): string
    {
        $template = $options['template'] ?? 'default';
        
        // Check if custom template exists
        $templatePath = "pdf.export.{$template}";
        
        try {
            return View::make($templatePath, $data)->render();
        } catch (Exception $e) {
            // Fallback to default template
            return $this->generateDefaultHtmlContent($data, $options);
        }
    }

    /**
     * Generate default HTML content
     */
    private function generateDefaultHtmlContent(array $data, array $options): string
    {
        $html = '<!DOCTYPE html>';
        $html .= '<html lang="en">';
        $html .= '<head>';
        $html .= '<meta charset="UTF-8">';
        $html .= '<meta name="viewport" content="width=device-width, initial-scale=1.0">';
        $html .= '<title>Articles Export</title>';
        $html .= $this->getDefaultStyles($options);
        $html .= '</head>';
        $html .= '<body>';
        
        // Header
        $html .= '<div class="header">';
        $html .= '<h1>Articles Export</h1>';
        $html .= '<p class="export-date">Generated on: ' . $data['export_date'] . '</p>';
        $html .= '<p class="export-stats">Total Articles: ' . $data['total_articles'] . '</p>';
        $html .= '</div>';
        
        // Statistics section
        if (!empty($data['statistics'])) {
            $html .= '<div class="statistics">';
            $html .= '<h2>Statistics</h2>';
            $html .= '<ul>';
            $html .= '<li>Total Words: ' . number_format($data['statistics']['total_words']) . '</li>';
            $html .= '<li>Total Tags: ' . $data['statistics']['total_tags'] . '</li>';
            $html .= '<li>Date Range: ' . $data['statistics']['date_range']['oldest'] . ' to ' . $data['statistics']['date_range']['newest'] . '</li>';
            $html .= '</ul>';
            $html .= '</div>';
        }
        
        // Articles content
        foreach ($data['articles'] as $article) {
            $html .= '<div class="article">';
            $html .= '<h2>' . htmlspecialchars($article->title) . '</h2>';
            
            if ($options['include_metadata'] ?? true) {
                $html .= '<div class="article-meta">';
                $html .= '<span>Created: ' . $article->created_at->format('Y-m-d H:i') . '</span>';
                if ($article->tags->count() > 0) {
                    $html .= '<span>Tags: ' . $article->tags->pluck('name')->implode(', ') . '</span>';
                }
                $html .= '</div>';
            }
            
            if ($options['include_images'] ?? true) {
                if ($article->featured_image) {
                    $html .= '<img src="' . htmlspecialchars($article->featured_image) . '" alt="Featured Image" class="featured-image">';
                }
            }
            
            $html .= '<div class="article-content">';
            $html .= $article->content;
            $html .= '</div>';
            
            // Include summaries if available
            if ($article->summaries->count() > 0 && ($options['include_summaries'] ?? false)) {
                $html .= '<div class="article-summaries">';
                $html .= '<h3>Summaries</h3>';
                foreach ($article->summaries as $summary) {
                    $html .= '<div class="summary">';
                    $html .= '<h4>' . ucfirst($summary->source) . ' Summary</h4>';
                    $html .= '<p>' . htmlspecialchars($summary->content) . '</p>';
                    if (!empty($summary->key_points)) {
                        $html .= '<ul>';
                        foreach ($summary->key_points as $point) {
                            $html .= '<li>' . htmlspecialchars($point) . '</li>';
                        }
                        $html .= '</ul>';
                    }
                    $html .= '</div>';
                }
                $html .= '</div>';
            }
            
            $html .= '</div>';
            $html .= '<div class="page-break"></div>';
        }
        
        $html .= '</body>';
        $html .= '</html>';
        
        return $html;
    }

    /**
     * Get default CSS styles
     */
    private function getDefaultStyles(array $options): string
    {
        $format = $options['format'] ?? 'A4';
        $orientation = $options['orientation'] ?? 'portrait';
        
        $styles = '<style>';
        $styles .= 'body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 20px; }';
        $styles .= '.header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #007bff; padding-bottom: 20px; }';
        $styles .= '.header h1 { color: #007bff; margin-bottom: 10px; }';
        $styles .= '.export-date, .export-stats { font-size: 14px; color: #666; }';
        $styles .= '.statistics { background: #f8f9fa; padding: 15px; margin: 20px 0; border-radius: 5px; }';
        $styles .= '.statistics h2 { margin-top: 0; color: #495057; }';
        $styles .= '.statistics ul { list-style: none; padding: 0; }';
        $styles .= '.statistics li { margin: 5px 0; }';
        $styles .= '.article { margin-bottom: 40px; }';
        $styles .= '.article h2 { color: #007bff; border-bottom: 1px solid #eee; padding-bottom: 10px; }';
        $styles .= '.article-meta { font-size: 12px; color: #666; margin: 10px 0; }';
        $styles .= '.article-meta span { margin-right: 15px; }';
        $styles .= '.featured-image { max-width: 100%; height: auto; margin: 15px 0; border-radius: 5px; }';
        $styles .= '.article-content { margin: 20px 0; }';
        $styles .= '.article-summaries { background: #f8f9fa; padding: 15px; margin-top: 20px; border-radius: 5px; }';
        $styles .= '.summary { margin: 15px 0; padding: 10px; background: white; border-radius: 3px; }';
        $styles .= '.summary h4 { margin-top: 0; color: #495057; }';
        $styles .= '.page-break { page-break-after: always; height: 1px; }';
        $styles .= '@media print { .page-break { page-break-after: always; } }';
        $styles .= '</style>';
        
        return $styles;
    }

    /**
     * Convert HTML to PDF
     */
    private function convertHtmlToPdf(string $html, array $options): string
    {
        // For now, we'll return HTML content
        // In production, you would integrate with a PDF library like DomPDF, mPDF, or wkhtmltopdf
        
        // This is a placeholder - implement actual PDF conversion
        // Example with DomPDF:
        // $dompdf = new \Dompdf\Dompdf();
        // $dompdf->loadHtml($html);
        // $dompdf->setPaper($options['format'] ?? 'A4', $options['orientation'] ?? 'portrait');
        // $dompdf->render();
        // return $dompdf->output();
        
        // For now, return HTML content wrapped in PDF marker
        return "PDF_CONTENT:" . base64_encode($html);
    }

    /**
     * Save PDF file
     */
    private function savePdfFile(string $pdfContent, array $options): string
    {
        $filename = $this->generateFilename($options);
        $filePath = $this->exportPath . '/' . $filename;
        
        // Remove PDF marker and decode if needed
        if (strpos($pdfContent, 'PDF_CONTENT:') === 0) {
            $pdfContent = base64_decode(substr($pdfContent, 12));
        }
        
        // Save to storage
        Storage::disk($this->storageDisk)->put($filePath, $pdfContent);
        
        return $filePath;
    }

    /**
     * Generate filename
     */
    private function generateFilename(array $options): string
    {
        $prefix = $options['filename_prefix'] ?? 'articles_export';
        $timestamp = now()->format('Y-m-d_H-i-s');
        $random = substr(md5(uniqid()), 0, 8);
        
        return "{$prefix}_{$timestamp}_{$random}.pdf";
    }

    /**
     * Generate download URL
     */
    private function generateDownloadUrl(string $filePath): string
    {
        // This would generate a temporary signed URL for download
        // For now, return a placeholder
        return url('/exports/' . basename($filePath));
    }

    /**
     * Get export URL
     */
    public function getExportUrl(string $filePath): ?string
    {
        if (Storage::disk($this->storageDisk)->exists($filePath)) {
            return Storage::disk($this->storageDisk)->url($filePath);
        }
        
        return null;
    }

    /**
     * Delete old exports
     */
    public function cleanupOldExports(int $daysToKeep = 7): int
    {
        $cutoffDate = now()->subDays($daysToKeep);
        $deletedCount = 0;
        
        try {
            $files = Storage::disk($this->storageDisk)->files($this->exportPath);
            
            foreach ($files as $file) {
                $lastModified = Storage::disk($this->storageDisk)->lastModified($file);
                
                if ($lastModified < $cutoffDate->timestamp) {
                    Storage::disk($this->storageDisk)->delete($file);
                    $deletedCount++;
                }
            }
            
            Log::info('PDF export cleanup completed', [
                'deleted_count' => $deletedCount,
                'days_to_keep' => $daysToKeep
            ]);
            
        } catch (Exception $e) {
            Log::error('PDF export cleanup failed', [
                'error' => $e->getMessage()
            ]);
        }
        
        return $deletedCount;
    }
}