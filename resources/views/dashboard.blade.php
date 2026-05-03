<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-1">
            <h2 class="font-semibold text-xl theme-text-primary leading-tight">
                Library
            </h2>
            <p class="text-sm theme-text-muted">
                Paste URL untuk menyimpan artikel, lalu baca di clean reader.
            </p>
        </div>
    </x-slot>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        <div class="lg:col-span-8 space-y-6">
            <div class="card overflow-hidden sm:rounded-lg">
                <div class="p-6">
                    <form method="POST" action="{{ route('dashboard.ingest') }}" class="space-y-3">
                        @csrf
                        <div class="flex flex-col sm:flex-row gap-3">
                            <div class="flex-1">
                                <label for="url" class="block font-medium text-sm theme-text-secondary">URL artikel</label>
                                <input
                                    id="url"
                                    name="url"
                                    type="url"
                                    value="{{ old('url') }}"
                                    placeholder="https://example.com/artikel"
                                    class="mt-1 block w-full rounded-lg shadow-sm border theme-border-primary bg-[color:var(--surface-primary)] text-[color:var(--text-primary)] placeholder:text-[color:var(--text-muted)] focus:border-[#AA5F3C] focus:ring-[#AA5F3C]"
                                    required
                                />
                                @error('url')
                                    <div class="mt-2 text-sm text-[color:var(--error)]">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="sm:self-end">
                                <button type="submit" class="btn btn-primary w-full sm:w-auto">
                                    Simpan
                                </button>
                            </div>
                        </div>

                        @if (session('status'))
                            <div class="text-sm text-[#8B9A7A] font-medium" role="status" aria-live="polite">
                                {{ session('status') }}
                            </div>
                        @endif
                    </form>
                </div>
            </div>

            <div class="card overflow-hidden sm:rounded-lg">
                <div class="p-6">
                    <div class="flex flex-col sm:flex-row gap-3 sm:items-center sm:justify-between">
                        <form method="GET" action="{{ route('dashboard') }}" class="flex flex-col sm:flex-row gap-3 sm:items-center w-full">
                            <div class="flex-1">
                                <label for="q" class="sr-only">Cari</label>
                                <input
                                    id="q"
                                    name="q"
                                    type="text"
                                    value="{{ $filters['q'] ?? '' }}"
                                    placeholder="Cari judul / domain / URL…"
                                    class="block w-full rounded-lg shadow-sm border theme-border-primary bg-[color:var(--surface-primary)] text-[color:var(--text-primary)] placeholder:text-[color:var(--text-muted)] focus:border-[#AA5F3C] focus:ring-[#AA5F3C]"
                                />
                            </div>
                            <div>
                                <label for="tag" class="sr-only">Tag</label>
                                <select id="tag" name="tag" class="block w-full rounded-lg shadow-sm border theme-border-primary bg-[color:var(--surface-primary)] text-[color:var(--text-primary)] focus:border-[#AA5F3C] focus:ring-[#AA5F3C]">
                                    <option value="">Semua tag</option>
                                    @foreach ($tags as $tag)
                                        <option value="{{ $tag->id }}" @selected((string)($filters['tag'] ?? '') === (string)$tag->id)>
                                            {{ $tag->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="flex items-center gap-2">
                                <input id="bookmarked" name="bookmarked" type="checkbox" value="1" class="rounded theme-border-primary text-[#AA5F3C] shadow-sm focus:ring-[#AA5F3C]" @checked(($filters['bookmarked'] ?? false) === true)>
                                <label for="bookmarked" class="text-sm theme-text-muted">Bookmarked</label>
                            </div>
                            <div class="sm:self-stretch">
                                <button type="submit" class="btn btn-secondary w-full sm:w-auto">
                                    Filter
                                </button>
                            </div>
                        </form>
                    </div>

                    <div class="mt-6 divide-y divide-black/5">
                        @forelse ($articles as $article)
                            <div class="py-5 flex flex-col gap-3">
                                <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-2">
                                    <div class="min-w-0">
                                        <div class="flex items-center gap-2">
                                            <div class="text-xs px-2 py-1 rounded-full border theme-border-primary bg-[color:var(--surface-secondary)] text-[color:var(--text-muted)]">
                                                {{ $article->processing_status ?? 'ready' }}
                                            </div>
                                            @if ($article->source_domain)
                                                <div class="text-xs theme-text-muted truncate">
                                                    {{ $article->source_domain }}
                                                </div>
                                            @endif
                                        </div>
                                        <div class="mt-2">
                                            <div class="font-semibold theme-text-primary truncate">
                                                {{ $article->title }}
                                            </div>
                                            @if ($article->excerpt)
                                            <div class="mt-1 text-sm theme-text-secondary overflow-hidden">
                                                {{ $article->excerpt }}
                                            </div>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="text-sm theme-text-muted whitespace-nowrap">
                                        {{ $article->created_at?->format('d M Y, H:i') }}
                                    </div>
                                </div>

                                <div class="flex flex-wrap items-center gap-2 text-sm">
                                    <div class="theme-text-muted">
                                        {{ $article->notes_count }} notes · {{ $article->bookmarks_count }} bookmarks
                                    </div>
                                    @foreach ($article->tags as $tag)
                                        <span class="text-xs px-2 py-1 rounded-full bg-[color:var(--bg-tertiary)] theme-text-secondary">
                                            {{ $tag->name }}
                                        </span>
                                    @endforeach
                                </div>

                                @if ($article->processing_status === 'failed' && $article->processing_error)
                                    <div class="text-sm bg-[color:var(--error-bg)] text-[color:var(--error)] border border-[color:var(--error-border)] rounded-lg px-3 py-2">
                                        {{ $article->processing_error }}
                                    </div>
                                @endif

                                <div class="flex items-center justify-end">
                                    @if ($article->processing_status === 'ready')
                                        <a class="btn btn-secondary" href="{{ route('articles.show', $article) }}">
                                            Baca
                                        </a>
                                    @else
                                        <form method="POST" action="{{ route('dashboard.retry', $article) }}">
                                            @csrf
                                            <button type="submit" class="btn btn-secondary">
                                                Proses lagi
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </div>
                        @empty
                            <div class="py-10 text-center text-sm theme-text-muted">
                                Belum ada artikel. Paste URL di atas untuk mulai menyimpan artikel.
                            </div>
                        @endforelse
                    </div>

                    <div class="mt-6">
                        {{ $articles->links() }}
                    </div>
                </div>
            </div>
        </div>

        <div class="lg:col-span-4 space-y-6 lg:sticky lg:top-24 self-start">
            <div class="card overflow-hidden sm:rounded-lg">
                <div class="p-6">
                    <div class="font-semibold theme-text-primary">Ringkasan</div>
                    <div class="mt-4 grid grid-cols-2 gap-3">
                        <div class="rounded-xl border theme-border-primary bg-[color:var(--surface-primary)] p-4">
                            <div class="text-xs theme-text-muted">Total</div>
                            <div class="mt-1 text-lg font-semibold theme-text-primary">{{ $stats['total'] ?? 0 }}</div>
                        </div>
                        <div class="rounded-xl border theme-border-primary bg-[color:var(--surface-primary)] p-4">
                            <div class="text-xs theme-text-muted">Ready</div>
                            <div class="mt-1 text-lg font-semibold theme-text-primary">{{ $stats['ready'] ?? 0 }}</div>
                        </div>
                        <div class="rounded-xl border theme-border-primary bg-[color:var(--surface-primary)] p-4">
                            <div class="text-xs theme-text-muted">Processing</div>
                            <div class="mt-1 text-lg font-semibold theme-text-primary">{{ $stats['processing'] ?? 0 }}</div>
                        </div>
                        <div class="rounded-xl border theme-border-primary bg-[color:var(--surface-primary)] p-4">
                            <div class="text-xs theme-text-muted">Failed</div>
                            <div class="mt-1 text-lg font-semibold theme-text-primary">{{ $stats['failed'] ?? 0 }}</div>
                        </div>
                    </div>
                    <div class="mt-4 rounded-xl border theme-border-primary bg-[color:var(--surface-primary)] p-4">
                        <div class="text-xs theme-text-muted">Bookmarked</div>
                        <div class="mt-1 text-lg font-semibold theme-text-primary">{{ $stats['bookmarked'] ?? 0 }}</div>
                    </div>
                </div>
            </div>

            <div class="card overflow-hidden sm:rounded-lg">
                <div class="p-6">
                    <div class="font-semibold theme-text-primary">Tips</div>
                    <ul class="mt-3 text-sm theme-text-secondary space-y-2">
                        <li>Gunakan filter Tag untuk kurasi artikel.</li>
                        <li>Gunakan Bookmark untuk artikel yang paling penting.</li>
                        <li>Jika proses gagal, coba ulang submit URL yang sama.</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
