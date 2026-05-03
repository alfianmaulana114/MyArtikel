<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

        <!-- Frieren Dark Mode Styles -->
        <link rel="stylesheet" href="{{ asset('css/theme/frieren-dark-mode.css') }}">
        
        <!-- No-Flash Dark Mode Loader (Must be first script) -->
        <script src="{{ asset('js/theme/frieren-dark-mode-loader.js') }}"></script>

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @stack('styles')
    </head>
    <body class="font-sans antialiased">
        <div
            x-data="{ sidebarOpen: false }"
            class="min-h-screen theme-bg-primary"
        >
            <!-- Mobile Sidebar (Drawer) -->
            <div
                x-show="sidebarOpen"
                x-cloak
                class="fixed inset-0 z-40 lg:hidden"
            >
                <div
                    class="absolute inset-0 bg-black/50"
                    @click="sidebarOpen = false"
                ></div>

                <div
                    class="absolute inset-y-0 left-0 w-72 theme-bg-secondary border-r theme-border-primary theme-shadow-secondary"
                >
                    @include('layouts.sidebar')
                </div>
            </div>

            <!-- Desktop Sidebar -->
            <div
                class="hidden lg:fixed lg:inset-y-0 lg:flex lg:w-72 lg:flex-col theme-bg-secondary border-r theme-border-primary"
            >
                @include('layouts.sidebar')
            </div>

            <!-- Main -->
            <div class="lg:pl-72">
                <!-- Topbar -->
                <header class="sticky top-0 z-30 theme-bg-primary border-b theme-border-primary">
                    <div class="flex h-16 items-center gap-3 px-4 sm:px-6 lg:px-8">
                        <button
                            type="button"
                            class="lg:hidden inline-flex h-10 w-10 items-center justify-center rounded-xl border theme-border-primary bg-[color:var(--surface-primary)] hover:bg-[color:var(--hover-bg)] transition"
                            @click="sidebarOpen = true"
                            aria-label="Buka menu"
                        >
                            <svg class="h-5 w-5 theme-text-primary" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                            </svg>
                        </button>

                        <div class="min-w-0 flex-1">
                            @isset($header)
                                {{ $header }}
                            @else
                                <div class="font-semibold theme-text-primary truncate">
                                    {{ config('app.name', 'Laravel') }}
                                </div>
                            @endisset
                        </div>

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

                            <select
                                data-theme-select
                                class="h-10 rounded-xl border theme-border-primary bg-[color:var(--surface-primary)] px-3 text-sm theme-text-primary hover:bg-[color:var(--hover-bg)] transition"
                                aria-label="Tema"
                            >
                                <option value="auto">System</option>
                                <option value="light">Light</option>
                                <option value="dark">Dark</option>
                            </select>

                            <x-dropdown align="right" width="48">
                                <x-slot name="trigger">
                                    <button class="btn btn-secondary">
                                        <div class="max-w-[12rem] truncate">{{ Auth::user()->name }}</div>

                                        <div class="ms-1">
                                            <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                            </svg>
                                        </div>
                                    </button>
                                </x-slot>

                                <x-slot name="content">
                                    <x-dropdown-link :href="route('profile.edit')">
                                        {{ __('Profile') }}
                                    </x-dropdown-link>

                                    <form method="POST" action="{{ route('logout') }}">
                                        @csrf

                                        <x-dropdown-link :href="route('logout')"
                                                onclick="event.preventDefault(); this.closest('form').submit();">
                                            {{ __('Log Out') }}
                                        </x-dropdown-link>
                                    </form>
                                </x-slot>
                            </x-dropdown>
                        </div>
                    </div>
                </header>

                <!-- Content -->
                <main class="px-4 py-6 sm:px-6 lg:px-8">
                    @isset($slot)
                        {{ $slot }}
                    @else
                        @yield('content')
                    @endisset
                </main>
            </div>
        </div>

        @stack('scripts')
    </body>
</html>
