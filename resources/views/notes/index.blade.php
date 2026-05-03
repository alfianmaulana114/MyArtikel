<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-1">
            <h2 class="font-semibold text-xl theme-text-primary leading-tight">
                Notes
            </h2>
            <p class="text-sm theme-text-muted">
                Catatan cepat untuk artikel yang kamu simpan.
            </p>
        </div>
    </x-slot>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        <div class="lg:col-span-8 space-y-6">
            <div class="card overflow-hidden sm:rounded-lg">
                <div class="p-6">
                    <div class="flex flex-col sm:flex-row gap-3 sm:items-end">
                        <div class="flex-1">
                            <label for="notes-search" class="block font-medium text-sm theme-text-secondary">Cari</label>
                            <input
                                id="notes-search"
                                type="text"
                                placeholder="Cari judul / isi note…"
                                class="mt-1 block w-full rounded-lg shadow-sm border theme-border-primary bg-[color:var(--surface-primary)] text-[color:var(--text-primary)] placeholder:text-[color:var(--text-muted)] focus:border-[#AA5F3C] focus:ring-[#AA5F3C]"
                            />
                        </div>

                        <div class="sm:w-56">
                            <label for="notes-category" class="block font-medium text-sm theme-text-secondary">Kategori</label>
                            <select
                                id="notes-category"
                                class="mt-1 block w-full rounded-lg shadow-sm border theme-border-primary bg-[color:var(--surface-primary)] text-[color:var(--text-primary)] focus:border-[#AA5F3C] focus:ring-[#AA5F3C]"
                            >
                                <option value="">Semua</option>
                            </select>
                        </div>

                        <div class="sm:w-56">
                            <label for="notes-type" class="block font-medium text-sm theme-text-secondary">Tipe</label>
                            <select
                                id="notes-type"
                                class="mt-1 block w-full rounded-lg shadow-sm border theme-border-primary bg-[color:var(--surface-primary)] text-[color:var(--text-primary)] focus:border-[#AA5F3C] focus:ring-[#AA5F3C]"
                            >
                                <option value="">Semua</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card overflow-hidden sm:rounded-lg">
                <div class="p-6">
                    <div id="notes-loading" class="hidden text-sm theme-text-muted">
                        Memuat…
                    </div>

                    <div id="notes-empty" class="hidden py-10 text-center text-sm theme-text-muted">
                        Belum ada notes.
                    </div>

                    <div id="notes-error" class="hidden text-sm bg-[color:var(--error-bg)] text-[color:var(--error)] border border-[color:var(--error-border)] rounded-lg px-3 py-2"></div>

                    <div id="notes-list" class="mt-4 divide-y divide-black/5"></div>

                    <div id="notes-pagination" class="mt-6 flex flex-wrap gap-2"></div>
                </div>
            </div>
        </div>

        <div class="lg:col-span-4 space-y-6 lg:sticky lg:top-24 self-start">
            <div class="card overflow-hidden sm:rounded-lg">
                <div class="p-6">
                    <div class="font-semibold theme-text-primary">Tips</div>
                    <ul class="mt-3 text-sm theme-text-secondary space-y-2">
                        <li>Notes bisa dihubungkan ke artikel (coming soon di UI).</li>
                        <li>Sinkronisasi note sudah ada endpoint /notes/sync.</li>
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
                    category: "",
                    type: "",
                };

                const els = {
                    search: document.getElementById("notes-search"),
                    category: document.getElementById("notes-category"),
                    type: document.getElementById("notes-type"),
                    loading: document.getElementById("notes-loading"),
                    empty: document.getElementById("notes-empty"),
                    error: document.getElementById("notes-error"),
                    list: document.getElementById("notes-list"),
                    pagination: document.getElementById("notes-pagination"),
                };

                let filtersLoaded = false;

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

                function escapeHtml(s) {
                    return String(s)
                        .replaceAll("&", "&amp;")
                        .replaceAll("<", "&lt;")
                        .replaceAll(">", "&gt;")
                        .replaceAll('"', "&quot;")
                        .replaceAll("'", "&#039;");
                }

                function renderNotes(items) {
                    els.list.innerHTML = items
                        .map((n) => {
                            const title = n.title || "Untitled";
                            const excerpt = n.content || n.body || "";
                            const articleTitle = n.article?.title || "";
                            const createdAt = n.created_at
                                ? new Date(n.created_at).toLocaleString()
                                : "";
                            const category = n.category || "";
                            const type = n.type || "";

                            return `
                                <div class="py-5 flex flex-col gap-2">
                                    <div class="flex items-start justify-between gap-3">
                                        <div class="min-w-0">
                                            <div class="font-semibold theme-text-primary truncate">${escapeHtml(title)}</div>
                                            ${
                                                articleTitle
                                                    ? `<div class="mt-1 text-xs theme-text-muted truncate">Artikel: ${escapeHtml(
                                                            articleTitle,
                                                      )}</div>`
                                                    : ""
                                            }
                                        </div>
                                        <div class="text-sm theme-text-muted whitespace-nowrap">${createdAt}</div>
                                    </div>

                                    ${
                                        excerpt
                                            ? `<div class="text-sm theme-text-secondary overflow-hidden">${escapeHtml(
                                                    String(excerpt).slice(0, 240),
                                              )}</div>`
                                            : ""
                                    }

                                    <div class="flex flex-wrap items-center gap-2 text-xs">
                                        ${
                                            category
                                                ? `<span class="px-2 py-1 rounded-full border theme-border-primary bg-[color:var(--surface-secondary)] text-[color:var(--text-muted)]">${escapeHtml(
                                                        category,
                                                  )}</span>`
                                                : ""
                                        }
                                        ${
                                            type
                                                ? `<span class="px-2 py-1 rounded-full border theme-border-primary bg-[color:var(--surface-secondary)] text-[color:var(--text-muted)]">${escapeHtml(
                                                        type,
                                                  )}</span>`
                                                : ""
                                        }
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

                function maybePopulateFilters(filters) {
                    if (filtersLoaded || !filters) return;

                    const categories = filters.categories || [];
                    const types = filters.types || [];

                    if (Array.isArray(categories)) {
                        categories.forEach((c) => {
                            const o = document.createElement("option");
                            o.value = c;
                            o.textContent = c;
                            els.category.appendChild(o);
                        });
                    }

                    if (Array.isArray(types)) {
                        types.forEach((t) => {
                            const o = document.createElement("option");
                            o.value = t;
                            o.textContent = t;
                            els.type.appendChild(o);
                        });
                    }

                    filtersLoaded = true;
                }

                async function load() {
                    setError("");
                    setEmpty(false);
                    setLoading(true);

                    const params = new URLSearchParams();
                    params.set("page", String(state.page));
                    if (state.search) params.set("search", state.search);
                    if (state.category) params.set("category", state.category);
                    if (state.type) params.set("type", state.type);

                    try {
                        const res = await fetch(`/notes/data?${params}`, {
                            credentials: "same-origin",
                            headers: { Accept: "application/json", "X-Requested-With": "XMLHttpRequest" },
                        });
                        const data = await res.json();
                        if (!data.success) throw new Error("failed");

                        maybePopulateFilters(data.filters);

                        const items = data.data ?? [];
                        setEmpty(items.length === 0);
                        renderNotes(items);
                        renderPagination({
                            current_page: data.pagination?.current_page,
                            last_page: data.pagination?.last_page,
                        });
                    } catch (e) {
                        setError("Gagal memuat notes.");
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

                els.category.addEventListener("change", (e) => {
                    state.category = e.target.value;
                    state.page = 1;
                    load();
                });

                els.type.addEventListener("change", (e) => {
                    state.type = e.target.value;
                    state.page = 1;
                    load();
                });

                load();
            })();
        </script>
    @endpush
</x-app-layout>
