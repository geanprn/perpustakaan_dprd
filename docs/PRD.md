## PRD Sistem Informasi Perpustakaan DPRD Kota Banjarmasin

Versi 1.0 | Oktober 2026 | Status: Draf untuk ditinjau pembimbing/kasubag

## 1. Ringkasan Produk

Sistem Informasi Perpustakaan DPRD Kota Banjarmasin adalah aplikasi web untuk membantu petugas mengelola koleksi buku dan transaksi peminjaman, serta memberi pengunjung akses baca ke katalog tanpa login. Sistem menggantikan pencatatan manual dengan data koleksi, stok, peminjaman, dan pengembalian yang terpusat dan dapat dilaporkan.

## 2. Latar Belakang dan Masalah

Perpustakaan membutuhkan cara yang rapi untuk mengetahui buku apa yang dimiliki, berapa yang tersedia, siapa yang sedang meminjam, dan riwayat transaksi. Tanpa sistem, informasi stok dan peminjaman rawan tidak akurat, sulit dicari, dan sulit direkap menjadi laporan.

## 3. Tujuan dan Indikator Keberhasilan

| Tujuan | Indikator keberhasilan |
| --- | --- |
| Katalog dapat dicari publik Pengunjung dapat mencari berdasarkan judul, |   |
|   | pengarang, atau kategori dan melihat detail buku |
|   | tanpa login |
| Stok akurat | Stok tersedia selalu sama dengan jumlah |
|   | eksemplar dikurangi jumlah yang sedang dipinjam |
| Transaksi tercatat lengkap Setiap peminjaman dan pengembalian tersimpan |   |
|   | dengan tanggal dan jumlah |
| Pelaporan mudah | Petugas dapat menampilkan laporan koleksi, stok, |
|   | dan transaksi per periode tanpa rekap manual |


## 4. Ruang Lingkup

## 4.1 Dalam Lingkup

- Katalog publik: daftar, pencarian, filter kategori, dan detail buku.

- Autentikasi admin/petugas (username dan password).

- Manajemen buku dan kategori, termasuk jumlah eksemplar dan stok.

- Manajemen data peminjam.

- Pencatatan peminjaman (satu transaksi, beberapa judul, jumlah per judul).

- Pencatatan pengembalian (bisa sebagian).

- Laporan dan riwayat transaksi.

## 4.2 Di Luar Lingkup (Versi 1)

- Peminjaman mandiri atau reservasi online oleh pengunjung.

- Kode inventaris unik per eksemplar fisik (stok hanya dicatat per judul).

- Perhitungan denda keterlambatan.

Registrasi atau login pengunjung/anggota.

- Notifikasi email/WhatsApp, barcode/QR, dan integrasi sistem lain.

## 5. Pengguna dan Hak Akses

| Peran | Autentikasi | Hak akses |
| --- | --- | --- |
| Pengunjung | Tidak perlu login Hanya baca: katalog, pencarian, detail buku |   |
| Admin/Petugas Login |   | Seluruh pengelolaan: buku, kategori, stok, |
|   |   | peminjam, peminjaman, pengembalian, laporan |

Catatan: alur bisnis menyebut "Admin/Petugas" sebagai satu aktor. Pada versi 1 cukup satu peran. Pemisahan peran (misalnya admin penuh dan petugas terbatas) dicantumkan sebagai pertanyaan terbuka.

## 6. User Stories

## Pengunjung

- Sebagai pengunjung, saya ingin melihat daftar buku agar tahu koleksi perpustakaan.

- Sebagai pengunjung, saya ingin mencari buku berdasarkan judul, pengarang, atau kategori agar cepat menemukan yang saya butuhkan.


- Sebagai pengunjung, saya ingin melihat detail buku dan ketersediaannya agar tahu apakah buku bisa dipinjam di perpustakaan.

## Admin/Petugas

- Sebagai petugas, saya ingin login dengan aman agar hanya petugas yang bisa mengubah data.

- Sebagai petugas, saya ingin menambah, mengubah, dan menghapus data buku dan kategori agar katalog selalu mutakhir.

