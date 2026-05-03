<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $article->title }}</title>
    <style>
        /* Magazine Template Styles */
        body {
            font-family: 'Georgia', 'Times New Roman', serif;
            font-size: 11pt;
            line-height: 1.7;
            color: #333;
            margin: 0;
            padding: 0;
            background: #fff;
        }
        
        .magazine-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2em;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        
        .magazine-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="25" cy="25" r="1" fill="white" opacity="0.1"/><circle cx="75" cy="75" r="1" fill="white" opacity="0.1"/><circle cx="50" cy="10" r="1" fill="white" opacity="0.1"/><circle cx="10" cy="60" r="1" fill="white" opacity="0.1"/><circle cx="90" cy="40" r="1" fill="white" opacity="0.1"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
            pointer-events: none;
        }
        
        .magazine-title {
            font-size: 2.5em;
            font-weight: 300;
            margin-bottom: 0.5em;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
            position: relative;
            z-index: 1;
        }
        
        .magazine-subtitle {
            font-size: 1.2em;
            opacity: 0.9;
            margin-bottom: 1em;
            position: relative;
            z-index: 1;
        }
        
        .magazine-meta {
            font-size: 0.9em;
            opacity: 0.8;
            position: relative;
            z-index: 1;
        }
        
        .magazine-meta .author {
            font-weight: 600;
            margin-right: 1em;
        }
        
        .magazine-meta .date {
            font-style: italic;
        }
        
        .magazine-tags {
            margin-top: 1em;
            position: relative;
            z-index: 1;
        }
        
        .magazine-tag {
            display: inline-block;
            background: rgba(255,255,255,0.2);
            color: white;
            padding: 0.3em 0.8em;
            border-radius: 20px;
            font-size: 0.8em;
            margin: 0.2em;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .magazine-content {
            padding: 2em;
            max-width: 800px;
            margin: 0 auto;
        }
        
        .article-intro {
            font-size: 1.3em;
            line-height: 1.6;
            color: #666;
            margin: 2em 0;
            padding: 1em;
            border-left: 4px solid #667eea;
            background: #f8f9fa;
            font-style: italic;
        }
        
        .content-section {
            margin: 2em 0;
        }
        
        .content-section h2 {
            font-size: 1.8em;
            color: #667eea;
            margin: 1.5em 0 1em 0;
            font-weight: 600;
            position: relative;
            padding-bottom: 0.5em;
        }
        
        .content-section h2::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 60px;
            height: 3px;
            background: linear-gradient(90deg, #667eea, #764ba2);
            border-radius: 2px;
        }
        
        .content-section h3 {
            font-size: 1.4em;
            color: #764ba2;
            margin: 1.2em 0 0.8em 0;
            font-weight: 500;
        }
        
        .content-section p {
            margin: 0 0 1.2em 0;
            text-align: justify;
            text-indent: 1em;
        }
        
        .content-section p:first-of-type {
            text-indent: 0;
            font-size: 1.1em;
            line-height: 1.8;
        }
        
        .highlight-box {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
            padding: 1.5em;
            margin: 2em 0;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            position: relative;
            overflow: hidden;
        }
        
        .highlight-box::before {
            content: '💡';
            position: absolute;
            top: 0.5em;
            left: 0.5em;
            font-size: 1.5em;
            opacity: 0.7;
        }
        
        .highlight-box h4 {
            margin: 0 0 0.5em 1.5em;
            font-size: 1.2em;
            font-weight: 600;
        }
        
        .highlight-box p {
            margin: 0;
            text-indent: 0;
        }
        
        .quote-section {
            background: #f8f9fa;
            border-left: 5px solid #667eea;
            margin: 2em 0;
            padding: 2em;
            position: relative;
            font-style: italic;
            font-size: 1.1em;
            line-height: 1.8;
        }
        
        .quote-section::before {
            content: '"';
            position: absolute;
            top: 0.2em;
            left: 0.2em;
            font-size: 4em;
            color: #667eea;
            opacity: 0.3;
            font-family: Georgia, serif;
        }
        
        .quote-author {
            text-align: right;
            font-style: normal;
            font-weight: 600;
            color: #667eea;
            margin-top: 1em;
        }
        
        .featured-image {
            text-align: center;
            margin: 3em 0;
            position: relative;
        }
        
        .featured-image img {
            max-width: 90%;
            border-radius: 15px;
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
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1em;
            margin: 2em 0;
        }
        
        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1.5em;
            border-radius: 10px;
            text-align: center;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .stat-number {
            font-size: 2em;
            font-weight: 700;
            display: block;
        }
        
        .stat-label {
            font-size: 0.9em;
            opacity: 0.9;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .summary-section {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 2em;
            margin: 3em 0;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        }
        
        .summary-section h2 {
            color: #667eea;
            font-size: 1.5em;
            margin-bottom: 1em;
            text-align: center;
        }
        
        .summary-content {
            background: white;
            padding: 1.5em;
            border-radius: 10px;
            margin: 1em 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .key-points {
            margin: 1.5em 0;
        }
        
        .key-points h3 {
            color: #764ba2;
            font-size: 1.2em;
            margin-bottom: 0.8em;
        }
        
        .key-points ul {
            list-style: none;
            padding: 0;
        }
        
        .key-points li {
            background: white;
            margin: 0.5em 0;
            padding: 0.8em 1em;
            border-radius: 8px;
            border-left: 4px solid #667eea;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            position: relative;
        }
        
        .key-points li::before {
            content: '▶';
            color: #667eea;
            font-weight: bold;
            margin-right: 0.5em;
        }
        
        .page-break {
            page-break-before: always;
        }
        
        .avoid-break {
            page-break-inside: avoid;
        }
        
        .magazine-footer {
            background: #333;
            color: white;
            padding: 2em;
            text-align: center;
            margin-top: 3em;
        }
        
        .magazine-footer .generated-date {
            font-size: 0.9em;
            opacity: 0.8;
        }
        
        @media print {
            body { padding: 0.5in; }
            .page-break { page-break-before: always; }
            .avoid-break { page-break-inside: avoid; }
            h1, h2, h3 { page-break-after: avoid; }
            img { page-break-inside: avoid; }
            .highlight-box, .summary-content, .key-points li {
                box-shadow: none;
                border: 1px solid #ddd;
            }
        }
    </style>
</head>
<body>
    <!-- Magazine Header -->
    <div class="magazine-header avoid-break">
        <h1 class="magazine-title">{{ $article->title }}</h1>
        @if(!empty($article->excerpt))
        <p class="magazine-subtitle">{{ $article->excerpt }}</p>
        @endif
        <div class="magazine-meta">
            <span class="author">By {{ $article->user->name }}</span>
            <span class="date">{{ $article->created_at->format('F j, Y') }}</span>
        </div>
        @if($article->tags->count() > 0)
        <div class="magazine-tags">
            @foreach($article->tags as $tag)
            <span class="magazine-tag">{{ $tag->name }}</span>
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

    <!-- Article Intro -->
    @if(!empty($article->excerpt))
    <div class="article-intro avoid-break">
        {{ $article->excerpt }}
    </div>
    @endif

    <!-- Main Content -->
    <div class="magazine-content">
        <div class="content-section avoid-break">
            @if(isset($clean_content))
                {!! $clean_content !!}
            @else
                {!! $article->content !!}
            @endif
        </div>
    </div>

    <!-- Statistics Section -->
    @if($options['include_statistics'] ?? false)
    <div class="stats-grid avoid-break">
        <div class="stat-card">
            <span class="stat-number">{{ str_word_count(strip_tags($article->content)) }}</span>
            <span class="stat-label">Words</span>
        </div>
        <div class="stat-card">
            <span class="stat-number">{{ $article->tags->count() }}</span>
            <span class="stat-label">Tags</span>
        </div>
        <div class="stat-card">
            <span class="stat-number">{{ $article->created_at->diffForHumans() }}</span>
            <span class="stat-label">Published</span>
        </div>
    </div>
    @endif

    <!-- Summaries Section -->
    @if($options['include_summaries'] && $summaries->count() > 0)
    <div class="summary-section avoid-break">
        <h2>Article Insights</h2>
        @foreach($summaries as $summary)
        <div class="summary-content avoid-break">
            <h3>{{ ucfirst($summary->source) }} Analysis</h3>
            <p>{{ $summary->content }}</p>
            
            @if(!empty($summary->key_points))
            <div class="key-points">
                <h4>Key Insights</h4>
                <ul>
                    @foreach($summary->key_points as $point)
                    <li>{{ $point }}</li>
                    @endforeach
                </ul>
            </div>
            @endif
        </div>
        @endforeach
    </div>
    @endif

    <!-- Magazine Footer -->
    <div class="magazine-footer avoid-break">
        <div class="generated-date">
            Generated by MyArtikel on {{ now()->format('F j, Y \a\t g:i A') }}
        </div>
    </div>
</body>
</html>