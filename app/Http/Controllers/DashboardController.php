<?php

namespace App\Http\Controllers;

use App\Http\Requests\ArticleSubmissionRequest;
use App\Models\Article;
use App\Models\Tag;
use App\Services\BackgroundProcessingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $userId = Auth::id();

        $query = Article::query()
            ->where('user_id', $userId)
            ->with(['tags'])
            ->withCount(['notes', 'bookmarks'])
            ->orderByDesc('created_at');

        if ($request->filled('q')) {
            $q = $request->string('q')->toString();
            $query->where(function ($sub) use ($q) {
                $sub->where('title', 'like', '%' . $q . '%')
                    ->orWhere('source_domain', 'like', '%' . $q . '%')
                    ->orWhere('source_url', 'like', '%' . $q . '%');
            });
        }

        if ($request->filled('tag')) {
            $tagId = (int) $request->input('tag');
            $query->whereHas('tags', function ($t) use ($tagId) {
                $t->where('tags.id', $tagId);
            });
        }

        if ($request->boolean('bookmarked')) {
            $query->whereHas('bookmarks', function ($b) use ($userId) {
                $b->where('user_id', $userId);
            });
        }

        $articles = $query->paginate(20)->withQueryString();

        $tags = Tag::query()
            ->where('user_id', $userId)
            ->orderBy('name')
            ->get(['id', 'name']);

        $stats = [
            'total' => Article::where('user_id', $userId)->count(),
            'ready' => Article::where('user_id', $userId)->where('processing_status', 'ready')->count(),
            'processing' => Article::where('user_id', $userId)->whereIn('processing_status', ['queued', 'fetching', 'extracting'])->count(),
            'failed' => Article::where('user_id', $userId)->where('processing_status', 'failed')->count(),
            'bookmarked' => Article::where('user_id', $userId)->whereHas('bookmarks', function ($b) use ($userId) {
                $b->where('user_id', $userId);
            })->count(),
        ];

        return view('dashboard', [
            'articles' => $articles,
            'tags' => $tags,
            'stats' => $stats,
            'filters' => [
                'q' => $request->input('q', ''),
                'tag' => $request->input('tag', ''),
                'bookmarked' => $request->boolean('bookmarked'),
            ],
        ]);
    }

    public function ingest(ArticleSubmissionRequest $request, BackgroundProcessingService $bg)
    {
        $userId = Auth::id();
        $url = $request->string('url')->toString();

        $article = Article::create([
            'user_id' => $userId,
            'title' => 'Memproses artikel…',
            'slug' => 'processing-' . uniqid(),
            'source_url' => $url,
            'canonical_url' => $url,
            'source_domain' => parse_url($url, PHP_URL_HOST),
            'content' => '',
            'processing_status' => 'queued',
            'status' => 'draft',
        ]);

        $result = $bg->processArticleIngestion($userId, $url, [
            'article_id' => $article->id,
        ]);

        if (!$result['success']) {
            $error = \Illuminate\Support\Str::limit((string) ($result['error'] ?? 'Gagal memproses URL.'), 1000, '…');
            $article->update([
                'processing_status' => 'failed',
                'processing_error' => $error,
            ]);

            return back()->withErrors([
                'url' => $error,
            ]);
        }

        $queue = $result['queue'] ?? null;
        $statusMessage = $queue === 'sync'
            ? 'URL diterima. Artikel diproses langsung.'
            : 'URL diterima. Artikel sedang diproses (pastikan queue worker jalan).';

        return redirect()
            ->route('dashboard')
            ->with('status', $statusMessage);
    }

    public function retry(Request $request, Article $article, BackgroundProcessingService $bg)
    {
        $userId = Auth::id();

        if ((int) $article->user_id !== (int) $userId) {
            abort(403);
        }

        $url = $article->source_url ?: $article->canonical_url;
        if (!$url) {
            return redirect()
                ->route('dashboard')
                ->with('status', 'URL artikel tidak ditemukan untuk diproses ulang.');
        }

        $article->update([
            'processing_status' => 'queued',
            'processing_error' => null,
        ]);

        $result = $bg->processArticleIngestion($userId, $url, [
            'article_id' => $article->id,
        ]);

        if (!$result['success']) {
            $error = \Illuminate\Support\Str::limit((string) ($result['error'] ?? 'Gagal memproses ulang.'), 1000, '…');
            $article->update([
                'processing_status' => 'failed',
                'processing_error' => $error,
            ]);
        }

        $queue = $result['queue'] ?? null;
        $statusMessage = $queue === 'sync'
            ? 'Proses ulang dijalankan langsung.'
            : 'Proses ulang diantrikan (pastikan queue worker jalan).';

        return redirect()
            ->route('dashboard')
            ->with('status', $statusMessage);
    }
}