- Sebagai petugas, saya ingin mengelola data peminjam agar identitas peminjam tercatat.

- Sebagai petugas, saya ingin mencatat peminjaman beberapa judul sekaligus dan stok berkurang otomatis.

- Sebagai petugas, saya ingin mencatat pengembalian (penuh atau sebagian) dan stok bertambah otomatis.

- Sebagai petugas, saya ingin melihat laporan dan riwayat transaksi untuk keperluan pelaporan kepada atasan.

## 7. Kebutuhan Fungsional

Prioritas: P0 wajib untuk rilis pertama, P1 penting, P2 opsional/jika waktu cukup.


## 7.1 Halaman Publik dan Katalog

| ID | Kebutuhan | Prioritas |
| --- | --- | --- |
| FR-PUB-01 Halaman utama menampilkan pengantar |   | P0 |
|   | perpustakaan, kotak pencarian, dan akses ke |   |
|   | katalog |   |
| FR-PUB-02 Katalog menampilkan daftar buku dengan paginasi |   | P0 |
| FR-PUB-03 Pencarian berdasarkan judul, pengarang, atau |   | P0 |
|   | kategori |   |
| FR-PUB-04 Filter berdasarkan kategori |   | P1 |
| FR-PUB-05 Halaman detail buku: judul, pengarang, kategori, |   | P0 |
|   | penerbit, tahun terbit, lokasi rak, jumlah eksemplar, |   |
|   | stok tersedia |   |
| FR-PUB-06 Pengunjung tidak melihat tombol atau rute apa pun |   | P0 |
|   | untuk mengubah data atau meminjam |   |
| FR-PUB-07 Pesan kosong yang jelas jika pencarian tidak |   | P1 |
|   | menemukan hasil |   |

## 7.2 Autentikasi Admin

| ID | Kebutuhan | Prioritas |
| --- | --- | --- |
| FR-AUTH-01 Login dengan username dan password |   | P0 |
| FR-AUTH-02 Jika kredensial tidak valid, tampilkan pesan |   | P0 |
|   | kesalahan dan izinkan mencoba lagi |   |
| FR-AUTH-03 Jika valid, arahkan ke dashboard admin |   | P0 |
| FR-AUTH-04 Logout |   | P0 |
| FR-AUTH-05 Seluruh rute admin dilindungi middleware |   | P0 |
|   | autentikasi |   |
| FR-AUTH-06 Pembatasan percobaan login (rate limiting) |   | P1 |
| FR-AUTH-07 Ubah password oleh admin |   | P2 |


## 7.3 Dashboard Admin

| ID | Kebutuhan | Prioritas |
| --- | --- | --- |
| FR-DSH-01 Ringkasan: total judul, total eksemplar, total stok |   | P1 |
|   | tersedia, jumlah peminjaman aktif |   |
| FR-DSH-02 Navigasi ke menu buku, kategori, peminjam, |   | P0 |
|   | peminjaman, pengembalian, laporan |   |

## 7.4 Pengelolaan Buku dan Kategori

| ID | Kebutuhan | Prioritas |
| --- | --- | --- |
| FR-BK-01 Daftar buku dengan pencarian dan paginasi |   | P0 |
| FR-BK-02 Tambah buku: judul, pengarang, kategori, penerbit, |   | P0 |
|   | tahun terbit, lokasi rak, jumlah eksemplar |   |
| FR-BK-03 Ubah data buku |   | P0 |
| FR-BK-04 Hapus buku, dengan konfirmasi; ditolak jika buku |   | P0 |
|   | punya riwayat peminjaman (lihat aturan bisnis BR- |   |
|   | 08) |   |
| FR-BK-05 Validasi input; tampilkan pesan galat per kolom |   | P0 |
| FR-BK-06 Saat jumlah eksemplar diubah, stok tersedia |   | P0 |
|   | disesuaikan; jumlah eksemplar tidak boleh kurang |   |
|   | dari jumlah yang sedang dipinjam |   |
| FR-BK-07 CRUD kategori; kategori yang masih dipakai buku |   | P0 |
|   | tidak bisa dihapus |   |
| FR-BK-08 Notifikasi berhasil/gagal setelah setiap proses |   | P0 |
| FR-BK-09 Unggah sampul buku |   | P2 |

