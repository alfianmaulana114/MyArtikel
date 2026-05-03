<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$user = App\Models\User::query()->first();
$article = App\Models\Article::query()->first();

if (!$user || !$article) {
    fwrite(STDERR, "Need at least 1 user and 1 article in DB\n");
    exit(2);
}

$svc = $app->make(App\Services\SummarizationService::class);

$options = [
    'prefer_ai' => false,
    'max_words' => 80,
    'language' => 'id',
];

$first = $svc->generateSummary($article->id, $user->id, $options);
$second = $svc->generateSummary($article->id, $user->id, $options);

echo "article_id={$article->id}\n";
echo "user_id={$user->id}\n";
echo "first_success=" . (($first['success'] ?? false) ? 'true' : 'false') . "\n";
echo "first_source=" . ($first['source'] ?? '') . "\n";
if (($first['success'] ?? false) && isset($first['summary'])) {
    echo "first_summary_id={$first['summary']->id}\n";
    echo "first_word_count={$first['summary']->word_count}\n";
}

echo "second_success=" . (($second['success'] ?? false) ? 'true' : 'false') . "\n";
echo "second_source=" . ($second['source'] ?? '') . "\n";
if (($second['success'] ?? false) && isset($second['summary'])) {
    echo "second_summary_id={$second['summary']->id}\n";
    echo "second_word_count={$second['summary']->word_count}\n";
}

