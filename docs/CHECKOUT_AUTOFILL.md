# Checkout Autofill Customer Data

Fitur ini membuat form checkout otomatis terisi dari data customer, lalu fallback ke riwayat checkout terakhir jika profile belum lengkap.

## Alur Autofill

1. Customer login dan membuka halaman checkout.
2. Sistem mengambil order terakhir customer yang memiliki data pengiriman.
3. Sistem mengisi field yang tersedia dari riwayat checkout terakhir.
4. Sistem mengambil data profile customer dan menimpa field yang tidak kosong.
5. Customer tetap bisa mengubah field checkout sebelum membuat order.
6. Jika checkbox "Simpan kontak dan alamat ini ke profil saya" dicentang, data checkout yang baru akan disimpan ke profile customer setelah order berhasil dibuat.

## Field yang Diisi Otomatis

- Email
- Nama depan
- Nama belakang
- Nomor HP
- Alamat lengkap
- Kecamatan
- Kota
- Provinsi
- Kode pos

## Perubahan Database

Ditambahkan kolom baru di tabel `users`:

```php
$table->string('district')->nullable()->after('address');
```

Jalankan:

```bash
php artisan migrate
php artisan optimize:clear
```

## File yang Berubah

- `database/migrations/2026_06_08_020000_add_district_to_users_table.php`
- `app/Models/User.php`
- `resources/views/pages/shop/account/index.blade.php`
- `resources/views/pages/shop/checkout/index.blade.php`
- `resources/css/shop/checkout.css`

## Cara Test

1. Login sebagai customer.
2. Buka halaman Profile / Account.
3. Isi nomor HP, kecamatan, kota, provinsi, kode pos, dan alamat.
4. Simpan profile.
5. Tambahkan produk ke cart.
6. Masuk checkout.
7. Pastikan data checkout otomatis terisi dari profile.
8. Buat order.
9. Ubah data checkout dan centang checkbox simpan ke profile.
10. Setelah order dibuat, cek Profile lagi. Data profile harus ikut berubah.
11. Kosongkan beberapa data profile, lalu checkout lagi. Sistem akan fallback dari order terakhir.
