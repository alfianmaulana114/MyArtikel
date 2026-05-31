<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Project;
use App\Services\CitationFormatterService;
use App\Services\ProjectService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProjectController extends Controller
{
    protected ProjectService $projectService;

    protected CitationFormatterService $citationFormatter;

    public function __construct(ProjectService $projectService, CitationFormatterService $citationFormatter)
    {
        $this->projectService = $projectService;
        $this->citationFormatter = $citationFormatter;
    }

    public function index(Request $request)
    {
        $projects = Project::query()
            ->where('user_id', Auth::id())
            ->latest()
            ->withCount(['articles', 'notes', 'outlines'])
            ->paginate(20);

        return view('projects.index', [
            'projects' => $projects,
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $user = Auth::user();
        $query = Project::query()->where('user_id', $user->id);

        if ($request->has('status')) {
            $query->byStatus($request->status);
        }

        if ($request->has('search')) {
            $query->search($request->search);
        }

        if ($request->has('sort_by')) {
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);
        } else {
            $query->latest();
        }

        $projects = $query->withCount(['articles', 'notes', 'outlines'])->paginate($request->get('per_page', 20));

        return response()->json([
            'projects' => $projects,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'sometimes|in:drafting,reviewing,completed',
        ]);

        $project = Project::create([
            'user_id' => Auth::id(),
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'status' => $validated['status'] ?? 'drafting',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Project berhasil dibuat',
            'project' => $project,
        ], 201);
    }

    public function show(string $id)
    {
        $project = Project::where('user_id', Auth::id())
            ->with(['articles' => function ($query) {
                $query->with(['summaries' => function ($q) {
                    $q->latest()->limit(1);
                }]);
            }])
            ->withCount(['articles'])
            ->findOrFail($id);

        $stats = $this->projectService->getProjectStats($project);

        return view('projects.show', [
            'project' => $project,
            'stats' => $stats,
        ]);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $project = Project::where('user_id', Auth::id())->findOrFail($id);

        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'status' => 'sometimes|in:drafting,reviewing,completed',
        ]);

        $project->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Project berhasil diupdate',
            'project' => $project,
        ]);
    }

    public function destroy(string $id): JsonResponse
    {
        $project = Project::where('user_id', Auth::id())->findOrFail($id);
        $project->delete();

        return response()->json([
            'success' => true,
            'message' => 'Project berhasil dihapus',
        ]);
    }

    public function duplicate(string $id): JsonResponse
    {
        $project = Project::where('user_id', Auth::id())->findOrFail($id);
        $newProject = $this->projectService->duplicateProject($project);

        return response()->json([
            'success' => true,
            'message' => 'Project berhasil diduplikasi',
            'project' => $newProject,
        ]);
    }

    public function addArticle(Request $request, string $id): JsonResponse
    {
        $project = Project::where('user_id', Auth::id())->findOrFail($id);

        $validated = $request->validate([
            'article_id' => 'required|exists:articles,id',
            'role' => 'sometimes|in:reference,citation,inspiration,primary',
            'notes' => 'nullable|string',
        ]);

        $article = Article::where('user_id', Auth::id())->findOrFail($validated['article_id']);
        $project->addArticle($article, $validated['role'] ?? 'reference', $validated['notes'] ?? null);

        return response()->json([
            'success' => true,
            'message' => 'Artikel berhasil ditambahkan',
        ]);
    }

    public function removeArticle(string $projectId, string $articleId): JsonResponse
    {
        $project = Project::where('user_id', Auth::id())->findOrFail($projectId);
        $article = Article::where('user_id', Auth::id())->findOrFail($articleId);

        $project->removeArticle($article);

        return response()->json([
            'success' => true,
            'message' => 'Artikel berhasil dihapus dari project',
        ]);
    }

    /**
     * Generate bibliography in selected style
     */
    public function generateBibliography(Request $request, string $id): JsonResponse
    {
        $project = Project::where('user_id', Auth::id())->findOrFail($id);

        $validated = $request->validate([
            'style' => 'sometimes|in:apa,mla,ieee,chicago,harvard',
        ]);

        $style = $validated['style'] ?? 'apa';
        $articles = $project->articles()->get();

        if ($articles->isEmpty()) {
            return response()->json([
                'success' => false,
                'error' => 'Belum ada artikel di project',
            ], 400);
        }

        $citations = $this->citationFormatter->formatBibliography($articles, $style);

        return response()->json([
            'success' => true,
            'citations' => $citations,
            'style' => $style,
            'style_name' => CitationFormatterService::STYLES[$style],
        ]);
    }

    /**
     * Export bibliography to text
     */
    public function exportBibliography(Request $request, string $id)
    {
        $project = Project::where('user_id', Auth::id())->findOrFail($id);

        $validated = $request->validate([
            'style' => 'sometimes|in:apa,mla,ieee,chicago,harvard',
            'format' => 'sometimes|in:txt,bib',
        ]);

        $style = $validated['style'] ?? 'apa';
        $format = $validated['format'] ?? 'txt';
        $articles = $project->articles()->get();

        if ($articles->isEmpty()) {
            return response()->json([
                'success' => false,
                'error' => 'Belum ada artikel di project',
            ], 400);
        }

        if ($format === 'bib') {
            $content = $this->citationFormatter->exportToBibTeX($articles);
            $filename = 'bibliography_'.$project->id.'.bib';
            $mimeType = 'application/x-bibtex';
        } else {
            $content = $this->citationFormatter->exportToText($articles, $style);
            $filename = 'bibliography_'.$project->id.'.txt';
            $mimeType = 'text/plain';
        }

        return response($content, 200, [
            'Content-Type' => $mimeType.'; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }
}
