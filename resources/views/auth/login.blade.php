<x-guest-layout>
    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}">
        @csrf

        <div class="mb-6 text-center">
            <h1 class="text-2xl font-semibold tracking-tight theme-text-primary">
                {{ __('Log in') }}
            </h1>
            <p class="mt-1 text-sm theme-text-muted">
                Selamat datang kembali. Masuk untuk melanjutkan membaca dan menyimpan artikel.
            </p>
        </div>

        <div class="space-y-4">
            <!-- Email Address -->
            <div>
                <x-input-label for="email" :value="__('Email')" />
                <x-text-input
                    id="email"
                    type="email"
                    name="email"
                    :value="old('email')"
                    required
                    autofocus
                    autocomplete="username"
                    aria-invalid="{{ $errors->has('email') ? 'true' : 'false' }}"
                    aria-describedby="{{ $errors->has('email') ? 'email-error' : '' }}"
                    class="mt-1"
                />
                <x-input-error id="email-error" :messages="$errors->get('email')" class="mt-2" />
            </div>

            <!-- Password -->
            <div>
                <x-input-label for="password" :value="__('Password')" />
                <x-text-input
                    id="password"
                    type="password"
                    name="password"
                    required
                    autocomplete="current-password"
                    aria-invalid="{{ $errors->has('password') ? 'true' : 'false' }}"
                    aria-describedby="{{ $errors->has('password') ? 'password-error' : '' }}"
                    class="mt-1"
                />
                <x-input-error id="password-error" :messages="$errors->get('password')" class="mt-2" />
            </div>

            <!-- Remember Me -->
            <div class="flex items-center justify-between gap-3 pt-1">
                <label for="remember_me" class="inline-flex items-center">
                    <input
                        id="remember_me"
                        type="checkbox"
                        class="rounded theme-border-primary text-[#AA5F3C] shadow-sm focus:ring-[#AA5F3C]"
                        name="remember"
                    >
                    <span class="ms-2 text-sm theme-text-muted">{{ __('Remember me') }}</span>
                </label>

                @if (Route::has('password.request'))
                    <a
                        class="text-sm underline underline-offset-4 theme-text-secondary hover:text-[color:var(--text-link-hover)] rounded-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#AA5F3C]"
                        href="{{ route('password.request') }}"
                    >
                        {{ __('Forgot your password?') }}
                    </a>
                @endif
            </div>
        </div>

        <div class="mt-6">
            <x-primary-button class="w-full">
                {{ __('Log in') }}
            </x-primary-button>
        </div>

        @if (Route::has('register'))
            <p class="mt-6 text-center text-sm theme-text-muted">
                Belum punya akun?
                <a
                    class="underline underline-offset-4 theme-text-secondary hover:text-[color:var(--text-link-hover)] rounded-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#AA5F3C]"
                    href="{{ route('register') }}"
                >
                    Daftar
                </a>
            </p>
        @endif
    </form>
</x-guest-layout>
