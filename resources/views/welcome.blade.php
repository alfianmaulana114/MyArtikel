<x-marketing-layout
    title="MyArtikel — Pengalaman membaca yang tenang"
    description="Simpan artikel dari web, baca versi bersih, beri tag, buat catatan, dan rangkum saat perlu."
>
    <x-slot name="head">
        <meta name="keywords" content="pembaca bersih, catatan membaca, bookmark, mode gelap, rangkuman, export PDF">
        <meta name="author" content="MyArtikel">
        <meta name="twitter:card" content="summary_large_image">
        <meta name="twitter:title" content="MyArtikel — Pengalaman membaca yang tenang">
        <meta name="twitter:description" content="Simpan artikel, baca dengan fokus, rapikan dengan tag & catatan.">
    </x-slot>

    <section class="marketing-hero">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-14 sm:py-16 lg:py-20">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-10 items-center">
                <div class="lg:col-span-6">
                    <div class="inline-flex items-center gap-2 text-xs font-semibold tracking-wide uppercase theme-text-muted">
                        <span class="h-2 w-2 rounded-full bg-[color:var(--earth-accent-500)]"></span>
                        Fokus, rapi, tahan lama
                    </div>

                    <h1 class="mt-4 text-4xl sm:text-5xl font-semibold leading-tight theme-text-primary">
                        Simpan bacaan. Baca versi bersih. Catat yang penting.
                    </h1>

                    <p class="mt-5 text-lg theme-text-secondary leading-relaxed">
                        Paste URL, konten dibersihkan untuk dibaca, lalu rapikan dengan tag dan catatan.
                        Kalau butuh review cepat, buat rangkuman satu klik.
                    </p>

                    <div class="mt-8 flex flex-col sm:flex-row gap-3">
                        @auth
                            <a href="{{ route('dashboard') }}" class="btn btn-primary">
                                Masuk ke Library
                            </a>
                        @else
                            <a href="{{ Route::has('register') ? route('register') : route('login') }}" class="btn btn-primary">
                                Mulai Sekarang
                            </a>
                            <a href="#fitur" class="btn btn-secondary">
                                Lihat fitur inti
                            </a>
                        @endauth
                    </div>

                    <dl class="mt-10 grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div class="rounded-2xl border theme-border-primary bg-[color:var(--surface-primary)] p-4">
                            <dt class="text-xs theme-text-muted">Clean reader</dt>
                            <dd class="mt-1 font-semibold theme-text-primary">Tanpa distraksi</dd>
                        </div>
                        <div class="rounded-2xl border theme-border-primary bg-[color:var(--surface-primary)] p-4">
                            <dt class="text-xs theme-text-muted">Catatan</dt>
                            <dd class="mt-1 font-semibold theme-text-primary">Sambil membaca</dd>
                        </div>
                        <div class="rounded-2xl border theme-border-primary bg-[color:var(--surface-primary)] p-4">
                            <dt class="text-xs theme-text-muted">Rangkuman</dt>
                            <dd class="mt-1 font-semibold theme-text-primary">Cepat & terukur</dd>
                        </div>
                    </dl>
                </div>

                <div class="lg:col-span-6">
                    <div class="card overflow-hidden rounded-2xl">
                        <div class="border-b theme-border-primary px-5 py-4 flex items-center justify-between gap-3">
                            <div class="min-w-0">
                                <div class="text-xs theme-text-muted">Preview</div>
                                <div class="font-semibold theme-text-primary truncate">Library</div>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="inline-flex h-2 w-2 rounded-full bg-[color:var(--earth-secondary-500)]"></span>
                                <span class="inline-flex h-2 w-2 rounded-full bg-[color:var(--earth-accent-500)]"></span>
                                <span class="inline-flex h-2 w-2 rounded-full bg-[color:var(--earth-primary-500)]"></span>
                            </div>
                        </div>

                        <div class="p-5">
                            <div class="rounded-2xl border theme-border-primary bg-[color:var(--surface-secondary)] p-4">
                                <div class="text-xs theme-text-muted">URL artikel</div>
                                <div class="mt-2 flex gap-3">
                                    <div class="h-10 flex-1 rounded-xl border theme-border-primary bg-[color:var(--surface-primary)]"></div>
                                    <div class="h-10 w-28 rounded-xl bg-[color:var(--earth-primary-600)]"></div>
                                </div>
                            </div>

                            <div class="mt-4 grid grid-cols-1 gap-3">
                                @for ($i = 0; $i < 3; $i++)
                                    <div class="rounded-2xl border theme-border-primary bg-[color:var(--surface-primary)] p-4">
                                        <div class="flex items-start justify-between gap-3">
                                            <div class="min-w-0 flex-1">
                                                <div class="h-3 w-24 rounded-full bg-[color:var(--bg-tertiary)]"></div>
                                                <div class="mt-3 h-4 w-5/6 rounded-full bg-[color:var(--bg-tertiary)]"></div>
                                                <div class="mt-2 h-4 w-2/3 rounded-full bg-[color:var(--bg-tertiary)]"></div>
                                            </div>
                                            <div class="h-9 w-20 rounded-xl bg-[color:var(--hover-bg)] border theme-border-primary"></div>
                                        </div>
                                    </div>
                                @endfor
                            </div>
                        </div>
                    </div>

                    <p class="mt-3 text-sm theme-text-muted">
                        Tampilan landing memakai tema, token warna, dan komponen yang sama dengan dashboard.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <section id="cara-kerja" class="scroll-mt-24 theme-bg-secondary border-y theme-border-primary">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-14 sm:py-16">
            <div class="max-w-2xl">
                <h2 class="text-2xl sm:text-3xl font-semibold theme-text-primary">Cara kerja yang sederhana</h2>
                <p class="mt-3 theme-text-secondary">
                    Tidak ada ritual yang rumit. Simpan dulu, rapikan setelahnya.
                </p>
            </div>

            <div class="mt-10 grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="card p-6 rounded-2xl">
                    <div class="flex items-center gap-3">
                        <div class="h-10 w-10 rounded-xl bg-[color:var(--bg-tertiary)] border theme-border-primary flex items-center justify-center">
                            <svg class="h-5 w-5 theme-text-primary" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3M5 11h14M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <div class="font-semibold theme-text-primary">1) Paste URL</div>
                    </div>
                    <p class="mt-3 text-sm theme-text-secondary">
                        Simpan link dari mana saja. Cocok untuk “nanti dibaca”, tapi akhirnya lupa.
                    </p>
                </div>

                <div class="card p-6 rounded-2xl">
                    <div class="flex items-center gap-3">
                        <div class="h-10 w-10 rounded-xl bg-[color:var(--bg-tertiary)] border theme-border-primary flex items-center justify-center">
                            <svg class="h-5 w-5 theme-text-primary" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4h16v6H4zM4 14h16v6H4z" />
                            </svg>
                        </div>
                        <div class="font-semibold theme-text-primary">2) Dibersihkan</div>
                    </div>
                    <p class="mt-3 text-sm theme-text-secondary">
                        Konten diekstrak jadi format baca. Fokus ke teks, bukan pop-up dan banner.
                    </p>
                </div>

                <div class="card p-6 rounded-2xl">
                    <div class="flex items-center gap-3">
                        <div class="h-10 w-10 rounded-xl bg-[color:var(--bg-tertiary)] border theme-border-primary flex items-center justify-center">
                            <svg class="h-5 w-5 theme-text-primary" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01" />
                            </svg>
                        </div>
                        <div class="font-semibold theme-text-primary">3) Baca & catat</div>
                    </div>
                    <p class="mt-3 text-sm theme-text-secondary">
                        Tambahkan catatan, tag, bookmark, atau buat rangkuman saat kamu butuh.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <section id="fitur" class="scroll-mt-24">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-14 sm:py-16">
            <div class="flex flex-col lg:flex-row lg:items-end lg:justify-between gap-6">
                <div class="max-w-2xl">
                    <h2 class="text-2xl sm:text-3xl font-semibold theme-text-primary">Fitur inti (yang benar-benar dipakai)</h2>
                    <p class="mt-3 theme-text-secondary">
                        Lebih sedikit, tapi konsisten. UI mengikuti sistem komponen yang sama dengan dashboard (card, border, shadow, token warna).
                    </p>
                </div>
                <div class="flex gap-3">
                    <a href="{{ route('login') }}" class="btn btn-secondary">Login</a>
                    @if (Route::has('register'))
                        <a href="{{ route('register') }}" class="btn btn-primary">Buat akun</a>
                    @endif
                </div>
            </div>

            <div class="mt-10 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <div class="card p-6 rounded-2xl">
                    <div class="flex items-start gap-3">
                        <div class="h-11 w-11 rounded-2xl bg-[color:var(--bg-tertiary)] border theme-border-primary flex items-center justify-center">
                            <svg class="h-5 w-5 theme-text-primary" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-4-7 4V5z" />
                            </svg>
                        </div>
                        <div>
                            <div class="font-semibold theme-text-primary">Simpan artikel</div>
                            <p class="mt-1 text-sm theme-text-secondary">Paste URL, artikel masuk ke library dan bisa diproses di background.</p>
                        </div>
                    </div>
                </div>

                <div class="card p-6 rounded-2xl">
                    <div class="flex items-start gap-3">
                        <div class="h-11 w-11 rounded-2xl bg-[color:var(--bg-tertiary)] border theme-border-primary flex items-center justify-center">
                            <svg class="h-5 w-5 theme-text-primary" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h10M7 11h10M7 15h7M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <div>
                            <div class="font-semibold theme-text-primary">Clean reader</div>
                            <p class="mt-1 text-sm theme-text-secondary">Baca versi bersih: fokus ke isi, bukan elemen halaman web.</p>
                        </div>
                    </div>
                </div>

                <div class="card p-6 rounded-2xl">
                    <div class="flex items-start gap-3">
                        <div class="h-11 w-11 rounded-2xl bg-[color:var(--bg-tertiary)] border theme-border-primary flex items-center justify-center">
                            <svg class="h-5 w-5 theme-text-primary" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16h8M8 12h8m-6 8h6a2 2 0 002-2V6a2 2 0 00-2-2H8a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <div>
                            <div class="font-semibold theme-text-primary">Catatan</div>
                            <p class="mt-1 text-sm theme-text-secondary">Tulis poin penting saat membaca. Cocok untuk ringkas ulang nanti.</p>
                        </div>
                    </div>
                </div>

                <div class="card p-6 rounded-2xl">
                    <div class="flex items-start gap-3">
                        <div class="h-11 w-11 rounded-2xl bg-[color:var(--bg-tertiary)] border theme-border-primary flex items-center justify-center">
                            <svg class="h-5 w-5 theme-text-primary" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M3 11l9 9a2 2 0 002.828 0l6.364-6.364a2 2 0 000-2.828l-9-9A2 2 0 0010.586 1H5a2 2 0 00-2 2v5.586A2 2 0 003 10.414V11z" />
                            </svg>
                        </div>
                        <div>
                            <div class="font-semibold theme-text-primary">Tag & kurasi</div>
                            <p class="mt-1 text-sm theme-text-secondary">Kelompokkan bacaan, bikin jalur belajar, dan filter cepat.</p>
                        </div>
                    </div>
                </div>

                <div class="card p-6 rounded-2xl">
                    <div class="flex items-start gap-3">
                        <div class="h-11 w-11 rounded-2xl bg-[color:var(--bg-tertiary)] border theme-border-primary flex items-center justify-center">
                            <svg class="h-5 w-5 theme-text-primary" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5h6M9 9h6M9 13h6M7 21h10a2 2 0 002-2V7a2 2 0 00-2-2h-1V3H8v2H7a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <div>
                            <div class="font-semibold theme-text-primary">Rangkuman</div>
                            <p class="mt-1 text-sm theme-text-secondary">Buat ringkasan ketika butuh review cepat (dengan kontrol kuota).</p>
                        </div>
                    </div>
                </div>

                <div class="card p-6 rounded-2xl">
                    <div class="flex items-start gap-3">
                        <div class="h-11 w-11 rounded-2xl bg-[color:var(--bg-tertiary)] border theme-border-primary flex items-center justify-center">
                            <svg class="h-5 w-5 theme-text-primary" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                        </div>
                        <div>
                            <div class="font-semibold theme-text-primary">Export PDF</div>
                            <p class="mt-1 text-sm theme-text-secondary">Simpan untuk dibaca offline / arsip. Riwayat export tetap tercatat.</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-10 card rounded-2xl overflow-hidden">
                <div class="p-6 sm:p-8 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-6">
                    <div class="max-w-2xl">
                        <div class="font-semibold theme-text-primary">Siap menyusun library bacaan?</div>
                        <p class="mt-1 text-sm theme-text-secondary">
                            Mulai dari satu URL. Sisanya biar MyArtikel yang rapikan.
                        </p>
                    </div>
                    <div class="flex gap-3">
                        <a href="{{ route('login') }}" class="btn btn-secondary">Login</a>
                        @if (Route::has('register'))
                            <a href="{{ route('register') }}" class="btn btn-primary">Buat akun</a>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section id="faq" class="scroll-mt-24 theme-bg-secondary border-t theme-border-primary">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-14 sm:py-16">
            <div class="max-w-2xl">
                <h2 class="text-2xl sm:text-3xl font-semibold theme-text-primary">FAQ</h2>
                <p class="mt-3 theme-text-secondary">Jawaban singkat untuk pertanyaan yang biasanya muncul.</p>
            </div>

            <div class="mt-10 grid grid-cols-1 lg:grid-cols-2 gap-6">
                <details class="card rounded-2xl p-6">
                    <summary class="cursor-pointer list-none flex items-center justify-between gap-4">
                        <span class="font-semibold theme-text-primary">MyArtikel cocok untuk siapa?</span>
                        <span class="faq-plus h-9 w-9 rounded-xl border theme-border-primary bg-[color:var(--surface-primary)] flex items-center justify-center theme-text-primary">
                            +
                        </span>
                    </summary>
                    <p class="mt-4 text-sm theme-text-secondary">
                        Untuk kamu yang sering menyimpan banyak bacaan, butuh versi bersih untuk dibaca, dan ingin “ingat” poin penting lewat catatan/tag.
                    </p>
                </details>

                <details class="card rounded-2xl p-6">
                    <summary class="cursor-pointer list-none flex items-center justify-between gap-4">
                        <span class="font-semibold theme-text-primary">Apakah ada mode gelap?</span>
                        <span class="faq-plus h-9 w-9 rounded-xl border theme-border-primary bg-[color:var(--surface-primary)] flex items-center justify-center theme-text-primary">
                            +
                        </span>
                    </summary>
                    <p class="mt-4 text-sm theme-text-secondary">
                        Ya. Tema mengikuti sistem light/dark/auto, sama seperti dashboard.
                    </p>
                </details>

                <details class="card rounded-2xl p-6">
                    <summary class="cursor-pointer list-none flex items-center justify-between gap-4">
                        <span class="font-semibold theme-text-primary">Apakah rangkuman otomatis selalu aktif?</span>
                        <span class="faq-plus h-9 w-9 rounded-xl border theme-border-primary bg-[color:var(--surface-primary)] flex items-center justify-center theme-text-primary">
                            +
                        </span>
                    </summary>
                    <p class="mt-4 text-sm theme-text-secondary">
                        Tidak. Rangkuman dibuat saat kamu minta, dan ada kontrol kuota supaya tetap terukur.
                    </p>
                </details>

                <details class="card rounded-2xl p-6">
                    <summary class="cursor-pointer list-none flex items-center justify-between gap-4">
                        <span class="font-semibold theme-text-primary">Apakah bisa dibaca offline?</span>
                        <span class="faq-plus h-9 w-9 rounded-xl border theme-border-primary bg-[color:var(--surface-primary)] flex items-center justify-center theme-text-primary">
                            +
                        </span>
                    </summary>
                    <p class="mt-4 text-sm theme-text-secondary">
                        Bisa lewat export PDF. Untuk versi bersihnya, konten tersimpan di library sehingga tetap bisa diakses tanpa harus membuka situs aslinya.
                    </p>
                </details>
            </div>
        </div>
    </section>
</x-marketing-layout>
