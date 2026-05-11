<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-1">
            <h2 class="font-semibold text-xl theme-text-primary leading-tight">
                Library
            </h2>
            <p class="text-sm theme-text-muted">
                Tambah artikel dari URL atau upload jurnal PDF untuk dianalisis AI.
            </p>
        </div>
    </x-slot>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        <div class="lg:col-span-8 space-y-6">
            <div x-data="{
                sourceType: 'url',
                uploading: false,
                fileName: '',
                dragOver: false,
                init() {
                    @if ($errors->has('pdf_file'))
                        this.sourceType = 'pdf';
                    @endif
                }
            }" class="card overflow-hidden sm:rounded-lg">
                <div class="p-6">
                    <div class="flex items-center gap-2 mb-5">
                        <div class="flex rounded-xl border theme-border-primary bg-[color:var(--surface-secondary)] p-1">
                            <button
                                type="button"
                                @click="sourceType = 'url'"
                                :class="sourceType === 'url'
                                    ? 'bg-[color:var(--surface-primary)] shadow-sm theme-text-primary'
                                    : 'theme-text-muted hover:theme-text-secondary'"
                                class="px-4 py-2 rounded-lg text-sm font-medium transition-all"
                            >
                                <span class="flex items-center gap-1.5">
                                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>
                                    URL
                                </span>
                            </button>
                            <button
                                type="button"
                                @click="sourceType = 'pdf'"
                                :class="sourceType === 'pdf'
                                    ? 'bg-[color:var(--surface-primary)] shadow-sm theme-text-primary'
                                    : 'theme-text-muted hover:theme-text-secondary'"
                                class="px-4 py-2 rounded-lg text-sm font-medium transition-all"
                            >
                                <span class="flex items-center gap-1.5">
                                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                                    Upload PDF
                                </span>
                            </button>
                        </div>
                    </div>

                    <form id="ingest-form" method="POST" action="{{ route('dashboard.ingest') }}" enctype="multipart/form-data" class="space-y-4">
                        @csrf
                        <input type="hidden" name="source_type" x-model="sourceType">

                        {{-- URL Input --}}
                        <div x-show="sourceType === 'url'" x-transition.opacity>
                            <label for="url" class="block font-medium text-sm theme-text-secondary mb-1.5">URL Artikel / Jurnal</label>
                            <input
                                id="url"
                                name="url"
                                type="url"
                                value="{{ old('url') }}"
                                placeholder="https://example.com/artikel-jurnal"
                                class="block w-full rounded-xl shadow-sm border theme-border-primary bg-[color:var(--surface-primary)] text-[color:var(--text-primary)] placeholder:text-[color:var(--text-muted)] focus:border-[#AA5F3C] focus:ring-[#AA5F3C] px-4 py-2.5"
                            />
                            @error('url')
                                <div class="mt-2 text-sm text-[color:var(--error)]">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- PDF Upload --}}
                        <div x-show="sourceType === 'pdf'" x-transition.opacity>
                            <label class="block font-medium text-sm theme-text-secondary mb-1.5">Upload Jurnal (PDF)</label>
                            <div
                                @dragover.prevent="dragOver = true"
                                @dragleave.prevent="dragOver = false"
                                @drop.prevent="dragOver = false; const f = $event.dataTransfer.files[0]; if(f) { $refs.fileInput.files = $event.dataTransfer.files; fileName = f.name; }"
                                :class="dragOver ? 'border-[#AA5F3C] bg-[color:var(--bg-tertiary)]' : 'theme-border-primary'"
                                class="relative border-2 border-dashed rounded-xl p-8 text-center transition-colors cursor-pointer"
                                @click="$refs.fileInput.click()"
                            >
                                <input
                                    x-ref="fileInput"
                                    id="pdf_file"
                                    name="pdf_file"
                                    type="file"
                                    accept=".pdf"
                                    class="hidden"
                                    @change="fileName = $refs.fileInput.files[0]?.name || ''"
                                />
                                <div x-show="!fileName" class="space-y-2">
                                    <svg class="mx-auto w-10 h-10 theme-text-muted" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 11v6m-3-3l3 3 3-3"/></svg>
                                    <div class="text-sm theme-text-secondary">
                                        <span class="font-medium text-[#AA5F3C]">Klik untuk pilih file</span> atau drag & drop
                                    </div>
                                    <div class="text-xs theme-text-muted">PDF maksimal 10MB</div>
                                </div>
                                <div x-show="fileName" class="space-y-1">
                                    <svg class="mx-auto w-8 h-8 text-[#AA5F3C]" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                    <div class="text-sm font-medium theme-text-primary" x-text="fileName"></div>
                                    <div class="text-xs theme-text-muted">Klik untuk ganti file</div>
                                </div>
                            </div>
                            @error('pdf_file')
                                <div class="mt-2 text-sm text-[color:var(--error)]">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Research Title --}}
                        <div>
                            <label for="research_title" class="block font-medium text-sm theme-text-secondary mb-1.5">
                                Judul Penelitian / Topik Riset
                                <span class="text-xs theme-text-muted font-normal">(opsional)</span>
                            </label>
                            <input
                                id="research_title"
                                name="research_title"
                                type="text"
                                value="{{ old('research_title') }}"
                                placeholder="contoh: Analisis Pengaruh Media Sosial terhadap Produktivitas Mahasiswa"
                                class="block w-full rounded-xl shadow-sm border theme-border-primary bg-[color:var(--surface-primary)] text-[color:var(--text-primary)] placeholder:text-[color:var(--text-muted)] focus:border-[#AA5F3C] focus:ring-[#AA5F3C] px-4 py-2.5"
                            />
                            <div class="mt-1 text-xs theme-text-muted">
                                AI akan memberikan saran kutipan yang relevan dengan judul penelitian Anda.
                            </div>
                        </div>

                        {{-- Submit --}}
                        <div class="flex items-center gap-3 pt-1">
                            <button type="submit" class="btn btn-primary" :disabled="uploading">
                                <span x-show="!uploading" class="flex items-center gap-1.5">
                                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                    Proses
                                </span>
                                <span x-show="uploading" class="flex items-center gap-1.5">
                                    <svg class="animate-spin w-4 h-4" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" class="opacity-25"/><path d="M4 12a8 8 0 018-8" stroke="currentColor" stroke-width="3" class="opacity-75"/></svg>
                                    Memproses…
                                </span>
                            </button>
                        </div>

                        @if (session('status'))
                            <div class="text-sm rounded-xl p-3 bg-[color:var(--bg-tertiary)] text-[#8B9A7A] font-medium" role="status" aria-live="polite">
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
                                    class="block w-full rounded-xl shadow-sm border theme-border-primary bg-[color:var(--surface-primary)] text-[color:var(--text-primary)] placeholder:text-[color:var(--text-muted)] focus:border-[#AA5F3C] focus:ring-[#AA5F3C] px-4 py-2"
                                />
                            </div>
                            <div>
                                <label for="tag" class="sr-only">Tag</label>
                                <select id="tag" name="tag" class="block w-full rounded-xl shadow-sm border theme-border-primary bg-[color:var(--surface-primary)] text-[color:var(--text-primary)] focus:border-[#AA5F3C] focus:ring-[#AA5F3C] px-3 py-2">
                                    <option value="">Semua tag</option>
                                    @foreach ($tags as $tag)
                                        <option value="{{ $tag->id }}" @selected((string)($filters['tag'] ?? '') === (string)$tag->id)>
                                            {{ $tag->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="flex items-center gap-2">
                                <input id="bookmarked" name="bookmarked" type="checkbox" value="1" class="rounded border theme-border-primary text-[#AA5F3C] shadow-sm focus:ring-[#AA5F3C]" @checked(($filters['bookmarked'] ?? false) === true)>
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
                                    <div class="min-w-0 flex-1">
                                        <div class="flex items-center gap-2 flex-wrap">
                                            @php
                                                $statusColors = [
                                                    'ready' => 'bg-[color:var(--bg-tertiary)] text-[#8B9A7A]',
                                                    'queued' => 'bg-[color:var(--bg-tertiary)] theme-text-muted',
                                                    'fetching' => 'bg-[color:var(--bg-tertiary)] text-[#D4A76A]',
                                                    'extracting' => 'bg-[color:var(--bg-tertiary)] text-[#D4A76A]',
                                                    'failed' => 'bg-[color:var(--bg-tertiary)] text-[color:var(--error)]',
                                                ];
                                                $status = $article->processing_status ?? 'ready';
                                                $badgeColor = $statusColors[$status] ?? 'bg-[color:var(--bg-tertiary)] theme-text-muted';
                                            @endphp
                                            <div class="text-xs px-2 py-1 rounded-full border theme-border-primary {{ $badgeColor }}">
                                                {{ $status }}
                                            </div>
                                            @if ($article->source_type === 'pdf')
                                                <div class="text-xs px-2 py-1 rounded-full bg-[color:var(--bg-tertiary)] text-[#AA5F3C] border border-[#AA5F3C]/20">
                                                    <span class="flex items-center gap-1">
                                                        <svg class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                                                        PDF
                                                    </span>
                                                </div>
                                            @endif
                                            @if ($article->source_domain && $article->source_domain !== 'pdf-upload')
                                                <div class="text-xs theme-text-muted truncate">
                                                    {{ $article->source_domain }}
                                                </div>
                                            @endif
                                            @if ($article->research_title)
                                                <div class="text-xs px-2 py-1 rounded-full bg-[color:var(--bg-tertiary)] theme-text-muted truncate max-w-[16rem]" title="{{ $article->research_title }}">
                                                    Riset: {{ $article->research_title }}
                                                </div>
                                            @endif
                                        </div>
                                        <div class="mt-2">
                                            <div class="font-semibold theme-text-primary truncate">
                                                {{ $article->title }}
                                            </div>
                                            @if ($article->excerpt)
                                            <div class="mt-1 text-sm theme-text-secondary overflow-hidden line-clamp-2">
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
                                    @if (!empty($article->ai_quotation_suggestions))
                                        <span class="text-xs px-2 py-1 rounded-full bg-[color:var(--bg-tertiary)] text-[#D4A76A]">
                                            <span class="flex items-center gap-1">
                                                <svg class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                                                Saran Kutipan
                                            </span>
                                        </span>
                                    @endif
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
                                    @elseif ($article->processing_status === 'failed')
                                        <form method="POST" action="{{ route('dashboard.retry', $article) }}">
                                            @csrf
                                            <button type="submit" class="btn btn-secondary">
                                                Proses lagi
                                            </button>
                                        </form>
                                    @else
                                        <span class="text-sm theme-text-muted">Sedang diproses…</span>
                                    @endif
                                </div>
                            </div>
                        @empty
                            <div class="py-12 text-center">
                                <svg class="mx-auto w-12 h-12 theme-text-muted mb-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                                <div class="text-sm theme-text-muted">
                                    Belum ada artikel. Tambah URL atau upload PDF di atas untuk mulai.
                                </div>
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
                        <li class="flex gap-2">
                            <span class="text-[#AA5F3C] mt-0.5">+</span>
                            <span>Upload PDF jurnal untuk analisis dengan AI.</span>
                        </li>
                        <li class="flex gap-2">
                            <span class="text-[#AA5F3C] mt-0.5">+</span>
                            <span>Isi Judul Penelitian untuk dapat saran kutipan yang relevan.</span>
                        </li>
                        <li class="flex gap-2">
                            <span class="text-[#AA5F3C] mt-0.5">+</span>
                            <span>Gunakan filter Tag dan Bookmark untuk kurasi artikel.</span>
                        </li>
                        <li class="flex gap-2">
                            <span class="text-[#AA5F3C] mt-0.5">+</span>
                            <span>Jika proses gagal, coba ulang submit URL yang sama.</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
