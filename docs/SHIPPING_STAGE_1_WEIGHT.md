# Shipping Stage 1 - Berat Produk & Total Berat Cart

Patch ini adalah tahap pertama sebelum integrasi API ongkir seperti RajaOngkir/Biteship.

## Tujuan

Sebelum ongkir bisa otomatis dan akurat, sistem harus tahu berat barang yang dikirim.
Patch ini menambahkan:

- `weight_gram` di produk
- `length_cm`, `width_cm`, `height_cm` sebagai dimensi opsional
- total berat cart di checkout
- total berat order di database
- snapshot berat per item order

## Perubahan checkout

Di bagian Metode Pengiriman akan muncul box kecil:

```txt
Total berat paket: 1,5 kg
```

Desainnya mengikuti style checkout yang sudah ada dan tidak mengubah layout besar.

## Perubahan admin produk

Di form produk ditambahkan field:

- Berat Produk (gram)
- Panjang (cm)
- Lebar (cm)
- Tinggi (cm)

Berat wajib diisi karena nanti akan dipakai untuk request ongkir API.
Dimensi masih opsional.

## Default berat produk lama

Produk lama otomatis punya default berat `1000 gram` setelah migrate.
Sebaiknya admin mengedit data berat sesuai produk asli.

Contoh berat awal yang masuk akal:

- CPU: 300 gram
- RAM: 150 gram
- SSD: 150 gram
- Motherboard: 1200 gram
- VGA: 1800 gram
- PSU: 2500 gram
- Casing: 6000 gram
- Monitor: 4500 gram

## Tahap berikutnya

Setelah berat produk siap, tahap berikutnya baru integrasi:

- API wilayah/destination
- API cek ongkir RajaOngkir/Biteship
- kurir dan service real-time
- fallback ongkir manual jika API error
