<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-1">
            <h2 class="font-semibold text-xl theme-text-primary leading-tight">Export PDF</h2>
            <p class="text-sm theme-text-muted">Export artikel menjadi PDF (default: judul + teks).</p>
        </div>
    </x-slot>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        <div class="lg:col-span-8 space-y-6">
            <div class="card overflow-hidden sm:rounded-lg">
                <div class="p-6">
                    <div class="flex items-center justify-between gap-3">
                        <div class="font-semibold theme-text-primary">Buat export</div>
                        <a href="{{ route('pdf.history') }}" class="btn btn-secondary">History</a>
                    </div>

                    <form id="pdfExportForm" method="POST" action="{{ route('pdf.export.multiple') }}" class="mt-5 space-y-6">
                        @csrf

                        <div>
                            <div class="flex items-center justify-between gap-3">
                                <label class="block font-medium text-sm theme-text-secondary">Pilih artikel</label>
                                <div class="flex items-center gap-2">
                                    <button type="button" class="btn btn-secondary" onclick="toggleAllArticles()">Select all</button>
                                    <button type="button" class="btn btn-secondary" onclick="clearAllArticles()">Clear</button>
                                </div>
                            </div>

                            <div class="mt-2 rounded-lg border theme-border-primary bg-[color:var(--surface-primary)] max-h-72 overflow-auto">
                                @if($articles->isEmpty())
                                    <div class="p-6 text-center text-sm theme-text-muted">
                                        Belum ada artikel. <a class="underline" href="{{ route('articles.create') }}">Buat artikel</a>
                                    </div>
                                @else
                                    <div class="divide-y divide-black/5">
                                        @foreach($articles as $article)
                                            <label class="flex items-start gap-3 p-4 hover:bg-[color:var(--hover-bg)] transition cursor-pointer">
                                                <input
                                                    class="article-checkbox mt-1 h-4 w-4 rounded border theme-border-primary bg-[color:var(--surface-primary)]"
                                                    type="checkbox"
                                                    name="article_ids[]"
                                                    value="{{ $article->id }}"
                                                />
                                                <div class="min-w-0">
                                                    <div class="font-medium theme-text-primary truncate">{{ $article->title }}</div>
                                                    <div class="text-xs theme-text-muted">
                                                        {{ $article->created_at->format('Y-m-d') }} • {{ str_word_count(strip_tags($article->content)) }} kata
                                                    </div>
                                                </div>
                                            </label>
                                        @endforeach
                                    </div>
                                @endif
                            </div>

                            <div class="mt-2 text-sm theme-text-muted">
                                <span id="selectedCount">0</span> artikel dipilih
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-12 gap-4">
                            <div class="sm:col-span-6">
                                <label for="template" class="block font-medium text-sm theme-text-secondary">Template</label>
                                <select
                                    id="template"
                                    name="template"
                                    class="mt-1 block w-full rounded-lg shadow-sm border theme-border-primary bg-[color:var(--surface-primary)] text-[color:var(--text-primary)] focus:border-[#AA5F3C] focus:ring-[#AA5F3C]"
                                >
                                    @foreach($templates as $template)
                                        <option value="{{ $template['name'] }}" {{ $template['name'] === 'default' ? 'selected' : '' }}>
                                            {{ ucfirst($template['name']) }}@if($template['description']) — {{ $template['description'] }}@endif
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="sm:col-span-3">
                                <label for="format" class="block font-medium text-sm theme-text-secondary">Format</label>
                                <select
                                    id="format"
                                    name="format"
                                    class="mt-1 block w-full rounded-lg shadow-sm border theme-border-primary bg-[color:var(--surface-primary)] text-[color:var(--text-primary)] focus:border-[#AA5F3C] focus:ring-[#AA5F3C]"
                                >
                                    <option value="A4">A4</option>
                                    <option value="A3">A3</option>
                                    <option value="Letter">Letter</option>
                                    <option value="Legal">Legal</option>
                                </select>
                            </div>

                            <div class="sm:col-span-3">
                                <label for="orientation" class="block font-medium text-sm theme-text-secondary">Orientasi</label>
                                <select
                                    id="orientation"
                                    name="orientation"
                                    class="mt-1 block w-full rounded-lg shadow-sm border theme-border-primary bg-[color:var(--surface-primary)] text-[color:var(--text-primary)] focus:border-[#AA5F3C] focus:ring-[#AA5F3C]"
                                >
                                    <option value="portrait">Portrait</option>
                                    <option value="landscape">Landscape</option>
                                </select>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-12 gap-4">
                            <div class="sm:col-span-4">
                                <label for="font_size" class="block font-medium text-sm theme-text-secondary">Ukuran font</label>
                                <select
                                    id="font_size"
                                    name="font_size"
                                    class="mt-1 block w-full rounded-lg shadow-sm border theme-border-primary bg-[color:var(--surface-primary)] text-[color:var(--text-primary)] focus:border-[#AA5F3C] focus:ring-[#AA5F3C]"
                                >
                                    <option value="10">10pt</option>
                                    <option value="11">11pt</option>
                                    <option value="12" selected>12pt</option>
                                    <option value="13">13pt</option>
                                    <option value="14">14pt</option>
                                    <option value="16">16pt</option>
                                    <option value="18">18pt</option>
                                    <option value="20">20pt</option>
                                </select>
                            </div>
                        </div>

                        <div>
                            <div class="font-medium text-sm theme-text-secondary">Opsi konten</div>
                            <div class="mt-3 grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <label class="flex items-center gap-3 rounded-lg border theme-border-primary bg-[color:var(--surface-primary)] px-4 py-3">
                                    <input type="hidden" name="include_images" value="0">
                                    <input class="h-4 w-4 rounded border theme-border-primary bg-[color:var(--surface-primary)]" type="checkbox" name="include_images" value="1">
                                    <span class="text-sm theme-text-primary">Include images</span>
                                </label>

                                <label class="flex items-center gap-3 rounded-lg border theme-border-primary bg-[color:var(--surface-primary)] px-4 py-3">
                                    <input type="hidden" name="include_metadata" value="0">
                                    <input class="h-4 w-4 rounded border theme-border-primary bg-[color:var(--surface-primary)]" type="checkbox" name="include_metadata" value="1" checked>
                                    <span class="text-sm theme-text-primary">Include metadata</span>
                                </label>

                                <label class="flex items-center gap-3 rounded-lg border theme-border-primary bg-[color:var(--surface-primary)] px-4 py-3">
                                    <input type="hidden" name="include_summaries" value="0">
                                    <input class="h-4 w-4 rounded border theme-border-primary bg-[color:var(--surface-primary)]" type="checkbox" name="include_summaries" value="1">
                                    <span class="text-sm theme-text-primary">Include summaries</span>
                                </label>

                                <label class="flex items-center gap-3 rounded-lg border theme-border-primary bg-[color:var(--surface-primary)] px-4 py-3">
                                    <input type="hidden" name="include_toc" value="0">
                                    <input class="h-4 w-4 rounded border theme-border-primary bg-[color:var(--surface-primary)]" type="checkbox" name="include_toc" value="1" checked>
                                    <span class="text-sm theme-text-primary">Include table of contents</span>
                                </label>

                                <label class="flex items-center gap-3 rounded-lg border theme-border-primary bg-[color:var(--surface-primary)] px-4 py-3">
                                    <input type="hidden" name="page_numbers" value="0">
                                    <input class="h-4 w-4 rounded border theme-border-primary bg-[color:var(--surface-primary)]" type="checkbox" name="page_numbers" value="1" checked>
                                    <span class="text-sm theme-text-primary">Page numbers</span>
                                </label>

                                <label class="flex items-center gap-3 rounded-lg border theme-border-primary bg-[color:var(--surface-primary)] px-4 py-3">
                                    <input type="hidden" name="watermark" value="0">
                                    <input class="h-4 w-4 rounded border theme-border-primary bg-[color:var(--surface-primary)]" type="checkbox" name="watermark" value="1">
                                    <span class="text-sm theme-text-primary">Watermark</span>
                                </label>
                            </div>

                            <div class="mt-2 text-xs theme-text-muted">
                                Tips: biar export stabil, images default dimatikan (PDF akan berisi judul + teks).
                            </div>
                        </div>

                        <div class="flex flex-wrap items-center gap-3">
                            <button type="submit" class="btn btn-primary" id="exportButton" {{ $articles->isEmpty() ? 'disabled' : '' }}>
                                Export PDF
                            </button>
                            <button type="button" class="btn btn-secondary" id="previewButton" {{ $articles->isEmpty() ? 'disabled' : '' }}>
                                Preview (soon)
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="lg:col-span-4 space-y-6 lg:sticky lg:top-24 self-start">
            <div class="card overflow-hidden sm:rounded-lg">
                <div class="p-6">
                    <div class="flex items-center justify-between gap-3">
                        <div class="font-semibold theme-text-primary">Recent exports</div>
                        <a href="{{ route('pdf.history') }}" class="btn btn-secondary">Lihat semua</a>
                    </div>

                    @if($recentExports->isEmpty())
                        <div class="mt-3 text-sm theme-text-muted">Belum ada export.</div>
                    @else
                        <div class="mt-4 space-y-3">
                            @foreach($recentExports as $export)
                                <div class="rounded-lg border theme-border-primary bg-[color:var(--surface-primary)] p-4">
                                    <div class="flex items-start justify-between gap-3">
                                        <div class="min-w-0">
                                            <div class="text-sm theme-text-primary">
                                                {{ ucfirst($export->metadata['template'] ?? 'default') }} • {{ $export->metadata['article_count'] ?? 1 }} artikel
                                            </div>
                                            <div class="text-xs theme-text-muted mt-1">{{ $export->created_at->format('Y-m-d H:i') }} • {{ $export->file_size_human }}</div>
                                        </div>
                                        <div class="text-xs theme-text-muted">
                                            @if($export->isReady())
                                                Ready
                                            @else
                                                {{ ucfirst($export->status) }}
                                            @endif
                                        </div>
                                    </div>

                                    @if($export->isReady())
                                        <div class="mt-3 flex items-center gap-2">
                                            <a href="{{ route('pdf.download', $export->id) }}" class="btn btn-secondary">Download</a>
                                            <a href="{{ route('pdf.preview', $export->id) }}" class="btn btn-secondary" target="_blank">Preview</a>
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

            <div class="card overflow-hidden sm:rounded-lg">
                <div class="p-6">
                    <div class="font-semibold theme-text-primary">Catatan</div>
                    <ul class="mt-3 text-sm theme-text-secondary space-y-2">
                        <li>Kalau konten artikel punya banyak gambar dari web, export lebih aman tanpa images.</li>
                        <li>Untuk batch besar, pakai export background (belum ditampilkan di UI).</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                updateSelectedCount();
                document.querySelectorAll('.article-checkbox').forEach(checkbox => {
                    checkbox.addEventListener('change', updateSelectedCount);
                });

                document.getElementById('pdfExportForm').addEventListener('submit', function(e) {
                    const selectedCount = document.querySelectorAll('.article-checkbox:checked').length;
                    if (selectedCount === 0) {
                        e.preventDefault();
                        alert('Pilih minimal 1 artikel.');
                        return;
                    }

                    const exportButton = document.getElementById('exportButton');
                    const originalText = exportButton.textContent;
                    exportButton.textContent = 'Processing...';
                    exportButton.disabled = true;

                    setTimeout(() => {
                        exportButton.textContent = originalText;
                        exportButton.disabled = false;
                    }, 10000);
                });
            });

            function updateSelectedCount() {
                const selectedCount = document.querySelectorAll('.article-checkbox:checked').length;
                document.getElementById('selectedCount').textContent = selectedCount;

                const hasSelection = selectedCount > 0;
                document.getElementById('exportButton').disabled = !hasSelection;
                document.getElementById('previewButton').disabled = !hasSelection;
            }

            function toggleAllArticles() {
                const checkboxes = document.querySelectorAll('.article-checkbox');
                const allChecked = Array.from(checkboxes).every(cb => cb.checked);
                checkboxes.forEach(checkbox => {
                    checkbox.checked = !allChecked;
                });
                updateSelectedCount();
            }

            function clearAllArticles() {
                document.querySelectorAll('.article-checkbox').forEach(checkbox => {
                    checkbox.checked = false;
                });
                updateSelectedCount();
            }

            document.getElementById('previewButton')?.addEventListener('click', function() {
                const selectedCount = document.querySelectorAll('.article-checkbox:checked').length;
                if (selectedCount === 0) {
                    alert('Pilih minimal 1 artikel.');
                    return;
                }
                alert('Preview akan ditambahkan. Untuk sekarang, export dulu lalu pakai tombol Preview di Recent exports.');
            });
        </script>
    @endpush
</x-app-layout>
