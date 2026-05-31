<?php

namespace App\Services;

use App\Models\Project;

class ProjectGraphService
{
    /**
     * Build knowledge graph nodes and edges from project data.
     * Zero AI/token usage — pure PHP + DB queries.
     */
    public function buildGraph(Project $project): array
    {
        $articles = $project->articles()
            ->with(['tags', 'summaries' => fn ($q) => $q->where('status', 'completed')->latest()])
            ->get();

        $nodes = [];
        $articleMap = [];
        foreach ($articles as $article) {
            $latestSummary = $article->summaries->first();
            $node = [
                'id' => 'article-'.$article->id,
                'label' => $this->truncateLabel($article->title, 40),
                'full_title' => $article->title,
                'article_id' => $article->id,
                'size' => 18 + min(count($article->tags) * 4, 24), // size based on tag count
                'color' => $this->getNodeColor($article->pivot->role ?? 'reference'),
                'summary' => $latestSummary ? $latestSummary->content : ($article->excerpt ?? 'Tidak ada ringkasan'),
                'key_points' => $latestSummary ? ($latestSummary->key_points ?? []) : [],
                'role' => $article->pivot->role ?? 'reference',
                'role_label' => $this->getRoleLabel($article->pivot->role ?? 'reference'),
            ];
            $nodes[] = $node;
            $articleMap[$article->id] = $node;
        }

        $edges = [];
        $edgeSet = []; // prevent duplicates

        // Edge type 1: shared tags
        $tagArticles = [];
        foreach ($articles as $article) {
            foreach ($article->tags as $tag) {
                $tagArticles[$tag->id][] = $article->id;
            }
        }

        foreach ($tagArticles as $tagId => $articleIds) {
            if (count($articleIds) < 2) {
                continue;
            }
            for ($i = 0; $i < count($articleIds); $i++) {
                for ($j = $i + 1; $j < count($articleIds); $j++) {
                    $a = $articleIds[$i];
                    $b = $articleIds[$j];
                    $edgeKey = min($a, $b).'-'.max($a, $b);
                    if (isset($edgeSet[$edgeKey])) {
                        $edgeSet[$edgeKey]['weight']++;
                        $edgeSet[$edgeKey]['shared_tags'][] = $tagId;
                    } else {
                        $edgeSet[$edgeKey] = [
                            'source' => 'article-'.$a,
                            'target' => 'article-'.$b,
                            'weight' => 1,
                            'shared_tags' => [$tagId],
                            'type' => 'shared_tags',
                        ];
                    }
                }
            }
        }

        // Edge type 2: AI citation links (from ai_quotation_suggestions JSON)
        foreach ($articles as $article) {
            $citations = $article->ai_quotation_suggestions;
            if (empty($citations) || ! is_array($citations)) {
                continue;
            }

            foreach ($articles as $otherArticle) {
                if ($article->id === $otherArticle->id) {
                    continue;
                }
                foreach ($citations as $citation) {
                    if (! is_array($citation)) {
                        continue;
                    }
                    $quote = $citation['quote'] ?? '';
                    if (stripos($quote, $otherArticle->title) !== false) {
                        $edgeKey = min($article->id, $otherArticle->id).'-'.max($article->id, $otherArticle->id);
                        if (isset($edgeSet[$edgeKey])) {
                            $edgeSet[$edgeKey]['weight'] += 2;
                            $edgeSet[$edgeKey]['type'] = 'citation_link';
                        } else {
                            $edgeSet[$edgeKey] = [
                                'source' => 'article-'.$article->id,
                                'target' => 'article-'.$otherArticle->id,
                                'weight' => 2,
                                'shared_tags' => [],
                                'type' => 'citation_link',
                            ];
                        }
                    }
                }
            }
        }

        // Edge type 3: fallback — connect articles in same project so graph isn't just scattered dots
        if (count($edgeSet) === 0 && count($articles) > 1) {
            // Connect every article to the first article (star topology)
            // This creates a visible web even when no metadata exists yet
            $firstArticle = $articles->first();
            foreach ($articles as $article) {
                if ($article->id === $firstArticle->id) {
                    continue;
                }
                $edgeKey = min($article->id, $firstArticle->id).'-'.max($article->id, $firstArticle->id);
                $edgeSet[$edgeKey] = [
                    'source' => 'article-'.$firstArticle->id,
                    'target' => 'article-'.$article->id,
                    'weight' => 1,
                    'shared_tags' => [],
                    'type' => 'project_group',
                ];
            }
        }

        // Convert edgeSet to edges array
        foreach ($edgeSet as $edge) {
            if ($edge['weight'] >= 1) {
                $edge['color'] = $edge['type'] === 'citation_link' ? $this->getCitationEdgeColor() : $this->getEdgeColor();
                $edges[] = $edge;
            }
        }

        // Sort edges by weight
        usort($edges, fn ($a, $b) => $b['weight'] <=> $a['weight']);

        return [
            'nodes' => $nodes,
            'edges' => $edges,
            'stats' => [
                'node_count' => count($nodes),
                'edge_count' => count($edges),
            ],
        ];
    }

    private function truncateLabel(string $text, int $maxLength): string
    {
        if (strlen($text) <= $maxLength) {
            return $text;
        }

        return substr($text, 0, $maxLength - 3).'...';
    }

    private function getNodeColor(string $role): string
    {
        // Muted earth tones matching Frieren theme (desaturated, cozy)
        return match ($role) {
            'primary' => '#9E735B',      // warm umber
            'citation' => '#A89880',   // muted sand
            'inspiration' => '#7A8B7A',// soft sage
            default => '#8C7E6E',       // warm greige
        };
    }

    private function getEdgeColor(): string
    {
        return '#C4B5A0'; // warm stone (visible on both light & dark backgrounds)
    }

    private function getCitationEdgeColor(): string
    {
        return '#A68B6B'; // darker stone for citations
    }

    private function getRoleLabel(string $role): string
    {
        return match ($role) {
            'primary' => 'Utama',
            'citation' => 'Kutipan',
            'inspiration' => 'Inspirasi',
            default => 'Referensi',
        };
    }
}