## 7.5 Pengelolaan Peminjam

| ID | Kebutuhan | Prioritas |
| --- | --- | --- |
| FR-PJ-01 Daftar peminjam dengan pencarian (nama, nomor |   | P0 |
|   | identitas) |   |
| FR-PJ-02 Tambah, ubah, hapus data peminjam |   | P0 |


| ID | Kebutuhan | Prioritas |
| --- | --- | --- |
| FR-PJ-03 Data peminjam: nama, nomor identitas |   | P0 |
|   | (NIP/NIK/lainnya), instansi/unit, nomor telepon, |   |
|   | alamat (opsional) |   |
| FR-PJ-04 Peminjam yang punya riwayat transaksi tidak bisa |   | P0 |
|   | dihapus |   |
| FR-PJ-05 Tambah peminjam baru langsung dari form |   | P1 |
|   | peminjaman |   |

## 7.6 Peminjaman

| ID | Kebutuhan | Prioritas |
| --- | --- | --- |
| FR-LN-01 Petugas mencari/memilih peminjam yang sudah |   | P0 |
|   | ada atau mencatat peminjam baru |   |
| FR-LN-02 Petugas memilih satu atau beberapa judul buku dan |   | P0 |
|   | mengisi jumlah per judul |   |
| FR-LN-03 Sistem memeriksa stok per judul; jika kurang, |   | P0 |
|   | tampilkan pesan dan minta perbaikan |   |
|   | jumlah/pilihan |   |
| FR-LN-04 Petugas mengisi tanggal peminjaman (default hari |   | P0 |
|   | ini) dan tanggal jatuh tempo (opsional) |   |
| FR-LN-05 Tanggal jatuh tempo tidak boleh lebih awal dari |   | P0 |
|   | tanggal peminjaman |   |
| FR-LN-06 Sistem menyimpan transaksi beserta rincian buku |   | P0 |
|   | dan jumlah dalam satu transaksi database (atomik) |   |
| FR-LN-07 Stok tersedia berkurang sesuai jumlah yang |   | P0 |
|   | dipinjam |   |
| FR-LN-08 Tampilkan konfirmasi peminjaman dan nomor |   | P0 |
|   | transaksi |   |
| FR-LN-09 Daftar transaksi dengan filter status (berjalan, |   | P0 |
|   | selesai, terlambat) dan pencarian peminjam |   |
| FR-LN-10 Cetak bukti peminjaman |   | P2 |


## 7.7 Pengembalian

| ID | Kebutuhan | Prioritas |
| --- | --- | --- |
| FR-RT-01 Petugas mencari transaksi peminjaman (nomor |   | P0 |
|   | transaksi atau nama peminjam) |   |
| FR-RT-02 Sistem menampilkan rincian buku: jumlah dipinjam, |   | P0 |
|   | sudah dikembalikan, sisa, dan status |   |
| FR-RT-03 Petugas mengisi jumlah yang dikembalikan per |   | P0 |
|   | judul |   |
| FR-RT-04 Validasi: jumlah pengembalian tidak boleh melebihi |   | P0 |
|   | sisa yang belum dikembalikan dan tidak boleh |   |
|   | nol/negatif |   |
| FR-RT-05 Sistem menyimpan tanggal dan jumlah |   | P0 |
|   | pengembalian sebagai catatan tersendiri (riwayat) |   |
| FR-RT-06 Stok tersedia bertambah sesuai jumlah yang benar- |   | P0 |
|   | benar dikembalikan |   |
| FR-RT-07 Status rincian menjadi selesai jika seluruh jumlah |   | P0 |
|   | telah dikembalikan; status transaksi selesai jika |   |
|   | semua rincian selesai |   |
| FR-RT-08 Tampilkan konfirmasi pengembalian |   | P0 |
| FR-RT-09 Penanda "terlambat" sebagai informasi jika lewat |   | P1 |
|   | jatuh tempo dan belum selesai; tanpa denda |   |

