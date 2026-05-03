# Playbook Penugasan Agent

Dokumen ini jadi aturan kerja saat kamu chat: aku bakal nentuin agent mana yang paling cocok, mendelegasikan kerja ke mereka, lalu aku rangkum hasilnya ke kamu.

## Agent yang tersedia

| Agent | Fokus | Dipakai saat |
|------|-------|--------------|
| `search` | Eksplorasi codebase cepat (nyari alur, file penting, keterkaitan modul) | Pertanyaan “di mana…?”, “gimana flow…?”, “bagian mana yang handle…?”, atau butuh konteks sebelum implementasi |
| `himmel-backend-engineer` | Backend/arsitektur/performa/DB/microservices (level senior) | Desain API, optimasi query, arsitektur Laravel, caching, queue, scaling, security backend |
| `Alfian` | Frontend/UI/UX/responsiveness/performance web | Layout, komponen UI, shadcn/ui, Vite, interaksi, aksesibilitas, CSS/JS |

## Aturan dispatch (penugasan)

1. Aku klasifikasikan request kamu ke: **frontend**, **backend**, **butuh eksplorasi**, atau **campuran**.
2. Aku assign agent sesuai fokus:
   - **Frontend dominan** → `Alfian`
   - **Backend dominan** → `himmel-backend-engineer`
   - **Butuh cari konteks/jejak di repo** → `search` (biasanya jalan duluan sebelum agent lain)
   - **Campuran** → kombinasi (maks 3 agent), biasanya:
     - `search` untuk peta lokasi + alur
     - lalu `Alfian`/`himmel-backend-engineer` untuk keputusan/implementasi sesuai domain
3. Aku verifikasi hasilnya (cek kesesuaian dengan repo, pola kode, dan constraint lingkungan).
4. Aku balikin ke kamu dalam format:
   - **Ringkasan**: 3–7 bullet
   - **Temuan penting**: file/flow/risiko
   - **Rekomendasi langkah berikutnya**: apa yang akan dikerjakan/diubah

## Output yang kamu bakal lihat

### Format ringkasan standar
- Agent yang dipakai: `…`
- Yang ditemukan/diputuskan: `…`
- Dampak ke codebase: `…`
- Risiko/edge case: `…`
- Next step: `…`

## Contoh skenario

### 1) “Tolong cariin flow login di project ini”
- Aku jalankan `search` untuk nemuin controller/middleware/routes yang relevan.
- Aku rangkum file yang terkait dan jelasin alurnya.

### 2) “Query list artikel lambat, optimasiin”
- Aku jalankan `search` untuk nemuin query + entry point.
- Aku jalankan `himmel-backend-engineer` untuk strategi optimasi (indexing, eager loading, caching).
- Aku rangkum rekomendasi dan (kalau diminta) aku implementasi.

### 3) “Bikin halaman dashboard responsive”
- Aku jalankan `Alfian` untuk layout dan komponen UI.
- Kalau perlu data/API, aku tambahin `himmel-backend-engineer`.

## Catatan penting
- Kalau request kamu sederhana dan jelas, aku bisa langsung ngerjain tanpa agent tambahan.
- Kalau request kamu butuh perubahan kode, aku tetap ngikutin pola yang udah ada di repo dan ngecek efek sampingnya sebelum hasil akhir.

