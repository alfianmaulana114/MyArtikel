<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-1">
            <h2 class="font-semibold text-xl theme-text-primary leading-tight">
                Articles
            </h2>
            <p class="text-sm theme-text-muted">
                Daftar semua artikel yang tersimpan.
            </p>
        </div>
    </x-slot>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        <div class="lg:col-span-8 space-y-6">
            <div class="card overflow-hidden sm:rounded-lg">
                <div class="p-6">
                    <div class="flex flex-col sm:flex-row gap-3 sm:items-end">
                        <div class="flex-1">
                            <label for="articles-search" class="block font-medium text-sm theme-text-secondary">Cari</label>
                            <input
                                id="articles-search"
                                type="text"
                                placeholder="Cari judul / excerpt…"
                                class="mt-1 block w-full rounded-lg shadow-sm border theme-border-primary bg-[color:var(--surface-primary)] text-[color:var(--text-primary)] placeholder:text-[color:var(--text-muted)] focus:border-[#AA5F3C] focus:ring-[#AA5F3C]"
                            />
                        </div>

                        <div class="sm:w-48">
                            <label for="articles-status" class="block font-medium text-sm theme-text-secondary">Status</label>
                            <select
                                id="articles-status"
                                class="mt-1 block w-full rounded-lg shadow-sm border theme-border-primary bg-[color:var(--surface-primary)] text-[color:var(--text-primary)] focus:border-[#AA5F3C] focus:ring-[#AA5F3C]"
                            >
                                <option value="">Semua</option>
                                <option value="ready">Ready</option>
                                <option value="processing">Processing</option>
                                <option value="failed">Failed</option>
                            </select>
                        </div>

                        <div class="sm:w-56">
                            <label for="articles-sort" class="block font-medium text-sm theme-text-secondary">Urutkan</label>
                            <select
                                id="articles-sort"
                                class="mt-1 block w-full rounded-lg shadow-sm border theme-border-primary bg-[color:var(--surface-primary)] text-[color:var(--text-primary)] focus:border-[#AA5F3C] focus:ring-[#AA5F3C]"
                            >
                                <option value="created_at:desc">Terbaru</option>
                                <option value="created_at:asc">Terlama</option>
                                <option value="title:asc">Judul (A-Z)</option>
                                <option value="title:desc">Judul (Z-A)</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card overflow-hidden sm:rounded-lg">
                <div class="p-6">
                    <div id="articles-loading" class="hidden text-sm theme-text-muted">
                        Memuat…
                    </div>

                    <div id="articles-empty" class="hidden py-10 text-center text-sm theme-text-muted">
                        Belum ada artikel.
                    </div>

                    <div id="articles-error" class="hidden text-sm bg-[color:var(--error-bg)] text-[color:var(--error)] border border-[color:var(--error-border)] rounded-lg px-3 py-2"></div>

                    <div id="articles-list" class="mt-4 divide-y divide-black/5"></div>

                    <div id="articles-pagination" class="mt-6 flex flex-wrap gap-2"></div>
                </div>
            </div>
        </div>

        <div class="lg:col-span-4 space-y-6 lg:sticky lg:top-24 self-start">
            <div class="card overflow-hidden sm:rounded-lg">
                <div class="p-6">
                    <div class="font-semibold theme-text-primary">Tips</div>
                    <ul class="mt-3 text-sm theme-text-secondary space-y-2">
                        <li>Pakai filter status untuk cek artikel gagal diproses.</li>
                        <li>Buka detail artikel untuk clean reader.</li>
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
                    search: "",
                    status: "",
                    sort_by: "created_at",
                    sort_order: "desc",
                };

                const els = {
                    search: document.getElementById("articles-search"),
                    status: document.getElementById("articles-status"),
                    sort: document.getElementById("articles-sort"),
                    loading: document.getElementById("articles-loading"),
                    empty: document.getElementById("articles-empty"),
                    error: document.getElementById("articles-error"),
                    list: document.getElementById("articles-list"),
                    pagination: document.getElementById("articles-pagination"),
                };

                function debounce(fn, delay = 250) {
                    let t;
                    return (...args) => {
                        clearTimeout(t);
                        t = setTimeout(() => fn(...args), delay);
                    };
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

                function renderArticles(items) {
                    els.list.innerHTML = items
                        .map((a) => {
                            const title = a.title || "(untitled)";
                            const excerpt = a.excerpt || "";
                            const domain = a.source_domain || "";
                            const status = a.processing_status || a.status || "ready";
                            const tags = Array.isArray(a.tags) ? a.tags : [];
                            const createdAt = a.created_at
                                ? new Date(a.created_at).toLocaleString()
                                : "";

                            return `
                                <div class="py-5 flex flex-col gap-3">
                                    <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-2">
                                        <div class="min-w-0">
                                            <div class="flex items-center gap-2">
                                                <div class="text-xs px-2 py-1 rounded-full border theme-border-primary bg-[color:var(--surface-secondary)] text-[color:var(--text-muted)]">
                                                    ${status}
                                                </div>
                                                ${domain ? `<div class="text-xs theme-text-muted truncate">${domain}</div>` : ""}
                                            </div>
                                            <div class="mt-2">
                                                <div class="font-semibold theme-text-primary truncate">
                                                    <a class="hover:text-[color:var(--text-link-hover)]" href="/articles/${a.id}">
                                                        ${escapeHtml(title)}
                                                    </a>
                                                </div>
                                                ${
                                                    excerpt
                                                        ? `<div class="mt-1 text-sm theme-text-secondary overflow-hidden">${escapeHtml(excerpt)}</div>`
                                                        : ""
                                                }
                                            </div>
                                        </div>
                                        <div class="text-sm theme-text-muted whitespace-nowrap">${createdAt}</div>
                                    </div>

                                    ${
                                        tags.length
                                            ? `<div class="flex flex-wrap items-center gap-2 text-sm">
                                                    ${tags
                                                        .map(
                                                            (t) =>
                                                                `<span class="text-xs px-2 py-1 rounded-full bg-[color:var(--bg-tertiary)] theme-text-secondary">${escapeHtml(
                                                                    t.name ?? t,
                                                                )}</span>`,
                                                        )
                                                        .join("")}
                                               </div>`
                                            : ""
                                    }

                                    <div class="flex items-center justify-end">
                                        <a class="btn btn-secondary" href="/articles/${a.id}">Baca</a>
                                    </div>
                                </div>
                            `;
                        })
                        .join("");
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
                        <button
                            type="button"
                            data-page="${page}"
                            class="btn btn-secondary ${active ? "opacity-100" : "opacity-90"}"
                        >${label}</button>
                    `;

                    if (current > 1) parts.push(mkBtn("Prev", current - 1));
                    for (let i = 1; i <= last; i++) {
                        const near = i === 1 || i === last || (i >= current - 2 && i <= current + 2);
                        if (!near) continue;
                        parts.push(mkBtn(String(i), i, i === current));
                    }
                    if (current < last) parts.push(mkBtn("Next", current + 1));

                    els.pagination.innerHTML = parts.join("");
                    els.pagination.querySelectorAll("[data-page]").forEach((b) => {
                        b.addEventListener("click", () => {
                            state.page = Number(b.getAttribute("data-page")) || 1;
                            load();
                        });
                    });
                }

                function escapeHtml(s) {
                    return String(s)
                        .replaceAll("&", "&amp;")
                        .replaceAll("<", "&lt;")
                        .replaceAll(">", "&gt;")
                        .replaceAll('"', "&quot;")
                        .replaceAll("'", "&#039;");
                }

                async function load() {
                    setError("");
                    setEmpty(false);
                    setLoading(true);

                    const params = new URLSearchParams();
                    params.set("page", String(state.page));
                    if (state.search) params.set("search", state.search);
                    if (state.status) params.set("status", state.status);
                    if (state.sort_by) params.set("sort_by", state.sort_by);
                    if (state.sort_order) params.set("sort_order", state.sort_order);

                    try {
                        const res = await fetch(`/articles/data?${params}`, {
                            credentials: "same-origin",
                            headers: { Accept: "application/json", "X-Requested-With": "XMLHttpRequest" },
                        });
                        const data = await res.json();

                        const paginator = data.articles;
                        const items = paginator?.data ?? [];

                        setEmpty(items.length === 0);
                        renderArticles(items);
                        renderPagination({
                            current_page: paginator?.current_page,
                            last_page: paginator?.last_page,
                        });
                    } catch (e) {
                        setError("Gagal memuat artikel.");
                    } finally {
                        setLoading(false);
                    }
                }

                els.search.addEventListener(
                    "input",
                    debounce((e) => {
                        state.search = e.target.value.trim();
                        state.page = 1;
                        load();
                    }),
                );

                els.status.addEventListener("change", (e) => {
                    state.status = e.target.value;
                    state.page = 1;
                    load();
                });

                els.sort.addEventListener("change", (e) => {
                    const [sortBy, sortOrder] = String(e.target.value).split(":");
                    state.sort_by = sortBy || "created_at";
                    state.sort_order = sortOrder || "desc";
                    state.page = 1;
                    load();
                });

                load();
            })();
        </script>
    @endpush
</x-app-layout>
