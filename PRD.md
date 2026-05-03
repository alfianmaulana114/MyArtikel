# PRD — MyArtikel

## 1) Overview

### 1.1 Ringkasan Produk
MyArtikel adalah web app untuk menyimpan artikel dari internet (cukup paste URL), membaca dalam tampilan clean reader tanpa iklan, menambahkan catatan pribadi, mengelola tags/kategori, melakukan pencarian, menggunakan dark mode, dan menyimpan bookmark. MyArtikel juga menyediakan fitur rangkuman otomatis dari link yang disimpan.

### 1.4 Arah Desain (UI)
- Nuansa visual terinspirasi dari **anime Frieren**: tenang, natural, “cozy”, dengan warna earth-tone / desaturated, tetapi tetap **profesional** dan mudah dibaca.
- Prioritas: readability (reader-first), konsisten light/dark, minim distraksi, dan typography yang rapi.

### 1.2 Masalah yang Diselesaikan
| Sebelum | Sesudah |
| --- | --- |
| Bookmark doang | Konten tersimpan dan bisa dibaca ulang |
| Tidak bisa offline | Bisa diakses tanpa internet lewat konten tersimpan / ekspor PDF |
| Banyak iklan & distraksi | Clean reader fokus membaca |
| Catatan tersebar | Notes terhubung ke artikel dan tersusun rapi |

### 1.3 Target Pengguna (Persona)
- **Pembaca fokus**: sering baca artikel panjang dan ingin pengalaman tanpa distraksi.
- **Pelajar/pekerja**: ingin menandai poin penting, membuat catatan, dan mencari kembali insight.
- **Kurator pribadi**: mengelompokkan artikel dengan tag/kategori untuk referensi.

## 2) Goals & Non-Goals

### 2.1 Goals (MVP)
- User wajib **register & login** sebelum menggunakan fitur inti.
- User bisa **simpan artikel via URL**, lalu baca dalam **clean reader**.
- User bisa **buat notes**, **tags**, dan melakukan **search**.
- User bisa **dark mode**.
- User bisa **bookmark/favorite** artikel.
- User bisa **mendapat rangkuman** dari artikel yang disimpan.
- User bisa **ekspor artikel ke PDF** (sebagai bentuk “offline” awal).
- Data **tersinkron** antar device (saved articles, notes, tags, bookmarks, rangkuman).
- Ada **landing page** untuk memperkenalkan MyArtikel dan CTA ke login/register.

### 2.2 Non-Goals (untuk fase awal)
- Dukungan login ke situs sumber (cookie/session untuk paywall) tidak termasuk MVP.
- Kolaborasi antar user / sharing publik tidak termasuk MVP.
- Extension browser (1-click save) tidak termasuk MVP.

## 3) Ruang Lingkup Fitur

### 3.1 Auth & Akun
**Requirement**
- Register (name, email, password)
- Login/Logout
- Forgot password + reset password
- (Opsional) Verifikasi email

**Acceptance criteria**
- Seluruh halaman aplikasi (kecuali landing) wajib protected oleh auth.
- Error message tidak membocorkan apakah email terdaftar atau tidak pada login (anti account enumeration).
- Rate limit pada login & submit URL (anti abuse).

### 3.2 Landing Page
**Tujuan**
- Menjelaskan value MyArtikel dalam 5–10 detik.
- Mendorong signup/login.

**Konten minimal**
- Hero (headline, subheadline, CTA “Mulai”, CTA “Lihat Demo”)
- Section fitur (Save, Clean Reader, Notes, Tags, Search, Dark Mode, Bookmark, Rangkuman, Export PDF)
- “Cara kerja” (Paste URL → Diproses → Baca & Catat)
- FAQ
- Footer (privacy/terms/contact)

**Acceptance criteria**
- CTA jelas dan tersedia di above-the-fold pada mobile & desktop.
- SEO basic: title, meta description, OG tags.

### 3.3 Simpan Artikel (Paste URL)
**User story**
- Sebagai user, saya bisa paste URL artikel, lalu MyArtikel menyimpan konten untuk dibaca ulang.

