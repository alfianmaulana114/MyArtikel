<?php

namespace App\Http\Controllers;

use App\Http\Requests\ArticleSubmissionRequest;
use App\Models\Article;
use App\Services\BackgroundProcessingService;
use App\Services\FileUploadSecurityService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $userId = Auth::id();

        $query = Article::query()
            ->where('user_id', $userId)
            ->orderByDesc('created_at');

        if ($request->filled('q')) {
            $q = $request->string('q')->toString();
            $query->where(function ($sub) use ($q) {
                $sub->where('title', 'like', '%'.$q.'%')
                    ->orWhere('source_domain', 'like', '%'.$q.'%')
                    ->orWhere('source_url', 'like', '%'.$q.'%');
            });
        }

        $articles = $query->with(['tags', 'user'])->paginate(20)->withQueryString();

        $stats = [
            'total' => Article::where('user_id', $userId)->count(),
            'ready' => Article::where('user_id', $userId)->where('processing_status', 'ready')->count(),
            'processing' => Article::where('user_id', $userId)->whereIn('processing_status', ['queued', 'fetching', 'extracting'])->count(),
            'failed' => Article::where('user_id', $userId)->where('processing_status', 'failed')->count(),
        ];

        return view('dashboard', [
            'articles' => $articles,
            'stats' => $stats,
            'filters' => [
                'q' => $request->input('q', ''),
            ],
        ]);
    }

    public function ingest(ArticleSubmissionRequest $request, BackgroundProcessingService $bg)
    {
        $userId = Auth::id();
        $sourceType = $request->input('source_type', 'url');
        $researchTitle = $request->string('research_title', '')->trim()->toString() ?: null;
        $researchContext = $request->string('research_context', '')->trim()->toString() ?: null;

        if ($sourceType === 'pdf') {
            return $this->ingestPdf($request, $bg, $userId, $researchTitle, $researchContext);
        }

        $url = $request->string('url')->toString();

        $article = Article::create([
            'user_id' => $userId,
            'title' => 'Memproses artikel…',
            'slug' => 'processing-'.uniqid(),
            'source_url' => $url,
            'canonical_url' => $url,
            'source_domain' => parse_url($url, PHP_URL_HOST),
            'source_type' => 'url',
            'research_title' => $researchTitle,
            'research_context' => $researchContext,
            'content' => '',
            'processing_status' => 'queued',
            'status' => 'draft',
        ]);

        $result = $bg->processArticleIngestion($userId, $url, [
            'article_id' => $article->id,
        ]);

        if (! $result['success']) {
            $error = Str::limit((string) ($result['error'] ?? 'Gagal memproses URL.'), 1000, '…');
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

    private function ingestPdf(ArticleSubmissionRequest $request, BackgroundProcessingService $bg, int $userId, ?string $researchTitle, ?string $researchContext)
    {
        $file = $request->file('pdf_file');

        $securityService = app(FileUploadSecurityService::class);
        $validation = $securityService->validateUpload($file, 'pdf');

        if (! $validation['valid']) {
            return back()->withErrors([
                'pdf_file' => $validation['message'],
            ]);
        }

        $filePath = $securityService->storeSecurely($file, 'journals');

        if (! $filePath) {
            return back()->withErrors([
                'pdf_file' => 'Gagal menyimpan file PDF.',
            ]);
        }

        $originalName = $file->getClientOriginalName();
        $title = $request->string('title', '')->trim()->toString() ?: pathinfo($originalName, PATHINFO_FILENAME);

        $article = Article::create([
            'user_id' => $userId,
            'title' => $title,
            'slug' => 'pdf-'.Str::slug($title).'-'.uniqid(),
            'source_type' => 'pdf',
            'file_path' => $filePath,
            'research_title' => $researchTitle,
            'research_context' => $researchContext,
            'source_domain' => 'pdf-upload',
            'content' => '',
            'processing_status' => 'queued',
            'status' => 'draft',
        ]);

        $result = $bg->processPdfIngestion($userId, $filePath, [
            'article_id' => $article->id,
        ]);

        if (! $result['success']) {
            $error = Str::limit((string) ($result['error'] ?? 'Gagal memproses PDF.'), 1000, '…');
            $article->update([
                'processing_status' => 'failed',
                'processing_error' => $error,
            ]);

            return back()->withErrors([
                'pdf_file' => $error,
            ]);
        }

        $queue = $result['queue'] ?? null;
        $statusMessage = $queue === 'sync'
            ? 'PDF berhasil diunggah dan diproses langsung.'
            : 'PDF berhasil diunggah. Sedang diproses (pastikan queue worker jalan).';

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
        if (! $url) {
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

        if (! $result['success']) {
            $error = Str::limit((string) ($result['error'] ?? 'Gagal memproses ulang.'), 1000, '…');
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
