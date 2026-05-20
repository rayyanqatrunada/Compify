# Compify

Compify adalah proyek e-commerce dummy bertema toko perlengkapan komputer modern. Proyek ini dibuat untuk portfolio dan tugas akhir siswa SMK PPLG dengan stack Laravel 13, Livewire 4, Filament 5, Tailwind CSS 4, Alpine.js, Vite, dan MySQL.

## Fitur

- Landing page modern: hero, featured products, categories, promo banner, about, testimonials, FAQ, newsletter, footer.
- Product listing dengan Livewire: search, category filter, stock filter, sorting, pagination, loading state, empty state.
- Product detail dengan gallery, spesifikasi, harga promo, stok, related products.
- Auth user: login, register, logout, role `admin` dan `user`.
- Filament admin panel: dashboard stats, grafik penjualan, CRUD produk, kategori, order, user, banner, testimonial.
- Database dummy: 20 produk, 5 kategori, 10 customer user, 10 testimonial, banner promo, dan order dummy.

## Akun Demo

Admin panel:

```txt
URL: /admin
Email: admin@compify.test
Password: password
```

User hasil factory juga memakai password:

```txt
password
```

## Setup

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
npm run build
php artisan serve
```

Untuk XAMPP/MySQL, buat database bernama `compify`, lalu gunakan konfigurasi berikut di `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=compify
DB_USERNAME=root
DB_PASSWORD=
```

## Catatan Belajar

Kode dipisah mengikuti struktur Laravel:

- `app/Models` untuk relasi database.
- `app/Livewire` untuk interaksi katalog dan newsletter.
- `app/Filament` untuk resource admin dan widget dashboard.
- `database/migrations`, `database/factories`, `database/seeders` untuk schema dan data dummy.
- `resources/views` untuk landing page, auth, produk, dan reusable Blade component.

## Verifikasi

```bash
php artisan test
npm run build
```
