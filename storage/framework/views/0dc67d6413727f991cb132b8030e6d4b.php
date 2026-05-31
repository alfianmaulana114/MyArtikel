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
                    <?php echo e($project->title); ?>

                </h2>
                <?php if($project->description): ?>
                    <p class="text-sm theme-text-muted mt-1"><?php echo e(Str::limit($project->description, 100)); ?></p>
                <?php endif; ?>
            </div>
            <div class="flex items-center gap-2">
                <a href="<?php echo e(route('projects.index')); ?>" class="btn btn-secondary">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m2 14l7-7m-7 7l-7-7"/></svg>
                    Kembali
                </a>
            </div>
        </div>
     <?php $__env->endSlot(); ?>

    <div class="py-6" x-data="projectWorkspace()">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            
            <div class="card rounded-xl mb-6">
                <div class="p-5">
                    <div class="flex flex-col lg:flex-row lg:items-center gap-4">
                        <div class="flex items-center gap-3 flex-1">
                        </div>

                        <div class="flex items-center gap-4 text-sm theme-text-muted">
                            <a href="<?php echo e(route('projects.graph', $project)); ?>" class="flex items-center gap-1 text-[#AA5F3C] hover:underline">
                                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                                Knowledge Graph
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            
            <div class="card rounded-xl overflow-hidden" x-data="{ activeTab: 'articles' }">
                <div class="flex border-b theme-border-primary bg-[color:var(--bg-tertiary)]">
                    <button @click="activeTab = 'articles'" :class="activeTab === 'articles' ? 'theme-text-primary border-b-[3px] border-[#AA5F3C] bg-[color:var(--surface-primary)]' : 'theme-text-muted border-b-[3px] border-transparent hover:theme-text-secondary'" class="flex-1 px-6 py-4 text-sm font-medium transition-all text-center">
                        <span class="flex items-center justify-center gap-2">
                            <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/></svg>
                            Artikel & Referensi
                        </span>
                    </button>
                    <button @click="activeTab = 'bibliography'" :class="activeTab === 'bibliography' ? 'theme-text-primary border-b-[3px] border-[#AA5F3C] bg-[color:var(--surface-primary)]' : 'theme-text-muted border-b-[3px] border-transparent hover:theme-text-secondary'" class="flex-1 px-6 py-4 text-sm font-medium transition-all text-center">
                        <span class="flex items-center justify-center gap-2">
                            <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                            Daftar Pustaka
                        </span>
                    </button>
                    <button @click="activeTab = 'settings'" :class="activeTab === 'settings' ? 'theme-text-primary border-b-[3px] border-[#AA5F3C] bg-[color:var(--surface-primary)]' : 'theme-text-muted border-b-[3px] border-transparent hover:theme-text-secondary'" class="flex-1 px-6 py-4 text-sm font-medium transition-all text-center">
                        <span class="flex items-center justify-center gap-2">
                            <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            Settings
                        </span>
                    </button>
                </div>

                
                <div x-show="activeTab === 'articles'" class="p-6">
                    <div class="flex items-center justify-between mb-5">
                        <h3 class="text-lg font-semibold theme-text-primary">Artikel & Referensi</h3>
                        <button @click="showAddArticleModal = true" class="btn btn-primary text-sm">
                            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                            Tambah Artikel
                        </button>
                    </div>

                    <div id="project-articles-list" class="space-y-4">
                        <?php $__empty_1 = true; $__currentLoopData = $project->articles; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $article): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                            <div class="card rounded-lg p-5" x-data="{ showSummary: false, showCitations: false }">
                                <div class="flex items-start justify-between gap-4 mb-3">
                                    <div class="flex-1 min-w-0">
                                        <h4 class="font-semibold theme-text-primary text-lg">
                                            <a href="<?php echo e(route('articles.show', $article->id)); ?>" class="hover:text-[#AA5F3C] transition-colors">
                                                <?php echo e($article->title); ?>

                                            </a>
                                        </h4>
                                        <?php if($article->excerpt): ?>
                                            <p class="text-sm theme-text-secondary mt-1 line-clamp-2"><?php echo e(Str::limit($article->excerpt, 200)); ?></p>
                                        <?php endif; ?>
                                        <div class="flex items-center gap-3 mt-2 text-xs theme-text-muted">
                                            <?php if($article->source_domain): ?>
                                                <span class="flex items-center gap-1">
                                                    <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/></svg>
                                                    <?php echo e($article->source_domain); ?>

                                                </span>
                                            <?php endif; ?>
                                            <?php if($article->fetched_at): ?>
                                                <span><?php echo e($article->fetched_at->format('d M Y')); ?></span>
                                            <?php endif; ?>
                                            <span class="px-2 py-0.5 rounded-full bg-[color:var(--bg-tertiary)]">
                                                <?php echo e(ucfirst($article->pivot->role)); ?>

                                            </span>
                                        </div>
                                    </div>
                                    <button @click="removeArticle(<?php echo e($article->id); ?>)" class="p-2 rounded-lg hover:bg-red-500/10 text-red-500 transition-colors flex-shrink-0" title="Hapus dari project">
                                        <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </button>
                                </div>

                                
                                <div class="flex flex-wrap gap-2 mt-4 pt-4 border-t theme-border-primary">
                                    <button @click="showSummary = !showSummary" class="btn btn-secondary text-xs">
                                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                        <span x-text="showSummary ? 'Sembunyikan Rangkuman' : 'Lihat Rangkuman'"></span>
                                    </button>
                                    <button @click="showCitations = !showCitations" class="btn btn-secondary text-xs">
                                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                                        <span x-text="showCitations ? 'Sembunyikan Kutipan' : 'Lihat Kutipan'"></span>
                                        <?php if(auth()->user()?->is_admin && !empty($article->ai_quotation_suggestions)): ?>
                                            <span class="ml-1 text-xs px-1.5 py-0.5 rounded-full bg-[#AA5F3C] text-white" x-show="!showCitations">
                                                <?php echo e(count($article->ai_quotation_suggestions)); ?>

                                            </span>
                                        <?php endif; ?>
                                    </button>
                                </div>

                                
                                <div x-show="showSummary" x-transition class="mt-4 p-4 rounded-lg bg-[color:var(--bg-tertiary)]">
                                    <div class="flex items-center justify-between mb-3">
                                        <h5 class="text-sm font-semibold theme-text-primary">Rangkuman</h5>

                                    </div>
                                    <?php
                                        $latestSummary = $article->summaries->first();
                                    ?>
                                    <?php if($latestSummary): ?>
                                        <div class="text-sm theme-text-secondary leading-relaxed whitespace-pre-wrap"><?php echo e($latestSummary->content); ?></div>
                                        <?php if(!empty($latestSummary->key_points)): ?>
                                            <div class="mt-3 flex flex-wrap gap-2">
                                                <?php $__currentLoopData = $latestSummary->key_points; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $point): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                    <span class="text-xs px-2 py-1 rounded-full bg-[color:var(--surface-primary)] theme-text-secondary"><?php echo e($point); ?></span>
                                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                            </div>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <div class="text-sm theme-text-muted">Belum ada rangkuman.</div>
                                        <button onclick="generateSummaryForArticle(<?php echo e($article->id); ?>)" class="mt-2 btn btn-secondary text-xs">
                                            <svg class="w-3.5 h-3.5 inline mr-1" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                                            Generate Rangkuman
                                        </button>
                                    <?php endif; ?>
                                </div>

                                
                                <div x-show="showCitations" x-transition class="mt-4 p-4 rounded-lg bg-[color:var(--bg-tertiary)]">
                                    <div class="flex items-center justify-between mb-3">
                                        <h5 class="text-sm font-semibold theme-text-primary">Saran Kutipan</h5>
                                    </div>
                                    <?php if($article->research_context || $article->research_title): ?>
                                        <div class="mb-3 text-xs theme-text-muted">
                                            Berdasarkan
                                            <?php if($article->research_context): ?>
                                                <span class="text-[#AA5F3C] font-medium"><?php echo e($article->research_context); ?></span>
                                            <?php else: ?>
                                                <span class="text-[#AA5F3C] font-medium"><?php echo e($article->research_title); ?></span>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                    <?php if(!empty($article->ai_quotation_suggestions)): ?>
                                        <div class="space-y-3">
                                            <?php $__currentLoopData = $article->ai_quotation_suggestions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $citation): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                <div class="p-3 rounded-lg bg-[color:var(--surface-primary)]">
                                                    <div class="flex items-center gap-2 mb-2">
                                                        <span class="text-xs px-2 py-0.5 rounded-full bg-[#AA5F3C] text-white">Kutipan #<?php echo e($index + 1); ?></span>
                                                        <?php if(!empty($citation['position'])): ?>
                                                            <span class="text-xs theme-text-muted"><?php echo e($citation['position']); ?></span>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div class="mb-2 space-y-2">
                                                        <div>
                                                            <div class="text-xs font-medium theme-text-primary mb-0.5">Kutipan Asli:</div>
                                                            <blockquote class="border-l-3 border-[#AA5F3C] pl-3 italic text-sm theme-text-secondary">
                                                                "<?php echo e($citation['quote'] ?? ''); ?>"
                                                            </blockquote>
                                                        </div>
                                                        <?php if(!empty($citation['paraphrase'])): ?>
                                                            <div class="p-2 rounded-lg bg-[#F5F0EB] dark:bg-[#2A2520] border border-[#D4A76A]/30">
                                                                <div class="text-xs font-medium text-[#AA5F3C] mb-0.5">Parafrase:</div>
                                                                <blockquote class="text-sm theme-text-secondary leading-relaxed">
                                                                    "<?php echo e($citation['paraphrase']); ?>"
                                                                </blockquote>
                                                            </div>
                                                        <?php else: ?>
                                                            <div class="paraphrase-action" data-article="<?php echo e($article->id); ?>" data-index="<?php echo e($index); ?>">
                                                                <button type="button" class="btn-paraphrase inline-flex items-center gap-1 text-xs px-2 py-1 rounded border border-dashed border-[#D4A76A]/50 text-[#AA5F3C] hover:bg-[#F5F0EB] dark:hover:bg-[#2A2520] transition-colors">
                                                                    <svg class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                                                    Buat Parafrase
                                                                </button>
                                                                <span class="paraphrase-status hidden text-xs theme-text-muted ml-1"></span>
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
                                                        <div class="mt-2 p-2 rounded bg-[color:var(--bg-tertiary)] border border-[color:var(--border-primary)]">
                                                            <div class="flex items-center gap-1.5 mb-1.5">
                                                                <div class="w-5 h-5 rounded-full bg-[#AA5F3C]/10 flex items-center justify-center shrink-0">
                                                                    <svg class="w-3 h-3 text-[#AA5F3C]" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                                                </div>
                                                                <span class="text-xs font-semibold theme-text-primary">Relevansi dengan riset</span>
                                                            </div>
                                                            <div class="text-xs theme-text-secondary leading-relaxed pl-6"><?php echo e($rel); ?></div>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                        </div>
                                    <?php elseif($article->research_title): ?>
                                        <div class="text-sm theme-text-muted mb-3">Saran kutipan sedang dibuat oleh AI…</div>
                                        <button onclick="generateCitationsForArticle(<?php echo e($article->id); ?>)" class="btn btn-secondary text-xs">
                                            <svg class="w-3.5 h-3.5 inline mr-1" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                                            Generate Kutipan
                                        </button>
                                    <?php else: ?>
                                        <div class="text-sm theme-text-muted">Isi judul penelitian saat submit artikel untuk mendapat saran kutipan.</div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                            <div class="text-center py-12">
                                <svg class="mx-auto w-16 h-16 theme-text-muted mb-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/></svg>
                                <div class="text-lg font-medium theme-text-primary">Belum ada artikel</div>
                                <div class="mt-2 text-sm theme-text-muted">Tambahkan artikel referensi untuk project ini.</div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                
                <div x-show="activeTab === 'bibliography'" class="p-6">
                    <div class="flex items-center justify-between mb-5">
                        <h3 class="text-lg font-semibold theme-text-primary">Daftar Pustaka</h3>
                        <div class="flex gap-2">
                            <select x-model="bibStyle" @change="loadBibliography" class="rounded-lg shadow-sm border theme-border-primary bg-[color:var(--surface-primary)] text-[color:var(--text-primary)] text-sm focus:border-[#AA5F3C] focus:ring-[#AA5F3C]">
                                <option value="apa">APA 7th</option>
                                <option value="mla">MLA 9th</option>
                                <option value="ieee">IEEE</option>
                                <option value="chicago">Chicago</option>
                                <option value="harvard">Harvard</option>
                            </select>
                            <button @click="copyBibliography" class="btn btn-secondary text-sm" :disabled="bibliography.length === 0">
                                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"/></svg>
                                Copy
                            </button>
                            <a :href="'/projects/<?php echo e($project->id); ?>/bibliography/export?style=' + bibStyle + '&format=txt'" class="btn btn-secondary text-sm">
                                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                Export .txt
                            </a>
                            <a :href="'/projects/<?php echo e($project->id); ?>/bibliography/export?style=' + bibStyle + '&format=bib'" class="btn btn-secondary text-sm">
                                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                Export .bib
                            </a>
                        </div>
                    </div>

                    <div x-show="loading" class="text-center py-8">
                        <svg class="animate-spin mx-auto w-8 h-8 theme-text-muted" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" class="opacity-25"/><path d="M4 12a8 8 0 018-8" stroke="currentColor" stroke-width="3" class="opacity-75"/></svg>
                        <div class="mt-3 text-sm theme-text-muted">Memuat daftar pustaka...</div>
                    </div>

                    <div x-show="!loading && bibliography.length === 0" class="text-center py-12">
                        <svg class="mx-auto w-16 h-16 theme-text-muted mb-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                        <div class="text-lg font-medium theme-text-primary">Belum ada artikel</div>
                        <div class="mt-2 text-sm theme-text-muted">Tambahkan artikel terlebih dahulu untuk generate daftar pustaka.</div>
                    </div>

                    <div x-show="!loading && bibliography.length > 0" class="space-y-3">
                        <template x-for="(citation, index) in bibliography" :key="index">
                            <div class="p-4 rounded-lg border theme-border-primary hover:bg-[color:var(--bg-tertiary)] transition-colors">
                                <div class="flex items-start gap-3">
                                    <span class="flex-shrink-0 w-6 h-6 rounded-full bg-[#AA5F3C]/10 flex items-center justify-center text-xs font-semibold text-[#AA5F3C]" x-text="index + 1"></span>
                                    <div class="flex-1 text-sm theme-text-secondary leading-relaxed" x-text="citation.formatted"></div>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

                
                <div x-show="activeTab === 'settings'" class="p-6">
                    <h3 class="text-lg font-semibold theme-text-primary mb-5">Pengaturan Project</h3>

                    <div class="space-y-6 max-w-2xl">
                        <div>
                            <label class="block text-sm font-medium theme-text-secondary mb-2">Judul Project</label>
                            <input type="text" id="edit-project-title" value="<?php echo e($project->title); ?>" class="block w-full rounded-lg shadow-sm border theme-border-primary bg-[color:var(--surface-primary)] text-[color:var(--text-primary)] focus:border-[#AA5F3C] focus:ring-[#AA5F3C]" />
                        </div>

                        <div>
                            <label class="block text-sm font-medium theme-text-secondary mb-2">Deskripsi</label>
                            <textarea id="edit-project-description" rows="3" class="block w-full rounded-lg shadow-sm border theme-border-primary bg-[color:var(--surface-primary)] text-[color:var(--text-primary)] focus:border-[#AA5F3C] focus:ring-[#AA5F3C]"><?php echo e($project->description); ?></textarea>
                        </div>


                        <div class="flex gap-3 pt-4">
                            <button @click="saveProjectSettings()" class="btn btn-primary">Simpan Perubahan</button>
                            <button @click="duplicateProject()" class="btn btn-secondary">Duplikasi Project</button>
                            <button @click="deleteProject()" class="btn btn-secondary text-red-500 hover:text-red-600">Hapus Project</button>
                        </div>
                    </div>
                </div>
            </div>

            
            <div x-show="showAddArticleModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
                <div class="flex items-center justify-center min-h-screen px-4">
                    <div class="fixed inset-0 bg-black/50" @click="showAddArticleModal = false"></div>
                    <div class="relative card rounded-2xl max-w-2xl w-full p-6 z-10 max-h-[80vh] overflow-y-auto">
                        <div class="flex items-center justify-between mb-5">
                            <h3 class="text-lg font-semibold theme-text-primary">Tambah Artikel Referensi</h3>
                            <button @click="showAddArticleModal = false" class="theme-text-muted hover:theme-text-primary">
                                <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>
                        <div class="mb-4">
                            <input type="text" x-model="articleSearch" @input="searchArticles" placeholder="Cari artikel..." class="block w-full rounded-lg shadow-sm border theme-border-primary bg-[color:var(--surface-primary)] text-[color:var(--text-primary)] placeholder:text-[color:var(--text-muted)] focus:border-[#AA5F3C] focus:ring-[#AA5F3C]" />
                        </div>
                        <div class="space-y-2 max-h-96 overflow-y-auto">
                            <template x-for="article in availableArticles" :key="article.id">
                                <div class="p-3 rounded-lg border theme-border-primary hover:bg-[color:var(--bg-tertiary)] cursor-pointer transition-colors" @click="addArticleToProject(article.id)">
                                    <div class="font-medium theme-text-primary" x-text="article.title"></div>
                                    <div class="text-sm theme-text-secondary mt-1 line-clamp-1" x-text="article.excerpt || 'Tidak ada excerpt'"></div>
                                    <div class="text-xs theme-text-muted mt-1" x-text="article.source_domain || ''"></div>
                                </div>
                            </template>
                            <div x-show="availableArticles.length === 0" class="text-center py-8 theme-text-muted">
                                Tidak ada artikel ditemukan
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php $__env->startPush('scripts'); ?>
        <script>
            document.addEventListener('alpine:init', () => {
                Alpine.data('projectWorkspace', () => ({
                    projectId: <?php echo e($project->id); ?>,
                    csrf: document.querySelector('meta[name="csrf-token"]').content,
                    loading: false,
                    showAddArticleModal: false,
                    articleSearch: '',
                    availableArticles: [],
                    allArticles: [],
                    bibStyle: 'apa',
                    bibliography: [],

                    async init() {
                        await this.loadArticles();
                        await this.loadBibliography();
                    },

                    async loadArticles() {
                        try {
                            const res = await fetch('/articles/data?per_page=100', {
                                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                            });
                            const data = await res.json();
                            this.allArticles = data.articles?.data || [];
                            this.availableArticles = this.allArticles;
                        } catch (e) {
                            console.error('Failed to load articles:', e);
                        }
                    },

                    searchArticles() {
                        if (!this.articleSearch) {
                            this.availableArticles = this.allArticles;
                            return;
                        }
                        const search = this.articleSearch.toLowerCase();
                        this.availableArticles = this.allArticles.filter(a =>
                            a.title.toLowerCase().includes(search) ||
                            (a.excerpt && a.excerpt.toLowerCase().includes(search))
                        );
                    },

                    async loadBibliography() {
                        this.loading = true;
                        try {
                            const res = await fetch(`/projects/${this.projectId}/bibliography?style=${this.bibStyle}`, {
                                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                            });
                            const json = await res.json();
                            if (json.success) {
                                this.bibliography = json.citations;
                            }
                        } catch (e) {
                            console.error('Failed to load bibliography:', e);
                        } finally {
                            this.loading = false;
                        }
                    },

                    async copyBibliography() {
                        const text = this.bibliography.map((c, i) => `${i + 1}. ${c.formatted}`).join('\n\n');
                        try {
                            await navigator.clipboard.writeText(text);
                            alert('Daftar pustaka berhasil disalin!');
                        } catch (e) {
                            alert('Gagal menyalin ke clipboard');
                        }
                    },

                    async addArticleToProject(articleId) {
                        try {
                            const res = await fetch(`/projects/${this.projectId}/add-article`, {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': this.csrf,
                                    'Accept': 'application/json',
                                },
                                body: JSON.stringify({ article_id: articleId, role: 'reference' }),
                            });
                            const json = await res.json();
                            if (json.success) {
                                window.location.reload();
                            } else {
                                alert(json.message || json.error || 'Gagal menambah artikel');
                            }
                        } catch (e) {
                            alert('Gagal menambah artikel');
                        }
                    },

                    async removeArticle(articleId) {
                        if (!confirm('Hapus artikel dari project?')) return;
                        try {
                            await fetch(`/projects/${this.projectId}/remove-article/${articleId}`, {
                                method: 'DELETE',
                                headers: { 'X-CSRF-TOKEN': this.csrf, 'Accept': 'application/json' },
                            });
                            window.location.reload();
                        } catch (e) {
                            alert('Gagal hapus artikel');
                        }
                    },

                    async saveProjectSettings() {
                        this.loading = true;
                        try {
                            const data = {
                                title: document.getElementById('edit-project-title').value,
                                description: document.getElementById('edit-project-description').value,
                            };
                            const res = await fetch(`/projects/${this.projectId}`, {
                                method: 'PUT',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': this.csrf,
                                    'Accept': 'application/json',
                                },
                                body: JSON.stringify(data),
                            });
                            const json = await res.json();
                            if (json.success) {
                                alert('Project berhasil diupdate');
                                window.location.reload();
                            } else {
                                alert(json.message || json.error || 'Gagal update project');
                            }
                        } catch (e) {
                            alert('Gagal update project');
                        } finally {
                            this.loading = false;
                        }
                    },

                    async duplicateProject() {
                        if (!confirm('Duplikasi project ini?')) return;
                        this.loading = true;
                        try {
                            const res = await fetch(`/projects/${this.projectId}/duplicate`, {
                                method: 'POST',
                                headers: { 'X-CSRF-TOKEN': this.csrf, 'Accept': 'application/json' },
                            });
                            const json = await res.json();
                            if (json.success) {
                                window.location.href = `/projects/${json.project.id}`;
                            } else {
                                alert(json.message || json.error || 'Gagal duplikasi project');
                            }
                        } catch (e) {
                            alert('Gagal duplikasi project');
                        } finally {
                            this.loading = false;
                        }
                    },

                    async deleteProject() {
                        if (!confirm('Hapus project ini? Tindakan ini tidak bisa dibatalkan.')) return;
                        if (!confirm('Yakin? Semua data project akan hilang.')) return;
                        this.loading = true;
                        try {
                            const res = await fetch(`/projects/${this.projectId}`, {
                                method: 'DELETE',
                                headers: { 'X-CSRF-TOKEN': this.csrf, 'Accept': 'application/json' },
                            });
                            const json = await res.json();
                            if (json.success) {
                                window.location.href = '/projects';
                            } else {
                                alert(json.message || json.error || 'Gagal hapus project');
                            }
                        } catch (e) {
                            alert('Gagal hapus project');
                        } finally {
                            this.loading = false;
                        }
                    },
                }));
            });

            // Helper functions for article-level actions
            function generateSummaryForArticle(articleId) {
                window.location.href = `/articles/${articleId}`;
            }

            async function generateCitationsForArticle(articleId) {
                if (!confirm('Generate saran kutipan<?php echo e(auth()->user()?->is_admin ? ' dengan AI' : ''); ?>?')) return;
                try {
                    const res = await fetch(`/articles/${articleId}/generate-citations`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json',
                        },
                    });
                    const json = await res.json();
                    if (json.success) {
                        window.location.reload();
                    } else {
                        alert(json.error || 'Gagal generate kutipan');
                    }
                } catch (e) {
                    alert('Gagal generate kutipan');
                }
            }

            // Handle individual paraphrase generation in project view
            document.querySelectorAll(".paraphrase-action .btn-paraphrase").forEach((btn) => {
                btn.addEventListener("click", async function () {
                    const wrapper = this.closest(".paraphrase-action");
                    const articleId = wrapper.dataset.article;
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
                                "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content,
                            },
                        });
                        const json = await res.json();
                        if (json.success && json.data?.paraphrase) {
                            const paraBlock = document.createElement("div");
                            paraBlock.className = "p-2 rounded-lg bg-[#F5F0EB] dark:bg-[#2A2520] border border-[#D4A76A]/30";
                            paraBlock.innerHTML = `
                                <div class="text-xs font-medium text-[#AA5F3C] mb-0.5">Parafrase:</div>
                                <blockquote class="text-sm theme-text-secondary leading-relaxed">
                                    "${json.data.paraphrase.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;")}"
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
        </script>
    <?php $__env->stopPush(); ?>

    
    <?php echo $__env->make('projects.chat-panel', ['project' => $project], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
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
<?php /**PATH C:\laragon\www\myartikel\resources\views/projects/show.blade.php ENDPATH**/ ?>