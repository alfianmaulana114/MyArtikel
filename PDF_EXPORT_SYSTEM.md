# PDF Export System Documentation

## Overview

Sistem PDF Export yang komprehensif untuk MyArtikel dengan fitur-fitur advanced:
- **Multi-template support** (Academic, Magazine, Minimal, Business)
- **Clean reader content processing**
- **Advanced typography and styling**
- **Page break optimization**
- **Batch export capabilities**
- **Export history tracking**
- **Background processing for large collections**

## Architecture

```
┌─────────────────┐    ┌─────────────────┐    ┌─────────────────┐
│   User Request  │    │  PDF Service    │    │  DOMPDF Engine  │
└─────────┬───────┘    └─────────┬───────┘    └─────────┬───────┘
          │                      │                      │
          ▼                      ▼                      ▼
┌─────────────────┐    ┌─────────────────┐    ┌─────────────────┐
│ Export Form     │───▶│ Template Engine │───▶│ PDF Generation │
│ Controller        │    │ (Blade Views)   │    │ (HTML to PDF)   │
└─────────────────┘    └─────────────────┘    └─────────────────┘
          │                      │                      │
          ▼                      ▼                      ▼
┌─────────────────┐    ┌─────────────────┐    ┌─────────────────┐
│ Export History  │◀───│ File Storage    │◀───│ Download/Preview│
│ Tracking        │    │ (Local/Disk)    │    │ URLs            │
└─────────────────┘    └─────────────────┘    └─────────────────┘
```

## Features

### 1. Template System

#### Available Templates
- **Default**: Clean and professional layout
- **Academic**: Academic-style with citations and references
- **Magazine**: Modern magazine-style with gradients and styling
- **Minimal**: Minimalist design focusing on content
- **Business**: Business-style with headers and footers

#### Template Features
```php
// Template configuration
$templates = [
    'academic' => [
        'description' => 'Academic-style layout with citations',
        'features' => ['abstract', 'keywords', 'references', 'page_numbers'],
        'typography' => 'Times New Roman, serif',
        'line_height' => 1.8
    ],
    'magazine' => [
        'description' => 'Modern magazine-style layout',
        'features' => ['gradients', 'highlight_boxes', 'statistics', 'image_captions'],
        'typography' => 'Georgia, serif',
        'line_height' => 1.7
    ],
    // ... more templates
];
```

### 2. Content Processing

#### Clean Reader Format
```php
// Content cleaning and optimization
private function processCleanReaderContent(string $content): string
{
    // Remove unwanted HTML elements
    $content = preg_replace('/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/mi', '', $content);
    
    // Strip unwanted tags while preserving content structure
    $allowedTags = '<p><br><strong><em><u><h1><h2><h3><h4><h5><h6><ul><ol><li><blockquote><code><pre><a><img><table><thead><tbody><tr><th><td>';
    $content = strip_tags($content, $allowedTags);
    
    // Optimize spacing and formatting
    $content = preg_replace('/<p>(\s*<br\s*\/?>\s*)*<\/p>/i', '', $content);
    $content = preg_replace('/\n\s*\n/', "\n\n", $content);
    
    return trim($content);
}
```

#### Typography Optimization
```php
// Advanced typography settings
private function getDefaultTypographyStyles(array $options): string
{
    $fontSize = $options['font_size'] ?? 12;
    $lineHeight = $options['line_height'] ?? 1.6;
    $fontFamily = $options['font_family'] ?? 'DejaVu Sans';
    
    return "
        body {
            font-family: {$fontFamily}, sans-serif;
            font-size: {$fontSize}px;
            line-height: {$lineHeight};
            color: #333;
            margin: 0;
            padding: 0;
        }
        
        h1 { font-size: " . ($fontSize * 2.5) . "px; margin: 20px 0 15px 0; }
        h2 { font-size: " . ($fontSize * 2) . "px; margin: 18px 0 12px 0; }
        h3 { font-size: " . ($fontSize * 1.5) . "px; margin: 15px 0 10px 0; }
        
        p { margin: 0 0 15px 0; text-align: justify; }
        
        /* Page break optimization */
        .page-break { page-break-before: always; }
        .avoid-break { page-break-inside: avoid; }
        h1, h2, h3 { page-break-after: avoid; }
        img { page-break-inside: avoid; }
    ";
}
```

### 3. Page Break Optimization

#### Smart Page Breaking
```php
// Page break rules for better layout
'page_breaks' => [
    'avoid_break_inside' => [
        'h1', 'h2', 'h3', 'h4', 'h5', 'h6',
        'img', 'table', 'blockquote', '.avoid-break'
    ],
    'always_break_before' => [
        'h1', '.page-break', '.cover-page'
    ],
    'avoid_break_after' => [
        'h1', 'h2', 'h3'
    ],
]
```

