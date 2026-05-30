# Compify Optimization Notes

Dokumen ini mencatat tahap stabilisasi awal sebelum menambah fitur baru.

## Target tahap ini

1. Membuat struktur Livewire lebih mudah dibaca ulang.
2. Memastikan semua `Route::livewire()` mengarah ke file yang ada.
3. Menyamakan penggunaan layout ke `resources/views/components/layouts/*`.
4. Mengurangi file starter/dummy lama yang tidak dipakai.
5. Menyamakan checkout/order dengan kolom database yang benar.

## Perubahan utama

### 1. Nama file Livewire distandarkan

File yang sebelumnya memakai prefix hasil encoding `#U26a1` diganti menjadi nama normal, misalnya:

```txt
resources/views/pages/shop/home/#U26a1index.blade.php
resources/views/pages/shop/home/index.blade.php
```

Livewire tetap bisa membaca file tanpa emoji/prefix, dan struktur jadi lebih mudah dipahami.

### 2. Layout disamakan

Semua referensi lama:

```php
#[Layout('layouts.admin')]
#[Layout('layouts.shop')]
```

diganti menjadi:

```php
#[Layout('components.layouts.admin')]
#[Layout('components.layouts.shop')]
```

Karena file layout yang benar ada di:

```txt
resources/views/components/layouts/admin.blade.php
resources/views/components/layouts/shop.blade.php
```

### 3. Halaman route yang salah/belum ada diperbaiki

Route:

```php
pages::shop.checkout.payment
```

sekarang punya file yang benar:

```txt
resources/views/pages/shop/checkout/payment.blade.php
```

Route:

```php
pages::admin.content.home-sections.category-products
```

sekarang punya file dummy yang aman dibuka:

```txt
resources/views/pages/admin/content/home-sections/category-products.blade.php
```

### 4. Checkout/order disinkronkan dengan migration

Checkout sekarang membuat order memakai kolom asli tabel `orders`:

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

`OrderItem` juga sekarang mengisi `product_name`, karena kolom tersebut wajib di migration.

### 5. Model dirapikan

`Order` sekarang punya:

```php
Order::generateOrderNumber()
```

`OrderItem` sekarang punya relasi:

```php
product()
```

`User` sekarang punya `username` di fillable dan duplicate `avatar` dihapus dari daftar fillable.

### 6. Migration duplicate avatar dibuat aman

Migration profile user dibuat idempotent dengan `Schema::hasColumn()` agar `migrate:fresh` tidak gagal karena kolom `avatar` sudah dibuat oleh migration sebelumnya.

### 7. File tidak terpakai dibersihkan

File starter/dummy yang tidak terhubung ke route dihapus dari struktur aktif, seperti:

```txt
resources/views/welcome.blade.php
resources/views/components/layouts/auth.blade.php
resources/views/pages/auth/shop/index.blade.php
resources/views/pages/shop/component-database.blade.php
resources/views/pages/shop/how-to-shop.blade.php
resources/views/pages/shop/promo.blade.php
resources/views/pages/shop/warranty.blade.php
resources/views/pages/admin/content/home-sections/index.blade.php
```

## Catatan penting untuk ZIP hasil akhir

ZIP hasil optimalisasi tidak menyertakan:

```txt
.env
.git
vendor
node_modules
bootstrap/cache/*.php
storage/logs/*
```

Setelah extract, jalankan ulang dependency di komputer lokal/server:

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan storage:link
npm run build
```

## Verifikasi yang sudah dilakukan

- Semua `Route::livewire()` dicek dan sudah resolve ke file yang ada.
- Semua file PHP dan Blade dicek dengan `php -l`.
- `php artisan route:list` berhasil menampilkan route.
- `npm run build` belum bisa diverifikasi di environment ini karena `node_modules` bawaan ZIP tidak lengkap untuk Linux (`@rolldown/binding-linux-x64-gnu` hilang). Solusinya: hapus `node_modules`, lalu jalankan `npm install` ulang di lokal.

Verifikasi migration dengan SQLite belum bisa dilakukan di environment ini karena extension `pdo_sqlite` tidak tersedia. Jalankan `php artisan migrate:fresh --seed` di local yang memiliki driver database aktif.
