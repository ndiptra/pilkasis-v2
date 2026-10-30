# Pilkasis

Sistem informasi pemilihan (single page application) berbasis Laravel, Inertia.js, Vue 3, dan AdminLTE 3.

## Kebutuhan

- PHP 8.3+ dengan ekstensi `pdo_mysql`
- Composer
- Node.js 20+ dan npm
- MySQL dengan database `db_pilkasis`

## Instalasi

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan storage:link
npm run build
php artisan serve
```

Sesuaikan kredensial MySQL pada `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=db_pilkasis
DB_USERNAME=root
DB_PASSWORD=
```

## Akun Awal

Seeder hanya membuat satu akun superadmin:

- Email: `admin@pilkasis.test`
- Kata sandi: `password`

Segera ubah kata sandi melalui menu **Profil** setelah masuk.

## Menu Aplikasi

- **Dasbor**
- **DATA MASTER**: Identitas Sekolah, Data Pemilih, Data Paslon
- **PEMILIHAN**: Pengaturan, Hasil
- **PENGGUNA**: Data Pengguna, Profil, Keluar

## Pengembangan

```bash
npm run dev
php artisan serve
```

Halaman voting publik belum termasuk pada tahap ini. Struktur tabel `votes` sudah disiapkan untuk pengembangan tahap berikutnya.

## Pengujian

```bash
php artisan test
```