## 7.8 Laporan

| ID | Kebutuhan | Prioritas |
| --- | --- | --- |
| FR-RP-01 Daftar koleksi dan jumlah eksemplar |   | P0 |
| FR-RP-02 Ketersediaan stok buku |   | P0 |
| FR-RP-03 Riwayat peminjaman dengan filter rentang tanggal |   | P0 |
| FR-RP-04 Riwayat pengembalian dengan filter rentang |   | P0 |
|   | tanggal |   |
| FR-RP-05 Transaksi yang belum selesai |   | P0 |


| ID | Kebutuhan | Prioritas |
| --- | --- | --- |
| FR-RP-06 Rekapitulasi peminjaman dan pengembalian per |   | P1 |
|   | periode |   |
| FR-RP-07 Cetak (halaman siap cetak) dan/atau ekspor PDF |   | P1 |
|   | atau Excel |   |

## 8. Aturan Bisnis

| ID | Aturan |
| --- | --- |
| BR-01 Pengunjung hanya memiliki akses baca. |   |
| BR-02 Jumlah eksemplar dicatat per judul, bukan per |   |
|   | eksemplar fisik. |
| BR-03 Stok tersedia = jumlah eksemplar dikurangi total |   |
|   | jumlah yang sedang dipinjam (belum |
|   | dikembalikan). |
| BR-04 Peminjaman hanya boleh jika jumlah yang diminta |   |
|   | tidak melebihi stok tersedia. |
| BR-05 Satu transaksi peminjaman boleh memuat |   |
|   | beberapa judul dengan jumlah masing-masing. |
| BR-06 Pengembalian boleh sebagian; jumlah kumulatif |   |
|   | pengembalian tidak boleh melebihi jumlah |
|   | dipinjam. |
| BR-07 Tidak ada denda. Status terlambat hanya informasi, |   |
|   | berlaku jika tanggal jatuh tempo diisi. |
| BR-08 Buku, kategori, dan peminjam yang memiliki |   |
|   | riwayat/relasi tidak boleh dihapus permanen agar |
|   | riwayat tetap utuh. Alternatif: soft delete atau |
|   | nonaktifkan (lihat pertanyaan terbuka). |
| BR-09 Setiap perubahan stok harus terjadi bersamaan |   |
|   | dengan pencatatan transaksi penyebabnya dalam |
|   | satu transaksi database. |


## 9. Rancangan Data (Usulan Awal)

## 9.1 Entitas Utama

| Tabel | Kolom utama | Keterangan |
| --- | --- | --- |
| users | id, name, username (unik), password, | Akun admin/petugas |
|   | timestamps |   |
| categories | id, name (unik), timestamps | Kategori buku |
| books | id, category_id (FK), title, author, | Koleksi per judul; stok tingkat judul |
|   | publisher, publication_year, |   |
|   | shelf_location, total_copies, |   |
|   | available_copies, timestamps |   |
| borrowers | id, name, identity_number, institution, | Data peminjam |
|   | phone, address, timestamps |   |
| loans | id, loan_number (unik), borrower_id | Header transaksi |
|   | (FK), user_id (FK, petugas pencatat), |   |
|   | loan_date, due_date (nullable), status, |   |
|   | notes, timestamps |   |
| loan_items id, loan_id (FK), book_id (FK), |   | Rincian buku per transaksi |
|   | quantity, returned_quantity, status |   |
| returns | id, loan_item_id (FK), user_id (FK), | Riwayat pengembalian (mendukung |
|   | return_date, quantity, timestamps | pengembalian sebagian) |

## 9.2 Relasi

- Kategori 1 : N Buku.

- Peminjam 1 : N Peminjaman.

- Peminjaman 1 : N Rincian Peminjaman.

- Buku 1 : N Rincian Peminjaman.

- Rincian Peminjaman 1 : N Pengembalian.

- Petugas (user) 1 : N Peminjaman dan Pengembalian (sebagai pencatat).

