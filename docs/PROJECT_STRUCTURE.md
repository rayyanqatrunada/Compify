# Compify Project Structure

## Stack

- Laravel 13
- Livewire 4 single-file components
- Vite
- Tailwind CSS
- Laravel Socialite
- Maatwebsite Excel

## Prinsip struktur

Project ini memakai Livewire single-file component di `resources/views/pages`.

Contoh route:

```php
Route::livewire('/products', 'pages::shop.products.index')
```

berarti file aktifnya:

```txt
resources/views/pages/shop/products/index.blade.php
```

Jangan membuat controller baru untuk halaman biasa kecuali memang butuh endpoint non-Livewire.

## Folder penting

```txt
app/Models                         Model Eloquent
app/Http/Middleware                Middleware admin/customer
app/Http/Controllers/Admin         Controller import/export produk
app/Exports                        Export Excel
app/Imports                        Import Excel
app/Support                        Helper/support class
config/compify.php                 Path admin login dan admin panel
database/migrations                Struktur database
database/seeders                   Data awal/demo
resources/views/components/layouts Layout utama
resources/views/pages              Semua page Livewire
routes/web.php                     Semua route web/shop/admin
public/assets                      Asset statis bawaan project
```

## Layout aktif

```txt
components.layouts.shop           Untuk halaman toko/customer
components.layouts.admin          Untuk dashboard admin
components.layouts.customer-auth  Untuk login/register customer
components.layouts.guest          Untuk login admin
```

## Route group utama

### Shop

```txt
/                                 Home
/products                         Listing produk
/product/{slug}                   Detail produk
/category/{slug}                  Produk per kategori
/wishlist                         Wishlist session
/cart                             Cart session
/checkout                         Checkout customer
/checkout/payment/{order}         Detail pembayaran
```

### Customer auth

```txt
/sign-in
/sign-up
/account
/customer/logout
/auth/google/redirect
/auth/google/callback
```

### Admin

Path admin dikontrol dari:

```txt
config/compify.php
```

Default:

```txt
control-room/compify-admin-signin
cp-panel-9f7c2a8e4d1b
```

## Data flow checkout

1. Produk masuk ke session `cart`.
2. Customer membuka `/checkout`.
3. Checkout membuat `orders` dan `order_items`.
4. Cart session dihapus.
5. Customer diarahkan ke `/checkout/payment/{order}`.

Kolom order yang dipakai sekarang:

```txt
order_number
customer_name
customer_email
customer_phone
subtotal
shipping_cost
discount_amount
total_amount
order_status
payment_status
```

## Aturan menambah halaman baru

1. Tambahkan route di `routes/web.php`.
2. Buat file di `resources/views/pages/...` sesuai nama route.
3. Gunakan layout yang sesuai.
4. Pastikan nama route tidak bentrok.
5. Jalankan pengecekan:

```bash
php artisan route:list
php artisan view:cache
php artisan migrate:fresh --seed
```

Catatan: `view:cache` dan `migrate` butuh extension PHP/database lengkap di environment lokal.
