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
                Dashboard
            </h2>
            <p class="text-sm theme-text-muted">
                Ringkasan aktivitas dan artikel terbaru Anda.
            </p>
        </div>
     <?php $__env->endSlot(); ?>

    <div class="py-6">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            
            <div x-data="{
                sourceType: 'url',
                uploading: false,
                fileName: '',
                dragOver: false,
                expanded: false,
                init() {
                    <?php if($errors->has('pdf_file') || $errors->has('url') || old('url') || old('research_title')): ?>
                        this.expanded = true;
                    <?php endif; ?>
                    <?php if($errors->has('pdf_file')): ?>
                        this.sourceType = 'pdf';
                    <?php endif; ?>
                }
            }" class="card overflow-hidden rounded-xl">
                
                <button @click="expanded = !expanded" class="w-full flex items-center justify-between px-6 py-4 hover:bg-[color:var(--hover-bg)] transition-colors text-left">
                    <div class="flex items-center gap-4">
                        <div class="w-10 h-10 rounded-xl bg-[#AA5F3C]/10 flex items-center justify-center">
                            <svg class="w-5 h-5 text-[#AA5F3C]" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        </div>
                        <div>
                            <div class="font-semibold theme-text-primary">Tambah Artikel</div>
                            <div class="text-sm theme-text-muted">URL jurnal atau upload PDF</div>
                        </div>
                    </div>
                    <svg :class="expanded ? 'rotate-180' : ''" class="w-5 h-5 theme-text-muted transition-transform duration-200" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </button>

                
                <div x-show="expanded" x-collapse class="border-t theme-border-primary">
                    <form id="ingest-form" method="POST" action="<?php echo e(route('dashboard.ingest')); ?>" enctype="multipart/form-data" class="p-6 space-y-5">
                        <?php echo csrf_field(); ?>
                        <input type="hidden" name="source_type" x-model="sourceType">

                        
                        <div class="flex rounded-lg border theme-border-primary bg-[color:var(--surface-secondary)] p-1 w-fit">
                            <button type="button" @click="sourceType = 'url'"
                                :class="sourceType === 'url' ? 'bg-[color:var(--surface-primary)] shadow-sm theme-text-primary' : 'theme-text-muted hover:theme-text-secondary'"
                                class="px-4 py-2 rounded-md text-sm font-medium transition-all">
                                <span class="flex items-center gap-1.5">
                                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>
                                    URL
                                </span>
                            </button>
                            <button type="button" @click="sourceType = 'pdf'"
                                :class="sourceType === 'pdf' ? 'bg-[color:var(--surface-primary)] shadow-sm theme-text-primary' : 'theme-text-muted hover:theme-text-secondary'"
                                class="px-4 py-2 rounded-md text-sm font-medium transition-all">
                                <span class="flex items-center gap-1.5">
                                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                                    PDF
                                </span>
                            </button>
                        </div>

                        
                        <div x-show="sourceType === 'url'" x-transition.opacity>
                            <label for="url" class="block text-sm font-medium theme-text-secondary mb-1.5">URL Artikel / Jurnal</label>
                            <input id="url" name="url" type="url" value="<?php echo e(old('url')); ?>"
                                placeholder="https://example.com/artikel-jurnal"
                                class="block w-full rounded-lg shadow-sm border theme-border-primary bg-[color:var(--surface-primary)] text-[color:var(--text-primary)] placeholder:text-[color:var(--text-muted)] focus:border-[#AA5F3C] focus:ring-[#AA5F3C] px-4 py-2.5 sm:text-sm" />
                            <?php $__errorArgs = ['url'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="mt-2 text-sm text-[color:var(--error)]"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>

                        
                        <div x-show="sourceType === 'pdf'" x-transition.opacity>
                            <label class="block text-sm font-medium theme-text-secondary mb-1.5">Upload Jurnal (PDF)</label>
                            <div @dragover.prevent="dragOver = true" @dragleave.prevent="dragOver = false"
                                @drop.prevent="dragOver = false; const f = $event.dataTransfer.files[0]; if(f) { $refs.fileInput.files = $event.dataTransfer.files; fileName = f.name; }"
                                :class="dragOver ? 'border-[#AA5F3C] bg-[color:var(--bg-tertiary)]' : 'theme-border-primary'"
                                class="relative border-2 border-dashed rounded-lg p-6 text-center transition-colors cursor-pointer"
                                @click="$refs.fileInput.click()">
                                <input x-ref="fileInput" id="pdf_file" name="pdf_file" type="file" accept=".pdf" class="hidden"
                                    @change="fileName = $refs.fileInput.files[0]?.name || ''" />
                                <div x-show="!fileName" class="space-y-1">
                                    <svg class="mx-auto w-8 h-8 theme-text-muted" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 11v6m-3-3l3 3 3-3"/></svg>
                                    <div class="text-sm theme-text-muted"><span class="font-medium text-[#AA5F3C]">Klik atau drop</span> file PDF (maks 10MB)</div>
                                </div>
                                <div x-show="fileName" class="space-y-1">
                                    <svg class="mx-auto w-6 h-6 text-[#AA5F3C]" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                    <div class="text-sm font-medium theme-text-primary" x-text="fileName"></div>
                                </div>
                            </div>
                            <?php $__errorArgs = ['pdf_file'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="mt-2 text-sm text-[color:var(--error)]"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>

                        
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                            <div>
                                <label for="research_title" class="block text-sm font-medium theme-text-secondary mb-1.5">Judul Penelitian <span class="text-xs theme-text-muted font-normal">(opsional)</span></label>
                                <input id="research_title" name="research_title" type="text" value="<?php echo e(old('research_title')); ?>"
                                    placeholder="Analisis Pengaruh Media Sosial…"
                                    class="block w-full rounded-lg shadow-sm border theme-border-primary bg-[color:var(--surface-primary)] text-[color:var(--text-primary)] placeholder:text-[color:var(--text-muted)] focus:border-[#AA5F3C] focus:ring-[#AA5F3C] px-4 py-2.5 sm:text-sm" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium theme-text-secondary mb-1.5">Konteks Riset <span class="text-xs theme-text-muted font-normal">(opsional)</span></label>
                                <select id="research_context_select"
                                    class="block w-full rounded-lg shadow-sm border theme-border-primary bg-[color:var(--surface-primary)] text-[color:var(--text-primary)] focus:border-[#AA5F3C] focus:ring-[#AA5F3C] px-4 py-2.5 sm:text-sm">
                                    <option value="">— Pilih —</option>
                                    <option value="Bab 1 — Pendahuluan">Bab 1 — Pendahuluan</option>
                                    <option value="Bab 2 — Tinjauan Pustaka">Bab 2 — Tinjauan Pustaka</option>
                                    <option value="Bab 3 — Metodologi">Bab 3 — Metodologi</option>
                                    <option value="Bab 4 — Pembahasan / Hasil">Bab 4 — Pembahasan / Hasil</option>
                                    <option value="Bab 5 — Kesimpulan & Saran">Bab 5 — Kesimpulan</option>
                                    <option value="Landasan Teori">Landasan Teori</option>
                                    <option value="Kerangka Pemikiran">Kerangka Pemikiran</option>
                                    <option value="Analisis Data">Analisis Data</option>
                                    <option value="__custom__">Ketik manual…</option>
                                </select>
                                <input id="research_context" name="research_context" type="text" value="<?php echo e(old('research_context')); ?>"
                                    placeholder="misal: kutipan untuk variabel X"
                                    class="hidden block w-full rounded-lg shadow-sm border theme-border-primary bg-[color:var(--surface-primary)] text-[color:var(--text-primary)] placeholder:text-[color:var(--text-muted)] focus:border-[#AA5F3C] focus:ring-[#AA5F3C] px-4 py-2.5 sm:text-sm mt-2" />
                            </div>
                        </div>

                        <script>
                            (function() {
                                const sel = document.getElementById('research_context_select');
                                const inp = document.getElementById('research_context');
                                if (!sel || !inp) return;
                                sel.addEventListener('change', function() {
                                    if (this.value === '__custom__') { inp.classList.remove('hidden'); inp.focus(); inp.value = ''; }
                                    else { inp.classList.add('hidden'); inp.value = this.value; }
                                });
                                inp.addEventListener('input', function() { if (this.value.trim()) sel.value = '__custom__'; });
                                if (inp.value && !sel.querySelector('option[value="' + inp.value + '"]')) { sel.value = '__custom__'; inp.classList.remove('hidden'); }
                            })();
                        </script>

                        
                        <div class="flex items-center justify-end gap-3 pt-2">
                            <button type="submit" class="btn btn-primary" :disabled="uploading">
                                <span x-show="!uploading" class="flex items-center gap-2">
                                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                    Proses Artikel
                                </span>
                                <span x-show="uploading" class="flex items-center gap-2">
                                    <svg class="animate-spin w-4 h-4" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" class="opacity-25"/><path d="M4 12a8 8 0 018-8" stroke="currentColor" stroke-width="3" class="opacity-75"/></svg>
                                    Memproses…
                                </span>
                            </button>
                        </div>

                        <?php if(session('status')): ?>
                            <div class="flex items-center gap-2 text-sm rounded-lg p-4 bg-[color:var(--bg-tertiary)] text-[#8B9A7A] font-medium" role="status">
                                <svg class="w-5 h-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                <?php echo e(session('status')); ?>

                            </div>
                        <?php endif; ?>
                    </form>
                </div>
            </div>

            
            <div class="card rounded-xl p-5">
                <form method="GET" action="<?php echo e(route('dashboard')); ?>" class="flex flex-col sm:flex-row gap-4 sm:items-end">
                    <div class="flex-1">
                        <label for="q" class="block text-sm font-medium theme-text-secondary mb-1.5">Cari Artikel</label>
                        <div class="relative">
                            <svg class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 theme-text-muted pointer-events-none" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                            <input id="q" name="q" type="text" value="<?php echo e($filters['q'] ?? ''); ?>"
                                placeholder="Judul, domain, atau URL…"
                                class="block w-full rounded-lg shadow-sm border theme-border-primary bg-[color:var(--surface-primary)] text-[color:var(--text-primary)] placeholder:text-[color:var(--text-muted)] focus:border-[#AA5F3C] focus:ring-[#AA5F3C] pl-10 pr-4 py-2.5 sm:text-sm" />
                        </div>
                    </div>
                    <div>
                        <button type="submit" class="btn btn-secondary w-full sm:w-auto">Cari</button>
                    </div>
                </form>
            </div>

            
            <div class="space-y-4">
                <?php $__empty_1 = true; $__currentLoopData = $articles; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $article): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <?php
                        $statusColors = [
                            'ready' => 'text-[#8B9A7A]',
                            'queued' => 'text-[#D4A76A]',
                            'fetching' => 'text-[#D4A76A]',
                            'extracting' => 'text-[#D4A76A]',
                            'failed' => 'text-[color:var(--error)]',
                        ];
                        $statusIcons = [
                            'ready' => '<svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>',
                            'queued' => '<svg class="w-3.5 h-3.5 animate-spin" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" class="opacity-25"/><path d="M4 12a8 8 0 018-8" stroke="currentColor" stroke-width="3" class="opacity-75"/></svg>',
                            'fetching' => '<svg class="w-3.5 h-3.5 animate-spin" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" class="opacity-25"/><path d="M4 12a8 8 0 018-8" stroke="currentColor" stroke-width="3" class="opacity-75"/></svg>',
                            'extracting' => '<svg class="w-3.5 h-3.5 animate-spin" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" class="opacity-25"/><path d="M4 12a8 8 0 018-8" stroke="currentColor" stroke-width="3" class="opacity-75"/></svg>',
                            'failed' => '<svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>',
                        ];
                        $status = $article->processing_status ?? 'ready';
                        $statusColor = $statusColors[$status] ?? 'theme-text-muted';
                        $statusIcon = $statusIcons[$status] ?? $statusIcons['ready'];
                    ?>

                    <a href="<?php echo e($article->processing_status === 'ready' ? route('articles.show', $article) : '#'); ?>"
                        class="block card rounded-xl hover:shadow-md transition-shadow <?php echo e($article->processing_status === 'ready' ? 'cursor-pointer' : 'cursor-default'); ?>">

                        
                        <?php if($status !== 'ready'): ?>
                            <div class="px-5 py-2.5 text-xs font-medium <?php echo e($statusColor); ?> border-b theme-border-primary bg-[color:var(--bg-tertiary)] flex items-center justify-between rounded-t-xl">
                                <?php if($status === 'failed'): ?>
                                    <span class="flex items-center gap-1.5"><?php echo $statusIcon; ?> Gagal diproses</span>
                                    <form method="POST" action="<?php echo e(route('dashboard.retry', $article)); ?>" class="inline">
                                        <?php echo csrf_field(); ?>
                                        <button type="submit" class="underline hover:no-underline">Coba lagi</button>
                                    </form>
                                <?php else: ?>
                                    <span class="flex items-center gap-1.5"><?php echo $statusIcon; ?> <?php echo e(ucfirst($status)); ?>&hellip;</span>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>

                        <?php if($article->processing_status === 'failed' && $article->processing_error): ?>
                            <div class="px-5 py-2 text-xs text-[color:var(--error)] bg-[color:var(--error-bg)]">
                                <?php echo e(\Illuminate\Support\Str::limit($article->processing_error, 120)); ?>

                            </div>
                        <?php endif; ?>

                        <div class="p-5">
                            <div class="flex items-start gap-4">
                                
                                <div class="shrink-0 w-12 h-12 rounded-xl bg-[#AA5F3C]/10 flex items-center justify-center">
                                    <?php if($article->source_type === 'pdf'): ?>
                                        <svg class="w-6 h-6 text-[#AA5F3C]" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                                    <?php else: ?>
                                        <svg class="w-6 h-6 text-[#AA5F3C]" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>
                                    <?php endif; ?>
                                </div>

                                
                                <div class="min-w-0 flex-1">
                                    <div class="font-semibold text-lg theme-text-primary leading-snug line-clamp-2">
                                        <?php echo e($article->title); ?>

                                    </div>

                                    
                                    <div class="mt-2 flex flex-wrap items-center gap-x-3 gap-y-1 text-sm theme-text-muted">
                                        <?php if($article->source_domain && $article->source_domain !== 'pdf-upload'): ?>
                                            <span class="flex items-center gap-1">
                                                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/></svg>
                                                <?php echo e($article->source_domain); ?>

                                            </span>
                                            <span class="text-gray-300 dark:text-gray-600">&bull;</span>
                                        <?php endif; ?>
                                        <span class="flex items-center gap-1">
                                            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                            <?php echo e($article->created_at?->format('d M Y')); ?>

                                        </span>
                                        <?php if(auth()->user()?->is_admin && !empty($article->ai_quotation_suggestions)): ?>
                                            <span class="text-gray-300 dark:text-gray-600">&bull;</span>
                                            <span class="text-[#D4A76A] font-medium flex items-center gap-1">
                                                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg>
                                                <?php echo e(count($article->ai_quotation_suggestions)); ?> kutipan
                                            </span>
                                        <?php endif; ?>
                                    </div>

                                    
                                    <?php if($article->research_title || $article->research_context): ?>
                                        <div class="mt-3 flex flex-wrap gap-2">
                                            <?php if($article->research_title): ?>
                                                <span class="inline-flex items-center gap-1.5 text-xs px-2.5 py-1 rounded-md bg-[color:var(--bg-tertiary)] theme-text-secondary border theme-border-primary" title="<?php echo e($article->research_title); ?>">
                                                    <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                                                    <?php echo e(\Illuminate\Support\Str::limit($article->research_title, 35)); ?>

                                                </span>
                                            <?php endif; ?>
                                            <?php if($article->research_context): ?>
                                                <span class="inline-flex items-center gap-1.5 text-xs px-2.5 py-1 rounded-md bg-[#AA5F3C]/10 text-[#AA5F3C] font-medium border border-[#AA5F3C]/20">
                                                    <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                                                    <?php echo e($article->research_context); ?>

                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>

                                    
                                    <?php if($article->excerpt && $status === 'ready'): ?>
                                        <div class="mt-3 text-sm theme-text-secondary line-clamp-2 leading-relaxed">
                                            <?php echo e($article->excerpt); ?>

                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </a>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <div class="card rounded-xl p-12 text-center">
                        <svg class="mx-auto w-16 h-16 theme-text-muted opacity-30 mb-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                        <div class="text-base font-medium theme-text-primary mb-1">Belum ada artikel</div>
                        <div class="text-sm theme-text-muted">
                            Tambahkan artikel pertama Anda melalui form di atas.
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            
            <?php if($articles->hasMorePages()): ?>
                <div class="pt-4">
                    <?php echo e($articles->links()); ?>

                </div>
            <?php endif; ?>
        </div>
    </div>
 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal9ac128a9029c0e4701924bd2d73d7f54)): ?>
<?php $attributes = $__attributesOriginal9ac128a9029c0e4701924bd2d73d7f54; ?>
<?php unset($__attributesOriginal9ac128a9029c0e4701924bd2d73d7f54); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal9ac128a9029c0e4701924bd2d73d7f54)): ?>
<?php $component = $__componentOriginal9ac128a9029c0e4701924bd2d73d7f54; ?>
<?php unset($__componentOriginal9ac128a9029c0e4701924bd2d73d7f54); ?>
<?php endif; ?><?php /**PATH C:\laragon\www\myartikel\resources\views/dashboard.blade.php ENDPATH**/ ?>