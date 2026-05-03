<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-1">
            <h2 class="font-semibold text-xl theme-text-primary leading-tight">
                Summaries
            </h2>
            <p class="text-sm theme-text-muted">
                Ringkasan artikel yang pernah kamu generate.
            </p>
        </div>
    </x-slot>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        <div class="lg:col-span-8 space-y-6">
            <div class="card overflow-hidden sm:rounded-lg">
                <div class="p-6">
                    <div class="flex flex-col sm:flex-row gap-3 sm:items-end">
                        <div class="flex-1">
                            <label for="summaries-article" class="block font-medium text-sm theme-text-secondary">Filter Article ID</label>
                            <input
                                id="summaries-article"
                                type="number"
                                min="1"
                                placeholder="contoh: 12"
                                class="mt-1 block w-full rounded-lg shadow-sm border theme-border-primary bg-[color:var(--surface-primary)] text-[color:var(--text-primary)] placeholder:text-[color:var(--text-muted)] focus:border-[#AA5F3C] focus:ring-[#AA5F3C]"
                            />
                        </div>
                        <div class="sm:w-auto">
                            <button id="summaries-refresh" type="button" class="btn btn-secondary w-full sm:w-auto">
                                Refresh
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card overflow-hidden sm:rounded-lg">
                <div class="p-6">
                    <div id="summaries-loading" class="hidden text-sm theme-text-muted">
                        Memuat…
                    </div>

                    <div id="summaries-empty" class="hidden py-10 text-center text-sm theme-text-muted">
                        Belum ada ringkasan.
                    </div>

                    <div id="summaries-error" class="hidden text-sm bg-[color:var(--error-bg)] text-[color:var(--error)] border border-[color:var(--error-border)] rounded-lg px-3 py-2"></div>

                    <div id="summaries-list" class="mt-4 divide-y divide-black/5"></div>

                    <div id="summaries-pagination" class="mt-6 flex flex-wrap gap-2"></div>
                </div>
            </div>
        </div>

        <div class="lg:col-span-4 space-y-6 lg:sticky lg:top-24 self-start">
            <div class="card overflow-hidden sm:rounded-lg">
                <div class="p-6">
                    <div class="font-semibold theme-text-primary">Quota</div>
                    <div id="quota-loading" class="mt-2 text-sm theme-text-muted">Memuat…</div>
                    <div id="quota-content" class="hidden mt-3 space-y-3 text-sm">
                        <div class="flex items-center justify-between">
                            <div class="theme-text-secondary">Gemini</div>
                            <div class="theme-text-primary" id="quota-gemini"></div>
                        </div>
                        <div class="flex items-center justify-between">
                            <div class="theme-text-secondary">Local</div>
                            <div class="theme-text-primary" id="quota-local"></div>
                        </div>
                        <div class="text-xs theme-text-muted" id="quota-note"></div>
                    </div>
                </div>
            </div>

            <div class="card overflow-hidden sm:rounded-lg">
                <div class="p-6">
                    <div class="font-semibold theme-text-primary">Cara pakai</div>
                    <ul class="mt-3 text-sm theme-text-secondary space-y-2">
                        <li>Buka detail artikel → klik “Buat ringkasan”.</li>
                        <li>Ringkasan tersimpan dan muncul di halaman ini.</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            (() => {
                const state = {
                    page: 1,
                    article_id: "",
                };

                const els = {
                    article: document.getElementById("summaries-article"),
                    refresh: document.getElementById("summaries-refresh"),
                    loading: document.getElementById("summaries-loading"),
                    empty: document.getElementById("summaries-empty"),
                    error: document.getElementById("summaries-error"),
                    list: document.getElementById("summaries-list"),
                    pagination: document.getElementById("summaries-pagination"),
                    quotaLoading: document.getElementById("quota-loading"),
                    quotaContent: document.getElementById("quota-content"),
                    quotaGemini: document.getElementById("quota-gemini"),
                    quotaLocal: document.getElementById("quota-local"),
                    quotaNote: document.getElementById("quota-note"),
                };

                const initialArticleId = new URLSearchParams(window.location.search).get("article_id");
                if (initialArticleId) {
                    state.article_id = String(initialArticleId).trim();
                    els.article.value = state.article_id;
                }

                function setLoading(isLoading) {
                    els.loading.classList.toggle("hidden", !isLoading);
                }

                function setError(message) {
                    els.error.textContent = message || "";
                    els.error.classList.toggle("hidden", !message);
                }

                function setEmpty(isEmpty) {
                    els.empty.classList.toggle("hidden", !isEmpty);
                }

                function escapeHtml(s) {
                    return String(s)
                        .replaceAll("&", "&amp;")
                        .replaceAll("<", "&lt;")
                        .replaceAll(">", "&gt;")
                        .replaceAll('"', "&quot;")
                        .replaceAll("'", "&#039;");
                }

                function renderList(items) {
                    els.list.innerHTML = items
                        .map((s) => {
                            const title = s.article?.title || `Article #${s.article_id}`;
                            const content = s.content || "";
                            const createdAt = s.created_at ? new Date(s.created_at).toLocaleString() : "";
                            const source = s.source || "";
                            const wordCount = s.word_count ?? 0;
                            const points = Array.isArray(s.key_points) ? s.key_points : [];

                            return `
                                <div class="py-5 space-y-3">
                                    <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-2">
                                        <div class="min-w-0">
                                            <div class="font-semibold theme-text-primary truncate">
                                                <a class="hover:text-[color:var(--text-link-hover)]" href="/articles/${s.article_id}">
                                                    ${escapeHtml(title)}
                                                </a>
                                            </div>
                                            <div class="mt-1 text-xs theme-text-muted">
                                                ${createdAt}${source ? ` · ${escapeHtml(source)}` : ""}${wordCount ? ` · ${wordCount} words` : ""}
                                            </div>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <button type="button" class="btn btn-secondary" data-view="${s.id}">View</button>
                                            <button type="button" class="btn btn-secondary" data-delete="${s.id}">Delete</button>
                                        </div>
                                    </div>

                                    <div class="text-sm theme-text-secondary">
                                        ${escapeHtml(content).slice(0, 280)}${content.length > 280 ? "…" : ""}
                                    </div>

                                    ${
                                        points.length
                                            ? `<div class="flex flex-wrap gap-2">
                                                    ${points
                                                        .slice(0, 6)
                                                        .map(
                                                            (p) =>
                                                                `<span class="text-xs px-2 py-1 rounded-full bg-[color:var(--bg-tertiary)] theme-text-secondary">${escapeHtml(
                                                                    p,
                                                                )}</span>`,
                                                        )
                                                        .join("")}
                                               </div>`
                                            : ""
                                    }
                                </div>
                            `;
                        })
                        .join("");

                    els.list.querySelectorAll("[data-view]").forEach((b) => {
                        b.addEventListener("click", async () => {
                            const id = b.getAttribute("data-view");
                            await viewSummary(id);
                        });
                    });

                    els.list.querySelectorAll("[data-delete]").forEach((b) => {
                        b.addEventListener("click", async () => {
                            const id = b.getAttribute("data-delete");
                            await deleteSummary(id);
                        });
                    });
                }

                function renderPagination(p) {
                    if (!p || !p.last_page || p.last_page <= 1) {
                        els.pagination.innerHTML = "";
                        return;
                    }

                    const parts = [];
                    const current = p.current_page;
                    const last = p.last_page;

                    const mkBtn = (label, page, active = false) => `
                        <button type="button" data-page="${page}" class="btn btn-secondary ${active ? "opacity-100" : "opacity-90"}">${label}</button>
                    `;

                    if (current > 1) parts.push(mkBtn("Prev", current - 1));
                    for (let i = 1; i <= last; i++) {
                        const near = i === 1 || i === last || (i >= current - 2 && i <= current + 2);
                        if (!near) continue;
                        parts.push(mkBtn(String(i), i, i === current));
                    }
                    if (current < last) parts.push(mkBtn("Next", current + 1));

                    els.pagination.innerHTML = parts.join("");
                    els.pagination.querySelectorAll("[data-page]").forEach((btn) => {
                        btn.addEventListener("click", () => {
                            state.page = Number(btn.getAttribute("data-page")) || 1;
                            load();
                        });
                    });
                }

                async function loadQuota() {
                    try {
                        els.quotaLoading.classList.remove("hidden");
                        els.quotaContent.classList.add("hidden");

                        const res = await fetch("/summaries/quota", {
                            credentials: "same-origin",
                            headers: { Accept: "application/json", "X-Requested-With": "XMLHttpRequest" },
                        });
                        const json = await res.json();
                        const q = json?.data?.quota_status;

                        const gemini = q?.gemini;
                        const local = q?.local;

                        els.quotaGemini.textContent = gemini
                            ? `${gemini.used_today}/${gemini.limit} (${gemini.remaining} left)`
                            : "-";
                        els.quotaLocal.textContent = local
                            ? `${local.used_today}/${local.limit} (${local.remaining} left)`
                            : "-";
                        els.quotaNote.textContent = "Ringkasan AI mengikuti quota harian.";

                        els.quotaLoading.classList.add("hidden");
                        els.quotaContent.classList.remove("hidden");
                    } catch (e) {
                        els.quotaLoading.textContent = "Gagal memuat quota.";
                    }
                }

                async function load() {
                    setError("");
                    setEmpty(false);
                    setLoading(true);

                    const params = new URLSearchParams();
                    params.set("page", String(state.page));
                    if (state.article_id) params.set("article_id", state.article_id);

                    try {
                        const res = await fetch(`/summaries/data?${params}`, {
                            credentials: "same-origin",
                            headers: { Accept: "application/json", "X-Requested-With": "XMLHttpRequest" },
                        });
                        const json = await res.json();
                        if (!json?.success) throw new Error("failed");

                        const paginator = json.data;
                        const items = paginator?.data ?? [];

                        setEmpty(items.length === 0);
                        renderList(items);
                        renderPagination({
                            current_page: paginator?.current_page,
                            last_page: paginator?.last_page,
                        });
                    } catch (e) {
                        setError("Gagal memuat summaries.");
                    } finally {
                        setLoading(false);
                    }
                }

                async function viewSummary(id) {
                    try {
                        const res = await fetch(`/summaries/${id}`, {
                            credentials: "same-origin",
                            headers: { Accept: "application/json", "X-Requested-With": "XMLHttpRequest" },
                        });
                        const json = await res.json();
                        const s = json?.data;
                        if (!s) return;

                        const title = s.article?.title || `Article #${s.article_id}`;
                        const body = (s.content || "").trim();
                        alert(`${title}\n\n${body}`);
                    } catch (e) {
                        alert("Gagal membuka summary.");
                    }
                }

                async function deleteSummary(id) {
                    if (!confirm("Hapus summary ini?")) return;
                    try {
                        const res = await fetch(`/summaries/${id}`, {
                            method: "DELETE",
                            credentials: "same-origin",
                            headers: {
                                Accept: "application/json",
                                "X-Requested-With": "XMLHttpRequest",
                                "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content,
                            },
                        });
                        const json = await res.json();
                        if (!json?.success) throw new Error("failed");
                        load();
                    } catch (e) {
                        alert("Gagal menghapus summary.");
                    }
                }

                els.refresh.addEventListener("click", () => load());
                els.article.addEventListener("change", (e) => {
                    state.article_id = String(e.target.value || "").trim();
                    state.page = 1;
                    load();
                });

                loadQuota();
                load();
            })();
        </script>
    @endpush
</x-app-layout>

