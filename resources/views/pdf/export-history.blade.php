<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-1">
            <h2 class="font-semibold text-xl theme-text-primary leading-tight">Export History</h2>
            <p class="text-sm theme-text-muted">Daftar export PDF yang pernah dibuat.</p>
        </div>
    </x-slot>

    <div class="space-y-6">
        <div class="card overflow-hidden sm:rounded-lg">
            <div class="p-6">
                <div class="flex flex-col sm:flex-row sm:items-center gap-3 sm:justify-between">
                    <div class="font-semibold theme-text-primary">Filter</div>
                    <div class="flex flex-wrap items-center gap-2">
                        <a href="{{ route('pdf.export.form') }}" class="btn btn-primary">New export</a>
                        <button type="button" class="btn btn-secondary" onclick="cleanupOldExports()">Cleanup</button>
                    </div>
                </div>

                <form method="GET" action="{{ route('pdf.history') }}" class="mt-4 grid grid-cols-1 sm:grid-cols-12 gap-4">
                    <div class="sm:col-span-3">
                        <label for="status" class="block font-medium text-sm theme-text-secondary">Status</label>
                        <select
                            class="mt-1 block w-full rounded-lg shadow-sm border theme-border-primary bg-[color:var(--surface-primary)] text-[color:var(--text-primary)] focus:border-[#AA5F3C] focus:ring-[#AA5F3C]"
                            id="status"
                            name="status"
                        >
                            <option value="">All</option>
                            <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                            <option value="processing" {{ request('status') === 'processing' ? 'selected' : '' }}>Processing</option>
                            <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Completed</option>
                            <option value="failed" {{ request('status') === 'failed' ? 'selected' : '' }}>Failed</option>
                        </select>
                    </div>

                    <div class="sm:col-span-3">
                        <label for="template" class="block font-medium text-sm theme-text-secondary">Template</label>
                        <select
                            class="mt-1 block w-full rounded-lg shadow-sm border theme-border-primary bg-[color:var(--surface-primary)] text-[color:var(--text-primary)] focus:border-[#AA5F3C] focus:ring-[#AA5F3C]"
                            id="template"
                            name="template"
                        >
                            <option value="">All</option>
                            @foreach(['default', 'academic', 'magazine', 'minimal', 'business'] as $template)
                                <option value="{{ $template }}" {{ request('template') === $template ? 'selected' : '' }}>
                                    {{ ucfirst($template) }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="sm:col-span-3">
                        <label for="date_from" class="block font-medium text-sm theme-text-secondary">Date from</label>
                        <input
                            type="date"
                            class="mt-1 block w-full rounded-lg shadow-sm border theme-border-primary bg-[color:var(--surface-primary)] text-[color:var(--text-primary)] focus:border-[#AA5F3C] focus:ring-[#AA5F3C]"
                            id="date_from"
                            name="date_from"
                            value="{{ request('date_from') }}"
                        >
                    </div>

                    <div class="sm:col-span-3">
                        <label for="date_to" class="block font-medium text-sm theme-text-secondary">Date to</label>
                        <input
                            type="date"
                            class="mt-1 block w-full rounded-lg shadow-sm border theme-border-primary bg-[color:var(--surface-primary)] text-[color:var(--text-primary)] focus:border-[#AA5F3C] focus:ring-[#AA5F3C]"
                            id="date_to"
                            name="date_to"
                            value="{{ request('date_to') }}"
                        >
                    </div>

                    <div class="sm:col-span-12 flex flex-wrap items-center gap-2">
                        <button type="submit" class="btn btn-primary">Apply</button>
                        <a href="{{ route('pdf.history') }}" class="btn btn-secondary">Clear</a>
                    </div>
                </form>
            </div>
        </div>

        <div class="card overflow-hidden sm:rounded-lg">
            <div class="p-6">
                <div class="flex items-center justify-between gap-3">
                    <div class="font-semibold theme-text-primary">History</div>
                    <a href="{{ route('pdf.export.form') }}" class="btn btn-secondary">Buat export</a>
                </div>

                @if($exports->isEmpty())
                    <div class="mt-6 text-center text-sm theme-text-muted">
                        Belum ada export.
                    </div>
                @else
                    <div class="mt-5 overflow-auto rounded-lg border theme-border-primary">
                        <table class="min-w-full text-sm">
                            <thead class="bg-[color:var(--surface-secondary)]">
                                <tr class="text-left theme-text-secondary">
                                    <th class="px-4 py-3 font-medium">Date</th>
                                    <th class="px-4 py-3 font-medium">Articles</th>
                                    <th class="px-4 py-3 font-medium">Template</th>
                                    <th class="px-4 py-3 font-medium">Format</th>
                                    <th class="px-4 py-3 font-medium">Size</th>
                                    <th class="px-4 py-3 font-medium">Time</th>
                                    <th class="px-4 py-3 font-medium">Status</th>
                                    <th class="px-4 py-3 font-medium">Expires</th>
                                    <th class="px-4 py-3 font-medium">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-black/5 bg-[color:var(--surface-primary)]">
                                @foreach($exports as $export)
                                    <tr data-export-status="{{ $export->status }}">
                                        <td class="px-4 py-3">
                                            <div class="theme-text-primary">{{ $export->created_at->format('Y-m-d') }}</div>
                                            <div class="text-xs theme-text-muted">{{ $export->created_at->format('H:i') }}</div>
                                        </td>
                                        <td class="px-4 py-3 theme-text-primary">{{ $export->article_count }}</td>
                                        <td class="px-4 py-3 theme-text-primary">{{ ucfirst($export->metadata['template'] ?? 'default') }}</td>
                                        <td class="px-4 py-3 text-xs theme-text-muted">
                                            {{ $export->metadata['options']['format'] ?? 'A4' }}
                                            {{ $export->metadata['options']['orientation'] ?? 'portrait' }}
                                        </td>
                                        <td class="px-4 py-3 theme-text-primary">{{ $export->file_size_human }}</td>
                                        <td class="px-4 py-3 text-xs theme-text-muted">{{ $export->processing_time_human }}</td>
                                        <td class="px-4 py-3">
                                            <span class="text-xs theme-text-primary">{{ ucfirst($export->status) }}</span>
                                        </td>
                                        <td class="px-4 py-3 text-xs theme-text-muted">
                                            {{ $export->expires_at ? $export->expires_at->format('Y-m-d') : 'Never' }}
                                        </td>
                                        <td class="px-4 py-3">
                                            <div class="flex flex-wrap items-center gap-2">
                                                @if($export->isReady())
                                                    <a href="{{ route('pdf.download', $export->id) }}" class="btn btn-secondary">Download</a>
                                                    <a href="{{ route('pdf.preview', $export->id) }}" class="btn btn-secondary" target="_blank">Preview</a>
                                                @endif
                                                <button type="button" class="btn btn-secondary" onclick="deleteExport({{ $export->id }})">Delete</button>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4">
                        {{ $exports->appends(request()->query())->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            function deleteExport(exportId) {
                if (!confirm('Hapus export ini?')) return;

                fetch(`/pdf/export/${exportId}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                        'Content-Type': 'application/json'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Error: ' + (data.error || 'Failed to delete export'));
                    }
                })
                .catch(() => {
                    alert('Failed to delete export. Please try again.');
                });
            }

            function cleanupOldExports() {
                alert('Cleanup akan ditambahkan. Untuk sekarang, export yang expired bisa dihapus manual.');
            }

            document.addEventListener('DOMContentLoaded', function() {
                const hasProcessing = Array.from(document.querySelectorAll('[data-export-status]'))
                    .some(row => ['pending','processing'].includes(row.getAttribute('data-export-status')));
                if (hasProcessing) {
                    setTimeout(() => location.reload(), 30000);
                }
            });
        </script>
    @endpush
</x-app-layout>

