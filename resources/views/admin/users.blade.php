<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-1">
            <h2 class="font-semibold text-xl theme-text-primary leading-tight">Manajemen User</h2>
            <p class="text-sm theme-text-muted">Kelola semua user platform — aktifkan, nonaktifkan, atau hapus.</p>
        </div>
    </x-slot>

    @if (session('status'))
        <div class="mb-4 text-sm rounded-xl p-3 bg-[color:var(--bg-tertiary)] text-[#8B9A7A] font-medium">
            {{ session('status') }}
        </div>
    @endif
    @if (session('error'))
        <div class="mb-4 text-sm rounded-xl p-3 bg-[color:var(--error-bg)] text-[color:var(--error)] font-medium">
            {{ session('error') }}
        </div>
    @endif

    <div class="card overflow-hidden">
        <div class="p-4 border-b theme-border-primary">
            <form method="GET" action="{{ route('admin.users') }}" class="flex flex-col sm:flex-row gap-3 sm:items-center">
                <div class="flex-1">
                    <input
                        name="q"
                        type="text"
                        value="{{ request('q') }}"
                        placeholder="Cari nama / email…"
                        class="block w-full rounded-xl shadow-sm border theme-border-primary bg-[color:var(--surface-primary)] text-[color:var(--text-primary)] placeholder:text-[color:var(--text-muted)] focus:border-[#AA5F3C] focus:ring-[#AA5F3C] px-4 py-2"
                    />
                </div>
                <div>
                    <select name="status" class="block w-full rounded-xl shadow-sm border theme-border-primary bg-[color:var(--surface-primary)] text-[color:var(--text-primary)] focus:border-[#AA5F3C] focus:ring-[#AA5F3C] px-3 py-2">
                        <option value="">Semua status</option>
                        <option value="active" @selected(request('status') === 'active')>Aktif</option>
                        <option value="inactive" @selected(request('status') === 'inactive')>Nonaktif</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-secondary whitespace-nowrap">Filter</button>
                @if (request()->anyFilled(['q', 'status']))
                    <a href="{{ route('admin.users') }}" class="text-sm theme-text-muted hover:theme-text-primary whitespace-nowrap">Reset</a>
                @endif
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
                    @forelse ($users as $user)
                        <tr class="hover:bg-[color:var(--hover-bg)] transition-colors">
                            <td class="p-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-full bg-[color:var(--bg-tertiary)] flex items-center justify-center text-xs font-semibold theme-text-secondary">
                                        {{ strtoupper(substr($user->name, 0, 2)) }}
                                    </div>
                                    <div>
                                        <div class="font-medium theme-text-primary">{{ $user->name }}</div>
                                        @if ($user->is_admin)
                                            <span class="text-[10px] px-1.5 py-0.5 rounded-full bg-[#AA5F3C]/10 text-[#AA5F3C] font-medium">Admin</span>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="p-4 theme-text-secondary hidden md:table-cell">{{ $user->email }}</td>
                            <td class="p-4">
                                <span class="font-medium theme-text-primary">{{ $user->articles_count }}</span>
                            </td>
                            <td class="p-4 hidden sm:table-cell">
                                <span class="text-xs px-2 py-1 rounded-full border {{ $user->is_active ? 'border-[#8B9A7A]/30 text-[#8B9A7A] bg-[#8B9A7A]/5' : 'border-[color:var(--error)]/30 text-[color:var(--error)] bg-[color:var(--error)]/5' }}">
                                    {{ $user->is_active ? 'Aktif' : 'Nonaktif' }}
                                </span>
                            </td>
                            <td class="p-4 theme-text-muted whitespace-nowrap hidden lg:table-cell">{{ $user->created_at->format('d M Y') }}</td>
                            <td class="p-4">
                                <div class="flex items-center justify-end gap-2">
                                    @if (!$user->is_admin)
                                        <form method="POST" action="{{ route('admin.users.toggle', $user) }}" class="inline">
                                            @csrf
                                            <button type="submit" class="text-xs px-3 py-1.5 rounded-lg border theme-border-primary hover:bg-[color:var(--hover-bg)] theme-text-secondary transition-colors">
                                                {{ $user->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                            </button>
                                        </form>
                                        <form method="POST" action="{{ route('admin.users.delete', $user) }}" class="inline"
                                            onsubmit="return confirm('Hapus user {{ addslashes($user->name) }} dan semua datanya?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-xs px-3 py-1.5 rounded-lg border border-[color:var(--error)]/30 text-[color:var(--error)] hover:bg-[color:var(--error)]/5 transition-colors">
                                                Hapus
                                            </button>
                                        </form>
                                    @else
                                        <span class="text-xs theme-text-muted">—</span>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="p-8 text-center theme-text-muted">Tidak ada user ditemukan.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="p-4 border-t theme-border-primary">
            {{ $users->links() }}
        </div>
    </div>
</x-app-layout>