**Flow**
1. User submit URL.
2. Sistem validasi & normalisasi URL (hapus tracking params, follow canonical).
3. Sistem fetch halaman (HTTP) lalu ekstrak konten utama.
4. Sistem simpan konten bersih (clean HTML/text), metadata, dan status proses.
5. Artikel muncul di library user.

**Edge cases**
- URL tidak valid / tidak bisa diakses.
- Konten terlalu besar.
- Redirect berantai.

**Acceptance criteria**
- Sistem menampilkan status: queued → fetching → extracting → ready/failed.
- Artikel yang gagal memberi alasan singkat dan bisa di-retry.

### 3.4 Clean Reader (Tanpa Iklan)
**Requirement**
- Tampilkan konten utama dengan typography nyaman (lebar bacaan, line-height).
- Tampilkan judul, sumber, tanggal (jika tersedia).
- Konten aman dari XSS (sanitasi).

**Acceptance criteria**
- Konten tidak menampilkan script/style/iframe berbahaya.
- Link tetap bisa diklik, tetapi dibuka aman (noopener/noreferrer).

### 3.5 Notes
**Requirement**
- Tambah/edit/hapus note untuk artikel.
- Note bisa “terikat” ke paragraf/posisi (ideal) atau minimal tersimpan sebagai note bebas per artikel (MVP minimal).

**Acceptance criteria**
- Notes tersimpan dan tampil konsisten antar device.
- UI menampilkan daftar notes per artikel.

### 3.6 Tags / Kategori
**Requirement**
- User bisa menambahkan tag ke artikel.
- Filter library berdasarkan tag.

**Acceptance criteria**
- Tag bisa dibuat saat mengetik (create-on-enter).
- Autocomplete tag yang sudah pernah dibuat user.

### 3.7 Search
**Scope MVP**
- Search global di library: cari judul, domain sumber, tag, dan isi note.
- (Opsional) Search dalam artikel (find-in-page).

**Acceptance criteria**
- Hasil search relevan dan cepat (paging).

### 3.8 Dark Mode
**Requirement**
- Toggle dark mode.
- Simpan preferensi (per user atau per device).

**Acceptance criteria**
- Kontras minimal AA untuk teks utama.
- Tidak ada flash theme yang mengganggu saat load.

### 3.9 Bookmarks / Favorites
**Requirement**
- Mark/unmark artikel sebagai bookmark/favorite.
- Filter view “Bookmarked”.

### 3.10 Rangkuman Otomatis dari Link
**User story**
- Sebagai user, saya bisa melihat ringkasan poin utama dari artikel yang saya simpan.

**Output minimal**
- 1 paragraf ringkasan singkat
- 5–10 bullet poin penting
- (Opsional) “Key takeaways” dan “Pertanyaan yang belum terjawab”

**Acceptance criteria**
- Ringkasan diproses async (tidak blok UI).
- Ringkasan tersimpan dan bisa di-regenerate.
- Rangkuman dibuat **on-demand** (user klik Generate) untuk mengontrol pemakaian quota/biaya.

### 3.11 Offline / Export PDF
**Interpretasi kebutuhan (berdasarkan masukan user)**
- Offline awal dipenuhi dengan **ekspor artikel menjadi PDF** (bisa diunduh dan dibaca offline).

**Requirement**
- Export PDF dari clean reader (minimal: **judul + teks**).

**Acceptance criteria**
- PDF terbaca rapi (typography, page breaks wajar).
- Tidak menyertakan elemen iklan dari halaman asli.

## 4) System Requirements (Non-Functional)

### 4.1 Security
- SSRF protection untuk fitur fetch URL:
  - Hanya `http/https`, block private IP ranges, block localhost, block link-local, batasi redirect.
  - Batasi port (80/443).
- Rate limiting:
  - Login attempts, submit URL, regenerate summary.
- Sanitasi HTML:
  - Jangan render HTML mentah tanpa sanitasi.
- Secrets management:
  - API key provider rangkuman disimpan di `.env` (tidak pernah dilog).

### 4.2 Performance
- Pemrosesan artikel dan rangkuman dilakukan via queue.
- Caching dedup untuk URL yang sama (canonical + content hash) agar tidak memproses berulang.
- Pagination untuk library dan search.

