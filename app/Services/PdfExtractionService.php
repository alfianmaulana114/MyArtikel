<?php

namespace App\Services;

use Smalot\PdfParser\Parser;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Exception;

class PdfExtractionService
{
    private Parser $parser;

    public function __construct()
    {
        $this->parser = new Parser();
    }

    /**
     * Extract text from uploaded PDF file
     */
    public function extractFromPath(string $filePath): array
    {
        try {
            $startTime = microtime(true);

            // Resolve full path from storage
            $fullPath = Storage::disk('public')->path($filePath);

            if (!file_exists($fullPath)) {
                throw new Exception('File not found: ' . $filePath);
            }

            // Parse PDF
            $pdf = $this->parser->parseFile($fullPath);

            // Extract metadata if available
            $details = $pdf->getDetails();
            $metadata = [];
            if (!empty($details['Title'] ?? null)) {
                $metadata['title'] = $details['Title'];
            }
            if (!empty($details['Author'] ?? null)) {
                $metadata['author'] = $details['Author'];
            }
            if (!empty($details['Subject'] ?? null)) {
                $metadata['subject'] = $details['Subject'];
            }
            if (!empty($details['Keywords'] ?? null)) {
                $metadata['keywords'] = $details['Keywords'];
            }
            if (!empty($details['Creator'] ?? null)) {
                $metadata['creator'] = $details['Creator'];
            }
            if (!empty($details['Producer'] ?? null)) {
                $metadata['producer'] = $details['Producer'];
            }

            // Get all pages
            $pages = $pdf->getPages();
            $pageCount = count($pages);

            // Extract text from all pages
            $fullText = '';
            foreach ($pages as $index => $page) {
                $pageText = $page->getText();
                if (trim($pageText) !== '') {
                    $fullText .= $pageText . "\n";
                }
            }

            $fullText = trim($fullText);

            if (empty($fullText)) {
                throw new Exception('No text could be extracted from the PDF. The file may be scanned or image-based.');
            }

            $wordCount = str_word_count($fullText);
            $processingTime = round((microtime(true) - $startTime) * 1000, 2);

            Log::info('PDF text extraction completed', [
                'file_path' => $filePath,
                'pages' => $pageCount,
                'word_count' => $wordCount,
                'processing_time_ms' => $processingTime,
            ]);

            return [
                'success' => true,
                'text' => $fullText,
                'page_count' => $pageCount,
                'word_count' => $wordCount,
                'metadata' => $metadata,
                'processing_time_ms' => $processingTime,
            ];

        } catch (Exception $e) {
            Log::error('PDF text extraction failed', [
                'file_path' => $filePath,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Extract text from raw PDF content (for direct file handling)
     */
    public function extractFromContent(string $pdfContent): array
    {
        try {
            $startTime = microtime(true);

            // Write to temp file
            $tempFile = tempnam(sys_get_temp_dir(), 'pdf_extract_');
            file_put_contents($tempFile, $pdfContent);

            // Parse
            $pdf = $this->parser->parseFile($tempFile);

            $pages = $pdf->getPages();
            $fullText = '';
            foreach ($pages as $page) {
                $pageText = $page->getText();
                if (trim($pageText) !== '') {
                    $fullText .= $pageText . "\n";
                }
            }

            // Cleanup temp file
            unlink($tempFile);

            $fullText = trim($fullText);
            $wordCount = str_word_count($fullText);
            $processingTime = round((microtime(true) - $startTime) * 1000, 2);

            return [
                'success' => true,
                'text' => $fullText,
                'word_count' => $wordCount,
                'processing_time_ms' => $processingTime,
            ];

        } catch (Exception $e) {
            Log::error('PDF text extraction from content failed', [
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
}
