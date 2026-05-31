function projectStats() {
    return {
        stats: { total: 0, drafting: 0, reviewing: 0, completed: 0 },
        updateStats(projects) {
            this.stats.total = projects.length;
            this.stats.drafting = projects.filter(p => p.status === 'drafting').length;
            this.stats.reviewing = projects.filter(p => p.status === 'reviewing').length;
            this.stats.completed = projects.filter(p => p.status === 'completed').length;
        }
    };
}

(() => {
    const state = {
        page: 1,
        search: "",
        status: "",
    };

    const els = {
        search: document.getElementById("projects-search"),
        status: document.getElementById("projects-status"),
        loading: document.getElementById("projects-loading"),
        empty: document.getElementById("projects-empty"),
        error: document.getElementById("projects-error"),
        list: document.getElementById("projects-list"),
        pagination: document.getElementById("projects-pagination"),
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

    function escapeHtml(s) {
        return String(s)
            .replaceAll("&", "&amp;")
            .replaceAll("<", "&lt;")
            .replaceAll(">", "&gt;")
            .replaceAll('"', "&quot;")
            .replaceAll("'", "&#039;");
    }

    function getStatusBadge(status) {
        const badges = {
            drafting: { color: 'bg-[#D4A76A]/10 text-[#D4A76A] border-[#D4A76A]/20', label: 'Drafting' },
            reviewing: { color: 'bg-purple-500/10 text-purple-600 dark:text-purple-400 border-purple-500/20', label: 'Reviewing' },
            completed: { color: 'bg-[#8B9A7A]/10 text-[#8B9A7A] border-[#8B9A7A]/20', label: 'Completed' },
        };
        const badge = badges[status] || badges.drafting;
        return `<span class="text-xs px-2 py-0.5 rounded-md font-medium border ${badge.color}">${badge.label.toUpperCase()}</span>`;
    }

    function renderProjects(items) {
        els.list.innerHTML = items
            .map((p) => {
                const progress = p.metadata?.progress?.percentage || 0;

                return `
                    <div class="card rounded-xl p-5 hover:shadow-lg transition-shadow cursor-pointer group flex flex-col justify-between min-h-[220px]" onclick="window.location.href='/projects/${p.id}'">
                        <div>
                            <div class="flex items-start justify-between gap-3 mb-3">
                                <div class="flex-1 min-w-0">
                                    <h3 class="font-semibold text-lg theme-text-primary truncate group-hover:text-[#AA5F3C] transition-colors">
                                        ${escapeHtml(p.title)}
                                    </h3>
                                </div>
                            </div>
                            <div class="mb-4">
                                ${getStatusBadge(p.status)}
                            </div>

                            ${p.description ? `<p class="text-sm theme-text-secondary mb-4 line-clamp-2 leading-relaxed">${escapeHtml(p.description)}</p>` : '<div class="mb-4"></div>'}
                        </div>

                        <div class="space-y-3 mt-auto">
                            <div class="flex items-center justify-between text-xs theme-text-muted">
                                <span>Progress</span>
                                <span>${progress}%</span>
                            </div>
                            <div class="w-full bg-[color:var(--bg-tertiary)] rounded-full h-1.5">
                                <div class="bg-[#AA5F3C] h-1.5 rounded-full transition-all" style="width: ${progress}%"></div>
                            </div>

                            <div class="flex items-center justify-between text-xs theme-text-muted pt-3 border-t theme-border-primary mt-3">
                                <div class="flex items-center gap-3">
                                    <span class="flex items-center gap-1.5" title="Artikel Tersimpan">
                                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/></svg>
                                        ${p.articles_count || 0}
                                    </span>
                                    <span class="flex items-center gap-1.5" title="Sub-bab/Outline">
                                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                        ${p.outlines_count || 0}
                                    </span>
                                </div>
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

    async function load() {
        setError("");
        setEmpty(false);
        setLoading(true);

        const params = new URLSearchParams();
        params.set("page", String(state.page));
        if (state.search) params.set("search", state.search);
        if (state.status) params.set("status", state.status);

        try {
            const res = await fetch(`/projects/data?${params}`, {
                credentials: "same-origin",
                headers: { Accept: "application/json", "X-Requested-With": "XMLHttpRequest" },
            });
            const data = await res.json();

            const paginator = data.projects;
            const items = paginator?.data ?? [];

            setEmpty(items.length === 0);
            renderProjects(items);
            renderPagination({
                current_page: paginator?.current_page,
                last_page: paginator?.last_page,
            });

            // Update stats
            if (window.Alpine) {
                const statsComponent = document.querySelector('[x-data="projectStats()"]');
                if (statsComponent && statsComponent.__x) {
                    statsComponent.__x.$data.updateStats(items);
                }
            }
        } catch (e) {
            setError("Gagal memuat project.");
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

    load();
})();