## 9.3 Catatan Integritas Data

- Tambahkan constraint: available_copies >= 0 , available_copies <= total_copies , returned_quantity <= quantity .


- Gunakan DB::transaction dan penguncian baris ( lockForUpdate ) pada buku saat peminjaman/pengembalian agar stok tidak salah jika dua petugas bekerja bersamaan.

- available_copies bersifat turunan yang disimpan untuk performa; sediakan pemeriksaan konsistensi atau perintah artisan rekonsiliasi.

- Status rincian: dipinjam atau selesai . Status transaksi: berjalan atau selesai . Status "terlambat" dihitung dari due_date , tidak disimpan.

## 10. Kebutuhan Non-Fungsional

| Kategori | Kebutuhan |
| --- | --- |
| Keamanan | Password di-hash (bcrypt/argon2 bawaan Laravel), |
|   | perlindungan CSRF, validasi di sisi server, otorisasi |
|   | pada seluruh rute admin, tidak ada data sensitif di |
|   | katalog publik |
| Kinerja | Halaman katalog dan pencarian tampil kurang dari |
|   | 2 detik pada koleksi hingga beberapa ribu judul; |
|   | gunakan paginasi dan indeks pada judul, |
|   | pengarang, kategori |
| Usability | Antarmuka responsif (desktop utama, tetap |
|   | nyaman di ponsel), bahasa Indonesia, pesan galat |
|   | jelas, konfirmasi sebelum menghapus |
| Keandalan | Backup database berkala (disepakati dengan pihak |
|   | IT DPRD); transaksi database atomik |
| Maintainability Kode mengikuti konvensi Laravel, validasi memakai |   |
|   | Form Request, logika bisnis stok dipusatkan di |
|   | service/action class, ada pengujian otomatis pada |
|   | aturan stok |
| Kompatibilitas Browser modern (Chrome, Edge, Firefox versi |   |
|   | terbaru) |
| Audit | Setiap transaksi mencatat petugas dan waktu |
|   | pembuatan |

## 11. Arsitektur dan Teknologi

- Backend: Laravel (autentikasi memakai fitur starter kit).

- Database: MySQL.


- Frontend: starter kit React dengan React, TypeScript, Inertia, Tailwind CSS, dan shadcn/ui.

- Pola: aplikasi monolit Laravel dengan halaman React dirender lewat Inertia; tidak perlu API terpisah.

## Usulan Struktur Halaman

| Area Halaman |
| --- |
| Publik Beranda, Katalog (pencarian dan filter), Detail Buku |
| Admin Login, Dashboard, Buku (daftar, tambah, ubah), |
| Kategori, Peminjam, Peminjaman (daftar, buat, |
| detail), Pengembalian (cari transaksi, form), |
| Laporan |

Catatan: starter kit Laravel bawaan menyertakan fitur registrasi, reset password, dan pengaturan profil. Karena akun hanya dibuat oleh pihak berwenang, nonaktifkan registrasi publik dan buat akun admin lewat seeder.

## 12. Kriteria Penerimaan (Contoh Skenario Kunci)

- 1. Peminjaman berhasil. Diberikan buku A dengan stok tersedia 5, saat petugas mencatat peminjaman 2 eksemplar, maka transaksi tersimpan dan stok tersedia buku A menjadi 3.

- 2. Stok tidak cukup. Diberikan buku A stok tersedia 1, saat petugas meminta 2 eksemplar, maka sistem menolak, menampilkan pesan stok tidak mencukupi, dan tidak ada data tersimpan.

- 3. Pengembalian sebagian. Diberikan rincian dipinjam 3, saat petugas mencatat pengembalian 1, maka stok bertambah 1, sisa 2, status rincian tetap dipinjam.

- 4. Pengembalian melebihi sisa. Diberikan sisa 2, saat petugas mengisi 3, maka sistem menolak dengan pesan validasi.

- 5. Pengembalian lengkap. Setelah seluruh jumlah dikembalikan, status rincian menjadi selesai; jika semua rincian selesai, status transaksi menjadi selesai.

