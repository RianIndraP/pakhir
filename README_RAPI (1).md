# 🏨 Sistem Pemindai Pemesanan Hotel

Sistem manajemen pemesanan hotel berbasis web dengan dukungan **kode QR**, yang memungkinkan hotel mengelola pemesanan kamar dan menggunakan pemindaian kode QR untuk proses **check-in/check-out** tamu secara efisien.

---

## 📌 Gambaran Proyek

Sistem ini menyediakan solusi lengkap bagi hotel untuk:

- Mengelola pemesanan kamar
- Melacak ketersediaan kamar
- Menyederhanakan proses check-in/check-out menggunakan kode QR

Tamu akan menerima kode QR untuk pemesanan mereka, yang dapat dipindai oleh staf hotel untuk proses check-in cepat dan validasi pemesanan.

---

## ✅ Yang Sudah Dikerjakan

### 📁 Database

- **File**: `database/hotel_booking.sql`
- Struktur tabel: `users`, `rooms`, `guests`, `bookings`, `booking_history`
- Data awal untuk user dan kamar

### 📄 Dokumentasi

- **File**: `README.md`
- Dokumentasi teknis dan penggunaan sistem

### 🔐 Sistem Autentikasi

- `login.html`: Form login untuk admin dan staf
- `includes/proses_login.php`: Verifikasi login
- `includes/db.php`: Koneksi ke MySQL

### 📊 Halaman Admin dan Staf

- `dashboard.php`: Statistik pemesanan dan kamar
- `bookings.php`: Daftar pemesanan dan aksi staf hotel

### 🛌 Sistem Pemesanan

- `booking_form.php`: Form pemesanan tamu
- `booking_success.php`: Konfirmasi + QR Code

### 📱 Sistem QR Code

- `generate_qr.php`: Membuat QR Code dari data booking
- `scan.php`: Scanner QR untuk staf hotel
- `confirm_booking.php`: Verifikasi hasil scan dan perubahan status

---

## 📂 Struktur Folder

```plaintext
hotel_booking_scanner/
├── index.php                    # (Sudah dibuat)
├── login.html                   # (Sudah dibuat)
├── logout.php                   # (Sudah dibuat)
├── dashboard.php                # (Sudah dibuat)
├── bookings.php                 # (Sudah dibuat)
├── booking_form.php             # (Sudah dibuat)
├── booking_success.php          # (Sudah dibuat)
├── booking_details.php          # (Sudah dibuat)
├── process_booking.php          # (Sudah dibuat)
├── rooms.php                    # (Sudah dibuat)
├── generate_qr.php              # (Sudah dibuat)
├── scan.php                     # (Sudah dibuat)
├── confirm_booking.php          # (Sudah dibuat)
├── includes/
│   ├── db.php                   # (Sudah dibuat)
│   └── proses_login.php         # (Sudah dibuat)
├── assets/
│   ├── css/style.css
│   ├── js/script.js
│   ├── js/scanner.js
│   └── images/
│       ├── logo.png
│       └── success-icon.png
├── qrcodes/
├── database/
│   └── hotel_booking.sql
└── admin/
    └── users.php                # (Rencana)
```

---

## 🗃️ Struktur Database

- `users`: akun admin dan staf (`username`, `password`, `role`)
- `rooms`: data kamar hotel (`nomor`, `tipe`, `kapasitas`, `harga`, `status`)
- `guests`: data tamu (`nama`, `kontak`, `identitas`)
- `bookings`: data pemesanan (`id`, `tamu`, `check-in`, `status`)
- `booking_history`: riwayat status pemesanan (audit trail)

---

## 🚀 Fitur yang Telah Diimplementasikan

- 🔐 **Login & Session**  
  Verifikasi pengguna, session aman, redirect berdasarkan peran

- 📊 **Dashboard Admin**  
  Statistik pemesanan & kamar, navigasi sistem

- 📋 **Manajemen Pemesanan**  
  Daftar, filter, aksi check-in/out/batal

- 🛌 **Formulir Pemesanan**  
  Input data tamu, pilihan kamar, validasi, hitung otomatis

- 📱 **QR Code System**  
  QR unik, scan via kamera browser, redirect otomatis

---

## 📅 Update Progres

### 🗓️ 19 Mei 2025

