# Backend & Database Playground — Mini POS + Inventory System

**Tujuan:** menghidupkan kembali ritme coding, memperdalam Laravel & database (SQLite → MySQL → PostgreSQL), dan membangun portofolio yang bisa dijelaskan saat interview Junior Backend Developer.

**Prinsip:** quest kecil, coba sendiri dulu, baru minta bantuan saat stuck. Tidak mengejar fitur lengkap — mengejar pemahaman.

---

## Peta Quest

### Fase 0 — Fondasi
- **Q0.1** Laravel jalan dengan SQLite (setup, `.env`, `php artisan migrate` sukses)

### Fase 1 — Struktur Data Inti
- **Q1.1** Migration + Model: `categories`, `products`
- **Q1.2** Migration + Model: `suppliers`
- **Q1.3** Relationship dasar: Product belongsTo Category, belongsTo Supplier
- **Q1.4** Seeder/Factory untuk data dummy yang realistis

### Fase 2 — Stock & Sales Domain
- **Q2.1** Migration: `stock_movements` (relasi ke Product, tipe in/out, alasan)
- **Q2.2** Migration: `sales` & `sale_items` (relasi many-to-many via pivot table dengan atribut tambahan seperti qty & harga saat transaksi)
- **Q2.3** Relationship lanjutan: hasMany, belongsToMany, accessor untuk hitung total stok/omzet

### Fase 3 — REST API Dasar
- **Q3.1** CRUD API untuk Category & Product (Controller, Route resource)
- **Q3.2** Form Request untuk validasi
- **Q3.3** API Resource untuk shaping response
- **Q3.4** Pagination & search/filter (query scope, request query params)

### Fase 4 — Auth & Authorization
- **Q4.1** Authentication API dengan Sanctum (login, token, logout)
- **Q4.2** Role Admin vs Cashier (kolom role, middleware/gate sederhana)
- **Q4.3** Policy: aturan siapa boleh apa (mis. Cashier tidak boleh hapus product)

### Fase 5 — Business Logic Inti (bagian paling "backend")
- **Q5.1** Service class untuk proses transaksi penjualan
- **Q5.2** Database transaction (DB::transaction) — sale + sale_items + stock_movements harus atomic
- **Q5.3** Business rule: stok tidak boleh negatif, validasi di level service bukan hanya form
- **Q5.4** Automated testing (Pest/PHPUnit): stok negatif ditolak, Cashier tidak bisa akses endpoint tertentu, transaksi rollback saat gagal

### Fase 6 — Perbandingan Database
- **Q6.1** Migrasi project ke MySQL: bandingkan konfigurasi `.env`, tipe data, constraint, behavior migration
- **Q6.2** Migrasi project ke PostgreSQL: bandingkan lagi — sequence, tipe data (enum native, JSONB), constraint, query behavior berbeda dengan SQLite/MySQL

### Fase 7 — Opsional (jika masih semangat)
- **Q7.1** Queue: notifikasi async saat stok menipis
- **Q7.2** Scheduler: laporan penjualan harian otomatis
- **Q7.3** Deployment sederhana ke Linux/Nginx

---

## Cara Kerja Tiap Quest
1. Penjelasan singkat konsep yang relevan.
2. Requirement jelas (apa yang harus dicapai, bukan langkah persis).
3. Kamu coding sendiri dulu.
4. Diskusi saat ada error, kebingungan, atau ingin review keputusan desain.
5. Refleksi singkat: keputusan teknis apa yang diambil dan kenapa (bekal cerita interview).