#### Content Flow Management
```css
/* CSS for optimal content flow */
.avoid-break {
    page-break-inside: avoid;
}

.page-break {
    page-break-before: always;
}

h1, h2, h3 {
    page-break-after: avoid;
}

img {
    page-break-inside: avoid;
    max-width: 100%;
    height: auto;
}
```

### 4. Batch Export Features

#### Multiple Articles Export
```php
// Export multiple articles with table of contents
public function exportMultipleArticles($articles, array $options = []): array
{
    $pdfData = [
        'articles' => $articles,
        'total_articles' => $articles->count(),
        'statistics' => [
            'total_words' => $articles->sum(fn($article) => str_word_count(strip_tags($article->content))),
            'total_tags' => $articles->pluck('tags')->flatten()->unique('id')->count(),
            'date_range' => [
                'oldest' => $articles->min('created_at')->format('Y-m-d'),
                'newest' => $articles->max('created_at')->format('Y-m-d')
            ]
        ],
        'table_of_contents' => $this->generateTableOfContents($articles)
    ];
    
    return $this->generatePdfFromTemplate($pdfData, 'multiple-articles', $options);
}
```

#### Background Processing
```php
// Background processing for large collections
dispatch(new ProcessPdfExport(
    $userId,
    $articleIds,
    [
        'template' => 'magazine',
        'include_toc' => true,
        'batch_size' => 50,
        'compress' => true
    ]
))->onQueue('exports');
```

## Usage Examples

### Basic Single Article Export

```php
use App\Services\AdvancedPdfExportService;

$pdfService = app(AdvancedPdfExportService::class);

// Simple export
$result = $pdfService->exportSingleArticle($article, [
    'template' => 'default',
    'format' => 'A4',
    'orientation' => 'portrait'
]);

if ($result['success']) {
    // Download URL
    $downloadUrl = $result['download_url'];
    $fileSize = $result['file_size'];
    $processingTime = $result['processing_time'];
}
```

### Advanced Export with Custom Options

```php
// Advanced export with all options
$result = $pdfService->exportSingleArticle($article, [
    'template' => 'academic',
    'format' => 'A4',
    'orientation' => 'portrait',
    'font_size' => 12,
    'line_height' => 1.8,
    'include_images' => true,
    'include_summaries' => true,
    'include_metadata' => true,
    'page_numbers' => true,
    'watermark' => false,
    'header_footer' => true,
    'compress' => true,
    'clean_reader_format' => true,
    'filename_prefix' => 'research_paper'
]);
```

### Batch Export Multiple Articles

```php
// Export multiple articles as collection
$articles = Article::where('user_id', $userId)
    ->whereIn('id', [1, 2, 3, 4, 5])
    ->get();

$result = $pdfService->exportMultipleArticles($articles, [
    'template' => 'magazine',
    'include_toc' => true,
    'include_statistics' => true,
    'page_numbers' => true,
    'watermark' => false,
    'compress' => true
]);
```

### Template-based Export

```php
// Use custom template
$result = $pdfService->exportWithTemplate($articles, 'business', [
    'format' => 'Letter',
    'orientation' => 'landscape',
    'include_company_logo' => true,
    'include_header_footer' => true,
    'watermark_text' => 'CONFIDENTIAL'
]);
```

## API Endpoints

### Export Form
```http
GET /pdf/export
```
Returns the PDF export form with available templates and options.

### Single Article Export
```http
POST /pdf/export/single/{article}
Content-Type: application/json

{
    "template": "academic",
    "format": "A4",
    "orientation": "portrait",
    "include_images": true,
    "include_summaries": false,
    "font_size": 12,
    "page_numbers": true
}
```

### Multiple Articles Export
```http
POST /pdf/export/multiple
Content-Type: application/json

{
    "article_ids": [1, 2, 3, 4, 5],
    "template": "magazine",
    "include_toc": true,
    "include_statistics": true,
    "compress": true
}
```

### Export History
```http
GET /pdf/history?limit=20&status=completed&template=academic
```

### Download Export
```http
GET /pdf/download/{export}
```

### Preview Export
```http
GET /pdf/preview/{export}
```

## Configuration

### DOMPDF Configuration
```php
// config/dompdf.php
return [
    'font_dir' => storage_path('fonts/'),
    'font_cache' => storage_path('fonts/'),
    'temp_dir' => sys_get_temp_dir(),
    'chroot' => realpath(base_path()),
    
    // Performance settings
    'enable_font_subsetting' => true,
    'enable_php' => false,
    'enable_javascript' => false,
    'enable_remote' => true,
    'enable_html5_parser' => true,
    
    // Default settings
    'format' => 'A4',
    'orientation' => 'portrait',
    'default_font' => 'DejaVu Sans',
    'dpi' => 96,
    
    // Typography
    'typography' => [
        'line_height' => 1.6,
        'font_size' => [
            'body' => 12,
            'heading1' => 24,
            'heading2' => 20,
            'heading3' => 16,
        ],
    ],
    
    // Page breaks
    'page_breaks' => [
        'avoid_break_inside' => ['h1', 'h2', 'h3', 'img', 'table'],
        'always_break_before' => ['h1', '.page-break'],
        'avoid_break_after' => ['h1', 'h2', 'h3'],
    ],
    
    // Security
    'security' => [
        'enable_remote' => true,
        'enable_php' => false,
        'enable_javascript' => false,
        'allowed_protocols' => ['http', 'https', 'file'],
    ],
];
```

