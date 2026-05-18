<aside class="h-full flex flex-col">
    <div class="flex h-16 items-center justify-between gap-3 px-6 border-b theme-border-primary">
        <a href="{{ route('dashboard') }}" class="flex items-center gap-3 min-w-0">
            <x-application-logo class="block h-9 w-auto fill-current theme-text-primary" />
            <div class="min-w-0">
                <div class="font-semibold theme-text-primary truncate leading-tight">
                    {{ config('app.name', 'MyArtikel') }}
                </div>
                <div class="text-xs theme-text-muted truncate">
                    {{ auth()->user()?->is_admin ? 'Admin Panel' : 'Personal reading hub' }}
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

    @php
        $linkBase = 'group flex items-center gap-3 rounded-xl px-3 py-2 text-sm font-medium transition';
        $linkIdle = 'theme-text-secondary hover:bg-[color:var(--hover-bg)] hover:text-[color:var(--text-primary)]';
        $linkActive = 'bg-[color:var(--hover-bg)] text-[color:var(--text-primary)]';
        $iconBase = 'h-5 w-5 opacity-80 group-hover:opacity-100';
    @endphp

    @if (auth()->user()?->is_admin)
        {{-- Admin Navigation --}}
        <nav class="flex-1 overflow-y-auto px-4 py-4 space-y-1">
            <div class="px-3 pb-2 text-xs font-semibold tracking-wider uppercase theme-text-muted">
                Admin
            </div>

            <a
                href="{{ route('admin.dashboard') }}"
                class="{{ $linkBase }} {{ request()->routeIs('admin.dashboard') ? $linkActive : $linkIdle }}"
            >
                <svg class="{{ $iconBase }}" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0h6" />
                </svg>
                Dashboard
            </a>

            <a
                href="{{ route('admin.users') }}"
                class="{{ $linkBase }} {{ request()->routeIs('admin.users*') ? $linkActive : $linkIdle }}"
            >
                <svg class="{{ $iconBase }}" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                </svg>
                Users
            </a>

            <a
                href="{{ route('admin.articles') }}"
                class="{{ $linkBase }} {{ request()->routeIs('admin.articles*') ? $linkActive : $linkIdle }}"
            >
                <svg class="{{ $iconBase }}" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                </svg>
                Articles
            </a>

            <a
                href="{{ route('admin.system') }}"
                class="{{ $linkBase }} {{ request()->routeIs('admin.system*') ? $linkActive : $linkIdle }}"
            >
                <svg class="{{ $iconBase }}" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2a2 2 0 012-2h2a2 2 0 012 2v2m-7 0h8m-8 0a2 2 0 01-2-2V7a2 2 0 012-2h8a2 2 0 012 2v8a2 2 0 01-2 2" />
                </svg>
                System
            </a>
        </nav>
    @else
        {{-- User Navigation --}}
        <nav class="flex-1 overflow-y-auto px-4 py-4 space-y-1">
            <div class="px-3 pb-2 text-xs font-semibold tracking-wider uppercase theme-text-muted">
                Library
            </div>

            <a
                href="{{ route('dashboard') }}"
                class="{{ $linkBase }} {{ request()->routeIs('dashboard') ? $linkActive : $linkIdle }}"
            >
                <svg class="{{ $iconBase }}" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0h6" />
                </svg>
                Dashboard
            </a>

            <a
                href="{{ route('articles.index') }}"
                class="{{ $linkBase }} {{ request()->routeIs('articles.*') ? $linkActive : $linkIdle }}"
            >
                <svg class="{{ $iconBase }}" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                </svg>
                Articles
            </a>

            <a
                href="{{ route('projects.index') }}"
                class="{{ $linkBase }} {{ request()->routeIs('projects.*') ? $linkActive : $linkIdle }}"
            >
                <svg class="{{ $iconBase }}" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" />
                </svg>
                Workspace
            </a>
        </nav>
    @endif

    <div class="px-4 py-4 border-t theme-border-primary">
        <a
            href="{{ route('profile.edit') }}"
            class="{{ $linkBase }} {{ request()->routeIs('profile.*') ? $linkActive : $linkIdle }}"
        >
            <svg class="{{ $iconBase }}" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0z" />
            </svg>
            Settings
        </a>

        <form method="POST" action="{{ route('logout') }}" class="mt-2">
            @csrf
            <button
                type="submit"
                class="{{ $linkBase }} w-full {{ $linkIdle }}"
            >
                <svg class="{{ $iconBase }}" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h6a2 2 0 012 2v1" />
                </svg>
                Logout
            </button>
        </form>
    </div>
</aside>
