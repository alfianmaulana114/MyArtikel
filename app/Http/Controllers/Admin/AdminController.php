<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\User;
use App\Models\Summary;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class AdminController extends Controller
{
    public function dashboard()
    {
        $totalUsers = User::count();
        $totalArticles = Article::count();
        $totalSummaries = Summary::count();

        $articleStats = [
            'ready' => Article::where('processing_status', 'ready')->count(),
            'processing' => Article::whereIn('processing_status', ['queued', 'fetching', 'extracting'])->count(),
            'failed' => Article::where('processing_status', 'failed')->count(),
        ];

        $monthlyTrends = $this->getMonthlyTrends();

        $recentUsers = User::orderByDesc('created_at')->take(5)->get();

        $topUsers = User::withCount('articles')
            ->orderByDesc('articles_count')
            ->take(5)
            ->get();

        $activeUsers = User::where('is_active', true)->count();
        $inactiveUsers = User::where('is_active', false)->count();

        return view('admin.dashboard', compact(
            'totalUsers', 'totalArticles', 'totalSummaries',
            'articleStats', 'monthlyTrends',
            'recentUsers', 'topUsers', 'activeUsers', 'inactiveUsers'
        ));
    }

    public function users(Request $request)
    {
        $query = User::withCount('articles')->orderByDesc('created_at');

        if ($request->filled('q')) {
            $q = $request->string('q')->toString();
            $query->where(function ($sub) use ($q) {
                $sub->where('name', 'like', "%{$q}%")
                    ->orWhere('email', 'like', "%{$q}%");
            });
        }

        if ($request->filled('status')) {
            if ($request->input('status') === 'active') {
                $query->where('is_active', true);
            } elseif ($request->input('status') === 'inactive') {
                $query->where('is_active', false);
            }
        }

        $users = $query->paginate(20)->withQueryString();

        return view('admin.users', compact('users'));
    }

    public function toggleUser(User $user)
    {
        if ($user->is_admin) {
            return back()->with('error', 'Tidak bisa menonaktifkan admin.');
        }

        $user->update(['is_active' => !$user->is_active]);

        $status = $user->is_active ? 'diaktifkan' : 'dinonaktifkan';
        return back()->with('status', "User {$user->name} berhasil {$status}.");
    }

    public function deleteUser(User $user)
    {
        if ($user->is_admin) {
            return back()->with('error', 'Tidak bisa menghapus admin.');
        }

        $name = $user->name;
        $user->delete();

        return back()->with('status', "User {$name} berhasil dihapus beserta seluruh data terkait.");
    }

    public function articles(Request $request)
    {
        $query = Article::with('user')->withCount('summaries')->orderByDesc('created_at');

        if ($request->filled('q')) {
            $q = $request->string('q')->toString();
            $query->where(function ($sub) use ($q) {
                $sub->where('title', 'like', "%{$q}%")
                    ->orWhere('source_domain', 'like', "%{$q}%");
            });
        }

        if ($request->filled('status')) {
            $status = $request->input('status');
            if (in_array($status, ['ready', 'processing', 'failed'])) {
                if ($status === 'processing') {
                    $query->whereIn('processing_status', ['queued', 'fetching', 'extracting']);
                } else {
                    $query->where('processing_status', $status);
                }
            }
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', (int) $request->input('user_id'));
        }

        $articles = $query->paginate(20)->withQueryString();
        $users = User::orderBy('name')->get(['id', 'name']);

        return view('admin.articles', compact('articles', 'users'));
    }

    public function system()
    {
        $dbSize = $this->getDatabaseSize();
        $cacheStatus = Cache::get('admin:system:last_check') ? 'OK' : 'OK';
        $diskFree = $this->getDiskInfo();

        $failedJobs = DB::table('failed_jobs')->count();
        $pendingJobs = DB::table('jobs')->count();

        $recentFailedJobs = DB::table('failed_jobs')
            ->orderByDesc('failed_at')
            ->take(5)
            ->get();

        Cache::put('admin:system:last_check', now()->toDateTimeString(), 60);

        return view('admin.system', compact(
            'dbSize', 'cacheStatus', 'diskFree',
            'failedJobs', 'pendingJobs', 'recentFailedJobs'
        ));
    }

    protected function getMonthlyTrends(): array
    {
        $trends = [];
        $now = now();

        for ($i = 11; $i >= 0; $i--) {
            $month = $now->copy()->subMonths($i);
            $monthStart = $month->copy()->startOfMonth();
            $monthEnd = $month->copy()->endOfMonth();

            $trends[] = [
                'month' => $month->translatedFormat('M Y'),
                'articles' => Article::whereBetween('created_at', [$monthStart, $monthEnd])->count(),
                'users' => User::whereBetween('created_at', [$monthStart, $monthEnd])->count(),
                'summaries' => Summary::whereBetween('created_at', [$monthStart, $monthEnd])->count(),
            ];
        }

        return $trends;
    }

    protected function getDatabaseSize(): string
    {
        try {
            $dbName = DB::connection()->getDatabaseName();
            $result = DB::select("
                SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS size_mb
                FROM information_schema.tables
                WHERE table_schema = ?
                GROUP BY table_schema
            ", [$dbName]);

            if (!empty($result)) {
                return $result[0]->size_mb . ' MB';
            }
        } catch (\Exception $e) {
            //
        }

        return 'N/A';
    }

    protected function getDiskInfo(): string
    {
        $free = disk_free_space(base_path());
        $total = disk_total_space(base_path());

        if ($free === false || $total === false) {
            return 'N/A';
        }

        $freeGb = round($free / 1024 / 1024 / 1024, 1);
        $totalGb = round($total / 1024 / 1024 / 1024, 1);

        return "{$freeGb} GB free / {$totalGb} GB total";
    }
}
