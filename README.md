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

## Instalasi di VPS atau Hosting

Panduan berikut menggunakan VPS Linux dengan PHP-FPM, Nginx, MySQL, Composer, Node.js, dan npm. Nama paket, versi PHP-FPM, serta konfigurasi web server dapat berbeda sesuai distribusi dan provider hosting.

### VPS Linux

1. Siapkan domain, database MySQL, dan server dengan PHP 8.3+ beserta ekstensi Laravel yang diperlukan, Composer, Node.js, npm, serta Nginx atau web server lain.
2. Salin kode aplikasi ke server melalui Git atau unggah berkas. Contoh menggunakan Git:

   ```bash
   git clone <URL_REPOSITORY> /var/www/pilkasis
   cd /var/www/pilkasis
   cp .env.example .env
   ```

3. Isi `.env` untuk lingkungan produksi. Gunakan kredensial database khusus aplikasi, bukan `root`:

   ```env
   APP_ENV=production
   APP_DEBUG=false
   APP_URL=https://domain-anda.example

   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=db_pilkasis
   DB_USERNAME=pilkasis_user
   DB_PASSWORD=password-database-yang-kuat
   ```

   Jangan unggah atau commit `.env` ke repository. Simpan `APP_KEY` yang sama saat memperbarui aplikasi; jangan jalankan `key:generate` lagi pada instalasi yang sudah berjalan.
4. Pasang dependensi, buat aset frontend, dan siapkan database:

   ```bash
   composer install --no-dev --optimize-autoloader --no-interaction
   npm ci
   npm run build
   php artisan key:generate
   php artisan migrate --seed --force
   php artisan storage:link
   php artisan optimize
   ```

   Langkah ini untuk instalasi pertama. Pada deployment berikutnya, jangan jalankan `key:generate` atau `migrate --seed`; jalankan `php artisan migrate --force` untuk menerapkan migrasi baru. Migrasi menggunakan `--force` agar dapat berjalan di mode produksi. Seeder membuat akun superadmin bawaan; ubah kata sandinya sebelum membuka aplikasi untuk umum.
5. Atur document root/vhost web server ke direktori `public` aplikasi, misalnya `/var/www/pilkasis/public`, dan teruskan request ke `public/index.php`. Jangan jadikan direktori utama proyek sebagai document root karena dapat mengekspos `.env` dan berkas internal.
6. Berikan proses web server hak tulis ke `storage` dan `bootstrap/cache` saja sesuai pengguna/grup PHP-FPM. Jangan mengatasi masalah izin dengan memberikan akses `777`.
7. Aktifkan HTTPS, lalu pastikan domain, sertifikat TLS, versi PHP-FPM, dan kredensial database telah sesuai. Aplikasi siap dibuka setelah web server diarahkan ke domain.

### Shared Hosting

Pastikan paket hosting menyediakan PHP 8.3+, ekstensi PHP yang dibutuhkan, MySQL, Composer, serta akses SSH/terminal untuk menjalankan Artisan. Jika Node.js tidak tersedia di hosting, jalankan `npm ci` dan `npm run build` di komputer lokal atau pipeline deployment, lalu unggah folder `public/build` bersama kode aplikasi.

Atur document root domain ke folder `public` aplikasi. Jika panel hosting tidak memungkinkan pengaturan tersebut, minta bantuan provider untuk mengarahkan domain ke folder `public`; jangan memindahkan `public/index.php` ke root proyek atau mengekspos file `.env`. Atur `.env`, dependensi Composer, migrasi, storage link, izin folder, dan HTTPS mengikuti langkah VPS di atas.

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
