<?php if (isset($component)) { $__componentOriginal9ac128a9029c0e4701924bd2d73d7f54 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9ac128a9029c0e4701924bd2d73d7f54 = $attributes; } ?>
<?php $component = App\View\Components\AppLayout::resolve([] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('app-layout'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\App\View\Components\AppLayout::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
     <?php $__env->slot('header', null, []); ?> 
        <div class="flex flex-col gap-1">
            <h2 class="font-semibold text-xl theme-text-primary leading-tight">
                Workspace
            </h2>
            <p class="text-sm theme-text-muted">
                Kelola project makalah dan penelitian Anda.
            </p>
        </div>
     <?php $__env->endSlot(); ?>

    <div class="py-6">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            
            <div class="card rounded-xl p-5">
                <div class="flex flex-col sm:flex-row gap-4 sm:items-end">
                    <div class="flex-1">
                        <label for="projects-search" class="block text-sm font-medium theme-text-secondary mb-1.5">Cari Project</label>
                        <div class="relative">
                            <svg class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 theme-text-muted pointer-events-none" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                            <input
                                id="projects-search"
                                type="text"
                                placeholder="Cari judul atau deskripsi..."
                                class="block w-full rounded-lg shadow-sm border theme-border-primary bg-[color:var(--surface-primary)] text-[color:var(--text-primary)] placeholder:text-[color:var(--text-muted)] focus:border-[#AA5F3C] focus:ring-[#AA5F3C] pl-10 pr-4 py-2.5 sm:text-sm"
                            />
                        </div>
                    </div>
                    <div>
                        <button @click="$dispatch('open-create-modal')" class="btn btn-primary w-full sm:w-auto whitespace-nowrap">
                            <svg class="w-5 h-5 mr-1.5" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                            Project Baru
                        </button>
                    </div>
                </div>
            </div>

            
            <div>
                <div id="projects-loading" class="hidden text-center py-12">
                    <svg class="animate-spin mx-auto w-8 h-8 theme-text-muted" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" class="opacity-25"/><path d="M4 12a8 8 0 018-8" stroke="currentColor" stroke-width="3" class="opacity-75"/></svg>
                    <div class="mt-3 text-sm theme-text-muted">Memuat project...</div>
                </div>

                <div id="projects-empty" class="hidden text-center py-12 card rounded-xl">
                    <svg class="mx-auto w-16 h-16 theme-text-muted opacity-30 mb-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/></svg>
                    <div class="text-lg font-medium theme-text-primary mb-1">Belum ada project</div>
                    <div class="text-sm theme-text-muted">Buat project pertama Anda untuk mulai menyusun makalah.</div>
                </div>

                <div id="projects-error" class="hidden text-sm bg-[color:var(--error-bg)] text-[color:var(--error)] border border-[color:var(--error-border)] rounded-lg px-4 py-3"></div>

                <div id="projects-list" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5"></div>

                <div id="projects-pagination" class="mt-6 flex flex-wrap justify-center gap-2"></div>
            </div>
        </div>
    </div>

    
    <div x-data="createProjectModal" x-show="showCreateModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-black/50" x-transition.opacity @click="showCreateModal = false"></div>
            
            <div class="relative card rounded-2xl max-w-lg w-full p-6 z-10 shadow-xl border theme-border-primary" x-transition>
                <div class="flex items-center justify-between mb-5">
                    <h3 class="text-lg font-semibold theme-text-primary">Buat Project Baru</h3>
                    <button @click="showCreateModal = false" class="theme-text-muted hover:theme-text-primary">
                        <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <form @submit.prevent="createProject">
                    <div class="space-y-4">
                        <div>
                            <label for="new-project-title" class="block text-sm font-medium theme-text-secondary mb-1.5">Judul Project <span class="text-[color:var(--error)]">*</span></label>
                            <input
                                id="new-project-title"
                                type="text"
                                x-model="formData.title"
                                required
                                placeholder="Contoh: Analisis Dampak AI terhadap Pendidikan"
                                class="block w-full rounded-lg shadow-sm border theme-border-primary bg-[color:var(--surface-primary)] text-[color:var(--text-primary)] placeholder:text-[color:var(--text-muted)] focus:border-[#AA5F3C] focus:ring-[#AA5F3C] sm:text-sm px-4 py-2.5"
                            />
                        </div>

                        <div>
                            <label for="new-project-description" class="block text-sm font-medium theme-text-secondary mb-1.5">Deskripsi</label>
                            <textarea
                                id="new-project-description"
                                x-model="formData.description"
                                rows="3"
                                placeholder="Deskripsi singkat tentang project ini..."
                                class="block w-full rounded-lg shadow-sm border theme-border-primary bg-[color:var(--surface-primary)] text-[color:var(--text-primary)] placeholder:text-[color:var(--text-muted)] focus:border-[#AA5F3C] focus:ring-[#AA5F3C] sm:text-sm px-4 py-2.5"
                            ></textarea>
                        </div>
                    </div>

                    <div id="create-project-error" class="hidden mt-4 text-sm bg-[color:var(--error-bg)] text-[color:var(--error)] border border-[color:var(--error-border)] rounded-lg px-4 py-3"></div>

                    <div class="mt-6 flex gap-3">
                        <button type="button" @click="showCreateModal = false" class="btn btn-secondary flex-1">Batal</button>
                        <button type="submit" class="btn btn-primary flex-1 justify-center" :disabled="creating">
                            <span x-show="!creating">Buat Project</span>
                            <span x-show="creating" class="flex items-center justify-center gap-2">
                                <svg class="animate-spin w-4 h-4" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" class="opacity-25"/><path d="M4 12a8 8 0 018-8" stroke="currentColor" stroke-width="3" class="opacity-75"/></svg>
                                Membuat...
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <?php $__env->startPush('scripts'); ?>
        <script>
            (() => {
                const state = {
                    page: 1,
                    search: "",
                };

                const els = {
                    search: document.getElementById("projects-search"),
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

                function renderProjects(items) {
                    els.list.innerHTML = items
                        .map((p) => {
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

                                        ${p.description ? `<p class="text-sm theme-text-secondary mb-4 line-clamp-2 leading-relaxed">${escapeHtml(p.description)}</p>` : '<div class="mb-4"></div>'}
                                    </div>

                                    <div class="space-y-3 mt-auto">
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

                load();
            })();

            // Create Project Modal Logic
            document.addEventListener('alpine:init', () => {
                Alpine.data('createProjectModal', () => ({
                    showCreateModal: false,
                    creating: false,
                    formData: {
                        title: '',
                        description: '',
                    },
                    init() {
                        window.addEventListener('open-create-modal', () => {
                            this.showCreateModal = true;
                        });
                    },
                    async createProject() {
                        this.creating = true;
                        const errorEl = document.getElementById('create-project-error');
                        errorEl.classList.add('hidden');

                        try {
                            const res = await fetch('/projects', {
                                method: 'POST',
                                credentials: 'same-origin',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                    'Accept': 'application/json',
                                    'X-Requested-With': 'XMLHttpRequest',
                                },
                                body: JSON.stringify(this.formData),
                            });

                            const json = await res.json();

                            if (json.success) {
                                window.location.href = `/projects/${json.project.id}`;
                            } else {
                                errorEl.textContent = json.message || json.error || 'Gagal membuat project';
                                errorEl.classList.remove('hidden');
                            }
                        } catch (e) {
                            errorEl.textContent = 'Gagal membuat project';
                            errorEl.classList.remove('hidden');
                        } finally {
                            this.creating = false;
                        }
                    }
                }));
            });
        </script>
    <?php $__env->stopPush(); ?>
 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal9ac128a9029c0e4701924bd2d73d7f54)): ?>
<?php $attributes = $__attributesOriginal9ac128a9029c0e4701924bd2d73d7f54; ?>
<?php unset($__attributesOriginal9ac128a9029c0e4701924bd2d73d7f54); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal9ac128a9029c0e4701924bd2d73d7f54)): ?>
<?php $component = $__componentOriginal9ac128a9029c0e4701924bd2d73d7f54; ?>
<?php unset($__componentOriginal9ac128a9029c0e4701924bd2d73d7f54); ?>
<?php endif; ?>
<?php /**PATH C:\laragon\www\myartikel\resources\views/projects/index.blade.php ENDPATH**/ ?>