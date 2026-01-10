# TicketApp – Aplikasi Pengelolaan Tiket Transportasi (CI4 + Ajax + DataTables)

TicketApp adalah mini aplikasi web untuk pengelolaan tiket transportasi berbasis regional (daerah). Sistem ini mendukung pengaturan master tiket dan master harga (master price) serta transaksi pemesanan tiket oleh customer dengan stok otomatis yang ter-update secara real-time.

Aplikasi dibangun sebagai prototype yang clean, cepat, dan mudah dikembangkan untuk skala lebih besar.

---

## ✨ Fitur Utama

### 1) Authentication & Role Based Access
- Login berbasis email + password (session based)
- 3 role akses:
  - **Superadmin**
  - **Regional Admin**
  - **Customer**
- Menu dan hak akses otomatis mengikuti role user

### 2) Master Ticket (Superadmin & Regional Admin)
- CRUD tiket transportasi dengan informasi:
  - code (auto generate)
  - asal, tujuan (dropdown regions)
  - class: ekonomi / non-ekonomi / luxury
  - harga otomatis dari master price (reference)
  - stock, no polisi kendaraan
  - tanggal & jam keberangkatan
- Data listing menggunakan DataTables (server-side)
- **Regional Admin hanya boleh akses data tiket wilayahnya sendiri** (secure di backend)

### 3) Master Price (Superadmin Only)
- CRUD master harga sebagai acuan tiket
- Kombinasi unik:
  - region + origin + destination + class
- Status Active/Inactive untuk kontrol harga

### 4) Order Tiket (Customer)
- Customer melihat semua tiket dalam tampilan **card-based UI**
- Tombol “Pesan”
  - membuat order status `paid`
  - mengurangi stok otomatis
- Jika stok habis:
  - card menjadi abu-abu
  - tombol pesan nonaktif
- Jika pesan tiket yang sama:
  - **tidak membuat baris order baru**
  - namun menambah qty (cart-like)
- Cancel order:
  - menghapus order
  - stok tiket kembali sesuai qty

---

## 🧩 Struktur Database

Tabel utama:
- `users`
- `roles`
- `regions`
- `tickets`
- `master_prices`
- `orders`

Konsep penting:
- `origin` & `destination` menggunakan `regions.code` (JKT/BDG/SMG/SBY)
- `tickets.region_id` mengacu pada origin region (id asal)

---

## ⚙️ Spesifikasi Teknis

### Stack Teknologi
- Backend: **PHP 8.x**
- Framework: **CodeIgniter 4**
- Database: **MySQL / MariaDB**
- Frontend:
  - **Bootstrap 5**
  - **jQuery**
  - **DataTables (AJAX server-side)**
- Template UI:
  - Theme warna aksen brand (biru-kuning) + navbar custom

### Arsitektur & Konsep Teknis
- Session-based Authentication
- Role-based Access Control (RBAC)
- Soft Delete untuk tabel data inti (tickets, master_prices, orders)
- Server-side pagination untuk performa data listing
- AJAX-driven CRUD (modal-based form)
- Otomatisasi data:
  - Harga tiket mengambil referensi dari master price
  - Stok tiket terupdate otomatis berdasarkan transaksi order & cancel

---

## 🚀 Cara Install & Menjalankan

### 1) Clone Project
```bash
git clone <repo-url>
cd ticketapp