- 6. Akses pengunjung. Pengunjung yang membuka rute admin diarahkan ke halaman login.

- 7. Login gagal. Username/password salah menampilkan pesan kesalahan dan tetap di halaman login.

- 8. Tanpa denda. Transaksi melewati jatuh tempo hanya menampilkan penanda terlambat, tanpa nominal denda.


- 9. Edit eksemplar. Mengurangi jumlah eksemplar di bawah jumlah yang sedang dipinjam ditolak.

## 13. Rencana Rilis (Usulan)

| Tahap | Cakupan | Keluaran |
| --- | --- | --- |
| 1. Analisis dan | Konfirmasi PRD, ERD, wireframe | Dokumen disetujui kasubag |
| desain |   |   |
| 2. Fondasi | Setup proyek, autentikasi, seeder | Login dan kerangka admin |
|   | admin, layout | berjalan |
| 3. Katalog dan | CRUD kategori dan buku, katalog publik Katalog dapat diakses |   |
| koleksi |   | publik |
| 4. Transaksi | Peminjam, peminjaman, pengembalian, | Alur transaksi lengkap dan |
|   | aturan stok | teruji |
| 5. Laporan | Laporan, filter periode, cetak/ekspor | Laporan siap pakai |
| 6. Uji dan serah | Uji penerimaan dengan petugas, | Sistem live dan |
| terima | perbaikan, panduan singkat, | dokumentasi |
|   | deployment |   |

Sesuaikan durasi tiap tahap dengan sisa masa magang.

## 14. Risiko dan Mitigasi

| Risiko | Dampak | Mitigasi |
| --- | --- | --- |
| Stok tingkat judul tidak dapat | Stok bisa tidak sesuai | Sediakan fitur penyesuaian |
| melacak eksemplar hilang/rusak | kondisi fisik | stok manual oleh admin (P2) |
|   |   | dan catat alasan |
| Dua petugas mengubah stok | Stok salah | Transaksi database dan |
| bersamaan |   | penguncian baris |
| Data koleksi awal belum | Peluncuran tertunda | Sediakan impor CSV/Excel |
| terdigitalisasi |   | untuk data awal (P1) |
| Kebutuhan berubah dari pihak | Ruang lingkup | Tetapkan lingkup versi 1 dan |
| instansi | melebar | catat permintaan baru untuk |
|   |   | versi berikutnya |


| Risiko | Dampak | Mitigasi |
| --- | --- | --- |
| Hosting/server instansi belum | Hambatan | Konfirmasi lingkungan server |
| jelas | deployment | (PHP, MySQL, domain) sejak |
|   |   | awal |

## 15. Pertanyaan Terbuka untuk Kasubag/Pihak Instansi

- 1. Apakah perlu lebih dari satu peran (admin penuh dan petugas terbatas), dan berapa jumlah akun?

- 2. Siapa peminjam yang dilayani (pegawai sekretariat, anggota dewan, masyarakat umum)? Data identitas apa yang wajib dicatat?

- 3. Apakah tanggal jatuh tempo wajib atau opsional, dan berapa durasi pinjam standar?

- 4. Apakah ada batas maksimum buku per peminjam atau per transaksi?

- 5. Apakah stok yang "dipublikasikan" ke pengunjung berupa angka stok persis atau cukup status tersedia/habis?

- 6. Format laporan yang dibutuhkan (cetak, PDF, Excel) dan apakah ada kop surat resmi?

- 7. Apakah ada data koleksi yang sudah ada (Excel) untuk diimpor?

- 8. Di mana sistem akan di-hosting dan siapa yang mengelola backup?

- 9. Apakah penghapusan data memakai soft delete atau cukup dinonaktifkan?

## 16. Asumsi

- Sistem dipakai di jaringan/internet instansi dengan akses peramban modern.

- Peminjaman dilakukan langsung di perpustakaan; website hanya mencatat, bukan tempat peminjam mengajukan.

- Pengguna admin dibuat oleh pengembang atau administrator, bukan lewat pendaftaran publik.

- Bahasa antarmuka Bahasa Indonesia.
