<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Articles Collection</title>
    <style>
        /* Multiple Articles Collection Template */
        body {
            font-family: 'Georgia', 'Times New Roman', serif;
            font-size: 11pt;
            line-height: 1.7;
            color: #333;
            margin: 0;
            padding: 0;
            background: #fff;
        }
        
        /* Cover Page */
        .cover-page {
            height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        
        .cover-page::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="25" cy="25" r="1" fill="white" opacity="0.1"/><circle cx="75" cy="75" r="1" fill="white" opacity="0.1"/><circle cx="50" cy="10" r="1" fill="white" opacity="0.1"/><circle cx="10" cy="60" r="1" fill="white" opacity="0.1"/><circle cx="90" cy="40" r="1" fill="white" opacity="0.1"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
            pointer-events: none;
        }
        
        .cover-title {
            font-size: 3em;
            font-weight: 300;
            margin-bottom: 0.5em;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
            position: relative;
            z-index: 1;
        }
        
        .cover-subtitle {
            font-size: 1.2em;
            opacity: 0.9;
            margin-bottom: 2em;
            position: relative;
            z-index: 1;
        }
        
        .cover-stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 2em;
            margin-top: 3em;
            position: relative;
            z-index: 1;
        }
        
        .cover-stat {
            text-align: center;
        }
        
        .cover-stat-number {
            display: block;
            font-size: 2.5em;
            font-weight: 700;
            margin-bottom: 0.2em;
        }
        
        .cover-stat-label {
            font-size: 0.9em;
            opacity: 0.8;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .cover-date {
            position: absolute;
            bottom: 2em;
            right: 2em;
            font-size: 0.9em;
            opacity: 0.8;
        }
        
        /* Table of Contents */
        .table-of-contents {
            padding: 3em;
            background: #f8f9fa;
        }
        
        .toc-header {
            text-align: center;
            margin-bottom: 3em;
        }
        
        .toc-title {
            font-size: 2.5em;
            color: #667eea;
            margin-bottom: 0.5em;
        }
        
        .toc-subtitle {
            color: #666;
            font-size: 1.1em;
        }
        
        .toc-list {
            max-width: 600px;
            margin: 0 auto;
        }
        
        .toc-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1em 0;
            border-bottom: 1px solid #e9ecef;
            text-decoration: none;
            color: #333;
            transition: all 0.3s ease;
        }
        
        .toc-item:hover {
            background: #fff;
            padding-left: 1em;
            padding-right: 1em;
            margin: 0 -1em;
            border-radius: 8px;
            color: #667eea;
        }
        
        .toc-item-number {
            font-weight: 600;
            color: #667eea;
            margin-right: 1em;
            min-width: 2em;
        }
        
        .toc-item-title {
            flex: 1;
            font-weight: 500;
        }
        
        .toc-item-page {
            color: #666;
            font-size: 0.9em;
        }
        
        /* Article Sections */
        .article-section {
            padding: 3em;
            border-bottom: 1px solid #e9ecef;
            page-break-before: always;
        }
        
        .article-section:last-child {
            border-bottom: none;
        }
        
        .article-header {
            margin-bottom: 2em;
            padding-bottom: 1em;
            border-bottom: 2px solid #667eea;
        }
        
        .article-title {
            font-size: 2.2em;
            color: #333;
            margin-bottom: 0.5em;
            font-weight: 600;
        }
        
        .article-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 1em;
            font-size: 0.9em;
            color: #666;
        }
        
        .article-meta-item {
            display: flex;
            align-items: center;
            gap: 0.5em;
        }
        
        .article-meta-item::before {
            content: '•';
            color: #667eea;
            font-weight: bold;
        }
        
        .article-meta-item:first-child::before {
            display: none;
        }
        
        .article-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5em;
            margin-top: 1em;
        }
        
        .article-tag {
            background: #667eea;
            color: white;
            padding: 0.3em 0.8em;
            border-radius: 15px;
            font-size: 0.8em;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        /* Content Styling */
        .article-content {
            line-height: 1.8;
            color: #444;
        }
        
        .article-content h2 {
            font-size: 1.8em;
            color: #667eea;
            margin: 1.5em 0 1em 0;
            font-weight: 600;
            position: relative;
            padding-bottom: 0.5em;
        }
        
        .article-content h2::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 50px;
            height: 3px;
            background: #667eea;
            border-radius: 2px;
        }
        
        .article-content h3 {
            font-size: 1.4em;
            color: #764ba2;
            margin: 1.2em 0 0.8em 0;
            font-weight: 500;
        }
        
        .article-content p {
            margin: 0 0 1.2em 0;
            text-align: justify;
        }
        
        .article-content p:first-of-type {
            font-size: 1.1em;
            line-height: 1.7;
            color: #555;
        }
        
        /* Featured Images */
        .featured-image {
            text-align: center;
            margin: 2em 0;
            position: relative;
        }
        
        .featured-image img {
            max-width: 80%;
            border-radius: 10px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
            transition: transform 0.3s ease;
        }
        
        .featured-image img:hover {
            transform: scale(1.02);
        }
        
        .image-caption {
            font-size: 0.9em;
            color: #666;
            text-align: center;
            margin-top: 1em;
            font-style: italic;
        }
        
        /* Statistics Section */
        .collection-stats {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2em;
            margin: 2em 0;
            border-radius: 15px;
            text-align: center;
        }
        
        .collection-stats h3 {
            margin-bottom: 1em;
            font-size: 1.5em;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1em;
        }
        
        .stat-card {
            background: rgba(255,255,255,0.1);
            padding: 1em;
            border-radius: 10px;
            backdrop-filter: blur(10px);
        }
        
        .stat-number {
            display: block;
            font-size: 1.8em;
            font-weight: 700;
            margin-bottom: 0.2em;
        }
        
        .stat-label {
            font-size: 0.8em;
            opacity: 0.9;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        /* Footer */
        .collection-footer {
            background: #333;
            color: white;
            padding: 2em;
            text-align: center;
            margin-top: 3em;
        }
        
        .footer-content {
            max-width: 600px;
            margin: 0 auto;
        }
        
        .generated-date {
            font-size: 0.9em;
            opacity: 0.8;
            margin-bottom: 1em;
        }
        
        .collection-info {
            font-size: 0.8em;
            opacity: 0.7;
        }
        
        /* Page Breaks */
        .page-break {
            page-break-before: always;
        }
        
        .avoid-break {
            page-break-inside: avoid;
        }
        
        @media print {
            body { padding: 0.5in; }
            .page-break { page-break-before: always; }
            .avoid-break { page-break-inside: avoid; }
            .article-content h2 { page-break-after: avoid; }
            .featured-image img { page-break-inside: avoid; }
        }
    </style>
</head>
<body>
    <!-- Cover Page -->
    <div class="cover-page">
        <h1 class="cover-title">Articles Collection</h1>
        <p class="cover-subtitle">A curated selection of articles</p>
        
        <div class="cover-stats">
            <div class="cover-stat">
                <span class="cover-stat-number">{{ $total_articles }}</span>
                <span class="cover-stat-label">Articles</span>
            </div>
            <div class="cover-stat">
                <span class="cover-stat-number">{{ number_format($statistics['total_words'] ?? 0) }}</span>
                <span class="cover-stat-label">Words</span>
            </div>
            <div class="cover-stat">
                <span class="cover-stat-number">{{ $statistics['total_tags'] ?? 0 }}</span>
                <span class="cover-stat-label">Tags</span>
            </div>
        </div>
        
        <div class="cover-date">
            Generated on {{ now()->format('F j, Y') }}
        </div>
    </div>

    <!-- Table of Contents -->
    @if($options['include_toc'] ?? true)
    <div class="table-of-contents page-break">
        <div class="toc-header">
            <h2 class="toc-title">Table of Contents</h2>
            <p class="toc-subtitle">Navigate through your articles</p>
        </div>
        
        <div class="toc-list">
            @foreach($processed_articles ?? $articles as $index => $item)
                @php
                    $article = $item['article'] ?? $item;
                    $pageNumber = $index + 3; // Cover + TOC + articles
                @endphp
                <a href="#article-{{ $article->id }}" class="toc-item">
                    <span class="toc-item-number">{{ $index + 1 }}</span>
                    <span class="toc-item-title">{{ $article->title }}</span>
                    <span class="toc-item-page">Page {{ $pageNumber }}</span>
                </a>
            @endforeach
        </div>
    </div>
    @endif

    <!-- Collection Statistics -->
    @if(!empty($statistics))
    <div class="collection-stats page-break avoid-break">
        <h3>Collection Statistics</h3>
        <div class="stats-grid">
            <div class="stat-card">
                <span class="stat-number">{{ $total_articles }}</span>
                <span class="stat-label">Total Articles</span>
            </div>
            <div class="stat-card">
                <span class="stat-number">{{ number_format($statistics['total_words'] ?? 0) }}</span>
                <span class="stat-label">Total Words</span>
            </div>
            <div class="stat-card">
                <span class="stat-number">{{ $statistics['total_tags'] ?? 0 }}</span>
                <span class="stat-label">Unique Tags</span>
            </div>
            <div class="stat-card">
                <span class="stat-number">{{ $statistics['avg_word_count'] ?? 0 }}</span>
                <span class="stat-label">Avg Words/Article</span>
            </div>
            @if(isset($statistics['date_range']))
            <div class="stat-card">
                <span class="stat-number">{{ $statistics['date_range']['oldest'] }}</span>
                <span class="stat-label">Earliest Article</span>
            </div>
            <div class="stat-card">
                <span class="stat-number">{{ $statistics['date_range']['newest'] }}</span>
                <span class="stat-label">Latest Article</span>
            </div>
            @endif
        </div>
    </div>
    @endif

    <!-- Articles Content -->
    @foreach($processed_articles ?? $articles as $item)
        @php
            $article = $item['article'] ?? $item;
            $cleanContent = $item['clean_content'] ?? $article->content;
            $wordCount = $item['word_count'] ?? str_word_count(strip_tags($article->content));
        @endphp
        
        <div class="article-section avoid-break" id="article-{{ $article->id }}">
            <!-- Article Header -->
            <div class="article-header">
                <h1 class="article-title">{{ $article->title }}</h1>
                <div class="article-meta">
                    <div class="article-meta-item">
                        <strong>Author:</strong> {{ $article->user->name }}
                    </div>
                    <div class="article-meta-item">
                        <strong>Created:</strong> {{ $article->created_at->format('F j, Y') }}
                    </div>
                    <div class="article-meta-item">
                        <strong>Words:</strong> {{ number_format($wordCount) }}
                    </div>
                    @if($article->updated_at != $article->created_at)
                    <div class="article-meta-item">
                        <strong>Updated:</strong> {{ $article->updated_at->format('F j, Y') }}
                    </div>
                    @endif
                </div>
                
                @if($article->tags->count() > 0)
                <div class="article-tags">
                    @foreach($article->tags as $tag)
                    <span class="article-tag">{{ $tag->name }}</span>
                    @endforeach
                </div>
                @endif
            </div>

            <!-- Featured Image -->
            @if($options['include_images'] && $article->featured_image)
            <div class="featured-image avoid-break">
                <img src="{{ $article->featured_image }}" alt="Featured Image">
                @if(isset($article->meta['image_caption']))
                <p class="image-caption">{{ $article->meta['image_caption'] }}</p>
                @endif
            </div>
            @endif

            <!-- Article Content -->
            <div class="article-content avoid-break">
                @if(isset($cleanContent))
                    {!! $cleanContent !!}
                @else
                    {!! $article->content !!}
                @endif
            </div>

            <!-- Article Footer -->
            <div class="article-footer avoid-break">
                <p class="article-url">
                    <strong>Original URL:</strong> {{ url('/articles/' . $article->id) }}
                </p>
                <p class="export-date">
                    <strong>Exported on:</strong> {{ now()->format('F j, Y \a\t g:i A') }}
                </p>
            </div>
        </div>
    @endforeach

    <!-- Collection Footer -->
    <div class="collection-footer page-break">
        <div class="footer-content">
            <div class="generated-date">
                Generated by MyArtikel on {{ now()->format('F j, Y \a\t g:i A') }}
            </div>
            <div class="collection-info">
                This collection contains {{ $total_articles }} articles exported from your MyArtikel account.
                For more information, visit {{ url('/articles') }}
            </div>
        </div>
    </div>
</body>
</html>