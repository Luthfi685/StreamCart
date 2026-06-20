# 📊 Analisis Frontend - StreamCart

## 🏗️ Tech Stack

| Layer | Teknologi | Versi |
|---|---|---|
| Framework UI | **Ionic + Angular** | Ionic 8, Angular 20 |
| Build Tool | Angular CLI | ^20.0.0 |
| Mobile Runtime | **Capacitor** | 8.3.0 |
| Bahasa | TypeScript | ~5.9.0 |
| Testing | Karma + Jasmine | ~6.4.0 |
| Linting | ESLint + Angular ESLint | ~9.x |

> [!NOTE]
> Ini adalah aplikasi **hybrid mobile** (Ionic + Capacitor) yang bisa di-deploy sebagai web app sekaligus Android/iOS native app. Angular versi 20 yang digunakan adalah versi yang sangat baru.

---

## 📁 Struktur Folder

```
FRONTEND/
├── src/
│   ├── app/                    ← Semua halaman (pages)
│   │   ├── home/               ← Halaman utama (daftar live)
│   │   ├── login/              ← Login
│   │   ├── register/           ← Registrasi
│   │   ├── live-room/          ← Ruang live streaming ⭐ Core Feature
│   │   ├── checkout/           ← Pembayaran
│   │   ├── transactions/       ← Riwayat transaksi
│   │   ├── profile/            ← Profil user
│   │   ├── settings/           ← Pengaturan
│   │   ├── add-address/        ← Tambah alamat
│   │   ├── seller-dashboard/   ← Dashboard penjual
│   │   ├── create-live/        ← Buat sesi live baru
│   │   ├── manage-products/    ← Kelola produk
│   │   ├── store-profile/      ← Profil toko
│   │   └── admin-dashboard/    ← Dashboard admin
│   ├── assets/                 ← Gambar & icon
│   ├── environments/           ← Config dev/prod
│   ├── theme/                  ← Ionic theme variables
│   └── global.scss             ← Global styles
├── backend/                    ← 🚨 Backend Node.js (di dalam folder frontend!)
│   ├── server.js               ← Express API Server (671 baris)
│   ├── database.sqlite         ← SQLite Database (~150MB)
│   ├── package.json
│   └── uploads/                ← File upload (bukti bayar, dll)
├── angular.json
├── package.json
└── capacitor.config.ts
```

---

## 📄 Halaman & Fitur

### 👥 Roles User
Sistem punya 3 role: **buyer**, **seller**, dan **admin** — dengan routing yang berbeda setelah login.

### 🗺️ Routing Map

```
/ → redirect ke /login

/login          → Login (fallback mock login jika backend mati)
/register       → Registrasi akun baru
/home           → Feed live sessions yang aktif
/live-room      → Ruang live streaming (host/viewer mode)
/checkout       → Proses pembelian + upload bukti bayar
/transactions   → Riwayat transaksi
/profile        → Profil user
/settings       → Pengaturan (2FA, bank, password)
/add-address    → Tambah alamat pengiriman
/seller-dashboard  → Dashboard penjual (stats, live aktif, orders)
/create-live       → Buat sesi live baru
/manage-products   → CRUD produk
/store-profile     → Edit profil toko
/admin-dashboard   → Panel admin (users, orders, rooms, products, stats)
```

---

## ⭐ Fitur Core: Live Room

Ini adalah halaman paling kompleks (`live-room.page.ts` = 416 baris). Fitur-fiturnya:

| Fitur | Detail |
|---|---|
| **Kamera Asli** | `getUserMedia()` untuk video + audio |
| **Kamera Mock** | Canvas animation sebagai fallback jika kamera tidak ada |
| **Pinned Product** | Host bisa pin produk ke layar viewer |
| **Chat** | Chat message lokal (hanya UI, belum real-time WebSocket) |
| **Like System** | Floating hearts dengan animasi |
| **Follow** | Toggle follow seller |
| **Polling** | Polling setiap 3 detik untuk update produk pinned |
| **Buy in Live** | Buyer bisa langsung checkout dari live room |
| **End Live** | `keepalive: true` fetch saat halaman ditutup |

