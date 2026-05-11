<?php

namespace App\Services;

use App\Models\Project;
use App\Models\ProjectOutline;
use App\Models\Article;
use Illuminate\Support\Facades\Auth;

class ProjectService
{
    /**
     * Create new project with default outline
     */
    public function createProject(array $data, bool $withDefaultOutline = true): Project
    {
        $project = Project::create([
            'user_id' => Auth::id(),
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'status' => $data['status'] ?? 'drafting',
            'deadline' => $data['deadline'] ?? null,
        ]);

        if ($withDefaultOutline) {
            $this->createDefaultOutline($project);
        }

        return $project;
    }

    /**
     * Create default academic paper outline
     */
    public function createDefaultOutline(Project $project): void
    {
        $defaultSections = [
            ['title' => 'Pendahuluan', 'section_type' => 'introduction', 'position' => 1],
            ['title' => 'Tinjauan Pustaka', 'section_type' => 'literature_review', 'position' => 2],
            ['title' => 'Metodologi', 'section_type' => 'methodology', 'position' => 3],
            ['title' => 'Hasil dan Pembahasan', 'section_type' => 'results', 'position' => 4],
            ['title' => 'Kesimpulan', 'section_type' => 'conclusion', 'position' => 5],
        ];

        foreach ($defaultSections as $section) {
            ProjectOutline::create([
                'project_id' => $project->id,
                'title' => $section['title'],
                'section_type' => $section['section_type'],
                'position' => $section['position'],
            ]);
        }
    }

    /**
     * Generate outline using AI (token-efficient)
     * Uses minimal tokens by only sending essential context
     */
    public function generateOutlineWithAI(Project $project, string $topic, string $language = 'id'): array
    {
        $geminiService = app(\App\Services\GeminiSummarizationService::class);

        if (!$geminiService->isAvailable()) {
            throw new \Exception('AI service tidak tersedia');
        }

        // Efficient prompt: short, specific, structured output
        $prompt = $this->buildOutlinePrompt($topic, $language);

        // Use Gemini with strict token limit
        $result = $geminiService->generateWithPrompt($prompt, [
            'max_tokens' => 500, // Strict limit
            'temperature' => 0.7,
        ]);

        if ($result['success']) {
            $outlines = $this->parseOutlineResponse($result['content'], $project->id);
            
            return [
                'success' => true,
                'outlines' => $outlines,
                'tokens_used' => $result['tokens_used'] ?? 0,
            ];
        }

        return [
            'success' => false,
            'error' => $result['error'] ?? 'Gagal generate outline',
        ];
    }

    /**
     * Build token-efficient outline generation prompt
     */
    protected function buildOutlinePrompt(string $topic, string $language): string
    {
        $lang = $language === 'id' ? 'Bahasa Indonesia' : 'English';
        
        return <<<PROMPT
Buat outline makalah untuk: "{$topic}"
{$lang}, maks 8 sections.
Output JSON array saja:
[{"title":"...","section_type":"introduction|literature_review|methodology|results|conclusion|custom","position":1}]
PROMPT;
    }

    /**
     * Parse AI response into outline records
     */
    protected function parseOutlineResponse(string $content, int $projectId): array
    {
        // Extract JSON from markdown if needed
        $json = $this->extractJson($content);
        
        if (!$json) {
            throw new \Exception('Response AI tidak valid');
        }

        $data = json_decode($json, true);
        
        if (!is_array($data)) {
            throw new \Exception('Format outline tidak valid');
        }

        $outlines = [];
        foreach ($data as $index => $item) {
            if (!isset($item['title'])) continue;

            $outline = ProjectOutline::create([
                'project_id' => $projectId,
                'title' => $item['title'],
                'section_type' => $item['section_type'] ?? 'custom',
                'position' => $item['position'] ?? ($index + 1),
            ]);

            $outlines[] = $outline;
        }

        return $outlines;
    }

    /**
     * Extract JSON from markdown code blocks
     */
    protected function extractJson(string $content): ?string
    {
        // Try to find JSON in code block
        if (preg_match('/```(?:json)?\s*([\s\S]*?)```/', $content, $matches)) {
            return trim($matches[1]);
        }

        // Try to parse entire content as JSON
        $trimmed = trim($content);
        if (str_starts_with($trimmed, '[') || str_starts_with($trimmed, '{')) {
            return $trimmed;
        }

        return null;
    }