- ✅ `scan.php`: Pemindai QR Code via `html5-qrcode`
- ✅ `confirm_booking.php`: Validasi hasil scan, ubah status booking

---

### 🗓️ 22 Mei 2025

- ✅ `booking_details.php`:  
  Tampilkan detail lengkap pemesanan + audit trail + integrasi QR

- ✅ `process_booking.php`:  
  Backend validasi & penyimpanan pemesanan, menggunakan transaksi SQL dan Ajax endpoint

---

### 🗓️ 27 Mei 2025

- ✅ `rooms.php`:  
  CRUD kamar, validasi, proteksi kamar aktif, dashboard ketersediaan

- ✅ `logout.php`:  
  Logout aman, hapus sesi, redirect, logging aktivitas

---

## 🔜 Rencana Pengembangan Selanjutnya

- `admin/users.php`: Manajemen user admin & staf
- Validasi form & sanitasi input
- Proteksi SQL Injection, XSS, CSRF
- Hashing password dengan `bcrypt`

---

## 🚀 Petunjuk Instalasi

1. Clone/unduh repo ke server lokal
2. Buat database MySQL: `hotel_booking_scanner`
3. Import `database/hotel_booking.sql`
4. Konfigurasi file `includes/db.php`:

```php
$host = "localhost";
$username = "root";
$password = "";
$database = "hotel_booking_scanner";
```

5. Pastikan PHP mendukung GD/Imagick & `phpqrcode`
6. Akses sistem melalui browser

---

## 🔑 Login Default

- Username: `admin`  
- Password: `admin123`

---

## ⚙️ Teknologi yang Digunakan

| Teknologi    | Deskripsi                        |
| ------------ | -------------------------------- |
| PHP          | Backend server-side              |
| MySQL        | Database                         |
| HTML/CSS/JS  | Antarmuka pengguna               |
| phpqrcode    | Library pembuatan QR code        |
| html5-qrcode | JavaScript scanner QR di browser |

---

## 🔒 Catatan Keamanan

> ⚠️ Saat ini password disimpan **dalam bentuk teks biasa**  
> Untuk produksi, disarankan:
>
> - Gunakan `password_hash()`
> - Validasi semua input
> - Hindari SQL Injection
> - Gunakan HTTPS
> - Gunakan session yang aman

---

## 📋 Alur Pengguna

### Untuk Admin / Staf

1. Login via `login.html`
2. Masuk ke `dashboard.php` atau `bookings.php`
3. Tambah pemesanan di `booking_form.php`
4. Cetak/lihat QR Code di `booking_success.php`
5. Scan QR saat tamu datang (`scan.php`)
6. Ubah status di `confirm_booking.php`

### Untuk Tamu

1. Isi formulir pemesanan
2. Terima QR Code
3. Tunjukkan QR saat check-in

---

## 📊 Status Proyek

- ✅ **85% Selesai** – Fitur inti, QR Code, proses pemesanan, manajemen kamar & logout sudah berjalan
- 🚧 **15% Pengembangan** – Manajemen user dan penyempurnaan keamanan menyusul

---

📅 **Update Terakhir:** 27 Mei 2025


---

### 🗓️ 19 Mei 2025

- ✅ `admin/users.php`:  
  Implementasi manajemen user untuk admin dan staf  
  - CRUD pengguna (Create, Read, Update, Delete)  
  - Validasi username unik  
  - Proteksi penghapusan akun yang sedang aktif  
  - Manajemen peran (admin/staff)  
  - Opsi untuk memperbarui user tanpa mengubah password

- ✅ `includes/proses_login.php`:  
  Peningkatan sistem login  
  - Pencatatan log aktivitas login (berhasil/gagal)  
  - Penyimpanan informasi device dan IP pengguna  
  - Redirect berdasarkan peran user

---

## 📂 Struktur Folder (Update)

```plaintext
hotel_booking_scanner/
├── ...
├── includes/
│   ├── db.php
│   └── proses_login.php         # (Diperbarui)
├── ...
└── admin/
    └── users.php                # (Selesai)
```

---

## 📊 Status Proyek

- ✅ **95% Selesai** – Fitur manajemen user sudah diimplementasikan  
- 🚧 **5% Pengembangan** – Beberapa penyempurnaan minor menyusul

📅 **Update Terakhir:** 19 Mei 2025