---

## 🔌 Backend API (Embedded - `backend/server.js`)

Backend adalah **Node.js + Express** dengan **SQLite** yang tergabung dalam folder frontend!

### Database Schema

```sql
users        → id, username, email, password, role, bank_*, 2fa_*, store_*
products     → id, seller_id, name, description, price, stock, image_url
transactions → id, buyer_id, product_id, session_id, quantity, total_price,
               status, shipping_address, payment_proof, created_at
live_sessions→ id, seller_id, title, image_url, status, viewer_count,
               pinned_product_id, likes_count, created_at
```

### Endpoint Summary

| Group | Endpoints |
|---|---|
| **Auth** | `POST /api/login`, `POST /api/register` |
| **User** | `GET /me`, `PUT /password`, `PUT /bank`, `PUT /2fa`, `POST /register-seller` |
| **Admin** | `GET/POST/PUT/DELETE /api/admin/users`, `GET /api/admin/stats`, `GET /api/admin/live-sessions` |
| **Products** | `GET/POST/PUT/DELETE /api/products` |
| **Transactions** | `GET/POST /api/transactions`, `PUT /api/transactions/:id/status` |
| **Live Sessions** | `GET/POST /api/live-sessions`, `PUT .../end`, `PUT .../pin`, `PUT .../unpin` |
| **Regions Proxy** | `GET /api/regions/provinces`, `.../cities/:id`, `.../districts/:id` |

### Seed Data (default)
- `admin` / `admin123`
- `seller` / `seller123`
- `buyer` / `buyer123`

---

## ⚠️ Temuan & Masalah

### 🔴 Critical Issues

1. **Hardcoded API URL** — Semua HTTP call pakai `http://localhost:3000` secara hardcode. Tidak bisa di-deploy ke production tanpa refactor besar.
2. **Backend di dalam folder Frontend** — `backend/` di dalam `FRONTEND/` membuat struktur proyek tidak clean (jika ini akan diintegrasikan ke Laravel, harus dipindahkan).
3. **JWT Secret Hardcoded** — `supersecretkey_for_livecommerce_app` hardcoded di `server.js`.
4. **Email Credentials Placeholder** — `EMAIL_ANDA_DISINI@gmail.com` masih placeholder di `server.js`.

### 🟡 Perlu Perhatian

5. **Mock Login System** — Login bisa bypass backend jika server mati (ada `mockLogin()`). Ini berguna untuk dev tapi berbahaya di production.
6. **Chat tidak Real-time** — Chat di live room hanya lokal (tidak ada WebSocket). Pesan tidak tersinkron antar user.
7. **`environment.ts` kosong** — File ini hanya `production: false`, API URL tidak dikonfigurasi di sini (malah hardcoded di tiap komponen).
8. **Seller dashboard sangat minim** — Hanya tampilkan `activeLives` dan `totalOrders`, tidak ada chart atau detail.
9. **`register` page belum fungsional** — Login page menampilkan toast "Fitur registrasi akan segera hadir" untuk button register.

> [!IMPORTANT]
> Karena ini proyek Laravel (`StreamCart`), kemungkinan besar backend Express ini adalah **prototype/placeholder** yang akan digantikan dengan Laravel API. Perlu koordinasi antara endpoint di `server.js` dan route yang akan dibuat di Laravel.

---

## 💡 Rekomendasi

1. **Pindahkan API URL ke `environment.ts`** agar mudah diganti saat production.
2. **Implementasi WebSocket** (misalnya dengan Socket.io atau Laravel Reverb) untuk chat real-time.
3. **Hapus `mockLogin()` / mode offline** sebelum production, atau batasi hanya di build development.
4. **Pisahkan folder backend** dari FRONTEND jika akan dipakai paralel dengan Laravel backend.
5. **Tambahkan Angular Guards** (`CanActivate`) untuk proteksi route berdasarkan role, bukan hanya cek di `ngOnInit`.
6. **Buat Service layer** — HTTP calls tersebar di setiap page.ts, sebaiknya dibuat `AuthService`, `ProductService`, `LiveSessionService`, dll.
