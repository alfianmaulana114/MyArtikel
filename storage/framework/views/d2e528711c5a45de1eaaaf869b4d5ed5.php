<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames(([
    'title' => 'MyArtikel — Personal reading hub',
    'description' => 'Simpan artikel, baca dengan clean reader, beri tag, buat catatan, dan rangkum tanpa distraksi.',
]));

foreach ($attributes->all() as $__key => $__value) {
    if (in_array($__key, $__propNames)) {
        $$__key = $$__key ?? $__value;
    } else {
        $__newAttributes[$__key] = $__value;
    }
}

$attributes = new \Illuminate\View\ComponentAttributeBag($__newAttributes);

unset($__propNames);
unset($__newAttributes);

foreach (array_filter(([
    'title' => 'MyArtikel — Personal reading hub',
    'description' => 'Simpan artikel, baca dengan clean reader, beri tag, buat catatan, dan rangkum tanpa distraksi.',
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">

        <title><?php echo e($title); ?></title>
        <meta name="description" content="<?php echo e($description); ?>">

        <meta property="og:title" content="<?php echo e($title); ?>">
        <meta property="og:description" content="<?php echo e($description); ?>">
        <meta property="og:type" content="website">
        <meta property="og:url" content="<?php echo e(url('/')); ?>">

        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

        <!-- Frieren theme tokens (light/dark/auto) -->
        <link rel="stylesheet" href="<?php echo e(asset('css/theme/frieren-dark-mode.css')); ?>">
        <script src="<?php echo e(asset('js/theme/frieren-dark-mode-loader.js')); ?>"></script>

        <?php if(isset($head)): ?>
            <?php echo e($head); ?>

        <?php endif; ?>

        <?php echo app('Illuminate\Foundation\Vite')(['resources/css/app.css', 'resources/js/app.js']); ?>
    </head>
    <body class="font-sans antialiased theme-bg-primary theme-text-primary">
        <a
            href="#content"
            class="sr-only focus:not-sr-only focus:fixed focus:top-4 focus:left-4 focus:z-50 btn btn-secondary"
        >
            Lewati ke konten
        </a>

        <header class="fixed top-0 inset-x-0 z-50 border-b theme-border-primary marketing-nav">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="flex h-16 items-center justify-between gap-3">
                    <a href="<?php echo e(url('/')); ?>" class="flex items-center gap-3 min-w-0">
                        <?php if (isset($component)) { $__componentOriginal8892e718f3d0d7a916180885c6f012e7 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal8892e718f3d0d7a916180885c6f012e7 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.application-logo','data' => ['class' => 'h-8 w-8 fill-current theme-text-primary']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('application-logo'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'h-8 w-8 fill-current theme-text-primary']); ?>
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
                            <div class="font-semibold theme-text-primary truncate leading-tight">MyArtikel</div>
                            <div class="text-xs theme-text-muted truncate">Library bacaan</div>
                        </div>
                    </a>

                    <nav class="hidden md:flex items-center gap-6 text-sm theme-text-secondary">
                        <a href="#cara-kerja" class="nav-link">Cara kerja</a>
                        <a href="#fitur" class="nav-link">Fitur</a>
                        <a href="#faq" class="nav-link">FAQ</a>
                    </nav>

                    <div class="flex items-center gap-2">
                        <button
                            type="button"
                            data-theme-toggle
                            class="inline-flex h-10 w-10 items-center justify-center rounded-xl border theme-border-primary bg-[color:var(--surface-primary)] hover:bg-[color:var(--hover-bg)] transition"
                            aria-label="Toggle tema"
                            aria-pressed="false"
                        >
                            <svg data-icon="sun" class="h-5 w-5 theme-text-primary" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v2m0 14v2m9-9h-2M5 12H3m15.364-6.364-1.414 1.414M7.05 16.95l-1.414 1.414m0-11.314L7.05 7.05m9.9 9.9 1.414 1.414" />
                                <circle cx="12" cy="12" r="4" stroke-width="2" />
                            </svg>
                            <svg data-icon="moon" class="hidden h-5 w-5 theme-text-primary" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12.79A9 9 0 1111.21 3a7 7 0 009.79 9.79z" />
                            </svg>
                        </button>

                        <?php if(auth()->guard()->check()): ?>
                            <a href="<?php echo e(route('dashboard')); ?>" class="btn btn-primary">
                                Buka Dashboard
                            </a>
                        <?php else: ?>
                            <a href="<?php echo e(route('login')); ?>" class="btn btn-secondary">
                                Login
                            </a>
                            <?php if(Route::has('register')): ?>
                                <a href="<?php echo e(route('register')); ?>" class="btn btn-primary hidden sm:inline-flex">
                                    Buat Akun
                                </a>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </header>

        <main id="content" class="pt-16">
            <?php echo e($slot); ?>

        </main>

        <footer class="border-t theme-border-primary theme-bg-secondary">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-10">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-6">
                    <div>
                        <div class="font-semibold theme-text-primary">MyArtikel</div>
                        <div class="mt-1 text-sm theme-text-muted">
                            Tenang, rapi, dan dibuat untuk koleksi bacaan jangka panjang.
                        </div>
                    </div>
                    <div class="text-sm theme-text-muted">
                        &copy; <?php echo e(now()->year); ?> MyArtikel
                    </div>
                </div>
            </div>
        </footer>
    </body>
</html>
<?php /**PATH C:\laragon\www\myartikel\resources\views\components\marketing-layout.blade.php ENDPATH**/ ?>