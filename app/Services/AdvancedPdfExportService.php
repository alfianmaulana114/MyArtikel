<?php

namespace App\Services;

use App\Models\Article;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class AdvancedPdfExportService
{
    private string $storageDisk = 'local';

    private string $exportPath = 'exports/pdf';

    public function getAvailableTemplates(): array
    {
        return [
            ['name' => 'default', 'description' => 'Standard export'],
            ['name' => 'academic', 'description' => 'Serif typography, formal layout'],
            ['name' => 'magazine', 'description' => 'Bold headings, spacious layout'],
            ['name' => 'minimal', 'description' => 'Clean and compact'],
            ['name' => 'business', 'description' => 'Report-like layout'],
        ];
    }

    public function exportSingleArticle(Article $article, array $options = []): array
    {
        $options = array_merge([
            'include_toc' => false,
        ], $options);

        return $this->exportMultipleArticles(collect([$article]), $options);
    }

    public function exportMultipleArticles($articles, array $options = []): array
    {
        $startTime = microtime(true);

        try {
            $articles = $articles instanceof Collection ? $articles : collect($articles);
            if ($articles->isEmpty()) {
                return [
                    'success' => false,
                    'error' => 'No articles provided for export',
                ];
            }

            $options = $this->normalizeOptions($options);
            $html = $this->buildHtml($articles, $options);

            $pdf = app('dompdf.wrapper');
            $pdf->loadHTML($html);
            $pdf->setPaper($options['format'], $options['orientation']);
            $pdfContent = $pdf->output();

            $filePath = $this->savePdfFile($pdfContent, $options);

            return [
                'success' => true,
                'file_path' => $filePath,
                'file_size' => strlen($pdfContent),
                'processing_time' => microtime(true) - $startTime,
                'article_count' => $articles->count(),
            ];
        } catch (Throwable $e) {
            Log::error('PDF export failed', [
                'error' => $e->getMessage(),
                'template' => $options['template'] ?? null,
                'format' => $options['format'] ?? null,
                'orientation' => $options['orientation'] ?? null,
                'include_images' => $options['include_images'] ?? null,
                'article_count' => isset($articles) && $articles instanceof Collection ? $articles->count() : null,
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    public function exportWithTemplate($articles, string $template, array $options = []): array
    {
        $options['template'] = $template;

        return $this->exportMultipleArticles($articles, $options);
    }

    private function normalizeOptions(array $options): array
    {
        $defaults = [
            'template' => 'default',
            'format' => 'A4',
            'orientation' => 'portrait',
            'include_images' => true,
            'include_summaries' => false,
            'include_metadata' => true,
            'include_toc' => true,
            'font_size' => 12,
            'page_numbers' => true,
            'watermark' => false,
            'clean_reader_format' => true,
            'filename_prefix' => 'articles_export',
        ];

        $options = array_merge($defaults, $options);

        $validTemplates = collect($this->getAvailableTemplates())->pluck('name')->all();
        if (! in_array($options['template'], $validTemplates, true)) {
            $options['template'] = 'default';
        }

        $validFormats = ['A4', 'A3', 'Letter', 'Legal'];
        if (! in_array($options['format'], $validFormats, true)) {
            $options['format'] = 'A4';
        }

        $validOrientations = ['portrait', 'landscape'];
        if (! in_array($options['orientation'], $validOrientations, true)) {
            $options['orientation'] = 'portrait';
        }

        $fontSize = (int) $options['font_size'];
        if ($fontSize < 8) {
            $fontSize = 8;
        }
        if ($fontSize > 20) {
            $fontSize = 20;
        }
        $options['font_size'] = $fontSize;

        foreach (['include_images', 'include_summaries', 'include_metadata', 'include_toc', 'page_numbers', 'watermark'] as $key) {
            $options[$key] = (bool) ($options[$key] ?? false);
        }

        return $options;
    }

    private function savePdfFile(string $pdfContent, array $options): string
    {
        $filename = $this->generateFilename($options);
        $filePath = $this->exportPath.'/'.$filename;

        Storage::disk($this->storageDisk)->put($filePath, $pdfContent);

        return $filePath;
    }

    private function generateFilename(array $options): string
    {
        $prefix = $options['filename_prefix'] ?? 'articles_export';
        $prefix = Str::slug((string) $prefix, '_');
        if ($prefix === '') {
            $prefix = 'articles_export';
        }

        $timestamp = now()->format('Y-m-d_H-i-s');
        $random = Str::lower(Str::random(8));

        return "{$prefix}_{$timestamp}_{$random}.pdf";
    }

    private function buildHtml(Collection $articles, array $options): string
    {
        $title = $articles->count() === 1 ? 'Article Export' : 'Articles Export';
        $exportDate = now()->format('Y-m-d H:i');

        $css = $this->buildCss($options);
        $watermark = $options['watermark'] ? $this->buildWatermarkHtml() : '';

        $toc = '';
        if ($options['include_toc'] && $articles->count() > 1) {
            $toc .= '<div class="page toc">';
            $toc .= '<h1>Table of Contents</h1>';
            $toc .= '<ol class="toc-list">';
            foreach ($articles as $article) {
                $toc .= '<li>'.e((string) $article->title).'</li>';
            }
            $toc .= '</ol>';
            $toc .= '</div><div class="page-break"></div>';
        }

        $body = '';
        foreach ($articles as $index => $article) {
            $body .= '<div class="page">';

            $body .= '<h1 class="article-title">'.e((string) $article->title).'</h1>';

            if ($options['include_metadata']) {
                $createdAt = $article->created_at ? $article->created_at->format('Y-m-d H:i') : '';
                $body .= '<div class="meta">';
                if ($createdAt !== '') {
                    $body .= '<span>Created: '.e($createdAt).'</span>';
                }
                if ($article->relationLoaded('tags') && $article->tags && $article->tags->count() > 0) {
                    $body .= '<span>Tags: '.e($article->tags->pluck('name')->implode(', ')).'</span>';
                }
                $body .= '</div>';
            }

            if ($options['include_images'] && ! empty($article->featured_image)) {
                $src = $this->normalizeImageSrc((string) $article->featured_image);
                if ($src !== null) {
                    $body .= '<div class="featured-image-wrap">';
                    $body .= '<img class="featured-image" src="'.e($src).'" alt="Featured image">';
                    $body .= '</div>';
                }
            }

            $content = (string) ($article->content ?? '');
            $content = $options['clean_reader_format']
                ? $this->cleanArticleHtml($content, $options['include_images'])
                : $content;
            $body .= '<div class="content">'.$content.'</div>';

            if ($options['include_summaries'] && $article->relationLoaded('summaries') && $article->summaries && $article->summaries->count() > 0) {
                $body .= '<div class="summaries">';
                $body .= '<h2>Ringkasan</h2>';
                foreach ($article->summaries as $summary) {
                    if (($summary->status ?? null) !== 'completed') {
                        continue;
                    }
                    $body .= '<div class="summary">';
                    $body .= '<div class="summary-source">'.e(Str::upper((string) ($summary->source ?? ''))).'</div>';
                    $body .= '<div class="summary-content">'.e((string) ($summary->content ?? '')).'</div>';
                    $body .= '</div>';
                }
                $body .= '</div>';
            }

            $body .= '</div>';

            if ($index < $articles->count() - 1) {
                $body .= '<div class="page-break"></div>';
            }
        }

        $footer = $this->buildFooterHtml($options);

        return '<!DOCTYPE html>'
            .'<html lang="en"><head><meta charset="utf-8"><title>'.e($title).'</title>'
            .$css
            .'</head><body>'
            .$watermark
            .'<div class="doc-header"><div class="doc-title">'.e($title).'</div><div class="doc-date">'.e($exportDate).'</div></div>'
            .$toc
            .$body
            .$footer
            .'</body></html>';
    }

    private function buildCss(array $options): string
    {
        $fontSize = (int) $options['font_size'];

        $template = $options['template'] ?? 'default';
        $fonts = [
            'default' => ['heading' => 'DejaVu Sans', 'body' => 'DejaVu Sans'],
            'academic' => ['heading' => 'DejaVu Serif', 'body' => 'DejaVu Serif'],
            'magazine' => ['heading' => 'DejaVu Sans', 'body' => 'DejaVu Sans'],
            'minimal' => ['heading' => 'DejaVu Sans', 'body' => 'DejaVu Sans'],
            'business' => ['heading' => 'DejaVu Sans', 'body' => 'DejaVu Sans'],
        ];
        $font = $fonts[$template] ?? $fonts['default'];

        $primary = match ($template) {
            'magazine' => '#111827',
            'business' => '#0f172a',
            default => '#1f2937',
        };

        $accent = match ($template) {
            'academic' => '#374151',
            'magazine' => '#7c3aed',
            'business' => '#2563eb',
            default => '#2563eb',
        };

        $marginTop = (int) (config('dompdf.typography.margin_top') ?? 20);
        $marginRight = (int) (config('dompdf.typography.margin_right') ?? 15);
        $marginBottom = (int) (config('dompdf.typography.margin_bottom') ?? 20);
        $marginLeft = (int) (config('dompdf.typography.margin_left') ?? 15);

        return '<style>'
            .'@page { margin: '.$marginTop.'px '.$marginRight.'px '.$marginBottom.'px '.$marginLeft.'px; }'
            .'body { font-family: '.$font['body'].'; font-size: '.$fontSize.'pt; line-height: 1.6; color: '.$primary.'; }'
            .'.doc-header { margin-bottom: 18px; padding-bottom: 10px; border-bottom: 2px solid '.$accent.'; }'
            .'.doc-title { font-family: '.$font['heading'].'; font-size: 18pt; font-weight: 700; color: '.$accent.'; }'
            .'.doc-date { font-size: 10pt; color: #6b7280; margin-top: 4px; }'
            .'.page { }'
            .'.article-title { font-family: '.$font['heading'].'; font-size: 20pt; margin: 0 0 8px 0; color: '.$accent.'; }'
            .'.meta { font-size: 10pt; color: #6b7280; margin-bottom: 14px; }'
            .'.meta span { margin-right: 14px; }'
            .'.featured-image-wrap { margin: 12px 0 16px 0; }'
            .'.featured-image { max-width: 100%; height: auto; border-radius: 6px; }'
            .'.content { }'
            .'.content p { margin: 0 0 10px 0; }'
            .'.content h2, .content h3 { color: '.$primary.'; }'
            .'.summaries { margin-top: 18px; padding-top: 12px; border-top: 1px solid #e5e7eb; }'
            .'.summaries h2 { margin: 0 0 10px 0; font-size: 14pt; color: '.$primary.'; }'
            .'.summary { margin: 10px 0; padding: 10px; border: 1px solid #e5e7eb; border-radius: 6px; }'
            .'.summary-source { font-size: 9pt; color: #6b7280; letter-spacing: 0.06em; margin-bottom: 6px; }'
            .'.summary-content { white-space: pre-wrap; }'
            .'.toc h1 { font-family: '.$font['heading'].'; color: '.$accent.'; margin: 0 0 12px 0; }'
            .'.toc-list { margin: 0; padding-left: 18px; }'
            .'.toc-list li { margin: 6px 0; }'
            .'.page-break { page-break-after: always; }'
            .'.doc-footer { position: fixed; bottom: 8px; left: 0; right: 0; text-align: center; font-size: 9pt; color: #6b7280; }'
            .'</style>';
    }

    private function buildFooterHtml(array $options): string
    {
        $text = (string) (config('dompdf.footer.text') ?? 'Generated by MyArtikel');
        $text = trim($text);
        if ($text === '') {
            return '';
        }

        if (! ($options['page_numbers'] ?? false)) {
            return '<div class="doc-footer">'.e($text).'</div>';
        }

        return '<div class="doc-footer">'.e($text).'</div>';
    }

    private function buildWatermarkHtml(): string
    {
        $text = (string) (config('dompdf.watermark.text') ?? 'CONFIDENTIAL');
        $opacity = (float) (config('dompdf.watermark.opacity') ?? 0.1);
        $size = (int) (config('dompdf.watermark.size') ?? 72);
        $angle = (int) (config('dompdf.watermark.angle') ?? -45);
        $color = (string) (config('dompdf.watermark.color') ?? '#cccccc');

        return '<div style="position: fixed; top: 45%; left: 0; right: 0; text-align: center; z-index: -1; transform: rotate('.$angle.'deg); opacity: '.$opacity.'; font-size: '.$size.'px; color: '.e($color).'; font-family: DejaVu Sans;">'
            .e($text)
            .'</div>';
    }

    private function normalizeImageSrc(string $src): ?string
    {
        $src = trim($src);
        if ($src === '') {
            return null;
        }

        if (Str::startsWith($src, ['data:'])) {
            return $src;
        }

        if (Str::startsWith($src, '/')) {
            $path = public_path(ltrim($src, '/'));
            if (is_file($path)) {
                return $path;
            }
        }

        if (is_file($src)) {
            return $src;
        }

        return null;
    }

    private function cleanArticleHtml(string $html, bool $includeImages): string
    {
        $html = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', '', $html) ?? $html;
        $html = preg_replace('/<style\b[^>]*>(.*?)<\/style>/is', '', $html) ?? $html;
        $html = preg_replace('/<iframe\b[^>]*>(.*?)<\/iframe>/is', '', $html) ?? $html;
        $html = preg_replace('/<object\b[^>]*>(.*?)<\/object>/is', '', $html) ?? $html;
        $html = preg_replace('/<embed\b[^>]*>(.*?)<\/embed>/is', '', $html) ?? $html;

        $html = preg_replace_callback('/<img\b[^>]*>/i', function ($matches) use ($includeImages) {
            $tag = $matches[0];
            if (! $includeImages) {
                return '';
            }

            if (! preg_match('/\bsrc\s*=\s*([\'"])(.*?)\1/i', $tag, $m)) {
                return '';
            }

            $src = trim((string) ($m[2] ?? ''));
            if ($src === '') {
                return '';
            }

            if (! Str::startsWith(Str::lower($src), ['data:'])) {
                return '';
            }

            return $tag;
        }, $html) ?? $html;

        return $html;
    }
}
