<?php

namespace App\Services;

use App\Models\Article;
use Illuminate\Support\Collection;

class CitationFormatterService
{
    const STYLES = [
        'apa' => 'APA 7th Edition',
        'mla' => 'MLA 9th Edition',
        'ieee' => 'IEEE',
        'chicago' => 'Chicago',
        'harvard' => 'Harvard',
    ];

    /**
     * Format multiple articles into bibliography
     */
    public function formatBibliography(Collection $articles, string $style = 'apa'): array
    {
        $citations = [];

        foreach ($articles as $article) {
            $citations[] = $this->formatCitation($article, $style);
        }

        // Sort alphabetically by author
        usort($citations, function ($a, $b) {
            return strcasecmp($a['author_sort'], $b['author_sort']);
        });

        // Add numbering for IEEE
        if ($style === 'ieee') {
            foreach ($citations as $index => $citation) {
                $citations[$index]['formatted'] = '['.($index + 1).'] '.$citation['formatted'];
            }
        }

        return $citations;
    }

    /**
     * Format single article citation
     */
    public function formatCitation(Article $article, string $style = 'apa'): array
    {
        $metadata = $article->metadata ?? [];

        $data = [
            'author' => $this->extractAuthor($metadata, $article),
            'year' => $this->extractYear($metadata, $article),
            'title' => $article->title ?? 'Untitled',
            'journal' => $this->extractJournal($metadata, $article),
            'volume' => $metadata['volume'] ?? null,
            'issue' => $metadata['issue'] ?? null,
            'pages' => $metadata['pages'] ?? null,
            'doi' => $metadata['doi'] ?? null,
            'url' => $article->source_url ?? null,
            'source_domain' => $article->source_domain ?? null,
        ];

        $formatted = match ($style) {
            'apa' => $this->formatAPA($data),
            'mla' => $this->formatMLA($data),
            'ieee' => $this->formatIEEE($data),
            'chicago' => $this->formatChicago($data),
            'harvard' => $this->formatHarvard($data),
            default => $this->formatAPA($data),
        };

        return [
            'formatted' => $formatted,
            'author_sort' => $data['author'],
            'style' => $style,
            'article_id' => $article->id,
        ];
    }

    /**
     * Extract author name from metadata or content
     */
    protected function extractAuthor(array $metadata, Article $article): string
    {
        // Try metadata first
        if (! empty($metadata['author'])) {
            return $metadata['author'];
        }

        // Try OpenGraph author
        if (! empty($metadata['og_author'])) {
            return $metadata['og_author'];
        }

        // Try to extract from content (basic pattern matching)
        $content = $article->text_extracted ?: $article->content;
        if ($content) {
            // Look for "by Author Name" pattern
            if (preg_match('/^by\s+([A-Z][a-z]+(?:\s+[A-Z][a-z]+)+)/m', $content, $matches)) {
                return $matches[1];
            }
            // Look for author at start of article
            if (preg_match('/^([A-Z][a-z]+(?:\s+[A-Z][a-z]+)+)\s*,/', $content, $matches)) {
                return $matches[1];
            }
        }

        return 'Unknown';
    }

    /**
     * Extract year from metadata or dates
     */
    protected function extractYear(array $metadata, Article $article): string
    {
        if (! empty($metadata['year'])) {
            return $metadata['year'];
        }

        if (! empty($metadata['published_date'])) {
            return date('Y', strtotime($metadata['published_date']));
        }

        if ($article->fetched_at) {
            return $article->fetched_at->format('Y');
        }

        if ($article->published_at) {
            return $article->published_at->format('Y');
        }

        return 'n.d.';
    }

    /**
     * Extract journal/source name
     */
    protected function extractJournal(array $metadata, Article $article): string
    {
        if (! empty($metadata['journal'])) {
            return $metadata['journal'];
        }

        if (! empty($metadata['publication'])) {
            return $metadata['publication'];
        }

        if (! empty($metadata['og_site_name'])) {
            return $metadata['og_site_name'];
        }

        return $article->source_domain ?? 'Unknown Source';
    }

    /**
     * Format in APA 7th style
     */
    protected function formatAPA(array $data): string
    {
        $citation = $data['author'].' ('.$data['year'].'). ';
        $citation .= $data['title'].'. ';
        $citation .= $data['journal'];

        if ($data['volume']) {
            $citation .= ', '.$data['volume'];
            if ($data['issue']) {
                $citation .= '('.$data['issue'].')';
            }
        }

        if ($data['pages']) {
            $citation .= ', '.$data['pages'];
        }

        $citation .= '.';

        if ($data['doi']) {
            $citation .= ' https://doi.org/'.$data['doi'];
        } elseif ($data['url']) {
            $citation .= ' '.$data['url'];
        }

        return $citation;
    }

