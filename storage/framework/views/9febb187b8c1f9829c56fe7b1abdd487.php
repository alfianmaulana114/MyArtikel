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
            <h2 class="font-semibold text-xl theme-text-primary leading-tight">Semua Artikel</h2>
            <p class="text-sm theme-text-muted">Monitoring artikel dari seluruh user platform.</p>
        </div>
     <?php $__env->endSlot(); ?>

    <div class="card overflow-hidden">
        <div class="p-4 border-b theme-border-primary">
            <form method="GET" action="<?php echo e(route('admin.articles')); ?>" class="flex flex-col sm:flex-row gap-3 sm:items-center flex-wrap">
                <div class="flex-1 min-w-[180px]">
                    <input
                        name="q"
                        type="text"
                        value="<?php echo e(request('q')); ?>"
                        placeholder="Cari judul / domain…"
                        class="block w-full rounded-xl shadow-sm border theme-border-primary bg-[color:var(--surface-primary)] text-[color:var(--text-primary)] placeholder:text-[color:var(--text-muted)] focus:border-[#AA5F3C] focus:ring-[#AA5F3C] px-4 py-2"
                    />
                </div>
                <div>
                    <select name="status" class="block w-full rounded-xl shadow-sm border theme-border-primary bg-[color:var(--surface-primary)] text-[color:var(--text-primary)] focus:border-[#AA5F3C] focus:ring-[#AA5F3C] px-3 py-2">
                        <option value="">Semua status</option>
                        <option value="ready" <?php if(request('status') === 'ready'): echo 'selected'; endif; ?>>Ready</option>
                        <option value="processing" <?php if(request('status') === 'processing'): echo 'selected'; endif; ?>>Processing</option>
                        <option value="failed" <?php if(request('status') === 'failed'): echo 'selected'; endif; ?>>Failed</option>
                    </select>
                </div>
                <div>
                    <select name="user_id" class="block w-full rounded-xl shadow-sm border theme-border-primary bg-[color:var(--surface-primary)] text-[color:var(--text-primary)] focus:border-[#AA5F3C] focus:ring-[#AA5F3C] px-3 py-2">
                        <option value="">Semua user</option>
                        <?php $__currentLoopData = $users; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $u): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($u->id); ?>" <?php if((int)request('user_id') === $u->id): echo 'selected'; endif; ?>><?php echo e($u->name); ?></option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                </div>
                <button type="submit" class="btn btn-secondary whitespace-nowrap">Filter</button>
                <?php if(request()->anyFilled(['q', 'status', 'user_id'])): ?>
                    <a href="<?php echo e(route('admin.articles')); ?>" class="text-sm theme-text-muted hover:theme-text-primary whitespace-nowrap">Reset</a>
                <?php endif; ?>
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b theme-border-primary text-left">
                        <th class="p-4 font-medium theme-text-muted">Artikel</th>
                        <th class="p-4 font-medium theme-text-muted hidden md:table-cell">User</th>
                        <th class="p-4 font-medium theme-text-muted hidden sm:table-cell">Status</th>
                        <th class="p-4 font-medium theme-text-muted hidden lg:table-cell">Domain</th>
                        <th class="p-4 font-medium theme-text-muted hidden lg:table-cell">Stats</th>
                        <th class="p-4 font-medium theme-text-muted hidden xl:table-cell">Created</th>
                    </tr>
                </thead>
                <tbody class="divide-y theme-border-primary">
                    <?php $__empty_1 = true; $__currentLoopData = $articles; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $article): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <tr class="hover:bg-[color:var(--hover-bg)] transition-colors">
                            <td class="p-4">
                                <div class="max-w-xs">
                                    <div class="font-medium theme-text-primary truncate" title="<?php echo e($article->title); ?>">
                                        <?php echo e($article->title); ?>

                                    </div>
                                    <?php if($article->source_type === 'pdf'): ?>
                                        <span class="text-[10px] px-1.5 py-0.5 rounded-full bg-[#AA5F3C]/10 text-[#AA5F3C]">PDF</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td class="p-4 theme-text-secondary hidden md:table-cell whitespace-nowrap">
                                <?php echo e($article->user?->name ?? 'N/A'); ?>

                            </td>
                            <td class="p-4 hidden sm:table-cell">
                                <?php
                                    $statusColors = [
                                        'ready' => 'border-[#8B9A7A]/30 text-[#8B9A7A] bg-[#8B9A7A]/5',
                                        'queued' => 'border-[#D4A76A]/30 text-[#D4A76A] bg-[#D4A76A]/5',
                                        'fetching' => 'border-[#D4A76A]/30 text-[#D4A76A] bg-[#D4A76A]/5',
                                        'extracting' => 'border-[#D4A76A]/30 text-[#D4A76A] bg-[#D4A76A]/5',
                                        'failed' => 'border-[color:var(--error)]/30 text-[color:var(--error)] bg-[color:var(--error)]/5',
                                    ];
                                    $status = $article->processing_status ?? 'ready';
                                ?>
                                <span class="text-xs px-2 py-1 rounded-full border <?php echo e($statusColors[$status] ?? 'theme-border-primary theme-text-muted'); ?>">
                                    <?php echo e($status); ?>

                                </span>
                            </td>
                            <td class="p-4 theme-text-muted hidden lg:table-cell">
                                <?php if($article->source_domain && $article->source_domain !== 'pdf-upload'): ?>
                                    <?php echo e($article->source_domain); ?>

                                <?php else: ?>
                                    <span class="theme-text-muted">—</span>
                                <?php endif; ?>
                            </td>
                            <td class="p-4 theme-text-muted hidden lg:table-cell whitespace-nowrap">
                                <?php echo e($article->summaries_count); ?> summaries
                            </td>
                            <td class="p-4 theme-text-muted whitespace-nowrap hidden xl:table-cell">
                                <?php echo e($article->created_at?->format('d M Y')); ?>

                            </td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <tr>
                            <td colspan="6" class="p-8 text-center theme-text-muted">Tidak ada artikel ditemukan.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="p-4 border-t theme-border-primary">
            <?php echo e($articles->links()); ?>

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
<?php endif; ?>
<?php /**PATH C:\laragon\www\myartikel\resources\views\admin\articles.blade.php ENDPATH**/ ?>