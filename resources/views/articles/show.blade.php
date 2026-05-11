<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-1">
            <div class="flex items-center justify-between gap-3">
                <h2 class="font-semibold text-xl theme-text-primary leading-tight truncate">
                    {{ $article->title }}
                </h2>
                <a href="{{ route('dashboard') }}" class="btn btn-secondary">
                    Kembali
                </a>
            </div>
            <div class="flex flex-wrap items-center gap-2 text-sm theme-text-muted">
                @if ($article->source_type === 'pdf')
                    <span class="flex items-center gap-1">
                        <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                        PDF Upload
                    </span>
                @elseif ($article->source_domain)
                    <span>{{ $article->source_domain }}</span>
                @endif
                @if ($article->fetched_at)
                    <span>·</span>
                    <span>{{ $article->fetched_at->format('d M Y, H:i') }}</span>
                @endif
                @if ($article->processing_status)
                    <span>·</span>
                    <span>{{ $article->processing_status }}</span>
                @endif
                @if ($article->research_title)
                    <span>·</span>
                    <span class="text-[#AA5F3C]" title="{{ $article->research_title }}">Riset: {{ \Illuminate\Support\Str::limit($article->research_title, 40) }}</span>
                @endif
            </div>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
                <div class="lg:col-span-8 space-y-6">
            @if ($article->processing_status !== 'ready')
                <div class="card sm:rounded-lg">
                    <div class="p-6">
                        <div class="font-semibold theme-text-primary">Artikel masih diproses</div>
                        <div class="mt-2 text-sm theme-text-secondary">
                            Status: {{ $article->processing_status ?? 'queued' }}
                        </div>
                        @if ($article->processing_status === 'failed' && $article->processing_error)
                            <div class="mt-3 text-sm bg-[color:var(--error-bg)] text-[color:var(--error)] border border-[color:var(--error-border)] rounded-lg px-3 py-2">
                                {{ $article->processing_error }}
                            </div>
                        @endif
                    </div>
                </div>
            @endif

            <div class="card sm:rounded-lg overflow-hidden">
                <div class="p-6">
                    @if ($article->excerpt)
                        <div class="text-sm theme-text-secondary">
                            {{ $article->excerpt }}
                        </div>
                        <div class="mt-5 border-t theme-border-primary"></div>
                    @endif

                    @php
                        $raw = $article->text_extracted ?: ($article->content_sanitized ?: $article->content);
                        $text = trim(preg_replace('/\s+/', ' ', strip_tags((string) $raw)));
                    @endphp
                    <div class="mt-5 theme-text-primary leading-relaxed whitespace-pre-wrap">
                        {{ $text }}
                    </div>
                </div>
            </div>

            @if (!empty($article->tags))
                <div class="card sm:rounded-lg">
                    <div class="p-6">
                        <div class="font-semibold theme-text-primary">Tags</div>
                        <div class="mt-3 flex flex-wrap gap-2">
                            @foreach ($article->tags as $tag)
                                <span class="text-xs px-2 py-1 rounded-full bg-[color:var(--bg-tertiary)] theme-text-secondary">
                                    {{ $tag->name }}
                                </span>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif
                </div>

                {{-- Sidebar: Tab Rangkuman & Saran Kutipan --}}
                <div class="lg:col-span-4 space-y-6 lg:sticky lg:top-24 self-start" x-data="{
                    activeTab: 'summary',
                    init() {
                        @if (!empty($article->ai_quotation_suggestions))
                            this.activeTab = 'citations';
                        @endif
                    }
                }">
                    {{-- Tab Navigation --}}
                    <div class="card sm:rounded-lg overflow-hidden">
                        <div class="flex border-b theme-border-primary">
                            <button
                                @click="activeTab = 'summary'"
                                :class="activeTab === 'summary'
                                    ? 'theme-text-primary border-b-2 border-[#AA5F3C]'
                                    : 'theme-text-muted border-b-2 border-transparent hover:theme-text-secondary'"
                                class="flex-1 px-4 py-3 text-sm font-medium transition-colors text-center"
                            >
                                <span class="flex items-center justify-center gap-1.5">
                                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                    Rangkuman
                                </span>
                            </button>
                            <button
                                @click="activeTab = 'citations'"
                                :class="activeTab === 'citations'
                                    ? 'theme-text-primary border-b-2 border-[#AA5F3C]'
                                    : 'theme-text-muted border-b-2 border-transparent hover:theme-text-secondary'"
                                class="flex-1 px-4 py-3 text-sm font-medium transition-colors text-center"
                            >
                                <span class="flex items-center justify-center gap-1.5">
                                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                                    Saran Kutipan
                                </span>
                                @if (!empty($article->ai_quotation_suggestions))
                                    <span class="ml-1 text-xs px-1.5 py-0.5 rounded-full bg-[#AA5F3C] text-white">
                                        {{ count($article->ai_quotation_suggestions) }}
                                    </span>
                                @endif
                            </button>
                        </div>

                        {{-- Summary Tab Content --}}
                        <div x-show="activeTab === 'summary'" class="p-6">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <div class="font-semibold theme-text-primary">Ringkasan AI</div>
                                    <div class="mt-1 text-sm theme-text-muted">
                                        Generate ringkasan on-demand.
                                    </div>
                                </div>
                                <a class="btn btn-secondary" href="{{ route('summaries.page', ['article_id' => $article->id]) }}">
                                    Semua
                                </a>
                            </div>

                            <div class="mt-4 grid grid-cols-1 sm:grid-cols-3 gap-3">
                                <div class="sm:col-span-1">
                                    <label for="summary-language" class="block text-sm font-medium theme-text-secondary">Bahasa</label>
                                    <select id="summary-language" class="mt-1 block w-full rounded-lg shadow-sm border theme-border-primary bg-[color:var(--surface-primary)] text-[color:var(--text-primary)] focus:border-[#AA5F3C] focus:ring-[#AA5F3C]">
                                        <option value="id">ID</option>
                                        <option value="en">EN</option>
                                    </select>
                                </div>
                                <div class="sm:col-span-2">
                                    <label for="summary-words" class="block text-sm font-medium theme-text-secondary">Panjang</label>
                                    <select id="summary-words" class="mt-1 block w-full rounded-lg shadow-sm border theme-border-primary bg-[color:var(--surface-primary)] text-[color:var(--text-primary)] focus:border-[#AA5F3C] focus:ring-[#AA5F3C]">
                                        <option value="150">~150 kata</option>
                                        <option value="250">~250 kata</option>
                                        <option value="350">~350 kata</option>
                                    </select>
                                </div>
                            </div>

                            <div class="mt-3 flex items-center justify-between gap-3">
                                <label class="inline-flex items-center gap-2 text-sm theme-text-secondary">
                                    <input id="summary-prefer-ai" type="checkbox" class="rounded theme-border-primary text-[#AA5F3C] shadow-sm focus:ring-[#AA5F3C]" checked>
                                    Prefer AI
                                </label>
                                <label class="inline-flex items-center gap-2 text-sm theme-text-secondary">
                                    <input id="summary-async" type="checkbox" class="rounded theme-border-primary text-[#AA5F3C] shadow-sm focus:ring-[#AA5F3C]">
                                    Background
                                </label>
                            </div>
                            <div class="mt-2 text-xs theme-text-muted" id="summary-quota"></div>

                            <div class="mt-4 flex flex-wrap gap-2">
                                <button id="summary-generate" type="button" class="btn btn-primary">
                                    Buat ringkasan
                                </button>
                                <button id="summary-regenerate" type="button" class="btn btn-secondary">
                                    Regenerate
                                </button>
                            </div>

                            <div class="mt-4">
                                <div id="summary-status" class="text-sm theme-text-muted"></div>
                                <div id="summary-error" class="hidden mt-3 text-sm bg-[color:var(--error-bg)] text-[color:var(--error)] border border-[color:var(--error-border)] rounded-lg px-3 py-2"></div>
                            </div>

                            <div id="summary-result" class="hidden mt-4 space-y-4">
                                <div class="rounded-xl border theme-border-primary bg-[color:var(--surface-primary)] p-4">
                                    <div class="text-xs theme-text-muted">Ringkasan</div>
                                    <div id="summary-content" class="mt-2 text-sm theme-text-secondary leading-relaxed whitespace-pre-wrap"></div>
                                </div>
                                <div class="rounded-xl border theme-border-primary bg-[color:var(--surface-primary)] p-4">
                                    <div class="text-xs theme-text-muted">Poin Kunci</div>
                                    <div id="summary-points" class="mt-2 flex flex-wrap gap-2"></div>
                                </div>
                            </div>
                        </div>

                        {{-- Citations Tab Content --}}
                        <div x-show="activeTab === 'citations'" class="p-6">
                            <div class="font-semibold theme-text-primary">Saran Kutipan</div>
                            <div class="mt-1 text-sm theme-text-muted">
                                Kutipan yang direkomendasikan AI untuk riset Anda.
                            </div>

                            @if (!empty($article->ai_quotation_suggestions))
                                <div class="mt-4 space-y-4">
                                    @foreach ($article->ai_quotation_suggestions as $index => $citation)
                                        <div class="rounded-xl border theme-border-primary bg-[color:var(--surface-primary)] p-4">
                                            <div class="flex items-center gap-2 mb-2">
                                                <span class="text-xs px-2 py-0.5 rounded-full bg-[color:var(--bg-tertiary)] text-[#AA5F3C] font-medium">
                                                    #{{ $index + 1 }}
                                                </span>
                                                @if (!empty($citation['position']))
                                                    <span class="text-xs px-2 py-0.5 rounded-full bg-[color:var(--bg-tertiary)] theme-text-muted">
                                                        {{ $citation['position'] }}
                                                    </span>
                                                @endif
                                            </div>
                                            <blockquote class="border-l-3 border-[#AA5F3C] pl-3 italic text-sm theme-text-secondary">
                                                "{{ $citation['quote'] ?? '' }}"
                                            </blockquote>
                                            @if (!empty($citation['relevance']))
                                                <div class="mt-2 text-xs theme-text-muted leading-relaxed">
                                                    {{ $citation['relevance'] }}
                                                </div>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            @elseif ($article->research_title)
                                <div class="mt-4 rounded-xl border theme-border-primary bg-[color:var(--surface-primary)] p-4 text-center">
                                    <svg class="mx-auto w-8 h-8 theme-text-muted mb-2" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    <div class="text-sm theme-text-muted">
                                        Saran kutipan sedang dibuat oleh AI…
                                    </div>
                                    <div class="mt-1 text-xs theme-text-muted">
                                        Judul riset: "{{ $article->research_title }}"
                                    </div>
                                </div>
                            @else
                                <div class="mt-4 rounded-xl border theme-border-primary bg-[color:var(--surface-primary)] p-4 text-center">
                                    <svg class="mx-auto w-8 h-8 theme-text-muted mb-2" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg>
                                    <div class="text-sm theme-text-muted">
                                        Tidak ada Judul Penelitian.
                                    </div>
                                    <div class="mt-1 text-xs theme-text-muted">
                                        Isi judul penelitian saat submit artikel untuk mendapat saran kutipan AI.
                                    </div>
                                </div>
                            @endif

                            @if ($article->processing_status === 'ready' && !empty($article->text_extracted) && $article->research_title && empty($article->ai_quotation_suggestions))
                                <div class="mt-4">
                                    <button id="generate-citations-btn" type="button" class="btn btn-primary w-full text-sm" onclick="generateCitations()">
                                        <span class="flex items-center justify-center gap-1.5">
                                            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                                            Generate Saran Kutipan
                                        </span>
                                    </button>
                                </div>
                            @endif
                        </div>
                    </div>
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
                                      `<span class="text-xs px-2 py-1 rounded-full bg-[color:var(--bg-tertiary)] theme-text-secondary">${escapeHtml(
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
                        prefer_ai: !!els.preferAI.checked,
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
                        prefer_ai: !!els.preferAI.checked,
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

                els.generate.addEventListener("click", () => generate(!!els.async.checked));
                els.regenerate.addEventListener("click", () => regenerate());

                loadQuota();
                loadExistingLatest();
            })();
        </script>

        <script>
            window.generateCitations = async function() {
                const btn = document.getElementById('generate-citations-btn');
                if (!btn) return;
                btn.disabled = true;
                const origHtml = btn.innerHTML;
                btn.innerHTML = '<span class="flex items-center justify-center gap-1.5"><svg class="animate-spin w-4 h-4" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" class="opacity-25"/><path d="M4 12a8 8 0 018-8" stroke="currentColor" stroke-width="3" class="opacity-75"/></svg> Memproses…</span>';

                try {
                    const res = await fetch(`/articles/{{ $article->id }}/generate-citations`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                    });

                    const json = await res.json();
                    if (json.success) {
                        window.location.reload();
                    } else {
                        alert(json.error || 'Gagal menghasilkan saran kutipan.');
                        btn.disabled = false;
                        btn.innerHTML = origHtml;
                    }
                } catch (e) {
                    alert('Gagal menghasilkan saran kutipan.');
                    btn.disabled = false;
                    btn.innerHTML = origHtml;
                }
            };
        </script>
    @endpush
</x-app-layout>
