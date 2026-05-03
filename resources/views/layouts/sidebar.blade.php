<aside class="h-full flex flex-col">
    <div class="flex h-16 items-center justify-between gap-3 px-6 border-b theme-border-primary">
        <a href="{{ route('dashboard') }}" class="flex items-center gap-3 min-w-0">
            <x-application-logo class="block h-9 w-auto fill-current theme-text-primary" />
            <div class="min-w-0">
                <div class="font-semibold theme-text-primary truncate leading-tight">
                    {{ config('app.name', 'MyArtikel') }}
                </div>
                <div class="text-xs theme-text-muted truncate">
                    Personal reading hub
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
            href="{{ route('summaries.page') }}"
            class="{{ $linkBase }} {{ request()->routeIs('summaries.*') ? $linkActive : $linkIdle }}"
        >
            <svg class="{{ $iconBase }}" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01" />
            </svg>
            Summaries
        </a>

        <a
            href="{{ route('bookmarks.index') }}"
            class="{{ $linkBase }} {{ request()->routeIs('bookmarks.*') ? $linkActive : $linkIdle }}"
        >
            <svg class="{{ $iconBase }}" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v17l-7-4-7 4V5z" />
            </svg>
            Bookmarks
        </a>

        <div class="px-3 pt-6 pb-2 text-xs font-semibold tracking-wider uppercase theme-text-muted">
            Organize
        </div>

        <a
            href="{{ route('tags.index') }}"
            class="{{ $linkBase }} {{ request()->routeIs('tags.*') ? $linkActive : $linkIdle }}"
        >
            <svg class="{{ $iconBase }}" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M3 11l9 9a2 2 0 002.828 0l6.364-6.364a2 2 0 000-2.828l-9-9A2 2 0 0010.586 1H5a2 2 0 00-2 2v5.586A2 2 0 003 10.414V11z" />
            </svg>
            Tags
        </a>

        <a
            href="{{ route('notes.index') }}"
            class="{{ $linkBase }} {{ request()->routeIs('notes.*') ? $linkActive : $linkIdle }}"
        >
            <svg class="{{ $iconBase }}" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16h8M8 12h8m-6 8h6a2 2 0 002-2V6a2 2 0 00-2-2H8a2 2 0 00-2 2v12a2 2 0 002 2z" />
            </svg>
            Notes
        </a>

        <div class="px-3 pt-6 pb-2 text-xs font-semibold tracking-wider uppercase theme-text-muted">
            Tools
        </div>

        <a
            href="{{ route('pdf.export.form') }}"
            class="{{ $linkBase }} {{ request()->routeIs('pdf.export.*') ? $linkActive : $linkIdle }}"
        >
            <svg class="{{ $iconBase }}" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            Export PDF
        </a>

        <a
            href="{{ route('pdf.history') }}"
            class="{{ $linkBase }} {{ request()->routeIs('pdf.history') ? $linkActive : $linkIdle }}"
        >
            <svg class="{{ $iconBase }}" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            Export History
        </a>

        <a
            href="{{ route('jobs.monitoring') }}"
            class="{{ $linkBase }} {{ request()->routeIs('jobs.*') ? $linkActive : $linkIdle }}"
        >
            <svg class="{{ $iconBase }}" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2a2 2 0 012-2h2a2 2 0 012 2v2m-7 0h8m-8 0a2 2 0 01-2-2V7a2 2 0 012-2h8a2 2 0 012 2v8a2 2 0 01-2 2" />
            </svg>
            Jobs
        </a>
    </nav>

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
