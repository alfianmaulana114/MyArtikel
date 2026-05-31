<aside class="h-full flex flex-col">
    <div class="flex h-16 items-center justify-between gap-3 px-6 border-b theme-border-primary">
        <a href="<?php echo e(route('dashboard')); ?>" class="flex items-center gap-3 min-w-0">
            <?php if (isset($component)) { $__componentOriginal8892e718f3d0d7a916180885c6f012e7 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal8892e718f3d0d7a916180885c6f012e7 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.application-logo','data' => ['class' => 'block h-9 w-auto fill-current theme-text-primary']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('application-logo'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'block h-9 w-auto fill-current theme-text-primary']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal8892e718f3d0d7a916180885c6f012e7)): ?>
<?php $attributes = $__attributesOriginal8892e718f3d0d7a916180885c6f012e7; ?>
<?php unset($__attributesOriginal8892e718f3d0d7a916180885c6f012e7); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal8892e718f3d0d7a916180885c6f012e7)): ?>
<?php $component = $__componentOriginal8892e718f3d0d7a916180885c6f012e7; ?>
<?php unset($__componentOriginal8892e718f3d0d7a916180885c6f012e7); ?>
<?php endif; ?>
            <div class="min-w-0">
                <div class="font-semibold theme-text-primary truncate leading-tight">
                    <?php echo e(config('app.name', 'MyArtikel')); ?>

                </div>
                <div class="text-xs theme-text-muted truncate">
                    <?php echo e(auth()->user()?->is_admin ? 'Admin Panel' : 'Personal reading hub'); ?>

                </div>
            </div>
        </a>

        <button
            type="button"
            class="lg:hidden inline-flex h-9 w-9 items-center justify-center rounded-xl border theme-border-primary bg-[color:var(--surface-primary)] hover:bg-[color:var(--hover-bg)] transition"
            @click="sidebarOpen = false"
            aria-label="Tutup menu"
        >
            <svg class="h-4 w-4 theme-text-primary" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>
    </div>

    <?php
        $linkBase = 'group flex items-center gap-3 rounded-xl px-3 py-2 text-sm font-medium transition';
        $linkIdle = 'theme-text-secondary hover:bg-[color:var(--hover-bg)] hover:text-[color:var(--text-primary)]';
        $linkActive = 'bg-[color:var(--hover-bg)] text-[color:var(--text-primary)]';
        $iconBase = 'h-5 w-5 opacity-80 group-hover:opacity-100';
    ?>

    <?php if(auth()->user()?->is_admin): ?>
        
        <nav class="flex-1 overflow-y-auto px-4 py-4 space-y-1">
            <div class="px-3 pb-2 text-xs font-semibold tracking-wider uppercase theme-text-muted">
                Admin
            </div>

            <a
                href="<?php echo e(route('admin.dashboard')); ?>"
                class="<?php echo e($linkBase); ?> <?php echo e(request()->routeIs('admin.dashboard') ? $linkActive : $linkIdle); ?>"
            >
                <svg class="<?php echo e($iconBase); ?>" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0h6" />
                </svg>
                Dashboard
            </a>

            <a
                href="<?php echo e(route('admin.users')); ?>"
                class="<?php echo e($linkBase); ?> <?php echo e(request()->routeIs('admin.users*') ? $linkActive : $linkIdle); ?>"
            >
                <svg class="<?php echo e($iconBase); ?>" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                </svg>
                Users
            </a>

            <a
                href="<?php echo e(route('admin.articles')); ?>"
                class="<?php echo e($linkBase); ?> <?php echo e(request()->routeIs('admin.articles*') ? $linkActive : $linkIdle); ?>"
            >
                <svg class="<?php echo e($iconBase); ?>" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                </svg>
                Articles
            </a>

            <a
                href="<?php echo e(route('admin.system')); ?>"
                class="<?php echo e($linkBase); ?> <?php echo e(request()->routeIs('admin.system*') ? $linkActive : $linkIdle); ?>"
            >
                <svg class="<?php echo e($iconBase); ?>" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2a2 2 0 012-2h2a2 2 0 012 2v2m-7 0h8m-8 0a2 2 0 01-2-2V7a2 2 0 012-2h8a2 2 0 012 2v8a2 2 0 01-2 2" />
                </svg>
                System
            </a>
        </nav>
    <?php else: ?>
        
        <nav class="flex-1 overflow-y-auto px-4 py-4 space-y-1">
            <div class="px-3 pb-2 text-xs font-semibold tracking-wider uppercase theme-text-muted">
                Library
            </div>

            <a
                href="<?php echo e(route('dashboard')); ?>"
                class="<?php echo e($linkBase); ?> <?php echo e(request()->routeIs('dashboard') ? $linkActive : $linkIdle); ?>"
            >
                <svg class="<?php echo e($iconBase); ?>" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0h6" />
                </svg>
                Dashboard
            </a>

            <a
                href="<?php echo e(route('articles.index')); ?>"
                class="<?php echo e($linkBase); ?> <?php echo e(request()->routeIs('articles.*') ? $linkActive : $linkIdle); ?>"
            >
                <svg class="<?php echo e($iconBase); ?>" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                </svg>
                Articles
            </a>

            <a
                href="<?php echo e(route('projects.index')); ?>"
                class="<?php echo e($linkBase); ?> <?php echo e(request()->routeIs('projects.*') ? $linkActive : $linkIdle); ?>"
            >
                <svg class="<?php echo e($iconBase); ?>" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" />
                </svg>
                Workspace
            </a>
        </nav>
    <?php endif; ?>

    <div class="px-4 py-4 border-t theme-border-primary">
        <a
            href="<?php echo e(route('profile.edit')); ?>"
            class="<?php echo e($linkBase); ?> <?php echo e(request()->routeIs('profile.*') ? $linkActive : $linkIdle); ?>"
        >
            <svg class="<?php echo e($iconBase); ?>" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0z" />
            </svg>
            Settings
        </a>

        <form method="POST" action="<?php echo e(route('logout')); ?>" class="mt-2">
            <?php echo csrf_field(); ?>
            <button
                type="submit"
                class="<?php echo e($linkBase); ?> w-full <?php echo e($linkIdle); ?>"
            >
                <svg class="<?php echo e($iconBase); ?>" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h6a2 2 0 012 2v1" />
                </svg>
                Logout
            </button>
        </form>
    </div>
</aside>
<?php /**PATH C:\laragon\www\myartikel\resources\views/layouts/sidebar.blade.php ENDPATH**/ ?>