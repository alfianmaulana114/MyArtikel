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
        if (isLoading) els.list.innerHTML = "";
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
                    ? new Date(a.created_at).toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' })
                    : "";
                const isReady = status === 'ready';

                return `
                    <div class="card rounded-xl hover:shadow-md transition-shadow flex flex-col sm:flex-row gap-5 p-5">
                        <div class="shrink-0 w-12 h-12 rounded-xl bg-[#AA5F3C]/10 flex items-center justify-center">
                            ${a.source_type === 'pdf' 
                                ? '<svg class="w-6 h-6 text-[#AA5F3C]" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>'
                                : '<svg class="w-6 h-6 text-[#AA5F3C]" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>'
                            }
                        </div>
                        <div class="min-w-0 flex-1 flex flex-col justify-center">
                            <div class="font-semibold text-lg theme-text-primary truncate">
                                ${isReady ? `<a class="hover:text-[color:var(--text-link-hover)]" href="/articles/${a.id}">${escapeHtml(title)}</a>` : escapeHtml(title)}
                            </div>
                            
                            <div class="mt-2 flex flex-wrap items-center gap-x-3 gap-y-1 text-sm theme-text-muted">
                                <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-md text-xs font-medium border ${
                                    isReady ? 'bg-[#8B9A7A]/10 text-[#8B9A7A] border-[#8B9A7A]/20' : 
                                    status === 'failed' ? 'bg-[color:var(--error-bg)] text-[color:var(--error)] border-[color:var(--error-border)]' : 
                                    'bg-[#D4A76A]/10 text-[#D4A76A] border-[#D4A76A]/20'
                                }">
                                    ${status.toUpperCase()}
                                </span>
                                ${domain ? `
                                    <span class="flex items-center gap-1">
                                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/></svg>
                                        ${domain}
                                    </span>
                                    <span class="text-gray-300 dark:text-gray-600">&bull;</span>
                                ` : ""}
                                <span class="flex items-center gap-1">
                                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                    ${createdAt}
                                </span>
                            </div>

                            ${
                                tags.length
                                    ? `<div class="mt-3 flex flex-wrap items-center gap-2">
                                            ${tags
                                                .map(
                                                    (t) =>
                                                        `<span class="inline-flex items-center text-xs px-2.5 py-1 rounded-md bg-[color:var(--bg-tertiary)] theme-text-secondary border theme-border-primary">#${escapeHtml(
                                                            t.name ?? t,
                                                        )}</span>`,
                                                )
                                                .join("")}
                                       </div>`
                                    : ""
                            }

                            ${
                                excerpt && isReady
                                    ? `<div class="mt-3 text-sm theme-text-secondary line-clamp-2 leading-relaxed">${escapeHtml(excerpt)}</div>`
                                    : ""
                            }
                        </div>
                        
                        ${isReady ? `
                            <div class="flex items-center justify-end sm:flex-col sm:justify-center shrink-0">
                                <a class="btn btn-secondary w-full sm:w-auto" href="/articles/${a.id}">Baca Artikel</a>
                            </div>
                        ` : ""}
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
                class="px-4 py-2 text-sm font-medium rounded-lg transition-colors border ${
                    active 
                    ? "bg-[#AA5F3C] text-white border-[#AA5F3C]" 
                    : "bg-[color:var(--surface-primary)] text-[color:var(--text-primary)] border-[color:var(--border-primary)] hover:bg-[color:var(--hover-bg)]"
                }"
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