### 4.3 Reliability
- Status processing yang jelas (queued/processing/done/failed).
- Retry/backoff untuk error jaringan dan error provider AI.

## 5) Technical Approach (Laravel 13)

### 5.1 Stack & Constraint
- Backend: Laravel 13 (sudah terpasang).
- DB: MySQL (Laragon).
- Frontend: Blade + Vite + Tailwind (sudah ada baseline).
- Queue: disarankan Redis (atau driver lain yang dipilih) untuk job pipeline.

### 5.2 Modul Inti (Konseptual)
- **Article Ingestion**: normalisasi URL, fetch HTML, ekstraksi konten utama.
- **Reader**: render sanitized content.
- **Notes/Tags/Bookmarks**: CRUD + relasi ke user.
- **Summarization**: pipeline ringkas, caching, quota.
- **PDF Export**: generate PDF dari sanitized content + styling khusus print.

## 6) Desain Sistem Rangkuman (Rekomendasi)

### 6.1 Prinsip
- Async, idempotent, dan hemat biaya.
- Output terstruktur (agar UI konsisten).
- Aman dari SSRF dan XSS.

### 6.2 Opsi Provider & “Tanpa Keluar Biaya Developer”
Karena targetnya “kalau bisa benar-benar tanpa biaya dari developer”, opsi yang realistis:

**Opsi A — Local summarization (tanpa API)**
- Ringkasan ekstraktif (mis. ambil kalimat terpenting berbasis scoring).
- Pro: nol biaya API, bisa jalan offline.
- Kontra: kualitas ringkasan biasanya di bawah LLM.

**Opsi B — Gemini Pro via API key (BYOK / project key)**
- Pakai Gemini (Google AI) untuk ringkasan abstraktif.
- Pro: kualitas ringkasan bagus; jika ada free tier bisa menekan biaya.
- Kontra: tetap ada kuota/billing rule; butuh pengelolaan key dan kontrol rate limit.

**Rekomendasi MVP**
- Default pakai **Gemini** menggunakan API key milik kamu (project key), karena kamu sudah punya akses Gemini Pro.
- Siapkan fallback **Local summarization** jika quota habis atau provider error (agar fitur tetap usable tanpa hard-fail).

### 6.3 Pipeline Rangkuman (Async)
1. User menyimpan artikel (atau klik “Generate Summary”).
2. Sistem ambil hasil ekstraksi teks utama.
3. Preprocess (clean, limit panjang, chunk jika perlu).
4. Generate ringkasan:
   - Local summarizer (default) atau
   - Gemini (opsional)
5. Simpan hasil + metadata (model/prompt_version/biaya jika ada).

### 6.4 Cache & Dedup
- Cache key berbasis `canonical_url + content_hash + summary_mode + prompt_version`.
- Jika konten sama, reuse ringkasan untuk menghemat proses.

## 7) Data Model (Konseptual)
Minimal entity yang dibutuhkan:
- `users`
- `articles` (canonical_url, title, source_domain, fetched_at, content_sanitized, text_extracted, content_hash, status)
- `article_tags` + `tags` (per user)
- `notes` (per user per article)
- `bookmarks` (per user per article)
- `summaries` (per article, mode, output, status, metadata)

## 8) User Flows (MVP)
- **Landing → Register → Dashboard/Library**
- **Dashboard → Paste URL → Processing → Reader**
- **Reader → Add Note / Add Tag / Bookmark**
- **Library → Search/Filter tags**
- **Reader → View Summary**
- **Reader/Library → Export PDF**

## 9) Metrics (Sukses)
- Conversion landing → signup
- Aktivasi: % user yang menyimpan artikel pertama
- Retensi: user kembali dalam 7 hari
- Engagement: rata-rata notes per artikel, penggunaan tag, penggunaan search
- Kualitas summary: % user yang klik “regenerate” (indikasi tidak puas)

## 10) Open Questions
- Apakah notes harus terikat ke paragraf (anchor) sejak MVP, atau cukup note bebas per artikel dulu?
- Untuk Gemini Pro: model/endpoint yang dipakai dan batas quota yang ingin diterapkan per user/hari?
- Apakah perlu “folder/collection” selain tag?
