# PILKASIS - Aplikasi Pemilihan Ketua OSIS

Pilkasis adalah aplikasi web untuk mengelola pemilihan di sekolah dan melakukan pemungutan suara secara daring. Aplikasi menggunakan Laravel 13, Inertia.js 3, Vue 3, dan MySQL.

## Fitur

- Mengelola profil sekolah, pengguna, pemilihan, pemilih, dan pasangan calon.
- Mengimpor dan mengekspor data pemilih.
- Mengatur jadwal serta status pemilihan dan visibilitas hasil.
- Memungkinkan pemilih memberikan suara melalui halaman voting.
- Menampilkan hasil kepada panitia dan, jika diaktifkan, kepada publik.

Pemilih masuk menggunakan NISN 10 digit atau kode 4 digit untuk non-siswa, beserta kata sandi yang diberikan panitia.

## Persyaratan

- PHP 8.3 atau lebih baru dengan ekstensi `pdo_mysql`
- Composer
- Node.js dan npm
- MySQL

## Instalasi Lokal

1. Siapkan dan jalankan MySQL, lalu buat database bernama `db_pilkasis`.
2. Dari direktori proyek, pasang dependensi dan salin konfigurasi lingkungan:

   ```powershell
   composer install
   Copy-Item .env.example .env
   npm install
   ```

3. Sesuaikan pengaturan database pada `.env`:

   ```env
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=db_pilkasis
   DB_USERNAME=root
   DB_PASSWORD=
   ```

4. Siapkan aplikasi dan aset:

   ```powershell
   php artisan key:generate
   php artisan migrate --seed
   php artisan storage:link
   npm run build
   ```

5. Jalankan aplikasi:

   ```powershell
   php artisan serve
   ```

Buka alamat yang ditampilkan oleh Artisan, biasanya `http://127.0.0.1:8000`. Untuk pengembangan frontend dengan hot reload, jalankan `npm run dev` di terminal lain.

## Akun Awal

Seeder membuat akun superadmin berikut:

- Email: `admin@pilkasis.test`
- Kata sandi: `password`

Kredensial ini hanya untuk instalasi lokal. Segera ganti kata sandi setelah masuk dan jangan gunakan kredensial bawaan di lingkungan produksi.

## Alur Penggunaan

1. Masuk sebagai superadmin, lengkapi profil sekolah, lalu buat pemilihan.
2. Tambahkan pasangan calon dan data pemilih. Panitia perlu memberikan NISN atau kode serta kata sandi kepada setiap pemilih.
3. Atur jadwal dan status pemilihan. Pemilih dapat masuk dan memberikan suara saat pemilihan berlangsung dan voting diaktifkan.
4. Pantau hasil melalui aplikasi. Hasil publik hanya tersedia jika visibilitas publik diaktifkan.

## Pengembangan dan Pengujian

Jalankan server Laravel dan Vite pada terminal terpisah:

```powershell
php artisan serve
npm run dev
```

Jalankan pengujian:

```powershell
php artisan test
```