    /**
     * Format in MLA 9th style
     */
    protected function formatMLA(array $data): string
    {
        $citation = $data['author'].'. ';
        $citation .= '"'.$data['title'].'." ';
        $citation .= $data['journal'];

        if ($data['volume']) {
            $citation .= ', vol. '.$data['volume'];
        }

        if ($data['issue']) {
            $citation .= ', no. '.$data['issue'];
        }

        $citation .= ', '.$data['year'];

        if ($data['pages']) {
            $citation .= ', pp. '.$data['pages'];
        }

        $citation .= '.';

        if ($data['url']) {
            $citation .= ' '.$data['url'].'.';
        }

        return $citation;
    }

    /**
     * Format in IEEE style
     */
    protected function formatIEEE(array $data): string
    {
        // IEEE uses initials for first names
        $authorParts = explode(', ', $data['author']);
        $lastName = $authorParts[0];
        $firstName = $authorParts[1] ?? '';
        $initials = '';
        if ($firstName) {
            foreach (explode(' ', $firstName) as $name) {
                $initials .= strtoupper($name[0]).'. ';
            }
        }
        $author = trim($initials.' '.$lastName);

        $citation = $author.', ';
        $citation .= '"'.$data['title'].'," ';
        $citation .= $data['journal'];

        if ($data['volume']) {
            $citation .= ', vol. '.$data['volume'];
        }

        if ($data['issue']) {
            $citation .= ', no. '.$data['issue'];
        }

        if ($data['pages']) {
            $citation .= ', pp. '.$data['pages'];
        }

        $citation .= ', '.$data['year'].'.';

        if ($data['doi']) {
            $citation .= ' doi: '.$data['doi'].'.';
        } elseif ($data['url']) {
            $citation .= ' [Online]. Available: '.$data['url'].'.';
        }

        return $citation;
    }

    /**
     * Format in Chicago style
     */
    protected function formatChicago(array $data): string
    {
        $citation = $data['author'].'. ';
        $citation .= '"'.$data['title'].'." ';
        $citation .= $data['journal'];

        if ($data['volume']) {
            $citation .= ' '.$data['volume'];
            if ($data['issue']) {
                $citation .= ', no. '.$data['issue'];
            }
        }

        $citation .= ' ('.$data['year'].')';

        if ($data['pages']) {
            $citation .= ': '.$data['pages'];
        }

        $citation .= '.';

        if ($data['doi']) {
            $citation .= ' https://doi.org/'.$data['doi'];
        } elseif ($data['url']) {
            $citation .= ' '.$data['url'];
        }

        return $citation;
    }

    /**
     * Format in Harvard style
     */
    protected function formatHarvard(array $data): string
    {
        $citation = $data['author'].', '.$data['year'].'. ';
        $citation .= $data['title'].'. ';
        $citation .= $data['journal'];

        if ($data['volume']) {
            $citation .= ', '.$data['volume'];
            if ($data['issue']) {
                $citation .= '('.$data['issue'].')';
            }
        }

        if ($data['pages']) {
            $citation .= ', pp.'.$data['pages'];
        }

        $citation .= '.';

        if ($data['doi']) {
            $citation .= ' Available at: https://doi.org/'.$data['doi'];
        } elseif ($data['url']) {
            $citation .= ' Available at: '.$data['url'];
        }

        return $citation;
    }

    /**
     * Export bibliography to BibTeX format
     */
    public function exportToBibTeX(Collection $articles): string
    {
        $output = '';

        foreach ($articles as $index => $article) {
            $metadata = $article->metadata ?? [];
            $author = $this->extractAuthor($metadata, $article);
            $year = $this->extractYear($metadata, $article);
            $title = $article->title ?? 'Untitled';
            $journal = $this->extractJournal($metadata, $article);

            // Create citation key from author last name + year
            $authorParts = explode(', ', $author);
            $lastName = strtolower($authorParts[0]);
            $lastName = preg_replace('/[^a-z]/', '', $lastName);
            $citeKey = $lastName.$year.$index;

            $output .= '@article{'.$citeKey.",\n";
            $output .= '  author = {'.$author."},\n";
            $output .= '  title = {'.$title."},\n";
            $output .= '  journal = {'.$journal."},\n";
            $output .= '  year = {'.$year."},\n";

            if (! empty($metadata['volume'])) {
                $output .= '  volume = {'.$metadata['volume']."},\n";
            }
            if (! empty($metadata['issue'])) {
                $output .= '  number = {'.$metadata['issue']."},\n";
            }
            if (! empty($metadata['pages'])) {
                $output .= '  pages = {'.$metadata['pages']."},\n";
            }
            if (! empty($metadata['doi'])) {
                $output .= '  doi = {'.$metadata['doi']."},\n";
            }
            if ($article->source_url) {
                $output .= '  url = {'.$article->source_url."},\n";
            }

            $output .= "}\n\n";
        }

        return $output;
    }

    /**
     * Export bibliography to plain text
     */
    public function exportToText(Collection $articles, string $style = 'apa'): string
    {
        $citations = $this->formatBibliography($articles, $style);

        $output = 'Daftar Pustaka (Style: '.self::STYLES[$style].")\n";
        $output .= str_repeat('=', 60)."\n\n";

        foreach ($citations as $index => $citation) {
            $output .= ($index + 1).'. '.$citation['formatted']."\n\n";
        }

        return $output;
    }
}
