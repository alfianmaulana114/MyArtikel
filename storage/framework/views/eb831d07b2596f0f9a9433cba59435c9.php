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
            <h2 class="font-semibold text-xl theme-text-primary leading-tight">Manajemen User</h2>
            <p class="text-sm theme-text-muted">Kelola semua user platform — aktifkan, nonaktifkan, atau hapus.</p>
        </div>
     <?php $__env->endSlot(); ?>

    <?php if(session('status')): ?>
        <div class="mb-4 text-sm rounded-xl p-3 bg-[color:var(--bg-tertiary)] text-[#8B9A7A] font-medium">
            <?php echo e(session('status')); ?>

        </div>
    <?php endif; ?>
    <?php if(session('error')): ?>
        <div class="mb-4 text-sm rounded-xl p-3 bg-[color:var(--error-bg)] text-[color:var(--error)] font-medium">
            <?php echo e(session('error')); ?>

        </div>
    <?php endif; ?>

    <div class="card overflow-hidden">
        <div class="p-4 border-b theme-border-primary">
            <form method="GET" action="<?php echo e(route('admin.users')); ?>" class="flex flex-col sm:flex-row gap-3 sm:items-center">
                <div class="flex-1">
                    <input
                        name="q"
                        type="text"
                        value="<?php echo e(request('q')); ?>"
                        placeholder="Cari nama / email…"
                        class="block w-full rounded-xl shadow-sm border theme-border-primary bg-[color:var(--surface-primary)] text-[color:var(--text-primary)] placeholder:text-[color:var(--text-muted)] focus:border-[#AA5F3C] focus:ring-[#AA5F3C] px-4 py-2"
                    />
                </div>
                <div>
                    <select name="status" class="block w-full rounded-xl shadow-sm border theme-border-primary bg-[color:var(--surface-primary)] text-[color:var(--text-primary)] focus:border-[#AA5F3C] focus:ring-[#AA5F3C] px-3 py-2">
                        <option value="">Semua status</option>
                        <option value="active" <?php if(request('status') === 'active'): echo 'selected'; endif; ?>>Aktif</option>
                        <option value="inactive" <?php if(request('status') === 'inactive'): echo 'selected'; endif; ?>>Nonaktif</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-secondary whitespace-nowrap">Filter</button>
                <?php if(request()->anyFilled(['q', 'status'])): ?>
                    <a href="<?php echo e(route('admin.users')); ?>" class="text-sm theme-text-muted hover:theme-text-primary whitespace-nowrap">Reset</a>
                <?php endif; ?>
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b theme-border-primary text-left">
                        <th class="p-4 font-medium theme-text-muted">User</th>
                        <th class="p-4 font-medium theme-text-muted hidden md:table-cell">Email</th>
                        <th class="p-4 font-medium theme-text-muted">Articles</th>
                        <th class="p-4 font-medium theme-text-muted hidden sm:table-cell">Status</th>
                        <th class="p-4 font-medium theme-text-muted hidden lg:table-cell">Registered</th>
                        <th class="p-4 font-medium theme-text-muted text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y theme-border-primary">
                    <?php $__empty_1 = true; $__currentLoopData = $users; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $user): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <tr class="hover:bg-[color:var(--hover-bg)] transition-colors">
                            <td class="p-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-full bg-[color:var(--bg-tertiary)] flex items-center justify-center text-xs font-semibold theme-text-secondary">
                                        <?php echo e(strtoupper(substr($user->name, 0, 2))); ?>

                                    </div>
                                    <div>
                                        <div class="font-medium theme-text-primary"><?php echo e($user->name); ?></div>
                                        <?php if($user->is_admin): ?>
                                            <span class="text-[10px] px-1.5 py-0.5 rounded-full bg-[#AA5F3C]/10 text-[#AA5F3C] font-medium">Admin</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>
                            <td class="p-4 theme-text-secondary hidden md:table-cell"><?php echo e($user->email); ?></td>
                            <td class="p-4">
                                <span class="font-medium theme-text-primary"><?php echo e($user->articles_count); ?></span>
                            </td>
                            <td class="p-4 hidden sm:table-cell">
                                <span class="text-xs px-2 py-1 rounded-full border <?php echo e($user->is_active ? 'border-[#8B9A7A]/30 text-[#8B9A7A] bg-[#8B9A7A]/5' : 'border-[color:var(--error)]/30 text-[color:var(--error)] bg-[color:var(--error)]/5'); ?>">
                                    <?php echo e($user->is_active ? 'Aktif' : 'Nonaktif'); ?>

                                </span>
                            </td>
                            <td class="p-4 theme-text-muted whitespace-nowrap hidden lg:table-cell"><?php echo e($user->created_at->format('d M Y')); ?></td>
                            <td class="p-4">
                                <div class="flex items-center justify-end gap-2">
                                    <?php if(!$user->is_admin): ?>
                                        <form method="POST" action="<?php echo e(route('admin.users.toggle', $user)); ?>" class="inline">
                                            <?php echo csrf_field(); ?>
                                            <button type="submit" class="text-xs px-3 py-1.5 rounded-lg border theme-border-primary hover:bg-[color:var(--hover-bg)] theme-text-secondary transition-colors">
                                                <?php echo e($user->is_active ? 'Nonaktifkan' : 'Aktifkan'); ?>

                                            </button>
                                        </form>
                                        <form method="POST" action="<?php echo e(route('admin.users.delete', $user)); ?>" class="inline"
                                            onsubmit="return confirm('Hapus user <?php echo e(addslashes($user->name)); ?> dan semua datanya?')">
                                            <?php echo csrf_field(); ?>
                                            <?php echo method_field('DELETE'); ?>
                                            <button type="submit" class="text-xs px-3 py-1.5 rounded-lg border border-[color:var(--error)]/30 text-[color:var(--error)] hover:bg-[color:var(--error)]/5 transition-colors">
                                                Hapus
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <span class="text-xs theme-text-muted">—</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <tr>
                            <td colspan="6" class="p-8 text-center theme-text-muted">Tidak ada user ditemukan.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="p-4 border-t theme-border-primary">
            <?php echo e($users->links()); ?>

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
<?php /**PATH C:\laragon\www\myartikel\resources\views\admin\users.blade.php ENDPATH**/ ?>