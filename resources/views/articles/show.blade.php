<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-3">
            <div class="min-w-0 flex-1">
                <h2 class="font-semibold text-xl theme-text-primary leading-tight truncate">
                    {{ $article->title }}
                </h2>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('articles.index') }}" class="btn btn-secondary">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m2 14l7-7m-7 7l-7-7"/></svg>
                    Kembali
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            {{-- Status Processing --}}
            @if ($article->processing_status !== 'ready')
                <div class="mb-6 card sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex items-center gap-3">
                            <svg class="animate-spin w-5 h-5 text-[#AA5F3C]" viewBox="0 0 24 24" fill="none" stroke="currentColor"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" class="opacity-25"/><path d="M4 12a8 8 0 018-8" stroke="currentColor" stroke-width="3" class="opacity-75"/></svg>
                            <div>
                                <div class="font-semibold theme-text-primary">Artikel masih diproses</div>
                                <div class="text-sm theme-text-muted">
                                    Status: {{ $article->processing_status ?? 'queued' }}
                                </div>
                            </div>
                        </div>
                        @if ($article->processing_status === 'failed' && $article->processing_error)
                            <div class="mt-3 text-sm bg-[color:var(--error-bg)] text-[color:var(--error)] border border-[color:var(--error-border)] rounded-lg px-3 py-2">
                                {{ $article->processing_error }}
                            </div>
                        @endif
                    </div>
                </div>
            @endif

            {{-- Main Content --}}
            <div class="card sm:rounded-lg overflow-hidden mb-6">
                <div class="p-6 sm:p-8">
                    {{-- Source info --}}
                    @if ($article->source_url)
                        <div class="flex items-center gap-2 mb-4 text-sm theme-text-muted">
                            <svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>
                            <a href="{{ $article->source_url }}" target="_blank" rel="noopener noreferrer" class="hover:underline truncate">{{ $article->source_domain ?? parse_url($article->source_url, PHP_URL_HOST) }}</a>
                        </div>
                    @elseif ($article->source_type === 'pdf')
                        <div class="flex items-center gap-2 mb-4 text-sm theme-text-muted">
                            <svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                            <span>Upload PDF</span>
                        </div>
                    @endif

                    {{-- Research Title & Context --}}
                    <div class="mb-4" x-data="{
                        editing: false,
                        saving: false,
                        researchTitle: {{ Js::from($article->research_title ?? '') }},
                        researchContext: {{ Js::from($article->research_context ?? '') }}
                    }">
                        <div class="space-y-2" x-show="!editing">
                            @if ($article->research_title)
                                <div class="flex items-start gap-2">
                                    <div class="flex-1">
                                        <div class="text-xs theme-text-muted mb-0.5">Judul Penelitian</div>
                                        <div class="text-sm font-medium theme-text-secondary">{{ $article->research_title }}</div>
                                    </div>
                                </div>
                            @endif
                            @if ($article->research_context)
                                <div class="flex items-start gap-2">
                                    <div class="flex-1">
                                        <div class="text-xs theme-text-muted mb-0.5">Konteks Riset</div>
                                        <div class="text-sm theme-text-secondary">{{ $article->research_context }}</div>
                                    </div>
                                </div>
                            @endif
                            @if (! $article->research_title && ! $article->research_context)
                                <button @click="editing = true" class="flex items-center gap-2 text-sm px-3 py-2 rounded-lg border border-dashed theme-border-primary hover:bg-[color:var(--bg-tertiary)] transition-colors w-full">
                                    <svg class="w-4 h-4 text-[#AA5F3C]" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                    <span class="theme-text-muted">Tambah Judul Penelitian & Konteks agar saran kutipan lebih relevan</span>
                                </button>
                            @else
                                <button @click="editing = true" class="text-xs px-2 py-1 rounded-lg bg-[color:var(--bg-tertiary)] theme-text-muted hover:bg-[#AA5F3C] hover:text-white transition-colors" title="Edit">
                                    <svg class="w-3.5 h-3.5 inline" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                    Edit
                                </button>
                            @endif
                        </div>

                        <div x-show="editing" x-transition class="space-y-3">
                            <div>
                                <label class="block text-xs font-medium theme-text-muted mb-1">Judul Penelitian / Topik Riset</label>
                                <input type="text" x-model="researchTitle"
                                    class="block w-full rounded-xl shadow-sm border theme-border-primary bg-[color:var(--surface-primary)] text-[color:var(--text-primary)] placeholder:text-[color:var(--text-muted)] focus:border-[#AA5F3C] focus:ring-[#AA5F3C] px-4 py-2 text-sm"
                                    placeholder="contoh: Analisis Pengaruh Media Sosial terhadap Produktivitas Mahasiswa">
                            </div>
                            <div>
                                <label class="block text-xs font-medium theme-text-muted mb-1">Konteks Riset (bagian skripsi/kebutuhan)</label>
                                <select x-model="researchContextSelect"
                                    x-init="$watch('researchContextSelect', v => { if (v !== '__custom__') researchContext = v; })"
                                    class="block w-full rounded-xl shadow-sm border theme-border-primary bg-[color:var(--surface-primary)] text-[color:var(--text-primary)] focus:border-[#AA5F3C] focus:ring-[#AA5F3C] px-4 py-2 text-sm">
                                    <option value="">— Pilih konteks —</option>
                                    <option value="Bab 1 — Pendahuluan">Bab 1 — Pendahuluan</option>
                                    <option value="Bab 2 — Tinjauan Pustaka">Bab 2 — Tinjauan Pustaka</option>
                                    <option value="Bab 3 — Metodologi">Bab 3 — Metodologi</option>
                                    <option value="Bab 4 — Pembahasan / Hasil">Bab 4 — Pembahasan / Hasil</option>
                                    <option value="Bab 5 — Kesimpulan & Saran">Bab 5 — Kesimpulan & Saran</option>
                                    <option value="Landasan Teori">Landasan Teori</option>
                                    <option value="Kerangka Pemikiran">Kerangka Pemikiran</option>
                                    <option value="Analisis Data">Analisis Data</option>
                                    <option value="__custom__">Ketik manual…</option>
                                </select>
                                <input type="text" x-model="researchContext"
                                    x-show="researchContextSelect === '__custom__'"
                                    class="mt-2 block w-full rounded-xl shadow-sm border theme-border-primary bg-[color:var(--surface-primary)] text-[color:var(--text-primary)] placeholder:text-[color:var(--text-muted)] focus:border-[#AA5F3C] focus:ring-[#AA5F3C] px-4 py-2 text-sm"
                                    placeholder="misal: kutipan untuk variabel X di bab 3">
                            </div>
                            <div class="flex items-center gap-2">
                                <button @click="saving = true;
                                    fetch('{{ route('articles.update', $article->id) }}', {
                                        method: 'PUT',
                                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                                        body: JSON.stringify({ research_title: researchTitle, research_context: researchContext })
                                    }).then(r => r.json()).then(() => { saving = false; editing = false; window.location.reload(); }).catch(() => { saving = false; })"
                                    :disabled="saving"
                                    class="btn btn-primary text-xs px-3 py-1.5">
                                    Simpan
                                </button>
                                <button @click="editing = false"
                                    :disabled="saving"
                                    class="btn btn-secondary text-xs px-3 py-1.5">
                                    Batal
                                </button>
                            </div>
                        </div>
                    </div>

                    @if ($article->excerpt)
                        <div class="p-4 rounded-xl bg-[color:var(--bg-tertiary)] border-l-4 border-[#AA5F3C]">
                            <div class="text-sm theme-text-secondary leading-relaxed">
                                {{ $article->excerpt }}
                            </div>
                        </div>
                    @else
                        <div class="text-center py-8">
                            <svg class="mx-auto w-12 h-12 theme-text-muted mb-3" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            <div class="text-sm theme-text-muted">
                                Konten artikel tidak ditampilkan. Silakan gunakan fitur Rangkuman atau Saran Kutipan di bawah.
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Tab Section: Rangkuman & Saran Kutipan --}}
            <div class="card sm:rounded-lg overflow-hidden mb-6" x-data="{
                activeTab: 'summary',
            }">
                {{-- Tab Navigation --}}
                <div class="flex border-b theme-border-primary bg-[color:var(--bg-tertiary)]">
                    <button
                        @click="activeTab = 'summary'"
                        :class="activeTab === 'summary'
                            ? 'theme-text-primary border-b-3 border-[#AA5F3C] bg-[color:var(--surface-primary)]'
                            : 'theme-text-muted border-b-3 border-transparent hover:theme-text-secondary hover:bg-[color:var(--surface-primary)]/50'"
                        class="flex-1 px-6 py-4 text-sm font-medium transition-all text-center"
                    >
                        <span class="flex items-center justify-center gap-2">
                            <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            Rangkuman
                        </span>
                    </button>
                    <button
                        @click="activeTab = 'citations'"
                        :class="activeTab === 'citations'
                            ? 'theme-text-primary border-b-3 border-[#AA5F3C] bg-[color:var(--surface-primary)]'
                            : 'theme-text-muted border-b-3 border-transparent hover:theme-text-secondary hover:bg-[color:var(--surface-primary)]/50'"
                        class="flex-1 px-6 py-4 text-sm font-medium transition-all text-center relative"
                    >
                        <span class="flex items-center justify-center gap-2">
                            <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                            Saran Kutipan
                        </span>
                        @if (auth()->user()?->is_admin && !empty($article->ai_quotation_suggestions))
                            <span class="absolute top-2 right-4 text-xs px-2 py-0.5 rounded-full bg-[#AA5F3C] text-white">
                                {{ count($article->ai_quotation_suggestions) }}
                            </span>
                        @endif
                    </button>
                </div>

                {{-- Summary Tab Content --}}
                <div x-show="activeTab === 'summary'" x-transition class="p-6 sm:p-8">
                    <div class="flex items-start justify-between gap-3 mb-6">
                        <div>
                            <h3 class="text-lg font-semibold theme-text-primary">Ringkasan</h3>
                            <p class="text-sm theme-text-muted mt-1">
                                Generate ringkasan on-demand sesuai kebutuhan.
                            </p>
                        </div>

                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-4">
                        <div class="sm:col-span-1">
                            <label for="summary-language" class="block text-sm font-medium theme-text-secondary mb-2">Bahasa</label>
                            <select id="summary-language" class="block w-full rounded-lg shadow-sm border theme-border-primary bg-[color:var(--surface-primary)] text-[color:var(--text-primary)] focus:border-[#AA5F3C] focus:ring-[#AA5F3C]">
                                <option value="id">Indonesia</option>
                                <option value="en">English</option>
                            </select>
                        </div>
                        <div class="sm:col-span-2">
                            <label for="summary-words" class="block text-sm font-medium theme-text-secondary mb-2">Panjang Ringkasan</label>
                            <select id="summary-words" class="block w-full rounded-lg shadow-sm border theme-border-primary bg-[color:var(--surface-primary)] text-[color:var(--text-primary)] focus:border-[#AA5F3C] focus:ring-[#AA5F3C]">
                                <option value="150">~150 kata (Singkat)</option>
                                <option value="250">~250 kata (Sedang)</option>
                                <option value="350">~350 kata (Detail)</option>
                            </select>
                        </div>
                    </div>

                    @if (auth()->user()?->is_admin)
                    <div class="flex items-center justify-between gap-4 mb-4 p-4 rounded-lg bg-[color:var(--bg-tertiary)]">
                        <label class="inline-flex items-center gap-2 text-sm theme-text-secondary">
                            <input id="summary-prefer-ai" type="checkbox" class="rounded theme-border-primary text-[#AA5F3C] shadow-sm focus:ring-[#AA5F3C]" checked>
                            <span>Prefer AI (Gemini)</span>
                        </label>
                        <label class="inline-flex items-center gap-2 text-sm theme-text-secondary">
                            <input id="summary-async" type="checkbox" class="rounded theme-border-primary text-[#AA5F3C] shadow-sm focus:ring-[#AA5F3C]">
                            <span>Background Process</span>
                        </label>
                    </div>

                    <div class="text-xs theme-text-muted mb-4" id="summary-quota"></div>
                    @endif

                    <div class="flex flex-wrap gap-3 mb-6">
                        <button id="summary-generate" type="button" class="btn btn-primary">
                            <span class="flex items-center gap-2">
                                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                                Buat Ringkasan
                            </span>
                        </button>
                        <button id="summary-regenerate" type="button" class="btn btn-secondary">
                            <span class="flex items-center gap-2">
                                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                Regenerate
                            </span>
                        </button>
                    </div>

                    <div class="mb-4">
                        <div id="summary-status" class="text-sm theme-text-muted"></div>
                        <div id="summary-error" class="hidden mt-3 text-sm bg-[color:var(--error-bg)] text-[color:var(--error)] border border-[color:var(--error-border)] rounded-lg px-4 py-3"></div>
                    </div>

                    <div id="summary-result" class="hidden space-y-4">
                        <div class="rounded-xl border theme-border-primary bg-[color:var(--surface-primary)] p-5">
                            <div class="flex items-center gap-2 mb-3">
                                <svg class="w-4 h-4 text-[#AA5F3C]" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                <span class="text-sm font-medium theme-text-primary">Ringkasan</span>
                            </div>
                            <div id="summary-content" class="text-sm theme-text-secondary leading-relaxed whitespace-pre-wrap"></div>
                        </div>
                        <div class="rounded-xl border theme-border-primary bg-[color:var(--surface-primary)] p-5">
                            <div class="flex items-center gap-2 mb-3">
                                <svg class="w-4 h-4 text-[#AA5F3C]" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/></svg>
                                <span class="text-sm font-medium theme-text-primary">Poin Kunci</span>
                            </div>
                            <div id="summary-points" class="flex flex-wrap gap-2"></div>
                        </div>
                    </div>
                </div>

                {{-- Citations Tab Content --}}
                <div x-show="activeTab === 'citations'" x-transition class="p-6 sm:p-8">
                    <div class="flex items-start justify-between gap-3 mb-6">
                        <div>
                            <h3 class="text-lg font-semibold theme-text-primary">Saran Kutipan</h3>
                            <p class="text-sm theme-text-muted mt-1">
                                Saran kutipan dari artikel ini.
                            </p>
                        </div>
                        @if ($article->processing_status === 'ready' && !empty($article->text_extracted))
                            <button id="generate-citations-btn" type="button"
                                class="btn btn-primary text-sm shrink-0"
                                @if(empty($article->research_title)) disabled title="Isi Judul Penelitian terlebih dahulu" @endif>
                                <span class="flex items-center gap-1.5">
                                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                                    Buat Kutipan
                                </span>
                            </button>
                        @endif
                    </div>

                    @if ($article->processing_status === 'ready' && empty($article->research_title))
                        <div class="mb-4 p-3 rounded-lg bg-[color:var(--bg-tertiary)] border border-dashed theme-border-primary text-sm theme-text-muted">
                            <span class="font-medium text-[#AA5F3C]">Judul Penelitian belum diisi.</span> Tambahkan judul penelitian di atas agar saran kutipan lebih relevan.
                        </div>
                    @endif

                    @if (!empty($article->ai_quotation_suggestions))
                        <div class="space-y-4">
                            @foreach ($article->ai_quotation_suggestions as $index => $citation)
                                <div class="rounded-xl border theme-border-primary bg-[color:var(--surface-primary)] p-5 hover:shadow-md transition-shadow">
                                    <div class="flex items-center gap-3 mb-3">
                                        <span class="text-xs px-3 py-1 rounded-full bg-[#AA5F3C] text-white font-medium">
                                            Kutipan #{{ $index + 1 }}
                                        </span>
                                        @if (!empty($citation['position']))
                                            <span class="text-xs px-3 py-1 rounded-full bg-[color:var(--bg-tertiary)] theme-text-muted">
                                                {{ $citation['position'] }}
                                            </span>
                                        @endif
                                    </div>
                                    <blockquote class="border-l-4 border-[#AA5F3C] pl-4 italic text-sm theme-text-secondary leading-relaxed mb-3">
                                        "{{ $citation['quote'] ?? '' }}"
                                    </blockquote>
                                    @if (!empty($citation['relevance']))
                                        <div class="p-3 rounded-lg bg-[color:var(--bg-tertiary)]">
                                            <div class="text-xs font-medium theme-text-primary mb-1">Relevansi:</div>
                                            <div class="text-xs theme-text-muted leading-relaxed">
                                                {{ $citation['relevance'] }}
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="rounded-xl border theme-border-primary bg-[color:var(--surface-primary)] p-8 text-center">
                            <svg class="mx-auto w-12 h-12 theme-text-muted mb-3" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                            <div class="text-sm theme-text-muted">
                                Belum ada kutipan.
                            </div>
                            <div class="mt-2 text-xs theme-text-muted">
                                Klik tombol <strong>Generate Kutipan</strong> di atas untuk membuat saran kutipan dari artikel ini.
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            (() => {
                const articleId = {{ (int) $article->id }};
                const csrf = document.querySelector('meta[name="csrf-token"]').content;

                const els = {
                    language: document.getElementById("summary-language"),
                    words: document.getElementById("summary-words"),
                    preferAI: document.getElementById("summary-prefer-ai"),
                    async: document.getElementById("summary-async"),
                    quota: document.getElementById("summary-quota"),
                    generate: document.getElementById("summary-generate"),
                    regenerate: document.getElementById("summary-regenerate"),
                    status: document.getElementById("summary-status"),
                    error: document.getElementById("summary-error"),
                    result: document.getElementById("summary-result"),
                    content: document.getElementById("summary-content"),
                    points: document.getElementById("summary-points"),
                };

                let pollTimer = null;

                function setStatus(text) {
                    els.status.textContent = text || "";
                }

                function setError(text) {
                    els.error.textContent = text || "";
                    els.error.classList.toggle("hidden", !text);
                }

                function setLoading(isLoading) {
                    els.generate.disabled = isLoading;
                    els.regenerate.disabled = isLoading;
                    els.generate.classList.toggle("opacity-70", isLoading);
                    els.regenerate.classList.toggle("opacity-70", isLoading);
                }

                function escapeHtml(s) {
                    return String(s)
                        .replaceAll("&", "&amp;")
                        .replaceAll("<", "&lt;")
                        .replaceAll(">", "&gt;")
                        .replaceAll('"', "&quot;")
                        .replaceAll("'", "&#039;");
                }

                function renderSummary(summary) {
                    const content = summary?.content || "";
                    const points = Array.isArray(summary?.key_points) ? summary.key_points : [];

                    els.content.textContent = content;
                    els.points.innerHTML = points.length
                        ? points
                                .map(
                                    (p) =>
                                        `<span class="text-xs px-3 py-1.5 rounded-full bg-[color:var(--bg-tertiary)] theme-text-secondary">${escapeHtml(
                                            p,
                                        )}</span>`,
                                )
                                .join("")
                        : `<span class="text-sm theme-text-muted">-</span>`;

                    els.result.classList.remove("hidden");
                }

                function stopPolling() {
                    if (pollTimer) {
                        clearInterval(pollTimer);
                        pollTimer = null;
                    }
                }

                async function loadQuota() {
                    if (!els.quota) return;
                    try {
                        const res = await fetch("/summaries/quota", {
                            credentials: "same-origin",
                            headers: { Accept: "application/json", "X-Requested-With": "XMLHttpRequest" },
                        });
                        const json = await res.json();
                        const q = json?.data?.quota_status;
                        if (!q) return;

                        const gemini = q.gemini;
                        const local = q.local;
                        const geminiText = gemini ? `${gemini.remaining}/${gemini.limit} Gemini` : "";
                        const localText = local ? `${local.remaining}/${local.limit} Local` : "";
                        els.quota.textContent = [geminiText, localText].filter(Boolean).join(" · ");
                    } catch (e) {}
                }

                async function loadExistingLatest() {
                    try {
                        const res = await fetch(`/summaries/data?article_id=${articleId}`, {
                            credentials: "same-origin",
                            headers: { Accept: "application/json", "X-Requested-With": "XMLHttpRequest" },
                        });
                        const json = await res.json();
                        const first = json?.data?.data?.[0];
                        if (first?.content) {
                            renderSummary(first);
                            setStatus("Ringkasan terakhir ditampilkan.");
                        } else {
                            setStatus("Belum ada ringkasan untuk artikel ini.");
                        }
                    } catch (e) {
                        setStatus("Belum ada ringkasan untuk artikel ini.");
                    }
                }

                async function fetchSummary(id) {
                    const res = await fetch(`/summaries/${id}`, {
                        credentials: "same-origin",
                        headers: { Accept: "application/json", "X-Requested-With": "XMLHttpRequest" },
                    });
                    const json = await res.json();
                    if (!json?.success) throw new Error("failed");
                    return json.data;
                }

                async function fetchStatus(id) {
                    const res = await fetch(`/summaries/${id}/status`, {
                        credentials: "same-origin",
                        headers: { Accept: "application/json", "X-Requested-With": "XMLHttpRequest" },
                    });
                    const json = await res.json();
                    if (!json?.success) throw new Error("failed");
                    return json.data;
                }

                async function generate(asyncMode = true) {
                    stopPolling();
                    setError("");
                    setLoading(true);
                    els.result.classList.add("hidden");

                    const body = {
                        article_id: articleId,
                        max_words: Number(els.words.value || 150),
                        language: els.language.value || "id",
                        prefer_ai: els.preferAI ? !!els.preferAI.checked : true,
                        async: !!asyncMode,
                    };

                    try {
                        setStatus(asyncMode ? "Mengantrikan ringkasan…" : "Membuat ringkasan…");
                        const res = await fetch("/summaries/generate", {
                            method: "POST",
                            credentials: "same-origin",
                            headers: {
                                Accept: "application/json",
                                "Content-Type": "application/json",
                                "X-Requested-With": "XMLHttpRequest",
                                "X-CSRF-TOKEN": csrf,
                            },
                            body: JSON.stringify(body),
                        });

                        const json = await res.json();

                        if (res.status === 202 && json?.data?.summary_id) {
                            const summaryId = json.data.summary_id;
                            setStatus("Diproses di background…");

                            pollTimer = setInterval(async () => {
                                try {
                                    const st = await fetchStatus(summaryId);
                                    if (st.status === "completed") {
                                        stopPolling();
                                        const s = await fetchSummary(summaryId);
                                        renderSummary(s);
                                        setStatus("Selesai.");
                                        setLoading(false);
                                        loadQuota();
                                    } else if (st.status === "failed") {
                                        stopPolling();
                                        setError(st.error_message || "Gagal membuat ringkasan.");
                                        setStatus("Gagal.");
                                        setLoading(false);
                                        loadQuota();
                                    } else {
                                        setStatus(`Diproses… (${st.status})`);
                                    }
                                } catch (e) {
                                    stopPolling();
                                    setError("Gagal mengecek status ringkasan.");
                                    setLoading(false);
                                }
                            }, 2000);

                            return;
                        }

                        if (!json?.success) {
                            setError(json?.error || "Gagal membuat ringkasan.");
                            setStatus("Gagal.");
                            setLoading(false);
                            loadQuota();
                            return;
                        }

                        renderSummary(json.data);
                        setStatus("Selesai.");
                        setLoading(false);
                        loadQuota();
                    } catch (e) {
                        setError("Gagal membuat ringkasan.");
                        setStatus("Gagal.");
                        setLoading(false);
                    }
                }

                async function regenerate() {
                    stopPolling();
                    setError("");
                    setLoading(true);
                    els.result.classList.add("hidden");

                    const body = {
                        max_words: Number(els.words.value || 150),
                        language: els.language.value || "id",
                        prefer_ai: els.preferAI ? !!els.preferAI.checked : true,
                    };

                    try {
                        setStatus("Regenerate ringkasan…");
                        const res = await fetch(`/summaries/${articleId}/regenerate`, {
                            method: "POST",
                            credentials: "same-origin",
                            headers: {
                                Accept: "application/json",
                                "Content-Type": "application/json",
                                "X-Requested-With": "XMLHttpRequest",
                                "X-CSRF-TOKEN": csrf,
                            },
                            body: JSON.stringify(body),
                        });

                        const json = await res.json();
                        if (!json?.success) {
                            setError(json?.error || "Gagal regenerate ringkasan.");
                            setStatus("Gagal.");
                            setLoading(false);
                            loadQuota();
                            return;
                        }

                        renderSummary(json.data);
                        setStatus("Selesai.");
                        setLoading(false);
                        loadQuota();
                    } catch (e) {
                        setError("Gagal regenerate ringkasan.");
                        setStatus("Gagal.");
                        setLoading(false);
                    }
                }

                els.generate.addEventListener("click", () => generate(els.async ? !!els.async.checked : false));
                els.regenerate.addEventListener("click", () => regenerate());

                const genBtn = document.getElementById("generate-citations-btn");
                if (genBtn) {
                    genBtn.addEventListener("click", async () => {
                        genBtn.disabled = true;
                        const originalHTML = genBtn.querySelector("span").innerHTML;
                        genBtn.querySelector("span").textContent = "Memproses…";
                        try {
                            const res = await fetch(`/articles/{{ $article->id }}/generate-citations`, {
                                method: "POST",
                                credentials: "same-origin",
                                headers: {
                                    Accept: "application/json",
                                    "X-Requested-With": "XMLHttpRequest",
                                    "X-CSRF-TOKEN": csrf,
                                },
                            });
                            const json = await res.json();
                            if (json.success) {
                                window.location.reload();
                            } else {
                                alert(json.error || "Gagal membuat kutipan. Coba lagi nanti.");
                                genBtn.disabled = false;
                                genBtn.querySelector("span").innerHTML = originalHTML;
                            }
                        } catch (e) {
                            alert("Gagal menghubungi server. Pastikan koneksi internet aktif.");
                            genBtn.disabled = false;
                            genBtn.querySelector("span").innerHTML = originalHTML;
                        }
                    });
                }

                loadQuota();
                loadExistingLatest();
            })();
        </script>
    @endpush
</x-app-layout>
