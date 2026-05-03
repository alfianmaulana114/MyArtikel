<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-1">
            <h2 class="font-semibold text-xl theme-text-primary leading-tight">
                {{ __('Profile') }}
            </h2>
            <p class="text-sm theme-text-muted">
                Kelola informasi akun & keamanan.
            </p>
        </div>
    </x-slot>

    <div class="space-y-6 max-w-4xl">
        <div class="card sm:rounded-lg">
            <div class="p-6">
                @include('profile.partials.update-profile-information-form')
            </div>
        </div>

        <div class="card sm:rounded-lg">
            <div class="p-6">
                @include('profile.partials.update-password-form')
            </div>
        </div>

        <div class="card sm:rounded-lg">
            <div class="p-6">
                @include('profile.partials.delete-user-form')
            </div>
        </div>
    </div>
</x-app-layout>
