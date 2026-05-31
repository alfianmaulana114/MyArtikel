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
        <div class="flex items-center justify-between gap-3">
            <div class="min-w-0 flex-1">
                <h2 class="font-semibold text-xl theme-text-primary leading-tight truncate">
                    <?php echo e($article->title); ?>

                </h2>
            </div>
            <div class="flex items-center gap-2">
                <a href="<?php echo e(route('articles.index')); ?>" class="btn btn-secondary">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m2 14l7-7m-7 7l-7-7"/></svg>
                    Kembali
                </a>
            </div>
        </div>
     <?php $__env->endSlot(); ?>

    <div class="py-8">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            
            <?php if($article->processing_status !== 'ready'): ?>
                <div class="mb-6 card sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex items-center gap-3">
                            <svg class="animate-spin w-5 h-5 text-[#AA5F3C]" viewBox="0 0 24 24" fill="none" stroke="currentColor"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" class="opacity-25"/><path d="M4 12a8 8 0 018-8" stroke="currentColor" stroke-width="3" class="opacity-75"/></svg>
                            <div>
                                <div class="font-semibold theme-text-primary">Artikel masih diproses</div>
                                <div class="text-sm theme-text-muted">
                                    Status: <?php echo e($article->processing_status ?? 'queued'); ?>

                                </div>
                            </div>
                        </div>
                        <?php if($article->processing_status === 'failed' && $article->processing_error): ?>
                            <div class="mt-3 text-sm bg-[color:var(--error-bg)] text-[color:var(--error)] border border-[color:var(--error-border)] rounded-lg px-3 py-2">
                                <?php echo e($article->processing_error); ?>

                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>

            
            <div class="card sm:rounded-lg overflow-hidden mb-6">
                <div class="p-6 sm:p-8">
                    
                    <?php if($article->source_url): ?>
                        <div class="flex items-center gap-2 mb-4 text-sm theme-text-muted">
                            <svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>
                            <a href="<?php echo e($article->source_url); ?>" target="_blank" rel="noopener noreferrer" class="hover:underline truncate"><?php echo e($article->source_domain ?? parse_url($article->source_url, PHP_URL_HOST)); ?></a>
                        </div>
                    <?php elseif($article->source_type === 'pdf'): ?>
                        <div class="flex items-center gap-2 mb-4 text-sm theme-text-muted">
                            <svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                            <span>Upload PDF</span>
                        </div>
                    <?php endif; ?>

                    
                    <div class="mb-4" x-data="{
                        editing: false,
                        saving: false,
                        researchTitle: <?php echo e(Js::from($article->research_title ?? '')); ?>,
                        researchContext: <?php echo e(Js::from($article->research_context ?? '')); ?>

                    }">
                        <div class="space-y-2" x-show="!editing">
                            <?php if($article->research_title): ?>
                                <div class="flex items-start gap-2">
                                    <div class="flex-1">
                                        <div class="text-xs theme-text-muted mb-0.5">Judul Penelitian</div>
                                        <div class="text-sm font-medium theme-text-secondary"><?php echo e($article->research_title); ?></div>
                                    </div>
                                </div>
                            <?php endif; ?>
                            <?php if($article->research_context): ?>
                                <div class="flex items-start gap-2">
                                    <div class="flex-1">
                                        <div class="text-xs theme-text-muted mb-0.5">Konteks Riset</div>
                                        <div class="text-sm theme-text-secondary"><?php echo e($article->research_context); ?></div>
                                    </div>
                                </div>
                            <?php endif; ?>
                            <?php if(! $article->research_title && ! $article->research_context): ?>
                                <button @click="editing = true" class="flex items-center gap-2 text-sm px-3 py-2 rounded-lg border border-dashed theme-border-primary hover:bg-[color:var(--bg-tertiary)] transition-colors w-full">
                                    <svg class="w-4 h-4 text-[#AA5F3C]" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                    <span class="theme-text-muted">Tambah Judul Penelitian & Konteks agar saran kutipan lebih relevan</span>
                                </button>
                            <?php else: ?>
                                <button @click="editing = true" class="text-xs px-2 py-1 rounded-lg bg-[color:var(--bg-tertiary)] theme-text-muted hover:bg-[#AA5F3C] hover:text-white transition-colors" title="Edit">
                                    <svg class="w-3.5 h-3.5 inline" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                    Edit
                                </button>
                            <?php endif; ?>
                        </div>

                        <div x-show="editing" x-transition class="space-y-3">
                            <div>
                                <label class="block text-xs font-medium theme-text-muted mb-1">Judul Penelitian / Topik Riset</label>
                                <input type="text" x-model="researchTitle"
                                    class="block w-full rounded-xl shadow-sm border theme-border-primary bg-[color:var(--surface-primary)] text-[color:var(--text-primary)] placeholder:text-[color:var(--text-muted)] focus:border-[#AA5F3C] focus:ring-[#AA5F3C] px-4 py-2 text-sm"
                                    placeholder="contoh: Analisis Pengaruh Media Sosial terhadap Produktivitas Mahasiswa">
                            </div>
                            <div>
                                <label class="block text-xs font-medium theme-text-muted mb-1">Konteks Riset (bagian skripsi/kebutuhan)</label>
                                <select x-model="researchContextSelect"
                                    x-init="$watch('researchContextSelect', v => { if (v !== '__custom__') researchContext = v; })"
                                    class="block w-full rounded-xl shadow-sm border theme-border-primary bg-[color:var(--surface-primary)] text-[color:var(--text-primary)] focus:border-[#AA5F3C] focus:ring-[#AA5F3C] px-4 py-2 text-sm">
                                    <option value="">— Pilih konteks —</option>
                                    <option value="Bab 1 — Pendahuluan">Bab 1 — Pendahuluan</option>
                                    <option value="Bab 2 — Tinjauan Pustaka">Bab 2 — Tinjauan Pustaka</option>
                                    <option value="Bab 3 — Metodologi">Bab 3 — Metodologi</option>
                                    <option value="Bab 4 — Pembahasan / Hasil">Bab 4 — Pembahasan / Hasil</option>
                                    <option value="Bab 5 — Kesimpulan & Saran">Bab 5 — Kesimpulan & Saran</option>
                                    <option value="Landasan Teori">Landasan Teori</option>
                                    <option value="Kerangka Pemikiran">Kerangka Pemikiran</option>
                                    <option value="Analisis Data">Analisis Data</option>
                                    <option value="__custom__">Ketik manual…</option>
                                </select>
                                <input type="text" x-model="researchContext"
                                    x-show="researchContextSelect === '__custom__'"
                                    class="mt-2 block w-full rounded-xl shadow-sm border theme-border-primary bg-[color:var(--surface-primary)] text-[color:var(--text-primary)] placeholder:text-[color:var(--text-muted)] focus:border-[#AA5F3C] focus:ring-[#AA5F3C] px-4 py-2 text-sm"
                                    placeholder="misal: kutipan untuk variabel X di bab 3">
                            </div>
                            <div class="flex items-center gap-2">
                                <button @click="saving = true;
                                    fetch('<?php echo e(route('articles.update', $article->id)); ?>', {
                                        method: 'PUT',
                                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                                        body: JSON.stringify({ research_title: researchTitle, research_context: researchContext })
                                    }).then(r => r.json()).then(() => { saving = false; editing = false; window.location.reload(); }).catch(() => { saving = false; })"
                                    :disabled="saving"
                                    class="btn btn-primary text-xs px-3 py-1.5">
                                    Simpan
                                </button>
                                <button @click="editing = false"
                                    :disabled="saving"
                                    class="btn btn-secondary text-xs px-3 py-1.5">
                                    Batal
                                </button>
                            </div>
                        </div>
                    </div>

                    <?php if($article->excerpt): ?>
                        <div class="p-4 rounded-xl bg-[color:var(--bg-tertiary)] border-l-4 border-[#AA5F3C]">
                            <div class="text-sm theme-text-secondary leading-relaxed">
                                <?php echo e($article->excerpt); ?>

                            </div>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-8">
                            <svg class="mx-auto w-12 h-12 theme-text-muted mb-3" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            <div class="text-sm theme-text-muted">
                                Konten artikel tidak ditampilkan. Silakan gunakan fitur Rangkuman atau Saran Kutipan di bawah.
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            
            <div class="card sm:rounded-lg overflow-hidden mb-6" x-data="{
                activeTab: 'summary',
            }">
                
                <div class="flex border-b theme-border-primary bg-[color:var(--bg-tertiary)]">
                    <button
                        @click="activeTab = 'summary'"
                        :class="activeTab === 'summary'
                            ? 'theme-text-primary border-b-3 border-[#AA5F3C] bg-[color:var(--surface-primary)]'
                            : 'theme-text-muted border-b-3 border-transparent hover:theme-text-secondary hover:bg-[color:var(--surface-primary)]/50'"
                        class="flex-1 px-6 py-4 text-sm font-medium transition-all text-center"
                    >
                        <span class="flex items-center justify-center gap-2">
                            <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            Rangkuman
                        </span>
                    </button>
                    <button
                        @click="activeTab = 'citations'"
                        :class="activeTab === 'citations'
                            ? 'theme-text-primary border-b-3 border-[#AA5F3C] bg-[color:var(--surface-primary)]'
                            : 'theme-text-muted border-b-3 border-transparent hover:theme-text-secondary hover:bg-[color:var(--surface-primary)]/50'"
                        class="flex-1 px-6 py-4 text-sm font-medium transition-all text-center relative"
                    >
                        <span class="flex items-center justify-center gap-2">
                            <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                            Saran Kutipan
                        </span>
                        <?php if(auth()->user()?->is_admin && !empty($article->ai_quotation_suggestions)): ?>
                            <span class="absolute top-2 right-4 text-xs px-2 py-0.5 rounded-full bg-[#AA5F3C] text-white">
                                <?php echo e(count($article->ai_quotation_suggestions)); ?>

                            </span>
                        <?php endif; ?>
                    </button>
                </div>

                
                <div x-show="activeTab === 'summary'" x-transition class="p-6 sm:p-8">
                    <div class="flex items-start justify-between gap-3 mb-6">
                        <div>
                            <h3 class="text-lg font-semibold theme-text-primary">Ringkasan</h3>
                            <p class="text-sm theme-text-muted mt-1">
                                Generate ringkasan on-demand sesuai kebutuhan.
                            </p>
                        </div>

                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-4">
                        <div class="sm:col-span-1">
                            <label for="summary-language" class="block text-sm font-medium theme-text-secondary mb-2">Bahasa</label>
                            <select id="summary-language" class="block w-full rounded-lg shadow-sm border theme-border-primary bg-[color:var(--surface-primary)] text-[color:var(--text-primary)] focus:border-[#AA5F3C] focus:ring-[#AA5F3C]">
                                <option value="id">Indonesia</option>
                                <option value="en">English</option>
                            </select>
                        </div>
                        <div class="sm:col-span-2">
                            <label for="summary-words" class="block text-sm font-medium theme-text-secondary mb-2">Panjang Ringkasan</label>
                            <select id="summary-words" class="block w-full rounded-lg shadow-sm border theme-border-primary bg-[color:var(--surface-primary)] text-[color:var(--text-primary)] focus:border-[#AA5F3C] focus:ring-[#AA5F3C]">
                                <option value="150">~150 kata (Singkat)</option>
                                <option value="250">~250 kata (Sedang)</option>
                                <option value="350">~350 kata (Detail)</option>
                            </select>
                        </div>
                    </div>

                    <?php if(auth()->user()?->is_admin): ?>
                    <div class="flex items-center justify-between gap-4 mb-4 p-4 rounded-lg bg-[color:var(--bg-tertiary)]">
                        <label class="inline-flex items-center gap-2 text-sm theme-text-secondary">
                            <input id="summary-prefer-ai" type="checkbox" class="rounded theme-border-primary text-[#AA5F3C] shadow-sm focus:ring-[#AA5F3C]" checked>
                            <span>Prefer AI (Gemini)</span>
                        </label>
                        <label class="inline-flex items-center gap-2 text-sm theme-text-secondary">
                            <input id="summary-async" type="checkbox" class="rounded theme-border-primary text-[#AA5F3C] shadow-sm focus:ring-[#AA5F3C]">
                            <span>Background Process</span>
                        </label>
                    </div>

                    <div class="text-xs theme-text-muted mb-4" id="summary-quota"></div>
                    <?php endif; ?>

                    <div class="flex flex-wrap gap-3 mb-6">
                        <button id="summary-generate" type="button" class="btn btn-primary">
                            <span class="flex items-center gap-2">
                                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                                Buat Ringkasan
                            </span>
                        </button>
                        <button id="summary-regenerate" type="button" class="btn btn-secondary">
                            <span class="flex items-center gap-2">
                                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                Regenerate
                            </span>
                        </button>
                    </div>

                    <div class="mb-4">
                        <div id="summary-status" class="text-sm theme-text-muted"></div>
                        <div id="summary-error" class="hidden mt-3 text-sm bg-[color:var(--error-bg)] text-[color:var(--error)] border border-[color:var(--error-border)] rounded-lg px-4 py-3"></div>
                    </div>

                    <div id="summary-result" class="hidden space-y-4">
                        <div class="rounded-xl border theme-border-primary bg-[color:var(--surface-primary)] p-5">
                            <div class="flex items-center gap-2 mb-3">
                                <svg class="w-4 h-4 text-[#AA5F3C]" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                <span class="text-sm font-medium theme-text-primary">Ringkasan</span>
                            </div>
                            <div id="summary-content" class="text-sm theme-text-secondary leading-relaxed whitespace-pre-wrap"></div>
                        </div>
                        <div class="rounded-xl border theme-border-primary bg-[color:var(--surface-primary)] p-5">
                            <div class="flex items-center gap-2 mb-3">
                                <svg class="w-4 h-4 text-[#AA5F3C]" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/></svg>
                                <span class="text-sm font-medium theme-text-primary">Poin Kunci</span>
                            </div>
                            <div id="summary-points" class="flex flex-wrap gap-2"></div>
                        </div>
                    </div>
                </div>

                
                <div x-show="activeTab === 'citations'" x-transition class="p-6 sm:p-8">
                    <div class="flex items-start justify-between gap-3 mb-6">
                        <div>
                            <h3 class="text-lg font-semibold theme-text-primary">Saran Kutipan</h3>
                            <p class="text-sm theme-text-muted mt-1">
                                Saran kutipan dari artikel ini
                                <?php if($article->research_context): ?>
                                    <span class="text-[#AA5F3C] font-medium">untuk konteks: <?php echo e($article->research_context); ?></span>
                                <?php elseif($article->research_title): ?>
                                    untuk penelitian: <span class="text-[#AA5F3C] font-medium"><?php echo e($article->research_title); ?></span>
                                <?php endif; ?>
                            </p>
                        </div>
                        <?php if($article->processing_status === 'ready' && !empty($article->text_extracted)): ?>
                            <button id="generate-citations-btn" type="button"
                                class="btn btn-primary text-sm shrink-0"
                                <?php if(empty($article->research_title)): ?> disabled title="Isi Judul Penelitian terlebih dahulu" <?php endif; ?>>
                                <span class="flex items-center gap-1.5">
                                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                                    Buat Kutipan
                                </span>
                            </button>
                        <?php endif; ?>
                    </div>

                    <?php if($article->processing_status === 'ready' && empty($article->research_title)): ?>
                        <div class="mb-4 p-3 rounded-lg bg-[color:var(--bg-tertiary)] border border-dashed theme-border-primary text-sm theme-text-muted">
                            <span class="font-medium text-[#AA5F3C]">Judul Penelitian belum diisi.</span> Tambahkan judul penelitian di atas agar saran kutipan lebih relevan.
                        </div>
                    <?php endif; ?>

                    <?php
                        $firstCitation = $article->ai_quotation_suggestions[0] ?? null;
                        $generatedForTitle = $firstCitation['generated_for_title'] ?? null;
                        $generatedForContext = $firstCitation['generated_for_context'] ?? null;
                        $isStale = ($generatedForTitle !== null && $generatedForTitle !== $article->research_title)
                                || ($generatedForContext !== null && $generatedForContext !== $article->research_context);
                    ?>

                    <?php if($isStale): ?>
                        <div class="mb-4 p-3 rounded-lg bg-[color:var(--bg-tertiary)] border border-dashed border-[#D4A76A]/50 text-sm">
                            <div class="flex items-start gap-2">
                                <svg class="w-4 h-4 text-[#D4A76A] shrink-0 mt-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                <div>
                                    <span class="font-medium text-[#AA5F3C]">Konteks riset telah berubah</span> sejak kutipan terakhir dibuat.
                                    Klik tombol <strong>Buat Kutipan</strong> di atas untuk memperbarui agar sesuai dengan judul & konteks riset saat ini.
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if(!empty($article->ai_quotation_suggestions)): ?>
                        <div class="space-y-4">
                            <?php $__currentLoopData = $article->ai_quotation_suggestions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $citation): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <div class="rounded-xl border theme-border-primary bg-[color:var(--surface-primary)] p-5 hover:shadow-md transition-shadow">
                                    <div class="flex items-center gap-3 mb-3">
                                        <span class="text-xs px-3 py-1 rounded-full bg-[#AA5F3C] text-white font-medium">
                                            Kutipan #<?php echo e($index + 1); ?>

                                        </span>
                                        <?php if(!empty($citation['position'])): ?>
                                            <?php
                                                $positionLabels = [
                                                    'latar_belakang'  => 'Bab 1 — Latar Belakang',
                                                    'tinjauan_pustaka'=> 'Bab 2 — Tinjauan Pustaka',
                                                    'metodologi'      => 'Bab 3 — Metodologi',
                                                    'pembahasan'      => 'Bab 4 — Pembahasan',
                                                    'kesimpulan'      => 'Bab 5 — Kesimpulan',
                                                ];
                                                $posLabel = $positionLabels[$citation['position']] ?? ucwords(str_replace('_', ' ', $citation['position']));
                                            ?>
                                            <span class="text-xs px-3 py-1 rounded-full bg-[color:var(--bg-tertiary)] theme-text-muted">
                                                <?php echo e($posLabel); ?>

                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="mb-3 space-y-3">
                                        <div>
                                            <div class="text-xs font-medium theme-text-primary mb-1">Kutipan Asli:</div>
                                            <blockquote class="border-l-4 border-[#AA5F3C] pl-4 italic text-sm theme-text-secondary leading-relaxed">
                                                "<?php echo e($citation['quote'] ?? ''); ?>"
                                            </blockquote>
                                        </div>
                                        <?php if(!empty($citation['paraphrase'])): ?>
                                            <div class="p-3 rounded-lg bg-[#F5F0EB] dark:bg-[#2A2520] border border-[#D4A76A]/30">
                                                <div class="text-xs font-medium text-[#AA5F3C] mb-1">Parafrase:</div>
                                                <blockquote class="text-sm theme-text-secondary leading-relaxed">
                                                    "<?php echo e($citation['paraphrase']); ?>"
                                                </blockquote>
                                            </div>
                                        <?php else: ?>
                                            <div class="paraphrase-action" data-index="<?php echo e($index); ?>">
                                                <button type="button" class="btn-paraphrase inline-flex items-center gap-1.5 text-xs px-3 py-1.5 rounded-lg border border-dashed border-[#D4A76A]/50 text-[#AA5F3C] hover:bg-[#F5F0EB] dark:hover:bg-[#2A2520] transition-colors">
                                                    <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                                    Buat Parafrase
                                                </button>
                                                <span class="paraphrase-status hidden text-xs theme-text-muted ml-2"></span>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <?php if(!empty($citation['relevance'])): ?>
                                        <?php
                                            $rel = $citation['relevance'];
                                            // Clean up old format
                                            $rel = str_replace('(ekstraksi lokal)', '', $rel);
                                            $rel = trim($rel);
                                            if (str_starts_with($rel, 'Ditemukan relevan dengan topik:')) {
                                                $rel = str_replace('Ditemukan relevan dengan topik:', 'Relevan dengan topik penelitian', $rel);
                                            }
                                        ?>
                                        <div class="p-3 rounded-lg bg-[color:var(--bg-tertiary)] border border-[color:var(--border-primary)]">
                                            <div class="flex items-center gap-2 mb-2">
                                                <div class="w-6 h-6 rounded-full bg-[#AA5F3C]/10 flex items-center justify-center shrink-0">
                                                    <svg class="w-3.5 h-3.5 text-[#AA5F3C]" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                                </div>
                                                <div class="text-xs font-semibold theme-text-primary">Relevansi dengan riset</div>
                                            </div>
                                            <div class="text-sm theme-text-secondary leading-relaxed pl-8">
                                                <?php echo e($rel); ?>

                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </div>
                    <?php else: ?>
                        <div class="rounded-xl border theme-border-primary bg-[color:var(--surface-primary)] p-8 text-center">
                            <svg class="mx-auto w-12 h-12 theme-text-muted mb-3" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                            <div class="text-sm theme-text-muted">
                                Belum ada kutipan.
                            </div>
                            <div class="mt-2 text-xs theme-text-muted">
                                <?php if(empty($article->research_title)): ?>
                                    Isi <strong>Judul Penelitian</strong> terlebih dahulu agar kutipan yang dihasilkan sesuai dengan fokus riset Anda.
                                <?php else: ?>
                                    Klik tombol <strong>Buat Kutipan</strong> di atas untuk membuat saran kutipan yang relevan dengan <strong><?php echo e($article->research_title); ?></strong>.
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <?php $__env->startPush('scripts'); ?>
        <script>
            (() => {
                const articleId = <?php echo e((int) $article->id); ?>;
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
                                        `<span class="text-xs px-3 py-1.5 rounded-full bg-[color:var(--bg-tertiary)] theme-text-secondary">${escapeHtml(
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
                    if (!els.quota) return;
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
                        prefer_ai: els.preferAI ? !!els.preferAI.checked : true,
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
                        prefer_ai: els.preferAI ? !!els.preferAI.checked : true,
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

                els.generate.addEventListener("click", () => generate(els.async ? !!els.async.checked : false));
                els.regenerate.addEventListener("click", () => regenerate());

                const genBtn = document.getElementById("generate-citations-btn");
                if (genBtn) {
                    genBtn.addEventListener("click", async () => {
                        genBtn.disabled = true;
                        const originalHTML = genBtn.querySelector("span").innerHTML;
                        genBtn.querySelector("span").textContent = "Memproses…";
                        try {
                            const res = await fetch(`/articles/<?php echo e($article->id); ?>/generate-citations`, {
                                method: "POST",
                                credentials: "same-origin",
                                headers: {
                                    Accept: "application/json",
                                    "X-Requested-With": "XMLHttpRequest",
                                    "X-CSRF-TOKEN": csrf,
                                },
                            });
                            const json = await res.json();
                            if (json.success) {
                                window.location.reload();
                            } else {
                                alert(json.error || "Gagal membuat kutipan. Coba lagi nanti.");
                                genBtn.disabled = false;
                                genBtn.querySelector("span").innerHTML = originalHTML;
                            }
                        } catch (e) {
                            alert("Gagal menghubungi server. Pastikan koneksi internet aktif.");
                            genBtn.disabled = false;
                            genBtn.querySelector("span").innerHTML = originalHTML;
                        }
                    });
                }

                // Handle individual paraphrase generation buttons
                document.querySelectorAll(".btn-paraphrase").forEach((btn) => {
                    btn.addEventListener("click", async function () {
                        const wrapper = this.closest(".paraphrase-action");
                        const index = wrapper.dataset.index;
                        const status = wrapper.querySelector(".paraphrase-status");
                        this.disabled = true;
                        this.classList.add("opacity-70");
                        status.textContent = "Membuat parafrase…";
                        status.classList.remove("hidden");
                        try {
                            const res = await fetch(`/articles/${articleId}/paraphrase-citation/${index}`, {
                                method: "POST",
                                credentials: "same-origin",
                                headers: {
                                    Accept: "application/json",
                                    "X-Requested-With": "XMLHttpRequest",
                                    "X-CSRF-TOKEN": csrf,
                                },
                            });
                            const json = await res.json();
                            if (json.success && json.data?.paraphrase) {
                                // Replace button with paraphrase block
                                const paraBlock = document.createElement("div");
                                paraBlock.className = "p-3 rounded-lg bg-[#F5F0EB] dark:bg-[#2A2520] border border-[#D4A76A]/30";
                                paraBlock.innerHTML = `
                                    <div class="text-xs font-medium text-[#AA5F3C] mb-1">Parafrase:</div>
                                    <blockquote class="text-sm theme-text-secondary leading-relaxed">
                                        "${escapeHtml(json.data.paraphrase)}"
                                    </blockquote>
                                `;
                                wrapper.replaceWith(paraBlock);
                            } else {
                                status.textContent = json.error || "Gagal memparafrase.";
                                status.classList.add("text-red-500");
                                this.disabled = false;
                                this.classList.remove("opacity-70");
                            }
                        } catch (e) {
                            status.textContent = "Gagal menghubungi server.";
                            status.classList.add("text-red-500");
                            this.disabled = false;
                            this.classList.remove("opacity-70");
                        }
                    });
                });

                loadQuota();
                loadExistingLatest();
            })();
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
<?php /**PATH C:\laragon\www\myartikel\resources\views\articles\show.blade.php ENDPATH**/ ?>