<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-1">
            <h2 class="font-semibold text-xl theme-text-primary leading-tight">
                Tags
            </h2>
            <p class="text-sm theme-text-muted">
                Kelola tag untuk kurasi dan pencarian.
            </p>
        </div>
    </x-slot>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        <div class="lg:col-span-8 space-y-6">
            <div class="card overflow-hidden sm:rounded-lg">
                <div class="p-6">
                    <div class="flex flex-col sm:flex-row gap-3 sm:items-end">
                        <div class="flex-1">
                            <label for="tags-search" class="block font-medium text-sm theme-text-secondary">Cari</label>
                            <input
                                id="tags-search"
                                type="text"
                                placeholder="Cari nama tag…"
                                class="mt-1 block w-full rounded-lg shadow-sm border theme-border-primary bg-[color:var(--surface-primary)] text-[color:var(--text-primary)] placeholder:text-[color:var(--text-muted)] focus:border-[#AA5F3C] focus:ring-[#AA5F3C]"
                            />
                        </div>

                        <div class="sm:w-56">
                            <label for="tags-sort" class="block font-medium text-sm theme-text-secondary">Urutkan</label>
                            <select
                                id="tags-sort"
                                class="mt-1 block w-full rounded-lg shadow-sm border theme-border-primary bg-[color:var(--surface-primary)] text-[color:var(--text-primary)] focus:border-[#AA5F3C] focus:ring-[#AA5F3C]"
                            >
                                <option value="usage_count:desc">Paling sering dipakai</option>
                                <option value="name:asc">Nama (A-Z)</option>
                                <option value="name:desc">Nama (Z-A)</option>
                                <option value="created_at:desc">Terbaru</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card overflow-hidden sm:rounded-lg">
                <div class="p-6">
                    <div id="tags-loading" class="hidden text-sm theme-text-muted">
                        Memuat…
                    </div>

                    <div id="tags-empty" class="hidden py-10 text-center text-sm theme-text-muted">
                        Belum ada tag.
                    </div>

                    <div id="tags-error" class="hidden text-sm bg-[color:var(--error-bg)] text-[color:var(--error)] border border-[color:var(--error-border)] rounded-lg px-3 py-2"></div>

                    <div id="tags-list" class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-4"></div>

                    <div id="tags-pagination" class="mt-6 flex flex-wrap gap-2"></div>
                </div>
            </div>
        </div>

        <div class="lg:col-span-4 space-y-6 lg:sticky lg:top-24 self-start">
            <div class="card overflow-hidden sm:rounded-lg">
                <div class="p-6">
                    <div class="font-semibold theme-text-primary">Tips</div>
                    <ul class="mt-3 text-sm theme-text-secondary space-y-2">
                        <li>Gunakan tag untuk kurasi artikel penting.</li>
                        <li>Nantinya tag bisa dipakai untuk bulk-tagging.</li>
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
                    sort_by: "usage_count",
                    sort_order: "desc",
                };

                const els = {
                    search: document.getElementById("tags-search"),
                    sort: document.getElementById("tags-sort"),
                    loading: document.getElementById("tags-loading"),
                    empty: document.getElementById("tags-empty"),
                    error: document.getElementById("tags-error"),
                    list: document.getElementById("tags-list"),
                    pagination: document.getElementById("tags-pagination"),
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

                function escapeHtml(s) {
                    return String(s)
                        .replaceAll("&", "&amp;")
                        .replaceAll("<", "&lt;")
                        .replaceAll(">", "&gt;")
                        .replaceAll('"', "&quot;")
                        .replaceAll("'", "&#039;");
                }

                function renderTags(items) {
                    els.list.innerHTML = items
                        .map((t) => {
                            const name = t.name || "(untitled)";
                            const usage = t.usage_count ?? 0;
                            const color = t.color || "#7d5f3f";
                            const description = t.description || "";

                            return `
                                <div class="rounded-xl border theme-border-primary bg-[color:var(--surface-primary)] p-4">
                                    <div class="flex items-start justify-between gap-3">
                                        <div class="min-w-0">
                                            <div class="flex items-center gap-2">
                                                <span class="inline-flex h-2.5 w-2.5 rounded-full" style="background-color:${color}"></span>
                                                <div class="font-semibold theme-text-primary truncate">${escapeHtml(name)}</div>
                                            </div>
                                            ${
                                                description
                                                    ? `<div class="mt-1 text-sm theme-text-secondary">${escapeHtml(description)}</div>`
                                                    : ""
                                            }
                                        </div>
                                        <div class="text-xs px-2 py-1 rounded-full border theme-border-primary bg-[color:var(--surface-secondary)] text-[color:var(--text-muted)] whitespace-nowrap">
                                            ${usage} uses
                                        </div>
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

                async function load() {
                    setError("");
                    setEmpty(false);
                    setLoading(true);

                    const params = new URLSearchParams();
                    params.set("page", String(state.page));
                    if (state.search) params.set("search", state.search);
                    if (state.sort_by) params.set("sort_by", state.sort_by);
                    if (state.sort_order) params.set("sort_order", state.sort_order);

                    try {
                        const res = await fetch(`/tags/data?${params}`, {
                            credentials: "same-origin",
                            headers: { Accept: "application/json", "X-Requested-With": "XMLHttpRequest" },
                        });
                        const data = await res.json();
                        const paginator = data.tags;
                        const items = paginator?.data ?? [];

                        setEmpty(items.length === 0);
                        renderTags(items);
                        renderPagination({
                            current_page: paginator?.current_page,
                            last_page: paginator?.last_page,
                        });
                    } catch (e) {
                        setError("Gagal memuat tags.");
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

                els.sort.addEventListener("change", (e) => {
                    const [sortBy, sortOrder] = String(e.target.value).split(":");
                    state.sort_by = sortBy || "usage_count";
                    state.sort_order = sortOrder || "desc";
                    state.page = 1;
                    load();
                });

                load();
            })();
        </script>
    @endpush
</x-app-layout>