    /**
     * Generate section draft using AI (token-efficient)
     * Only uses relevant articles and notes, not full content
     */
    public function generateSectionDraft(ProjectOutline $outline, array $options = []): array
    {
        $geminiService = app(\App\Services\GeminiSummarizationService::class);

        if (!$geminiService->isAvailable()) {
            throw new \Exception('AI service tidak tersedia');
        }

        $project = $outline->project;
        $language = $options['language'] ?? 'id';
        
        // Get only essential context (not full article content)
        $context = $this->buildSectionContext($outline, $options);
        
        // Efficient prompt
        $prompt = $this->buildDraftPrompt($outline, $context, $language);

        // Strict token limit based on section type
        $maxTokens = $options['max_tokens'] ?? match($outline->section_type) {
            'introduction' => 800,
            'literature_review' => 1000,
            'methodology' => 600,
            'results' => 800,
            'conclusion' => 500,
            default => 600,
        };

        $result = $geminiService->generateWithPrompt($prompt, [
            'max_tokens' => $maxTokens,
            'temperature' => 0.8,
        ]);

        if ($result['success']) {
            return [
                'success' => true,
                'content' => $result['content'],
                'tokens_used' => $result['tokens_used'] ?? 0,
            ];
        }

        return [
            'success' => false,
            'error' => $result['error'] ?? 'Gagal generate draft',
        ];
    }

    /**
     * Build minimal context for section generation
     * Only includes summaries and key points, NOT full article text
     */
    protected function buildSectionContext(ProjectOutline $outline, array $options): array
    {
        $project = $outline->project;
        
        // Get articles assigned to this project
        $articles = $project->articles()->with('summaries')->limit(5)->get();
        
        $context = [
            'topic' => $project->title,
            'section' => $outline->title,
            'section_type' => $outline->section_type,
            'articles' => [],
        ];

        foreach ($articles as $article) {
            // Only use summaries (cheap), not full content (expensive)
            $latestSummary = $article->summaries()->latest()->first();
            
            $context['articles'][] = [
                'title' => $article->title,
                'excerpt' => $article->excerpt,
                'summary' => $latestSummary?->content,
                'key_points' => $latestSummary?->key_points,
            ];
        }

        return $context;
    }

    /**
     * Build token-efficient draft generation prompt
     */
    protected function buildDraftPrompt(ProjectOutline $outline, array $context, string $language): string
    {
        $lang = $language === 'id' ? 'Bahasa Indonesia' : 'English';
        $articleContext = '';

        foreach ($context['articles'] as $idx => $article) {
            $articleContext .= "\n[{$idx}] {$article['title']}";
            if ($article['summary']) {
                $articleContext .= "\n" . substr($article['summary'], 0, 300);
            }
            if ($article['excerpt']) {
                $articleContext .= "\n" . substr($article['excerpt'], 0, 200);
            }
        }

        $wordLimit = $this->getWordLimit($outline->section_type);

        return <<<PROMPT
Tulis draft section "{$context['section']}" untuk makalah "{$context['topic']}".
Referensi:{$articleContext}
Aturan: {$lang}, formal, 3-5 paragraf, sitasi [1][2], maks {$wordLimit} kata. Output teks draft saja.
PROMPT;
    }

    /**
     * Get word limit based on section type
     */
    protected function getWordLimit(string $sectionType): int
    {
        return match($sectionType) {
            'introduction' => 400,
            'literature_review' => 600,
            'methodology' => 350,
            'results' => 500,
            'conclusion' => 300,
            default => 400,
        };
    }

    /**
     * Duplicate project with all data
     */
    public function duplicateProject(Project $project): Project
    {
        $newProject = Project::create([
            'user_id' => Auth::id(),
            'title' => $project->title . ' (Copy)',
            'description' => $project->description,
            'status' => 'drafting',
            'deadline' => null,
        ]);

        // Copy outlines
        foreach ($project->outlines as $outline) {
            ProjectOutline::create([
                'project_id' => $newProject->id,
                'title' => $outline->title,
                'section_type' => $outline->section_type,
                'content' => null, // Don't copy content
                'position' => $outline->position,
                'parent_id' => null,
            ]);
        }

        // Copy article references
        foreach ($project->articles as $article) {
            $newProject->articles()->attach($article->id, [
                'role' => $article->pivot->role,
                'notes' => $article->pivot->notes,
            ]);
        }

        return $newProject;
    }

    /**
     * Get project statistics
     */
    public function getProjectStats(Project $project): array
    {
        $articles = $project->articles()->get();
        $wordCount = 0;
        
        foreach ($articles as $article) {
            $content = $article->text_extracted ?: $article->content;
            $wordCount += str_word_count(strip_tags((string) $content) ?: '');
        }

        return [
            'articles_count' => $articles->count(),
            'word_count' => $wordCount,
        ];
    }
}
