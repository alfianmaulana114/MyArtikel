<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $article->title }}</title>
    <style>
        /* Academic Template Styles */
        body {
            font-family: 'Times New Roman', serif;
            font-size: 12pt;
            line-height: 1.8;
            color: #000;
            margin: 0;
            padding: 1in;
            background: white;
        }
        
        .academic-header {
            text-align: center;
            margin-bottom: 2em;
            border-bottom: 2px solid #000;
            padding-bottom: 1em;
        }
        
        .academic-title {
            font-size: 18pt;
            font-weight: bold;
            margin-bottom: 1em;
            text-transform: uppercase;
        }
        
        .academic-meta {
            font-size: 11pt;
            margin-bottom: 0.5em;
        }
        
        .academic-meta .author {
            font-style: italic;
        }
        
        .academic-meta .date {
            font-weight: bold;
        }
        
        .abstract {
            margin: 2em 0;
            padding: 1em;
            border: 1px solid #ccc;
            background: #f9f9f9;
        }
        
        .abstract h3 {
            text-align: center;
            margin-bottom: 1em;
            font-size: 14pt;
            text-transform: uppercase;
        }
        
        .keywords {
            margin: 1em 0;
            font-size: 11pt;
        }
        
        .keywords strong {
            text-transform: uppercase;
        }
        
        .content-section {
            margin: 2em 0;
        }
        
        .content-section h2 {
            font-size: 14pt;
            font-weight: bold;
            margin: 1.5em 0 1em 0;
            text-transform: uppercase;
            border-bottom: 1px solid #000;
            padding-bottom: 0.5em;
        }
        
        .content-section h3 {
            font-size: 13pt;
            font-weight: bold;
            margin: 1.2em 0 0.8em 0;
        }
        
        .content-section p {
            text-align: justify;
            margin: 0 0 1em 0;
            text-indent: 0.5in;
        }
        
        .content-section p:first-of-type {
            text-indent: 0;
        }
        
        .citation {
            font-size: 10pt;
            color: #666;
            margin: 1em 0;
            padding-left: 1em;
            border-left: 2px solid #ccc;
        }
        
        .references {
            margin-top: 3em;
            border-top: 2px solid #000;
            padding-top: 1em;
        }
        
        .references h3 {
            font-size: 14pt;
            text-transform: uppercase;
            margin-bottom: 1em;
        }
        
        .references ol {
            font-size: 11pt;
            line-height: 1.6;
        }
        
        .references li {
            margin-bottom: 0.5em;
        }
        
        .page-break {
            page-break-before: always;
        }
        
        .avoid-break {
            page-break-inside: avoid;
        }
        
        .featured-image {
            text-align: center;
            margin: 2em 0;
        }
        
        .featured-image img {
            max-width: 80%;
            border: 1px solid #ccc;
            padding: 5px;
        }
        
        .summary-section {
            margin: 2em 0;
            padding: 1em;
            background: #f5f5f5;
            border-left: 4px solid #333;
        }
        
        .summary-section h4 {
            font-size: 12pt;
            font-weight: bold;
            margin-bottom: 1em;
        }
        
        .key-points {
            margin: 1em 0;
        }
        
        .key-points h5 {
            font-size: 11pt;
            font-weight: bold;
            margin-bottom: 0.5em;
        }
        
        .key-points ul {
            margin-left: 1em;
        }
        
        .key-points li {
            margin-bottom: 0.3em;
        }
        
        @media print {
            body { margin: 0.5in; }
            .page-break { page-break-before: always; }
            .avoid-break { page-break-inside: avoid; }
            h1, h2, h3 { page-break-after: avoid; }
            img { page-break-inside: avoid; }
        }
    </style>
</head>
<body>
    <!-- Academic Header -->
    <div class="academic-header avoid-break">
        <div class="academic-title">{{ $article->title }}</div>
        <div class="academic-meta">
            <div class="author">By {{ $article->user->name }}</div>
            <div class="date">{{ $article->created_at->format('F j, Y') }}</div>
        </div>
        @if($article->tags->count() > 0)
        <div class="keywords">
            <strong>Keywords:</strong> {{ $article->tags->pluck('name')->implode(', ') }}
        </div>
        @endif
    </div>

    <!-- Abstract Section -->
    @if(!empty($article->excerpt))
    <div class="abstract avoid-break">
        <h3>Abstract</h3>
        <p>{{ $article->excerpt }}</p>
    </div>
    @endif

    <!-- Featured Image -->
    @if($options['include_images'] && $article->featured_image)
    <div class="featured-image avoid-break">
        <img src="{{ $article->featured_image }}" alt="Featured Image">
    </div>
    @endif

    <!-- Main Content -->
    <div class="content-section avoid-break">
        @if(isset($clean_content))
            {!! $clean_content !!}
        @else
            {!! $article->content !!}
        @endif
    </div>

    <!-- Summaries Section -->
    @if($options['include_summaries'] && $summaries->count() > 0)
    <div class="summary-section avoid-break">
        <h2>Article Analysis</h2>
        @foreach($summaries as $summary)
        <div class="avoid-break">
            <h4>{{ ucfirst($summary->source) }} Summary</h4>
            <p>{{ $summary->content }}</p>
            
            @if(!empty($summary->key_points))
            <div class="key-points">
                <h5>Key Points:</h5>
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

    <!-- References Section -->
    @if($options['include_references'] ?? false)
    <div class="references page-break">
        <h3>References</h3>
        <ol>
            <li>{{ $article->title }}. Retrieved from {{ url('/articles/' . $article->id) }} on {{ now()->format('F j, Y') }}.</li>
            @if($article->meta && isset($article->meta['references']))
                @foreach($article->meta['references'] as $reference)
                <li>{{ $reference }}</li>
                @endforeach
            @endif
        </ol>
    </div>
    @endif
</body>
</html>