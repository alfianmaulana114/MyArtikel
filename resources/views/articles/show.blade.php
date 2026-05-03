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
                @if ($article->source_domain)
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
                    <div class="mt-5 theme-text-primary leading-relaxed">
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

                <div class="lg:col-span-4 space-y-6 lg:sticky lg:top-24 self-start">
                    <div class="card sm:rounded-lg overflow-hidden">
                        <div class="p-6">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <div class="font-semibold theme-text-primary">Ringkasan</div>
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
                                    <div class="text-xs theme-text-muted">Summary</div>
                                    <div id="summary-content" class="mt-2 text-sm theme-text-secondary leading-relaxed whitespace-pre-wrap"></div>
                                </div>
                                <div class="rounded-xl border theme-border-primary bg-[color:var(--surface-primary)] p-4">
                                    <div class="text-xs theme-text-muted">Key points</div>
                                    <div id="summary-points" class="mt-2 flex flex-wrap gap-2"></div>
                                </div>
                            </div>
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
    @endpush
</x-app-layout>
