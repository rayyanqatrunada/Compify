# Midtrans Payment Channel - Compify

Patch ini memecah tampilan metode pembayaran Midtrans menjadi beberapa channel seperti QRIS, BCA VA, BNI VA, GoPay, ShopeePay, DANA, dan retail.

## Konsep

Yang berubah di tampilan user:

- QRIS
- BCA Virtual Account
- BNI Virtual Account
- BRI Virtual Account
- Mandiri Bill Payment
- Permata Virtual Account
- GoPay
- ShopeePay
- DANA
- Indomaret
- Alfamart

Yang tetap di backend:

- Semua channel tetap memakai Midtrans Snap
- Status pembayaran tetap otomatis dari webhook Midtrans
- Admin tetap bisa sync status Midtrans

## File penting

- `app/Support/MidtransPaymentChannel.php`
- `app/Services/MidtransPaymentService.php`
- `app/Services/OrderPaymentStatusService.php`
- `app/Models/PaymentMethod.php`
- `app/Models/Order.php`
- `resources/views/pages/shop/checkout/index.blade.php`
- `resources/views/pages/shop/checkout/payment.blade.php`
- `resources/views/pages/admin/sales/orders/index.blade.php`
- `resources/views/pages/admin/sales/orders/show.blade.php`
- `resources/views/pages/admin/settings/payment-methods/index.blade.php`
- `database/migrations/2026_06_08_040000_add_midtrans_payment_channels.php`

## Setelah migrate

Migration akan membuat payment method Midtrans channel otomatis:

- QRIS
- BCA VA
- BNI VA
- BRI VA
- Mandiri Bill Payment
- Permata VA
- GoPay
- ShopeePay
- DANA
- Indomaret
- Alfamart

Generic payment method lama bernama `Midtrans` dengan slug `midtrans` akan dinonaktifkan supaya checkout tidak menampilkan satu opsi Midtrans umum.

## Cara kerja

Saat user memilih QRIS:

1. Order dibuat di Compify
2. `payment_channel = other_qris`
3. Backend membuat Snap transaction
4. Request ke Midtrans membawa `enabled_payments = ["other_qris"]`
5. Snap hanya menampilkan channel QRIS
6. Webhook Midtrans update status order
7. Admin melihat `QRIS via Midtrans`

Saat user memilih BCA VA:

1. `payment_channel = bca_va`
2. Request Snap membawa `enabled_payments = ["bca_va"]`
3. Admin melihat `BCA Virtual Account via Midtrans`

## Catatan

Channel yang tampil di Snap tetap bergantung pada channel yang sudah aktif di dashboard Midtrans merchant.
