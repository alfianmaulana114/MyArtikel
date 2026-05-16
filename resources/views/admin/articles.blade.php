<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-1">
            <h2 class="font-semibold text-xl theme-text-primary leading-tight">Semua Artikel</h2>
            <p class="text-sm theme-text-muted">Monitoring artikel dari seluruh user platform.</p>
        </div>
    </x-slot>

    <div class="card overflow-hidden">
        <div class="p-4 border-b theme-border-primary">
            <form method="GET" action="{{ route('admin.articles') }}" class="flex flex-col sm:flex-row gap-3 sm:items-center flex-wrap">
                <div class="flex-1 min-w-[180px]">
                    <input
                        name="q"
                        type="text"
                        value="{{ request('q') }}"
                        placeholder="Cari judul / domain…"
                        class="block w-full rounded-xl shadow-sm border theme-border-primary bg-[color:var(--surface-primary)] text-[color:var(--text-primary)] placeholder:text-[color:var(--text-muted)] focus:border-[#AA5F3C] focus:ring-[#AA5F3C] px-4 py-2"
                    />
                </div>
                <div>
                    <select name="status" class="block w-full rounded-xl shadow-sm border theme-border-primary bg-[color:var(--surface-primary)] text-[color:var(--text-primary)] focus:border-[#AA5F3C] focus:ring-[#AA5F3C] px-3 py-2">
                        <option value="">Semua status</option>
                        <option value="ready" @selected(request('status') === 'ready')>Ready</option>
                        <option value="processing" @selected(request('status') === 'processing')>Processing</option>
                        <option value="failed" @selected(request('status') === 'failed')>Failed</option>
                    </select>
                </div>
                <div>
                    <select name="user_id" class="block w-full rounded-xl shadow-sm border theme-border-primary bg-[color:var(--surface-primary)] text-[color:var(--text-primary)] focus:border-[#AA5F3C] focus:ring-[#AA5F3C] px-3 py-2">
                        <option value="">Semua user</option>
                        @foreach ($users as $u)
                            <option value="{{ $u->id }}" @selected((int)request('user_id') === $u->id)>{{ $u->name }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="btn btn-secondary whitespace-nowrap">Filter</button>
                @if (request()->anyFilled(['q', 'status', 'user_id']))
                    <a href="{{ route('admin.articles') }}" class="text-sm theme-text-muted hover:theme-text-primary whitespace-nowrap">Reset</a>
                @endif
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b theme-border-primary text-left">
                        <th class="p-4 font-medium theme-text-muted">Artikel</th>
                        <th class="p-4 font-medium theme-text-muted hidden md:table-cell">User</th>
                        <th class="p-4 font-medium theme-text-muted hidden sm:table-cell">Status</th>
                        <th class="p-4 font-medium theme-text-muted hidden lg:table-cell">Domain</th>
                        <th class="p-4 font-medium theme-text-muted hidden lg:table-cell">Stats</th>
                        <th class="p-4 font-medium theme-text-muted hidden xl:table-cell">Created</th>
                    </tr>
                </thead>
                <tbody class="divide-y theme-border-primary">
                    @forelse ($articles as $article)
                        <tr class="hover:bg-[color:var(--hover-bg)] transition-colors">
                            <td class="p-4">
                                <div class="max-w-xs">
                                    <div class="font-medium theme-text-primary truncate" title="{{ $article->title }}">
                                        {{ $article->title }}
                                    </div>
                                    @if ($article->source_type === 'pdf')
                                        <span class="text-[10px] px-1.5 py-0.5 rounded-full bg-[#AA5F3C]/10 text-[#AA5F3C]">PDF</span>
                                    @endif
                                </div>
                            </td>
                            <td class="p-4 theme-text-secondary hidden md:table-cell whitespace-nowrap">
                                {{ $article->user?->name ?? 'N/A' }}
                            </td>
                            <td class="p-4 hidden sm:table-cell">
                                @php
                                    $statusColors = [
                                        'ready' => 'border-[#8B9A7A]/30 text-[#8B9A7A] bg-[#8B9A7A]/5',
                                        'queued' => 'border-[#D4A76A]/30 text-[#D4A76A] bg-[#D4A76A]/5',
                                        'fetching' => 'border-[#D4A76A]/30 text-[#D4A76A] bg-[#D4A76A]/5',
                                        'extracting' => 'border-[#D4A76A]/30 text-[#D4A76A] bg-[#D4A76A]/5',
                                        'failed' => 'border-[color:var(--error)]/30 text-[color:var(--error)] bg-[color:var(--error)]/5',
                                    ];
                                    $status = $article->processing_status ?? 'ready';
                                @endphp
                                <span class="text-xs px-2 py-1 rounded-full border {{ $statusColors[$status] ?? 'theme-border-primary theme-text-muted' }}">
                                    {{ $status }}
                                </span>
                            </td>
                            <td class="p-4 theme-text-muted hidden lg:table-cell">
                                @if ($article->source_domain && $article->source_domain !== 'pdf-upload')
                                    {{ $article->source_domain }}
                                @else
                                    <span class="theme-text-muted">—</span>
                                @endif
                            </td>
                            <td class="p-4 theme-text-muted hidden lg:table-cell whitespace-nowrap">
                                {{ $article->summaries_count }} summaries
                            </td>
                            <td class="p-4 theme-text-muted whitespace-nowrap hidden xl:table-cell">
                                {{ $article->created_at?->format('d M Y') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="p-8 text-center theme-text-muted">Tidak ada artikel ditemukan.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="p-4 border-t theme-border-primary">
            {{ $articles->links() }}
        </div>
    </div>
</x-app-layout>
