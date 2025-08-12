# Aplikasi Apriori Test - Analisis Penjualan Wedang Jahe

Aplikasi web untuk analisis penjualan menggunakan algoritma Apriori dengan dashboard interaktif dan visualisasi data.

## 🚀 Fitur Utama

### 📊 Dashboard Interaktif
- **Chart Penjualan**: Visualisasi data penjualan dengan Chart.js
- **Role-based Access**: Tampilan berbeda untuk Admin dan User
- **Real-time Statistics**: Statistik penjualan, stok, dan transaksi

### 🔍 Analisis Data Apriori
- **Market Basket Analysis**: Analisis keranjang belanja
- **Association Rules**: Aturan asosiasi produk
- **Interactive Charts**: Grafik interaktif untuk analisis

### 👥 Manajemen User
- **Multi-role System**: Admin dan User dengan hak akses berbeda
- **User Management**: Kelola pengguna sistem
- **Secure Authentication**: Sistem login yang aman

### 📦 Manajemen Produk & Transaksi
- **Master Barang**: Kelola data produk
- **Transaksi**: Sistem transaksi lengkap
- **Laporan**: Export laporan ke PDF

## 🛠️ Teknologi yang Digunakan

- **Backend**: PHP 7+
- **Database**: MySQL
- **Frontend**: HTML5, CSS3, JavaScript
- **Charts**: Chart.js
- **PDF Export**: DomPDF
- **CSS Framework**: Bootstrap 4
- **Icons**: Font Awesome

## 📋 Persyaratan Sistem

- PHP 7.0 atau lebih tinggi
- MySQL 5.7 atau lebih tinggi
- Web Server (Apache/Nginx)
- Composer (untuk dependency management)

## 🚀 Instalasi

1. **Clone Repository**
   ```bash
   git clone https://github.com/niconnv/app-apriori-test.git
   cd app-apriori-test
   ```

2. **Install Dependencies**
   ```bash
   composer install
   ```

3. **Setup Database**
   - Import file `db/skripsi_iqrom.sql` ke MySQL
   - Konfigurasi koneksi database di `db/configdb.php`

4. **Konfigurasi Web Server**
   - Arahkan document root ke folder project
   - Pastikan mod_rewrite aktif (untuk Apache)

5. **Akses Aplikasi**
   - Buka browser dan akses `http://localhost/app-apriori-test`
   - Login dengan kredensial default (lihat database)

## 📁 Struktur Project

```
├── assets/                 # CSS, JS, dan asset lainnya
│   ├── css/
│   └── js/
├── db/                     # Database dan konfigurasi
│   ├── configdb.php
│   └── skripsi_iqrom.sql
├── func/                   # Fungsi dan logic aplikasi
│   ├── apriori.php
│   ├── barang.php
│   ├── transaksi.php
│   └── ...
├── vendor/                 # Composer dependencies
├── home.php               # Dashboard utama
├── analisa_data.php       # Halaman analisis Apriori
├── masterbarang.php       # Manajemen produk
└── ...
```

## 🎯 Penggunaan

### Admin
- Akses penuh ke semua fitur
- Melihat seluruh data penjualan
- Manajemen user dan produk
- Analisis data Apriori

### User
- Dashboard personal
- Melihat data penjualan sendiri
- Transaksi dan laporan

## 📊 Fitur Chart

- **Chart Penjualan Personal**: Data 6 bulan terakhir berdasarkan user
- **Chart Total Penjualan**: Seluruh data transaksi (Admin only)
- **Chart Analisis Apriori**: Visualisasi hasil analisis

## 🤝 Kontribusi

Kontribusi sangat diterima! Silakan:
1. Fork repository ini
2. Buat branch fitur (`git checkout -b feature/AmazingFeature`)
3. Commit perubahan (`git commit -m 'Add some AmazingFeature'`)
4. Push ke branch (`git push origin feature/AmazingFeature`)
5. Buat Pull Request

## 📝 Lisensi

Project ini menggunakan lisensi MIT. Lihat file `LICENSE` untuk detail.

## 👨‍💻 Author

**Nico Novanda**
- GitHub: [@niconnv](https://github.com/niconnv)

## 🙏 Acknowledgments

- Terima kasih kepada semua kontributor
- Inspirasi dari berbagai project open source
- Komunitas PHP dan JavaScript

---

⭐ Jangan lupa berikan star jika project ini membantu Anda!