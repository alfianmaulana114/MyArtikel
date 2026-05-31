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
            <h2 class="font-semibold text-xl theme-text-primary leading-tight">Admin Dashboard</h2>
            <p class="text-sm theme-text-muted">Ringkasan & monitoring platform secara global.</p>
        </div>
     <?php $__env->endSlot(); ?>

    
    <div class="grid grid-cols-2 md:grid-cols-3 gap-4 mb-6">
        <div class="card p-4 text-center">
            <div class="text-2xl font-bold theme-text-primary"><?php echo e($totalUsers); ?></div>
            <div class="text-xs theme-text-muted mt-1">Total Users</div>
            <div class="text-[10px] theme-text-muted mt-0.5"><?php echo e($activeUsers); ?> aktif · <?php echo e($inactiveUsers); ?> nonaktif</div>
        </div>
        <div class="card p-4 text-center">
            <div class="text-2xl font-bold theme-text-primary"><?php echo e($totalArticles); ?></div>
            <div class="text-xs theme-text-muted mt-1">Total Articles</div>
            <div class="text-[10px] theme-text-muted mt-0.5"><?php echo e($articleStats['ready']); ?> ready · <?php echo e($articleStats['processing']); ?> proc · <?php echo e($articleStats['failed']); ?> failed</div>
        </div>
        <div class="card p-4 text-center">
            <div class="text-2xl font-bold theme-text-primary"><?php echo e($totalSummaries); ?></div>
            <div class="text-xs theme-text-muted mt-1">Summaries</div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
        
        <div class="lg:col-span-2 card p-6" x-data="{
            trends: <?php echo e(Js::from($monthlyTrends)); ?>,
            init() {
                this.$nextTick(() => this.renderChart());
            },
            renderChart() {
                const canvas = this.$refs.chart;
                if (!canvas) return;
                const ctx = canvas.getContext('2d');
                const labels = this.trends.map(t => t.month);
                const articles = this.trends.map(t => t.articles);
                const users = this.trends.map(t => t.users);
                const maxVal = Math.max(...articles, ...users, 1);
                const w = canvas.width = canvas.offsetWidth * 2;
                const h = canvas.height = canvas.offsetHeight * 2;
                ctx.scale(2, 2);
                const cw = canvas.offsetWidth, ch = canvas.offsetHeight;
                const pad = { top: 20, right: 20, bottom: 50, left: 40 };
                const pw = cw - pad.left - pad.right;
                const ph = ch - pad.top - pad.bottom;

                ctx.clearRect(0, 0, cw, ch);

                // Grid
                ctx.strokeStyle = getComputedStyle(document.documentElement).getPropertyValue('--border-primary').trim() || '#e5e7eb';
                ctx.lineWidth = 0.5;
                for (let i = 0; i <= 4; i++) {
                    const y = pad.top + (ph / 4) * i;
                    ctx.beginPath();
                    ctx.moveTo(pad.left, y);
                    ctx.lineTo(cw - pad.right, y);
                    ctx.stroke();
                    const val = Math.round(maxVal - (maxVal / 4) * i);
                    ctx.fillStyle = getComputedStyle(document.documentElement).getPropertyValue('--text-muted').trim() || '#9ca3af';
                    ctx.font = '10px sans-serif';
                    ctx.fillText(val, 2, y + 3);
                }

                // X labels
                ctx.fillStyle = getComputedStyle(document.documentElement).getPropertyValue('--text-muted').trim() || '#9ca3af';
                ctx.font = '9px sans-serif';
                ctx.textAlign = 'center';
                labels.forEach((l, i) => {
                    const x = pad.left + (pw / (labels.length - 1)) * i;
                    ctx.fillText(l, x, ch - 5);
                });

                const drawLine = (data, color) => {
                    ctx.strokeStyle = color;
                    ctx.lineWidth = 2;
                    ctx.beginPath();
                    data.forEach((v, i) => {
                        const x = pad.left + (pw / (data.length - 1)) * i;
                        const y = pad.top + ph - (v / maxVal) * ph;
                        if (i === 0) ctx.moveTo(x, y);
                        else ctx.lineTo(x, y);
                    });
                    ctx.stroke();

                    // Dots
                    data.forEach((v, i) => {
                        const x = pad.left + (pw / (data.length - 1)) * i;
                        const y = pad.top + ph - (v / maxVal) * ph;
                        ctx.fillStyle = color;
                        ctx.beginPath();
                        ctx.arc(x, y, 3, 0, Math.PI * 2);
                        ctx.fill();
                    });
                };

                drawLine(articles, '#AA5F3C');
                drawLine(users, '#8B9A7A');
            },
            resizeHandler() { this.renderChart(); }
        }" x-init="window.addEventListener('resize', resizeHandler)" class="overflow-hidden">
            <h3 class="font-semibold theme-text-primary mb-4">Tren Bulanan (12 Bulan)</h3>
            <div class="flex items-center gap-4 mb-3 text-xs">
                <span class="flex items-center gap-1"><span class="w-3 h-0.5 inline-block rounded" style="background:#AA5F3C"></span> Articles</span>
                <span class="flex items-center gap-1"><span class="w-3 h-0.5 inline-block rounded" style="background:#8B9A7A"></span> Users</span>
            </div>
            <canvas x-ref="chart" class="w-full" style="height:250px"></canvas>
        </div>

        
        <div class="card p-6">
            <h3 class="font-semibold theme-text-primary mb-4">Top Contributors</h3>
            <div class="space-y-3">
                <?php $__empty_1 = true; $__currentLoopData = $topUsers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $i => $u): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2 min-w-0">
                            <span class="text-xs font-semibold theme-text-muted w-5"><?php echo e($i + 1); ?></span>
                            <span class="text-sm theme-text-primary truncate"><?php echo e($u->name); ?></span>
                        </div>
                        <span class="text-xs font-medium theme-text-secondary"><?php echo e($u->articles_count); ?> articles</span>
                    </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <div class="text-sm theme-text-muted">Belum ada data.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        
        <div class="card p-6">
            <h3 class="font-semibold theme-text-primary mb-4">User Terbaru</h3>
            <div class="space-y-3">
                <?php $__empty_1 = true; $__currentLoopData = $recentUsers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $u): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <div class="flex items-center justify-between">
                        <div class="min-w-0">
                            <div class="text-sm font-medium theme-text-primary truncate"><?php echo e($u->name); ?></div>
                            <div class="text-xs theme-text-muted truncate"><?php echo e($u->email); ?></div>
                        </div>
                        <span class="text-xs theme-text-muted whitespace-nowrap"><?php echo e($u->created_at->format('d M Y')); ?></span>
                    </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <div class="text-sm theme-text-muted">Belum ada user.</div>
                <?php endif; ?>
            </div>
        </div>

        
        <div class="card p-6">
            <h3 class="font-semibold theme-text-primary mb-4">Status Artikel</h3>
            <div class="space-y-3">
                <div class="flex items-center justify-between">
                    <span class="text-sm theme-text-secondary">Ready</span>
                    <span class="text-sm font-semibold text-[#8B9A7A]"><?php echo e($articleStats['ready']); ?></span>
                </div>
                <div class="w-full bg-[var(--border-primary)] rounded-full h-2">
                    <div class="bg-[#8B9A7A] h-2 rounded-full" style="width: <?php echo e($totalArticles > 0 ? round($articleStats['ready'] / $totalArticles * 100) : 0); ?>%"></div>
                </div>
                <div class="flex items-center justify-between mt-3">
                    <span class="text-sm theme-text-secondary">Processing</span>
                    <span class="text-sm font-semibold text-[#D4A76A]"><?php echo e($articleStats['processing']); ?></span>
                </div>
                <div class="w-full bg-[var(--border-primary)] rounded-full h-2">
                    <div class="bg-[#D4A76A] h-2 rounded-full" style="width: <?php echo e($totalArticles > 0 ? round($articleStats['processing'] / $totalArticles * 100) : 0); ?>%"></div>
                </div>
                <div class="flex items-center justify-between mt-3">
                    <span class="text-sm theme-text-secondary">Failed</span>
                    <span class="text-sm font-semibold text-[color:var(--error)]"><?php echo e($articleStats['failed']); ?></span>
                </div>
                <div class="w-full bg-[var(--border-primary)] rounded-full h-2">
                    <div class="bg-[color:var(--error)] h-2 rounded-full" style="width: <?php echo e($totalArticles > 0 ? round($articleStats['failed'] / $totalArticles * 100) : 0); ?>%"></div>
                </div>
            </div>
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
<?php /**PATH C:\laragon\www\myartikel\resources\views\admin\dashboard.blade.php ENDPATH**/ ?>