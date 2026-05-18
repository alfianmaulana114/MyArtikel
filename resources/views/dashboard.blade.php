<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl theme-text-primary">Library</h2>
            <div class="flex items-center gap-3 text-sm theme-text-muted">
                <span>{{ $stats['total'] ?? 0 }} artikel</span>
                @if(($stats['processing'] ?? 0) > 0)
                    <span class="inline-flex items-center gap-1">
                        <span class="w-2 h-2 rounded-full bg-[#D4A76A] animate-pulse"></span>
                        {{ $stats['processing'] }} diproses
                    </span>
                @endif
                @if(($stats['failed'] ?? 0) > 0)
                    <span class="inline-flex items-center gap-1 text-[color:var(--error)]">
                        <span class="w-2 h-2 rounded-full bg-[color:var(--error)]"></span>
                        {{ $stats['failed'] }} gagal
                    </span>
                @endif
            </div>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            {{-- Add Article — Collapsible --}}
            <div x-data="{
                sourceType: 'url',
                uploading: false,
                fileName: '',
                dragOver: false,
                expanded: false,
                init() {
                    @if ($errors->has('pdf_file') || $errors->has('url') || old('url') || old('research_title'))
                        this.expanded = true;
                    @endif
                    @if ($errors->has('pdf_file'))
                        this.sourceType = 'pdf';
                    @endif
                }
            }" class="card overflow-hidden sm:rounded-lg">
                {{-- Toggle Button --}}
                <button @click="expanded = !expanded" class="w-full flex items-center justify-between px-6 py-4 hover:bg-[color:var(--hover-bg)] transition-colors text-left">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-xl bg-[#AA5F3C]/10 flex items-center justify-center">
                            <svg class="w-5 h-5 text-[#AA5F3C]" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        </div>
                        <div>
                            <div class="font-medium theme-text-primary">Tambah Artikel</div>
                            <div class="text-xs theme-text-muted">URL jurnal atau upload PDF</div>
                        </div>
                    </div>
                    <svg :class="expanded ? 'rotate-180' : ''" class="w-5 h-5 theme-text-muted transition-transform duration-200" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </button>

                {{-- Form Body --}}
                <div x-show="expanded" x-collapse class="border-t theme-border-primary">
                    <form id="ingest-form" method="POST" action="{{ route('dashboard.ingest') }}" enctype="multipart/form-data" class="p-6 space-y-5">
                        @csrf
                        <input type="hidden" name="source_type" x-model="sourceType">

                        {{-- Source type tabs --}}
                        <div class="flex rounded-xl border theme-border-primary bg-[color:var(--surface-secondary)] p-1 w-fit">
                            <button type="button" @click="sourceType = 'url'"
                                :class="sourceType === 'url' ? 'bg-[color:var(--surface-primary)] shadow-sm theme-text-primary' : 'theme-text-muted hover:theme-text-secondary'"
                                class="px-4 py-2 rounded-lg text-sm font-medium transition-all">
                                <span class="flex items-center gap-1.5">
                                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>
                                    URL
                                </span>
                            </button>
                            <button type="button" @click="sourceType = 'pdf'"
                                :class="sourceType === 'pdf' ? 'bg-[color:var(--surface-primary)] shadow-sm theme-text-primary' : 'theme-text-muted hover:theme-text-secondary'"
                                class="px-4 py-2 rounded-lg text-sm font-medium transition-all">
                                <span class="flex items-center gap-1.5">
                                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                                    PDF
                                </span>
                            </button>
                        </div>

                        {{-- URL Input --}}
                        <div x-show="sourceType === 'url'" x-transition.opacity>
                            <label for="url" class="block text-sm font-medium theme-text-secondary mb-1.5">URL Artikel / Jurnal</label>
                            <input id="url" name="url" type="url" value="{{ old('url') }}"
                                placeholder="https://example.com/artikel-jurnal"
                                class="block w-full rounded-xl border theme-border-primary bg-[color:var(--surface-primary)] text-[color:var(--text-primary)] placeholder:text-[color:var(--text-muted)] focus:border-[#AA5F3C] focus:ring-[#AA5F3C] px-4 py-2.5 text-sm" />
                            @error('url')<div class="mt-2 text-sm text-[color:var(--error)]">{{ $message }}</div>@enderror
                        </div>

                        {{-- PDF Upload --}}
                        <div x-show="sourceType === 'pdf'" x-transition.opacity>
                            <label class="block text-sm font-medium theme-text-secondary mb-1.5">Upload Jurnal (PDF)</label>
                            <div @dragover.prevent="dragOver = true" @dragleave.prevent="dragOver = false"
                                @drop.prevent="dragOver = false; const f = $event.dataTransfer.files[0]; if(f) { $refs.fileInput.files = $event.dataTransfer.files; fileName = f.name; }"
                                :class="dragOver ? 'border-[#AA5F3C] bg-[color:var(--bg-tertiary)]' : 'theme-border-primary'"
                                class="relative border-2 border-dashed rounded-xl p-6 text-center transition-colors cursor-pointer"
                                @click="$refs.fileInput.click()">
                                <input x-ref="fileInput" id="pdf_file" name="pdf_file" type="file" accept=".pdf" class="hidden"
                                    @change="fileName = $refs.fileInput.files[0]?.name || ''" />
                                <div x-show="!fileName" class="space-y-1">
                                    <svg class="mx-auto w-8 h-8 theme-text-muted" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 11v6m-3-3l3 3 3-3"/></svg>
                                    <div class="text-sm theme-text-muted"><span class="font-medium text-[#AA5F3C]">Klik atau drop</span> file PDF (maks 10MB)</div>
                                </div>
                                <div x-show="fileName" class="space-y-1">
                                    <svg class="mx-auto w-6 h-6 text-[#AA5F3C]" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                    <div class="text-sm font-medium theme-text-primary" x-text="fileName"></div>
                                </div>
                            </div>
                            @error('pdf_file')<div class="mt-2 text-sm text-[color:var(--error)]">{{ $message }}</div>@enderror
                        </div>

                        {{-- Research info — side by side on desktop --}}
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label for="research_title" class="block text-sm font-medium theme-text-secondary mb-1.5">Judul Penelitian <span class="text-xs theme-text-muted font-normal">(opsional)</span></label>
                                <input id="research_title" name="research_title" type="text" value="{{ old('research_title') }}"
                                    placeholder="Analisis Pengaruh Media Sosial…"
                                    class="block w-full rounded-xl border theme-border-primary bg-[color:var(--surface-primary)] text-[color:var(--text-primary)] placeholder:text-[color:var(--text-muted)] focus:border-[#AA5F3C] focus:ring-[#AA5F3C] px-4 py-2.5 text-sm" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium theme-text-secondary mb-1.5">Konteks Riset <span class="text-xs theme-text-muted font-normal">(opsional)</span></label>
                                <select id="research_context_select"
                                    class="block w-full rounded-xl border theme-border-primary bg-[color:var(--surface-primary)] text-[color:var(--text-primary)] focus:border-[#AA5F3C] focus:ring-[#AA5F3C] px-4 py-2.5 text-sm">
                                    <option value="">— Pilih —</option>
                                    <option value="Bab 1 — Pendahuluan">Bab 1 — Pendahuluan</option>
                                    <option value="Bab 2 — Tinjauan Pustaka">Bab 2 — Tinjauan Pustaka</option>
                                    <option value="Bab 3 — Metodologi">Bab 3 — Metodologi</option>
                                    <option value="Bab 4 — Pembahasan / Hasil">Bab 4 — Pembahasan / Hasil</option>
                                    <option value="Bab 5 — Kesimpulan & Saran">Bab 5 — Kesimpulan</option>
                                    <option value="Landasan Teori">Landasan Teori</option>
                                    <option value="Kerangka Pemikiran">Kerangka Pemikiran</option>
                                    <option value="Analisis Data">Analisis Data</option>
                                    <option value="__custom__">Ketik manual…</option>
                                </select>
                                <input id="research_context" name="research_context" type="text" value="{{ old('research_context') }}"
                                    placeholder="misal: kutipan untuk variabel X"
                                    class="hidden block w-full rounded-xl border theme-border-primary bg-[color:var(--surface-primary)] text-[color:var(--text-primary)] placeholder:text-[color:var(--text-muted)] focus:border-[#AA5F3C] focus:ring-[#AA5F3C] px-4 py-2.5 text-sm mt-2" />
                            </div>
                        </div>

                        <script>
                            (function() {
                                const sel = document.getElementById('research_context_select');
                                const inp = document.getElementById('research_context');
                                if (!sel || !inp) return;
                                sel.addEventListener('change', function() {
                                    if (this.value === '__custom__') { inp.classList.remove('hidden'); inp.focus(); inp.value = ''; }
                                    else { inp.classList.add('hidden'); inp.value = this.value; }
                                });
                                inp.addEventListener('input', function() { if (this.value.trim()) sel.value = '__custom__'; });
                                if (inp.value && !sel.querySelector('option[value="' + inp.value + '"]')) { sel.value = '__custom__'; inp.classList.remove('hidden'); }
                            })();
                        </script>

                        {{-- Submit --}}
                        <div class="flex items-center gap-3">
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
                            <div class="flex items-center gap-2 text-sm rounded-xl p-3 bg-[color:var(--bg-tertiary)] text-[#8B9A7A] font-medium" role="status">
                                <svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                {{ session('status') }}
                            </div>
                        @endif
                    </form>
                </div>
            </div>

            {{-- Search --}}
            <div class="relative">
                <svg class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 theme-text-muted pointer-events-none" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                <form method="GET" action="{{ route('dashboard') }}" class="w-full">
                    <input id="q" name="q" type="text" value="{{ $filters['q'] ?? '' }}"
                        placeholder="Cari judul, domain, atau URL…"
                        class="block w-full rounded-xl border theme-border-primary bg-[color:var(--surface-primary)] text-[color:var(--text-primary)] placeholder:text-[color:var(--text-muted)] focus:border-[#AA5F3C] focus:ring-[#AA5F3C] pl-10 pr-4 py-2.5 text-sm" />
                </form>
            </div>

            {{-- Article List --}}
            <div class="space-y-3">
                @forelse ($articles as $article)
                    @php
                        $statusColors = [
                            'ready' => 'text-[#8B9A7A]',
                            'queued' => 'text-[#D4A76A]',
                            'fetching' => 'text-[#D4A76A]',
                            'extracting' => 'text-[#D4A76A]',
                            'failed' => 'text-[color:var(--error)]',
                        ];
                        $statusIcons = [
                            'ready' => '<svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>',
                            'queued' => '<svg class="w-3.5 h-3.5 animate-spin" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" class="opacity-25"/><path d="M4 12a8 8 0 018-8" stroke="currentColor" stroke-width="3" class="opacity-75"/></svg>',
                            'fetching' => '<svg class="w-3.5 h-3.5 animate-spin" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" class="opacity-25"/><path d="M4 12a8 8 0 018-8" stroke="currentColor" stroke-width="3" class="opacity-75"/></svg>',
                            'extracting' => '<svg class="w-3.5 h-3.5 animate-spin" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" class="opacity-25"/><path d="M4 12a8 8 0 018-8" stroke="currentColor" stroke-width="3" class="opacity-75"/></svg>',
                            'failed' => '<svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>',
                        ];
                        $status = $article->processing_status ?? 'ready';
                        $statusColor = $statusColors[$status] ?? 'theme-text-muted';
                        $statusIcon = $statusIcons[$status] ?? $statusIcons['ready'];
                    @endphp

                    <a href="{{ $article->processing_status === 'ready' ? route('articles.show', $article) : '#' }}"
                        class="block card sm:rounded-lg hover:shadow-md transition-shadow {{ $article->processing_status === 'ready' ? 'cursor-pointer' : 'cursor-default' }}">

                        {{-- Status bar --}}
                        @if ($status !== 'ready')
                            <div class="px-5 py-2.5 text-xs font-medium {{ $statusColor }} border-b theme-border-primary bg-[color:var(--bg-tertiary)] flex items-center justify-between">
                                @if ($status === 'failed')
                                    <span class="flex items-center gap-1.5">{!! $statusIcon !!} Gagal diproses</span>
                                    <form method="POST" action="{{ route('dashboard.retry', $article) }}" class="inline">
                                        @csrf
                                        <button type="submit" class="underline hover:no-underline">Coba lagi</button>
                                    </form>
                                @else
                                    <span class="flex items-center gap-1.5">{!! $statusIcon !!} {{ ucfirst($status) }}…</span>
                                @endif
                            </div>
                        @endif

                        @if ($article->processing_status === 'failed' && $article->processing_error)
                            <div class="px-5 py-2 text-xs text-[color:var(--error)] bg-[color:var(--error-bg)]">
                                {{ \Illuminate\Support\Str::limit($article->processing_error, 120) }}
                            </div>
                        @endif

                        <div class="p-5">
                            <div class="flex items-start gap-4">
                                {{-- Source icon --}}
                                <div class="shrink-0 w-10 h-10 rounded-xl bg-[color:var(--bg-tertiary)] flex items-center justify-center">
                                    @if ($article->source_type === 'pdf')
                                        <svg class="w-5 h-5 text-[#AA5F3C]" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                                    @else
                                        <svg class="w-5 h-5 text-[#AA5F3C]" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>
                                    @endif
                                </div>

                                {{-- Content --}}
                                <div class="min-w-0 flex-1">
                                    <div class="font-semibold theme-text-primary leading-snug line-clamp-2">
                                        {{ $article->title }}
                                    </div>

                                    {{-- Meta line --}}
                                    <div class="mt-1.5 flex flex-wrap items-center gap-x-2 gap-y-1 text-xs theme-text-muted">
                                        @if ($article->source_domain && $article->source_domain !== 'pdf-upload')
                                            <span>{{ $article->source_domain }}</span>
                                            <span>·</span>
                                        @endif
                                        <span>{{ $article->created_at?->format('d M Y') }}</span>
                                        @if (auth()->user()?->is_admin && !empty($article->ai_quotation_suggestions))
                                            <span>·</span>
                                            <span class="text-[#D4A76A] font-medium">{{ count($article->ai_quotation_suggestions) }} kutipan</span>
                                        @endif
                                        @if ($article->research_title)
                                            <span>·</span>
                                            <span class="truncate max-w-[12rem]" title="{{ $article->research_title }}">{{ \Illuminate\Support\Str::limit($article->research_title, 35) }}</span>
                                        @endif
                                    </div>

                                    {{-- Context badge --}}
                                    @if ($article->research_context)
                                        <div class="mt-2">
                                            <span class="text-xs px-2 py-0.5 rounded-md bg-[color:var(--bg-tertiary)] text-[#AA5F3C] font-medium">
                                                {{ $article->research_context }}
                                            </span>
                                        </div>
                                    @endif

                                    {{-- Excerpt --}}
                                    @if ($article->excerpt && $status === 'ready')
                                        <div class="mt-2 text-sm theme-text-secondary line-clamp-2 leading-relaxed">
                                            {{ \Illuminate\Support\Str::limit($article->excerpt, 140) }}
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </a>
                @empty
                    <div class="card sm:rounded-lg p-12 text-center">
                        <svg class="mx-auto w-16 h-16 theme-text-muted opacity-30 mb-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                        <div class="text-sm theme-text-muted">
                            Belum ada artikel.<br>Klik <strong class="text-[#AA5F3C]">Tambah Artikel</strong> di atas untuk mulai.
                        </div>
                    </div>
                @endforelse
            </div>

            {{-- Pagination --}}
            @if ($articles->hasMorePages())
                <div class="text-center">
                    {{ $articles->links() }}
                </div>
            @endif
        </div>
    </div>
</x-app-layout>