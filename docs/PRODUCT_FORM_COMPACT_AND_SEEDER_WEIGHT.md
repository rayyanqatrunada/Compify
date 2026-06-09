# Product Form Compact + DemoProductSeeder Weight

Patch ini menambahkan dua hal:

1. `DemoProductSeeder` otomatis mengisi berat dan dimensi produk berdasarkan kategori.
2. Form admin produk dibagi menjadi:
   - Data Wajib
   - Data Opsional

## Data wajib di form

- Nama Produk
- Kategori
- Harga Normal
- Stok
- Berat Produk (gram)

## Data opsional di dropdown

- SKU
- Brand
- Harga diskon
- Jadwal promo
- Dimensi
- Urutan tampil
- Produk unggulan
- Produk baru
- Status tampil
- Gambar produk
- Deskripsi produk

## Berat seed berdasarkan kategori

Contoh:
- RAM: 150 gram
- Processor: 300 gram
- Motherboard: 1200 gram
- VGA/GPU: 1800 gram
- PSU: 2500 gram
- Casing: 6000 gram
- Monitor: 4500 gram

Berat ini masih bisa diedit lagi dari admin.
