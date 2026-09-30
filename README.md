# Laravel Mini POS Backend Lab

Mini POS (Point of Sale) + Inventory System yang dibangun dengan Laravel, sebagai backend engineering playground. Tujuan utama project ini **bukan** membangun aplikasi POS yang siap produksi, melainkan tempat eksplorasi terstruktur untuk memperdalam kemampuan backend: desain skema database, REST API, business logic, database transaction, authorization, automated testing, dan perbandingan perilaku database (SQLite vs MySQL vs PostgreSQL).

Project ini dikerjakan secara bertahap lewat pendekatan "quest" — tiap fitur dibangun dengan keputusan teknis yang dipertimbangkan dan didokumentasikan, bukan sekadar mengikuti tutorial.

## Tech Stack

- **Framework**: Laravel 11
- **Database**: SQLite (environment utama), pernah diuji juga di MySQL dan PostgreSQL untuk perbandingan
- **Auth**: Laravel Sanctum (token-based)
- **Testing**: Pest PHP
- **API Client untuk pengujian**: Postman (collection tersedia di `docs/`)

## Fitur

- CRUD untuk `Category`, `Product`, `Supplier` dengan validasi (Form Request) dan response terformat (API Resource)
- Pagination, search, dan filter pada endpoint listing
- Autentikasi berbasis token (Sanctum)
- Otorisasi berbasis role — **Admin** (akses penuh CRUD data master) vs **Cashier** (hanya baca data master, bisa melakukan penjualan)
- Proses transaksi penjualan (`Sale` + `SaleItem`) dengan:
  - Database transaction (`DB::transaction`) untuk memastikan atomicity
  - Row locking (`lockForUpdate`) untuk mencegah race condition saat stok diperebutkan
  - Validasi stok tidak boleh negatif di level aplikasi
  - Pencatatan otomatis ke `StockMovement` setiap kali stok berkurang
- Automated test (Pest) untuk membuktikan business rule kritis: rollback transaksi saat gagal, restriksi role, dsb.

## Skema Database

Entitas utama: `users`, `categories`, `products`, `suppliers`, `stock_movements`, `sales`, `sale_items`.

Beberapa keputusan desain constraint yang disengaja (bukan default begitu saja):

| Relasi | Constraint | Alasan |
|---|---|---|
| `products.category_id` | `onDelete('restrict')` | Kategori tidak boleh terhapus selama masih ada produk terkait — mencegah kehilangan data produk tanpa sengaja |
| `products.supplier_id` | `nullable()`, `onDelete('nullOnDelete')` | Supplier bersifat opsional; hapus supplier hanya melepas relasi, tidak menghapus produk |
| `sales.user_id` | `onDelete('restrict')` | Riwayat penjualan tidak boleh hilang walau akun kasir dihapus |
| `sale_items.product_id` | `onDelete('restrict')` | Produk yang sudah pernah terjual tidak boleh dihapus, agar histori penjualan tetap akurat |
| `sale_items.sale_id` | `onDelete('cascade')` | Item transaksi memang bagian tak terpisahkan dari transaksinya |

`price_at_sale` di `sale_items` sengaja disimpan sebagai snapshot harga saat transaksi terjadi, tidak bergantung pada `products.price` yang bisa berubah di kemudian hari.

## Business Rule: Stok Tidak Boleh Negatif

Stok dijaga lewat **dua lapisan pertahanan**:

1. **Level aplikasi** (`SaleService`) — validasi eksplisit sebelum stok dikurangi, dengan pesan error yang jelas untuk konsumer API. Ini lapisan pertahanan utama dan konsisten di semua database.
2. **Level database** (`unsignedInteger` pada kolom `stock`) — lapisan tambahan yang bersifat *opportunistic*, karena penegakannya **tidak konsisten** antar database (lihat bagian Perbandingan Database di bawah).

Perubahan stok pada `products` **tidak bisa** dilakukan lewat `PUT /products/{id}` — field `stock` sengaja dihapus dari `UpdateProductRequest` agar setiap perubahan stok pasca-pembuatan produk wajib melalui proses yang tercatat (saat ini: proses penjualan). Ini mencegah stok berubah tanpa jejak audit.

> **Keterbatasan yang diketahui**: endpoint untuk restock manual / koreksi stok di luar proses penjualan belum diimplementasikan. Saat ini stok hanya bisa bertambah lewat seeder atau berkurang lewat `POST /sales`.

## Perbandingan Database: SQLite vs MySQL vs PostgreSQL

