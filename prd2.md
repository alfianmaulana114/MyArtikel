# PRD2 — Update Fitur Upload Jurnal & Saran Kutipan AI

## 1) Overview Update

### 1.1 Penambahan Lingkup

Aplikasi (MyArtikel/Proyek ini) tidak hanya berfungsi sebagai aplikasi penyimpan dan perangkum artikel dari URL, tetapi kini dioptimalkan untuk **kebutuhan riset dan akademik**. Pengguna dapat mengunggah (upload) dokumen jurnal (PDF) secara langsung dan memasukkan **Judul Penelitian** mereka. Sistem tidak hanya memberikan rangkuman umum, melainkan juga menganalisis relevansi jurnal/artikel terhadap judul penelitian dan merekomendasikan **bagian mana yang bagus untuk dikutip**.

## 2) Goals (Tambahan Fitur)

- User bisa **upload file jurnal (PDF/Doc)** sebagai alternatif dari sekadar "Paste URL".
- User bisa memasukkan **Judul Penelitian** (Research Title) saat menyimpan link web atau mengunggah jurnal.
- Sistem memberikan **Rangkuman** sekaligus **Saran Kutipan** yang relevan dengan Judul Penelitian user.

## 3) Detail Fitur Baru

### 3.1 Upload Jurnal (PDF)

**User Story:**

- Sebagai pengguna, saya bisa mengunggah file jurnal berbentuk PDF jika jurnal tersebut tidak memiliki versi web yang bisa di-_scrape_.

**Flow:**

1. User memilih opsi "Tambah Jurnal/Artikel".
2. UI menampilkan dua opsi: "Paste URL" atau "Upload File".
3. User mengunggah file PDF (dengan batasan ukuran tertentu, misal maks 10MB).
4. Sistem mengunggah file, lalu mengekstrak teks dari PDF tersebut untuk diproses oleh AI.

**Acceptance Criteria:**

- Form mendukung upload file berformat `.pdf`.
- Validasi ukuran file berjalan dengan baik.
- Terdapat indikator loading/progres saat file sedang diproses.

### 3.2 Input Judul Penelitian & Ekstraksi AI (Rangkuman + Saran Kutipan)

**User Story:**

- Sebagai pengguna (peneliti/mahasiswa), saya bisa memasukkan judul penelitian saya agar AI dapat memberikan saran kutipan yang spesifik dan nyambung dengan penelitian saya dari jurnal/artikel yang baru saja saya masukkan.

**Flow:**

1. Saat user paste URL atau upload PDF, terdapat input field tambahan: **"Judul Penelitian / Topik Riset"** (bisa opsional atau wajib, tergantung kebutuhan).
2. Jika field ini diisi, prompt ke LLM (seperti Gemini) akan disesuaikan untuk merangkum **dan** mencari kutipan yang relevan dengan judul penelitian.
3. Output AI yang ditampilkan memiliki dua bagian utama:
    - **Rangkuman:** Ringkasan isi jurnal secara keseluruhan.
    - **Saran Kutipan:** Paragraf/kalimat rekomendasi dari jurnal yang sangat bagus untuk dikutip, beserta penjelasan **mengapa** bagian tersebut relevan untuk mendukung judul penelitian user.

**Acceptance Criteria:**

- Input judul penelitian terintegrasi dengan baik ke dalam prompt AI.
- AI dapat mengembalikan hasil dengan format terstruktur (misal JSON) yang memisahkan antara `rangkuman` dan `saran_kutipan`.
- UI menampilkan tab atau bagian khusus untuk "Saran Kutipan".

## 4) Technical Approach

### 4.1 Ekstraksi Teks PDF

- **Backend (PHP/Laravel):** Bisa menggunakan library seperti `spatie/pdf-to-text` atau `smalot/pdfparser` untuk mengekstrak raw text dari file PDF.
- **Alternatif AI API:** Jika menggunakan API Gemini terbaru (misal Gemini 1.5 Pro/Flash), kita bisa langsung mengunggah file PDF melalui File API mereka, dan membiarkan Gemini yang membaca keseluruhan isi PDF tersebut tanpa perlu ekstraksi manual di sisi backend.

### 4.2 Pembaruan Prompt AI (Gemini)

- **Prompt Logic:**

    ```text
    Anda adalah asisten riset tingkat lanjut.
    Berikut adalah teks dari jurnal/artikel: "{text_atau_file_pdf}".
    Topik/Judul penelitian user adalah: "{judul_penelitian}".

    Tugas Anda:
    1. Buat rangkuman singkat dari jurnal tersebut.
    2. Carikan 3-5 kutipan persis dari teks jurnal di atas yang sangat relevan dan bagus untuk mendukung penelitian user.
    3. Untuk setiap kutipan, berikan penjelasan/saran mengapa kutipan tersebut cocok dan bagaimana user bisa menggunakannya dalam tulisan mereka.

    Format output harus terstruktur atau dalam format JSON untuk memudahkan parsing.
    ```

### 4.3 Penyesuaian Data Model (Database)`

Diperlukan modifikasi pada tabel penyimpanan (misal `articles` atau membuat tabel baru khusus riset):

- `source_type` (enum: 'url', 'pdf')
- `file_path` (nullable) - Path ke lokasi file PDF yang disimpan (misal di `storage/app/public/journals/`).
- `research_title` (nullable) - Menyimpan judul penelitian yang diinput user.
- `ai_quotation_suggestions` (json/text, nullable) - Menyimpan output spesifik saran kutipan dari AI.

## 5) User Flows (Draft)

1. **Halaman Dashboard/Library:** User menekan tombol [+ Tambah Riset / Artikel].
2. **Modal/Form Tambah:**
    - Pilih tipe: "URL Website" atau "Upload Jurnal (PDF)".
    - Input/Upload sumbernya.
    - Input Judul Penelitian / Topik Riset.
    - Klik **Proses/Simpan**.
3. **Proses Background:** Sistem mendownload teks web atau mengekstrak teks PDF, lalu memanggil _AiResearchService_.
4. **Hasil (Halaman Reader):** User dapat melihat:
    - Teks asli jurnal/artikel (atau preview PDF).
    - Panel/Tab **Rangkuman**.
    - Panel/Tab **Saran Kutipan**.
