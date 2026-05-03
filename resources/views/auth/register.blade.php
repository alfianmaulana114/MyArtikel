<x-guest-layout>
    <form method="POST" action="{{ route('register') }}">
        @csrf

        <div class="mb-6 text-center">
            <h1 class="text-2xl font-semibold tracking-tight theme-text-primary">
                {{ __('Register') }}
            </h1>
            <p class="mt-1 text-sm theme-text-muted">
                Buat akun untuk menyimpan artikel, bookmark, dan catatanmu.
            </p>
        </div>

        <div class="space-y-4">
            <!-- Name -->
            <div>
                <x-input-label for="name" :value="__('Name')" />
                <x-text-input
                    id="name"
                    type="text"
                    name="name"
                    :value="old('name')"
                    required
                    autofocus
                    autocomplete="name"
                    aria-invalid="{{ $errors->has('name') ? 'true' : 'false' }}"
                    aria-describedby="{{ $errors->has('name') ? 'name-error' : '' }}"
                    class="mt-1"
                />
                <x-input-error id="name-error" :messages="$errors->get('name')" class="mt-2" />
            </div>

            <!-- Email Address -->
            <div>
                <x-input-label for="email" :value="__('Email')" />
                <x-text-input
                    id="email"
                    type="email"
                    name="email"
                    :value="old('email')"
                    required
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
                    autocomplete="new-password"
                    aria-invalid="{{ $errors->has('password') ? 'true' : 'false' }}"
                    aria-describedby="{{ $errors->has('password') ? 'password-error' : '' }}"
                    class="mt-1"
                />
                <x-input-error id="password-error" :messages="$errors->get('password')" class="mt-2" />
            </div>

            <!-- Confirm Password -->
            <div>
                <x-input-label for="password_confirmation" :value="__('Confirm Password')" />
                <x-text-input
                    id="password_confirmation"
                    type="password"
                    name="password_confirmation"
                    required
                    autocomplete="new-password"
                    aria-invalid="{{ $errors->has('password_confirmation') ? 'true' : 'false' }}"
                    aria-describedby="{{ $errors->has('password_confirmation') ? 'password_confirmation-error' : '' }}"
                    class="mt-1"
                />
                <x-input-error id="password_confirmation-error" :messages="$errors->get('password_confirmation')" class="mt-2" />
            </div>
        </div>

        <div class="mt-6">
            <x-primary-button class="w-full">
                {{ __('Register') }}
            </x-primary-button>
        </div>

        <p class="mt-6 text-center text-sm theme-text-muted">
            {{ __('Already registered?') }}
            <a
                class="underline underline-offset-4 theme-text-secondary hover:text-[color:var(--text-link-hover)] rounded-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#AA5F3C]"
                href="{{ route('login') }}"
            >
                {{ __('Log in') }}
            </a>
        </p>
    </form>
</x-guest-layout>
