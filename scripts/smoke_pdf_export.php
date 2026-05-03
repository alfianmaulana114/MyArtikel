<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$user = App\Models\User::query()->first();
$article = App\Models\Article::query()->with(['tags', 'summaries'])->first();

if (!$user || !$article) {
    fwrite(STDERR, "Need at least 1 user and 1 article in DB\n");
    exit(2);
}

$svc = $app->make(App\Services\AdvancedPdfExportService::class);

$baseOptions = [
    'template' => 'default',
    'format' => 'A4',
    'orientation' => 'portrait',
    'include_summaries' => false,
    'include_metadata' => true,
    'include_toc' => false,
    'font_size' => 12,
    'page_numbers' => true,
    'watermark' => false,
    'clean_reader_format' => true,
];

foreach ([false, true] as $includeImages) {
    $options = array_merge($baseOptions, [
        'include_images' => $includeImages,
        'filename_prefix' => $includeImages ? 'smoke_pdf_images' : 'smoke_pdf_text',
    ]);

    $result = $svc->exportSingleArticle($article, $options);

    echo "include_images=" . ($includeImages ? 'true' : 'false') . "\n";
    echo "success=" . (($result['success'] ?? false) ? 'true' : 'false') . "\n";
    if (!($result['success'] ?? false)) {
        echo "error=" . ($result['error'] ?? '') . "\n";
        exit(1);
    }

    echo "file_path=" . $result['file_path'] . "\n";
    echo "file_size=" . $result['file_size'] . "\n";
    $exists = Illuminate\Support\Facades\Storage::disk('local')->exists($result['file_path']);
    echo "storage_exists=" . ($exists ? 'true' : 'false') . "\n";
}

