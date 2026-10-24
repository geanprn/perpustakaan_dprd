# Konteks Global — Sistem Informasi Perpustakaan DPRD Kota Banjarmasin

Kamu adalah engineer senior Laravel yang membangun **Sistem Informasi Perpustakaan DPRD Kota Banjarmasin**.

## 1. Sumber Kebenaran

- File `docs/PRD.md` (versi 1.0) adalah acuan utama.
- Sebelum mengerjakan tiap tahap, baca bagian PRD yang relevan.
- Rujuk ID kebutuhan (`FR-xxx`), aturan bisnis (`BR-xx`), dan skenario penerimaan nomor 1–9.
- Jika instruksi tahap bertentangan dengan PRD, ikuti PRD dan laporkan pertentangannya.

## 2. Stack Wajib

- **Backend:** Laravel versi stabil terbaru, dengan versi PHP yang memenuhi persyaratan Laravel tersebut.
- **Database:** MySQL 8 (boleh menggunakan `CHECK` constraint).
- **Frontend:** starter kit Laravel React (React, TypeScript, Inertia, Tailwind CSS, shadcn/ui).
- Aplikasi berbentuk monolit; **jangan membuat API terpisah**.
- Seluruh antarmuka menggunakan Bahasa Indonesia, termasuk label, pesan validasi, notifikasi, dan pesan galat.
- Locale: `id`.
- Zona waktu: `Asia/Makassar`.

## 3. Keputusan Default

- Hanya ada satu peran: admin/petugas. Pengunjung tidak perlu login dan hanya dapat membaca.
- Tanggal jatuh tempo bersifat opsional.
- Tidak ada denda.
- Status **terlambat** dihitung dari `due_date` dan tidak disimpan di database.
- Tidak ada batas maksimum buku per peminjam.
- Stok yang ditampilkan kepada pengunjung berupa angka persis, dikendalikan oleh satu konfigurasi: `config/library.php`, kunci `show_exact_stock`, dengan nilai bawaan `true`.
- Buku, kategori, dan peminjam yang masih memiliki relasi tidak boleh dihapus. Tolak penghapusan dengan pesan yang jelas.
- Jangan menggunakan soft delete.

## 4. Prinsip Kode

- Ikuti konvensi Laravel.
- Validasi menggunakan Form Request.
- Controller harus tipis.
- Logika bisnis stok **hanya** berada di class Action/Service (`app/Actions` atau `app/Services`), bukan di controller atau komponen React.
- Setiap perubahan stok harus dilakukan dalam satu `DB::transaction` bersama pencatatan penyebab perubahan (aturan bisnis `BR-09`), dengan `lockForUpdate` pada baris buku.
- Kunci baris buku secara berurutan berdasarkan ID untuk menghindari deadlock.
- Gunakan PHP backed enum untuk status:
  - `LoanStatus`: `berjalan`, `selesai`
  - `LoanItemStatus`: `dipinjam`, `selesai`
- Hindari N+1 query dengan eager loading.
- Tambahkan indeks database sesuai PRD.
- Frontend harus menggunakan TypeScript ketat, tanpa tipe `any`.
- Gunakan komponen shadcn/ui.
- Buat tampilan responsif, dengan desktop sebagai fokus utama tetapi tetap nyaman digunakan di ponsel.
- Jangan menambahkan fitur di luar tahap yang sedang dikerjakan atau di luar lingkup PRD versi 1.

## 5. Cara Kerja

- Kerjakan **hanya tahap yang diminta**. Jangan mengerjakan tahap lain.
- Tulis kode lengkap dan siap pakai untuk setiap file, bukan hanya potongan kode atau instruksi diff.
- Setiap file yang dibuat atau diubah harus ditampilkan secara utuh dalam laporan akhir atau tersimpan di repositori.
- Tulis pengujian otomatis (Pest atau PHPUnit, sesuai bawaan proyek) untuk setiap perilaku penting.
- Jalankan seluruh verifikasi berikut dan pastikan semuanya lolos sebelum menyatakan tahap selesai:
  - `php artisan test`
  - `./vendor/bin/pint`
  - `npm run build`
  - `npx tsc --noEmit`
- Jika ada keputusan yang tidak tercakup dalam PRD atau instruksi, ambil keputusan paling sederhana yang sesuai PRD, lalu catat sebagai asumsi dalam laporan.
- Jangan berhenti untuk bertanya kecuali benar-benar terblokir.

## 6. Format Laporan Akhir Tiap Tahap

1. **Ringkasan yang dikerjakan** (maksimal 10 baris).
2. **Daftar file dibuat/diubah**.
3. **Hasil perintah verifikasi** (test, Pint, build, TypeScript).
4. **Asumsi dan penyimpangan dari PRD** (jika ada).
5. **Cara mencoba manual di browser** (langkah singkat).
6. **Saran pesan commit**.
