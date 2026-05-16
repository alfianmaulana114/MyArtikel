<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-1">
            <h2 class="font-semibold text-xl theme-text-primary leading-tight">System Health</h2>
            <p class="text-sm theme-text-muted">Monitor status queue, cache, disk, database, dan failed jobs.</p>
        </div>
    </x-slot>

    {{-- Health Cards --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="card p-5">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-[color:var(--bg-tertiary)] flex items-center justify-center">
                    <svg class="w-5 h-5 theme-text-secondary" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2a2 2 0 012-2h2a2 2 0 012 2v2m-7 0h8m-8 0a2 2 0 01-2-2V7a2 2 0 012-2h8a2 2 0 012 2v8a2 2 0 01-2 2"/></svg>
                </div>
                <div>
                    <div class="text-xs theme-text-muted">Queue Jobs</div>
                    <div class="text-lg font-semibold theme-text-primary">{{ $pendingJobs }} pending</div>
                </div>
            </div>
        </div>

        <div class="card p-5">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl {{ $failedJobs > 0 ? 'bg-[color:var(--error)]/10' : 'bg-[color:var(--bg-tertiary)]' }} flex items-center justify-center">
                    <svg class="w-5 h-5 {{ $failedJobs > 0 ? 'text-[color:var(--error)]' : 'theme-text-secondary' }}" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>
                </div>
                <div>
                    <div class="text-xs {{ $failedJobs > 0 ? 'text-[color:var(--error)]' : 'theme-text-muted' }}">Failed Jobs</div>
                    <div class="text-lg font-semibold {{ $failedJobs > 0 ? 'text-[color:var(--error)]' : 'theme-text-primary' }}">{{ $failedJobs }}</div>
                </div>
            </div>
        </div>

        <div class="card p-5">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-[color:var(--bg-tertiary)] flex items-center justify-center">
                    <svg class="w-5 h-5 theme-text-secondary" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4"/></svg>
                </div>
                <div>
                    <div class="text-xs theme-text-muted">Database Size</div>
                    <div class="text-lg font-semibold theme-text-primary">{{ $dbSize }}</div>
                </div>
            </div>
        </div>

        <div class="card p-5">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-[color:var(--bg-tertiary)] flex items-center justify-center">
                    <svg class="w-5 h-5 theme-text-secondary" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2"/></svg>
                </div>
                <div>
                    <div class="text-xs theme-text-muted">Disk Space</div>
                    <div class="text-sm font-semibold theme-text-primary">{{ $diskFree }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Laravel Info --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="card p-4">
            <div class="text-xs theme-text-muted mb-1">Laravel Version</div>
            <div class="text-sm font-semibold theme-text-primary">{{ app()->version() }}</div>
        </div>
        <div class="card p-4">
            <div class="text-xs theme-text-muted mb-1">PHP Version</div>
            <div class="text-sm font-semibold theme-text-primary">{{ phpversion() }}</div>
        </div>
        <div class="card p-4">
            <div class="text-xs theme-text-muted mb-1">Environment</div>
            <div class="text-sm font-semibold theme-text-primary">{{ app()->environment() }}</div>
        </div>
    </div>

    {{-- Recent Failed Jobs --}}
    <div class="card overflow-hidden">
        <div class="p-4 border-b theme-border-primary">
            <h3 class="font-semibold theme-text-primary">Recent Failed Jobs</h3>
        </div>
        @if ($recentFailedJobs->isNotEmpty())
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b theme-border-primary text-left">
                            <th class="p-4 font-medium theme-text-muted">UUID</th>
                            <th class="p-4 font-medium theme-text-muted">Connection</th>
                            <th class="p-4 font-medium theme-text-muted">Queue</th>
                            <th class="p-4 font-medium theme-text-muted">Failed At</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y theme-border-primary">
                        @foreach ($recentFailedJobs as $job)
                            <tr>
                                <td class="p-4 font-mono text-xs theme-text-muted">{{ $job->uuid }}</td>
                                <td class="p-4 theme-text-secondary">{{ $job->connection }}</td>
                                <td class="p-4 theme-text-secondary">{{ $job->queue }}</td>
                                <td class="p-4 theme-text-muted whitespace-nowrap">{{ $job->failed_at }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="p-8 text-center text-sm theme-text-muted">Tidak ada failed jobs.</div>
        @endif
    </div>
</x-app-layout>
