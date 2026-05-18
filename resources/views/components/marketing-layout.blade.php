@props([
    'title' => 'MyArtikel — Personal reading hub',
    'description' => 'Simpan artikel, baca dengan clean reader, beri tag, buat catatan, dan rangkum tanpa distraksi.',
])

<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ $title }}</title>
        <meta name="description" content="{{ $description }}">

        <meta property="og:title" content="{{ $title }}">
        <meta property="og:description" content="{{ $description }}">
        <meta property="og:type" content="website">
        <meta property="og:url" content="{{ url('/') }}">

        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

        <!-- Frieren theme tokens (light/dark/auto) -->
        <link rel="stylesheet" href="{{ asset('css/theme/frieren-dark-mode.css') }}">
        <script src="{{ asset('js/theme/frieren-dark-mode-loader.js') }}"></script>

        @isset($head)
            {{ $head }}
        @endisset

        @vite(['resources/css/app.css', 'resources/js/app.js'])
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
                    <a href="{{ url('/') }}" class="flex items-center gap-3 min-w-0">
                        <x-application-logo class="h-8 w-8 fill-current theme-text-primary" />
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

                        @auth
                            <a href="{{ route('dashboard') }}" class="btn btn-primary">
                                Buka Dashboard
                            </a>
                        @else
                            <a href="{{ route('login') }}" class="btn btn-secondary">
                                Login
                            </a>
                            @if (Route::has('register'))
                                <a href="{{ route('register') }}" class="btn btn-primary hidden sm:inline-flex">
                                    Buat Akun
                                </a>
                            @endif
                        @endauth
                    </div>
                </div>
            </div>
        </header>

        <main id="content" class="pt-16">
            {{ $slot }}
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
                        &copy; {{ now()->year }} MyArtikel
                    </div>
                </div>
            </div>
        </footer>
    </body>
</html>