Project ini sempat dijalankan di ketiga database untuk membandingkan pengalaman migrasi, konfigurasi, dan penegakan constraint. Migration yang sama (tanpa modifikasi) berhasil dijalankan di ketiganya — tapi perilakunya berbeda untuk kasus tertentu.

**Temuan utama — constraint `unsignedInteger` pada kolom `stock`:**

| Database | Tipe data hasil | Update `stock` menjadi `-5` |
|---|---|---|
| SQLite | `INTEGER` (type affinity, longgar) | Diterima tanpa penolakan |
| MySQL 8+ | `int unsigned` (native, ketat) | **Ditolak** — `SQLSTATE[22003]: Numeric value out of range` |
| PostgreSQL | `integer` polos (tidak ada konsep UNSIGNED) | Diterima tanpa penolakan |

**Kesimpulan**: `unsignedInteger` bukan constraint yang portable antar database — MySQL kebetulan menegakkannya secara native, sementara SQLite dan PostgreSQL tidak. Untuk proteksi database-level yang konsisten di ketiga database, dibutuhkan `CHECK` constraint eksplisit (`CHECK (stock >= 0)`), yang didukung penuh oleh PostgreSQL dan MySQL 8.0.16+, tapi terbatas di SQLite (hanya bisa didefinisikan saat `CREATE TABLE` awal via raw SQL, tidak bisa ditambahkan lewat `ALTER TABLE`).

Karena keterbatasan itu, project ini tetap mengandalkan **validasi level aplikasi** sebagai lapisan pertahanan utama terhadap stok negatif — konsisten di semua database, terlepas dari constraint database-level yang tersedia.

**Catatan lain:**
- Semua migration Laravel berhasil dijalankan tanpa modifikasi di SQLite, MySQL, maupun PostgreSQL.
- Seluruh automated test (`SaleServiceTest`) tetap lolos di ketiga database, karena business rule diuji di level aplikasi.
- PostgreSQL memperkenalkan konsep **schema** (default `public`) sebagai lapisan tambahan antara database dan tabel, yang tidak eksplisit ada di MySQL/SQLite.

## Testing

Business rule kritis diuji lewat automated test (Pest), termasuk:

- Transaksi penjualan berhasil mengurangi stok dan mencatat `StockMovement`
- Transaksi **rollback total** (tidak ada `Sale`/`SaleItem`/`StockMovement` yang tersimpan, stok tidak berubah) ketika salah satu item dalam transaksi kekurangan stok
- Penjualan dengan item kosong atau quantity nol ditolak
- Cashier tidak bisa melakukan operasi admin-only (create/update/delete data master) lewat API — mendapat `403`
- Admin bisa melakukan operasi yang sama — mendapat `200`/`201`

Jalankan seluruh test:
```bash
php artisan test
```

## API Documentation

Collection Postman lengkap (seluruh endpoint, contoh body request, termasuk skenario gagal seperti stok tidak cukup dan akses tanpa izin) tersedia di [`docs/postman_collection.json`](docs/postman_collection.json).

**Cara pakai:**
1. Buka Postman → **Import** → pilih file `docs/postman_collection.json`
2. Sesuaikan `base_url` dengan environment lokal kamu (default: `http://127.0.0.1:8000/api`)
3. Login lewat endpoint `POST /login` untuk mendapatkan token, lalu gunakan token itu di header `Authorization: Bearer {token}` untuk endpoint yang terproteksi

## Instalasi Lokal

```bash
git clone https://github.com/ZamiFaiz24/laravel-mini-pos-backend-lab.git
cd laravel-mini-pos-backend-lab
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate --seed
php artisan serve
```

Akun default hasil seeder (cek `UserSeeder` untuk detail lengkap):
- Admin: `admin@example.com` / `password`
- Cashier: `cashier1@example.com` / `password`

## Roles & Permissions

| Aksi | Admin | Cashier |
|---|---|---|
| Lihat data master (category/product/supplier) | ✅ | ✅ |
| Create/Update/Delete data master | ✅ | ❌ (403) |
| Melakukan transaksi penjualan | ✅ | ✅ |

## Catatan Pengembangan

Project ini dibangun secara bertahap dengan pendekatan quest-based — setiap fitur diawali dengan pemahaman konsep, dikerjakan mandiri, lalu di-review untuk konsistensi desain. Beberapa keputusan teknis yang sempat dipertimbangkan ulang di tengah jalan (bukan diterima begitu saja) turut dicatat di atas sebagai bagian dari proses berpikir, bukan hanya hasil akhirnya.

---

*Project pembelajaran pribadi — dibangun untuk memperdalam pemahaman backend engineering dengan Laravel, sebagai persiapan melamar posisi Junior Backend/Web Developer.*
