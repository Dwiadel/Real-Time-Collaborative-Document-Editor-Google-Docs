# 📄 Real-Time Collaborative Document Editor (Google Docs Clone)

Aplikasi web kolaborasi dokumen secara real-time berbasis Laravel 12, Membuat Google Docs.

---

## 🚀 Fitur Utama

- ✅ **Multi-User Editing** — 2 atau lebih pengguna dapat mengedit dokumen yang sama secara bersamaan
- ✅ **Live Cursor Tracking** — Posisi kursor setiap pengguna ditampilkan secara real-time
- ✅ **Version History** — Riwayat perubahan dokumen tersimpan otomatis setiap 30 detik
- ✅ **Conflict Resolution / Who Edited What** — Menampilkan siapa yang mengedit bagian mana
- ✅ **Auto Save** — Dokumen tersimpan otomatis saat pengguna mengetik
- ✅ **Autentikasi** — Sistem login dan register pengguna

---

## 🛠️ Teknologi yang Digunakan

| Teknologi | Kegunaan |
|-----------|----------|
| Laravel 12 | Backend Framework |
| Laravel Breeze | Autentikasi (Login/Register) |
| Laravel Reverb | WebSocket untuk Real-time |
| MySQL | Database |
| Tailwind CSS | Styling / UI |
| Vite | Asset Bundler |
| Pusher JS | Client WebSocket |

---

## ⚙️ Cara Instalasi & Menjalankan Project

### Prasyarat
Pastikan sudah terinstall:
- PHP 8.2+
- Composer
- Node.js & NPM
- MySQL
- Laragon / XAMPP

### Langkah Instalasi

**1. Clone Repository**
```bash
git clone https://github.com/Dwiadel/Real-Time-Collaborative-Document-Editor-Google-Docs.git
cd Real-Time-Collaborative-Document-Editor-Google-Docs
```

**2. Install Dependencies PHP**
```bash
composer install
```

**3. Install Dependencies Node**
```bash
npm install
```

**4. Setup Environment**
```bash
cp .env.example .env
php artisan key:generate
```

**5. Konfigurasi Database**

Buka file `.env` dan sesuaikan:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=gdocs_app
DB_USERNAME=root
DB_PASSWORD=
```

**6. Buat Database**

Buka phpMyAdmin → buat database baru bernama `gdocs_app`

**7. Jalankan Migration**
```bash
php artisan migrate
```

**8. Jalankan Aplikasi**

Buka 3 terminal secara bersamaan:

Terminal 1:
```bash
php artisan serve
```

Terminal 2:
```bash
npm run dev
```

Terminal 3:
```bash
php artisan reverb:start
```

**9. Buka di Browser**
http://127.0.0.1:8000/register

---

## 📁 Struktur Project
gdocs-app/
├── app/
│   ├── Events/
│   │   ├── DocumentUpdated.php    # Event broadcast update dokumen
│   │   └── CursorMoved.php        # Event broadcast posisi kursor
│   ├── Http/Controllers/
│   │   └── DocumentController.php # Controller utama dokumen
│   └── Models/
│       ├── Document.php           # Model dokumen
│       └── DocumentHistory.php    # Model riwayat dokumen
├── database/migrations/
│   ├── create_documents_table.php
│   └── create_document_histories_table.php
├── resources/views/documents/
│   ├── index.blade.php            # Halaman daftar dokumen
│   └── edit.blade.php             # Halaman editor dokumen
└── routes/
└── web.php                    # Routing aplikasi

---

## 🗄️ Struktur Database

### Tabel `documents`
| Kolom | Tipe | Keterangan |
|-------|------|------------|
| id | bigint | Primary key |
| user_id | bigint | ID pemilik dokumen |
| title | varchar | Judul dokumen |
| content | longtext | Isi dokumen |
| created_at | timestamp | Waktu dibuat |
| updated_at | timestamp | Waktu diupdate |

### Tabel `document_histories`
| Kolom | Tipe | Keterangan |
|-------|------|------------|
| id | bigint | Primary key |
| document_id | bigint | ID dokumen |
| user_id | bigint | ID pengguna yang edit |
| title | varchar | Judul saat itu |
| content | longtext | Isi dokumen saat itu |
| created_at | timestamp | Waktu perubahan |

---

## 🔄 Cara Penggunaan

### 1. Register & Login
- Buka `http://127.0.0.1:8000/register`
- Daftar akun baru
- Login dengan akun yang sudah didaftarkan

### 2. Membuat Dokumen Baru
- Klik tombol **"+ Buat Dokumen"**
- Dokumen baru otomatis terbuka di editor

### 3. Mengedit Dokumen
- Klik judul untuk mengubah nama dokumen
- Klik area editor untuk mulai mengetik
- Dokumen tersimpan otomatis setiap 1 detik

### 4. Kolaborasi Real-time
- Bagikan URL dokumen ke pengguna lain
- Contoh: `http://127.0.0.1:8000/documents/1/edit`
- Pengguna lain buka URL yang sama → bisa edit bersamaan

### 5. Melihat Riwayat Perubahan
- Klik tombol **"📋 History"** di navbar
- Klik salah satu riwayat untuk memulihkan versi tersebut

### 6. Melihat Who Edited What
- Klik tombol **"⚡ Who Edited"** di navbar
- Menampilkan log siapa yang terakhir mengedit


---

## 📝 Catatan

- Aplikasi ini berjalan secara **lokal** (localhost)

---

## 📜 Lisensi

Project ini dibuat untuk keperluan **tugas mata kuliah Pemrograman Web**.