### Storage Configuration
```php
// config/filesystems.php
'disks' => [
    'pdf_exports' => [
        'driver' => 'local',
        'root' => storage_path('app/exports/pdf'),
        'url' => env('APP_URL').'/storage/exports/pdf',
        'visibility' => 'private',
    ],
],
```

## Performance Optimization

### 1. Font Subsetting
```php
// Enable font subsetting to reduce file size
'enable_font_subsetting' => true,
```

### 2. Image Optimization
```php
// Image compression and resizing
'images' => [
    'quality' => 85,
    'max_width' => 1200,
    'max_height' => 1200,
    'resize' => true,
    'compression' => true,
],
```

### 3. Memory Management
```php
// Memory and execution time limits
'performance' => [
    'memory_limit' => '256M',
    'max_execution_time' => 300,
    'enable_compression' => true,
],
```

### 4. Caching
```php
// Enable caching for better performance
'enable_font_caching' => true,
'enable_image_caching' => true,
'enable_css_caching' => true,
```

## Command Line Management

### Export Statistics
```bash
php artisan pdf:export-manager stats
```

### Cleanup Old Exports
```bash
php artisan pdf:export-manager cleanup --days=30
```

### Test Export Functionality
```bash
php artisan pdf:export-manager test --article-id=1
```

### Optimize Large Exports
```bash
php artisan pdf:export-manager optimize
```

## Error Handling

### Common Issues and Solutions

#### 1. Memory Issues with Large PDFs
```php
// Increase memory limit for large exports
ini_set('memory_limit', '512M');
set_time_limit(300);

// Use chunked processing for large collections
$articles->chunk(10, function ($chunk) use (&$pdfData) {
    foreach ($chunk as $article) {
        $pdfData['articles'][] = $this->processArticle($article);
    }
});
```

#### 2. Font Loading Issues
```php
// Ensure fonts are properly loaded
$pdf->setOptions([
    'fontDir' => storage_path('fonts/'),
    'fontCache' => storage_path('fonts/'),
    'defaultFont' => 'DejaVu Sans',
]);
```

#### 3. Image Loading Issues
```php
// Configure image loading
$pdf->setOptions([
    'enable_remote' => true,
    'isRemoteEnabled' => true,
    'chroot' => realpath(base_path()),
]);
```

## Security Considerations

### 1. Content Sanitization
```php
// Sanitize HTML content before PDF generation
private function sanitizeContent(string $content): string
{
    // Remove potentially dangerous content
    $content = strip_tags($content, $this->allowedTags);
    
    // Validate image URLs
    $content = $this->validateImageUrls($content);
    
    return $content;
}
```

### 2. File Access Control
```php
// Secure file downloads
public function download(int $exportId)
{
    $export = Export::where('id', $exportId)
        ->where('user_id', Auth::id())
        ->firstOrFail();
    
    if ($export->isExpired()) {
        throw new Exception('Export has expired');
    }
    
    return Storage::disk('local')->download($export->file_path);
}
```

### 3. Rate Limiting
```php
// Implement rate limiting for exports
Route::middleware(['auth', 'throttle:pdf-exports'])->group(function () {
    Route::post('/pdf/export/multiple', [PdfExportController::class, 'exportMultiple']);
});
```

## Monitoring and Analytics

### Export Tracking
```php
// Track export metrics
Export::create([
    'user_id' => $userId,
    'type' => 'pdf',
    'file_path' => $filePath,
    'file_size' => $fileSize,
    'status' => 'completed',
    'metadata' => [
        'template' => $template,
        'article_count' => $articleCount,
        'processing_time' => $processingTime,
        'options' => $exportOptions
    ],
    'processing_time_ms' => intval($processingTime * 1000),
    'expires_at' => now()->addDays(30)
]);
```

### Performance Monitoring
```php
// Monitor processing times and success rates
$stats = [
    'total_exports' => Export::where('type', 'pdf')->count(),
    'avg_processing_time' => Export::where('type', 'pdf')->avg('processing_time_ms'),
    'success_rate' => Export::where('type', 'pdf')->where('status', 'completed')->count() / Export::where('type', 'pdf')->count() * 100,
    'avg_file_size' => Export::where('type', 'pdf')->avg('file_size'),
];
```

This comprehensive PDF export system provides professional-quality document generation with extensive customization options, performance optimization, and robust error handling for the MyArtikel application.