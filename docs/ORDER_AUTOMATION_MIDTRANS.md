# Order Automation Midtrans

## Flow otomatis Midtrans

```txt
Customer checkout
↓
Order dibuat dengan payment_status = pending dan order_status = pending
↓
Customer diarahkan ke Midtrans Snap
↓
Customer membayar
↓
Midtrans mengirim webhook ke /payment/midtrans/notification
↓
Sistem validasi signature Midtrans
↓
Jika settlement/capture accept:
  payment_status = paid
  order_status = processing
  paid_at terisi
↓
Admin melihat order masuk tab Diproses
```

## Tab order admin

- `Semua`: semua order.
- `Baru`: order_status masih pending.
- `Diproses`: order_status processing atau shipped.
- `Selesai`: order_status completed.
- `Batal/Gagal`: order_status cancelled atau payment_status failed/expired/cancelled/refunded.

## Tombol Sync Pending Midtrans

Tombol ini mengecek maksimal 20 order Midtrans yang masih pending. Fungsinya sebagai backup jika webhook belum masuk, terutama saat development lokal.

## Status manual admin

Untuk order Midtrans, status pembayaran tidak diedit manual dari detail order. Admin cukup:

1. Klik `Cek Status Midtrans`, atau
2. Tunggu webhook otomatis, lalu
3. Ubah status order menjadi diproses/dikirim/selesai.

Order Midtrans yang belum paid tidak bisa langsung dipindah ke processing/shipped/completed dari detail order. Ini mencegah pesanan diproses sebelum pembayaran lunas.
