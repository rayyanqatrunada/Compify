# Dokumentasi Flowchart Lengkap Project Compify

Dibuat: 05 June 2026 03:42

## Daftar Isi
1. [Ringkasan Sistem](#ringkasan-sistem)
2. [Flow Utama dari User Masuk Website](#flow-utama-dari-user-masuk-website)
3. [Flow Auth Admin dan Customer](#flow-auth-admin-dan-customer)
4. [Flow Shop Layout](#flow-shop-layout)
5. [Flow Admin Layout dan Sidebar](#flow-admin-layout-dan-sidebar)
6. [Flow Home Page dan Home Layout](#flow-home-page-dan-home-layout)
7. [Flow Produk, Kategori, Search, dan Brand](#flow-produk-kategori-search-dan-brand)
8. [Flow Product Pricing dan Flash Sale Price](#flow-product-pricing-dan-flash-sale-price)
9. [Flow Stok Flash Sale dan Limit Pembelian](#flow-stok-flash-sale-dan-limit-pembelian)
10. [Flow Wishlist](#flow-wishlist)
11. [Flow Cart Produk dan Paket Kombo](#flow-cart-produk-dan-paket-kombo)
12. [Flow Checkout sampai Order Created](#flow-checkout-sampai-order-created)
13. [Flow Payment Branch: WhatsApp, Midtrans, Manual](#flow-payment-branch-whatsapp-midtrans-manual)
14. [Flow Fonnte Notification](#flow-fonnte-notification)
15. [Flow Payment Page](#flow-payment-page)
16. [Flow Admin Orders dan Restore Stock](#flow-admin-orders-dan-restore-stock)
17. [Flow Event Frontend](#flow-event-frontend)
18. [Flow Admin Event Management](#flow-admin-event-management)
19. [Flow Combo Package Detail dan Cart](#flow-combo-package-detail-dan-cart)
20. [Flow Admin Dashboard Data](#flow-admin-dashboard-data)
21. [Flow Newsletter](#flow-newsletter)
22. [Flow Database / ERD Ringkas](#flow-database-erd-ringkas)
23. [Flow Migrations / Modul Database](#flow-migrations-modul-database)
24. [Full Flow Pembelian dari Awal sampai Akhir](#full-flow-pembelian-dari-awal-sampai-akhir)
25. [Known Gaps / Hal yang Perlu Dicek Lagi](#known-gaps-hal-yang-perlu-dicek-lagi)

## 1. Ringkasan Sistem

Dokumen ini merangkum flow project Compify dari awal sampai akhir: akses shop, auth admin/customer, home layout, product pricing, event, cart, checkout, payment, Fonnte, order management, dashboard admin, sampai relasi database.

Format utama flowchart menggunakan Mermaid. File Markdown dapat langsung dibuka di editor yang mendukung Mermaid seperti GitHub, VS Code dengan ekstensi Mermaid, atau Mermaid Live Editor. File DOCX disediakan sebagai dokumentasi baca dan arsip, dengan kode Mermaid tetap dicantumkan agar mudah diedit.

Catatan akurasi: dokumen ini dibuat dari file project yang dikirim: auth.php, Middleware, Models, Services, migrations, admin views, shop views, dan layout admin/shop.

```mermaid
flowchart TD
    A[Compify] --> B[Shop Frontend]
    A --> C[Admin Panel]

    B --> B1[Home]
    B --> B2[Produk dan Kategori]
    B --> B3[Event]
    B --> B4[Cart]
    B --> B5[Checkout]
    B --> B6[Account Customer]

    C --> C1[Dashboard]
    C --> C2[Catalog]
    C --> C3[Content]
    C --> C4[Event Management]
    C --> C5[Orders]
    C --> C6[Customers]
    C --> C7[Layout]
    C --> C8[Configure]
```

Catatan:
- Shop frontend dipakai guest/customer untuk melihat produk, event, cart, checkout, dan akun.
- Admin panel dipakai admin untuk mengatur data produk, event, order, layout home, payment, shipping, Fonnte, dan customer.
- Guard auth admin dan customer sama-sama memakai provider users, tetapi akses dipisahkan lewat guard dan role.

## 2. Flow Utama dari User Masuk Website

```mermaid
flowchart TD
    A[User membuka website Compify] --> B{Jenis akses}

    B -->|Guest / Customer| C[Shop Frontend]
    B -->|Admin| D[Admin Login]

    C --> C1[Shop Layout]
    C1 --> C2[Header: Logo, Cart, Wishlist, Account]
    C1 --> C3[Navbar: Home, Kategori, Merk, Produk, Event, About]
    C1 --> C4[Footer: Info, Support, Company, Newsletter]
    C1 --> E[Halaman Shop]

    E --> E1[Home]
    E --> E2[Produk Index]
    E --> E3[Detail Produk]
    E --> E4[Category Page]
    E --> E5[Event Page]
    E --> E6[Wishlist]
    E --> E7[Cart]
    E --> E8[Checkout]
    E --> E9[Account Customer]
    E --> E10[Static Pages]

    D --> D1[Auth Guard admin]
    D1 --> D2[Middleware admin]
    D2 --> D3[Admin Panel]
    D3 --> F[Admin Modules]

    F --> F1[Dashboard / Analytics]
    F --> F2[Catalog: Product, Category, Brand]
    F --> F3[Content: Banner, About, Static Pages]
    F --> F4[Event: Settings, Hero, Flash Sale, Full Banner, Combo]
    F --> F5[Sales: Orders]
    F --> F6[Customers: Data, Newsletter, Reviews]
    F --> F7[Layout: Home Layout]
    F --> F8[Configure: Shop, Payment, Shipping, Fonnte]
```

## 3. Flow Auth Admin dan Customer

```mermaid
flowchart TD
    A[User masuk route auth] --> B{Route auth}

    B -->|Customer Login| C[Form Login Customer]
    B -->|Customer Register| D[Form Register Customer]
    B -->|Google Login| E[Google OAuth Customer]
    B -->|Admin Login| F[Form Login Admin]

    C --> C1[Validasi email/password]
    C1 --> C2[Auth::guard customer attempt]
    C2 --> C3{role == customer?}
    C3 -->|Ya| C4[Customer login berhasil]
    C4 --> C5[Redirect Home]
    C3 -->|Tidak| C6[Tolak login customer]

    D --> D1[Validasi data register]
    D1 --> D2[Create User role customer]
    D2 --> D3[Login guard customer]
    D3 --> D4[Redirect Home]

    E --> E1[Ambil data Google]
    E1 --> E2{Email sudah ada?}
    E2 -->|Akun admin| E3[Tolak login customer]
    E2 -->|Akun customer / user baru| E4[Update/Create customer]
    E4 --> E5[Login guard customer]
    E5 --> E6[Redirect Home]

    F --> F1[Validasi email/password]
    F1 --> F2[Auth::guard admin attempt]
    F2 --> F3{role == admin?}
    F3 -->|Ya| F4[Admin login berhasil]
    F4 --> F5[Redirect Admin Dashboard]
    F3 -->|Tidak| F6[Tolak login admin]

    G[Route customer protected] --> G1[EnsureCustomerAuthenticated]
    G1 --> G2{guard customer aktif dan role customer?}
    G2 -->|Ya| G3[Lanjut customer area]
    G2 -->|Tidak| G4[Redirect customer login]

    H[Route admin protected] --> H1[EnsureUserIsAdmin]
    H1 --> H2{guard admin aktif dan role admin?}
    H2 -->|Ya| H3[Lanjut admin panel]
    H2 -->|Tidak| H4[Redirect admin login]
```

Catatan:
- Admin dan customer tetap bisa dipisah walaupun memakai tabel users yang sama.
- Kunci keamanan ada pada guard yang benar dan role check yang ketat.
- Customer login/register diarahkan ke Home, bukan intended admin URL.

## 4. Flow Shop Layout

```mermaid
flowchart TD
    A[Shop Layout] --> B[Load menu kategori parent + children aktif]
    A --> C[Load brand navbar aktif]
    A --> D[Hitung wishlist dari session]
    A --> E[Hitung cart dari session]
    A --> F{Customer login?}

    F -->|Ya| F1[Tampilkan avatar/account link]
    F -->|Tidak| F2[Tampilkan tombol Masuk]

    A --> G[Header compact]
    A --> H[Mobile sidebar]
    A --> I[Main navbar]
    A --> J[Main slot halaman]
    A --> K{Route home?}

    K -->|Ya| K1[Footer full + newsletter]
    K -->|Tidak| K2[Footer minimal]

    H --> H1[Accordion Kategori]
    H --> H2[Accordion Merk]
    I --> I1[Mega Menu Kategori]
    I --> I2[Mega Menu Merk]
    I --> I3[Search Produk]
```

## 5. Flow Admin Layout dan Sidebar

```mermaid
flowchart TD
    A[Admin Layout] --> B[auth admin user]
    B --> C[Render sidebar]
    C --> D[Dashboard]
    C --> E[Analytic]
    C --> F[Product]
    C --> G[Banner]
    C --> H[Info]
    C --> I[Event]
    C --> J[Orders]
    C --> K[Customer]
    C --> L[Layout]
    C --> M[Configure]

    F --> F1[Products]
    F --> F2[Categories]
    F --> F3[Brands]

    G --> G1[Category Products]
    G --> G2[Category Grid]
    G --> G3[Hero Banners]
    G --> G4[Full Banners]
    G --> G5[Split Banners]
    G --> G6[Gallery 3 Images]

    H --> H1[About Page]
    H1 --> H11[About Hero]
    H1 --> H12[About Intro]
    H1 --> H13[About Stats]
    H1 --> H14[About Quote]
    H1 --> H15[About Values]
    H --> H2[Static Pages]

    I --> I1[Atur Event]
    I --> I2[Image Hero]
    I --> I3[Flash Sale]
    I --> I4[Full Banner]
    I --> I5[Paket Kombo]

    K --> K1[Data Customer]
    K --> K2[Newsletter Subscribers]
    K --> K3[Reviews]

    L --> L1[Home Layout]

    M --> M1[Shop Settings]
    M --> M2[Payment Methods]
    M --> M3[Shipping Methods]
    M --> M4[Fonnte Settings]
```

## 6. Flow Home Page dan Home Layout

```mermaid
flowchart TD
    A[Customer buka Home] --> B[Home Index]
    B --> C[Load hero/banner]
    B --> D[Load category grid]
    B --> E[Load HomeLayoutGroup aktif]

    E --> F{Ada group aktif?}
    F -->|Tidak| G[HomeLayoutGroup::current buat Default Layout]
    F -->|Ya| H[Load HomeLayoutSlot]
    G --> H

    H --> I[Loop slot berdasarkan slot_number]
    I --> J{slot_type}

    J -->|none| J1[Skip]
    J -->|product_display| K{product_source}
    J -->|full_banner| L[Render HomeSection Full Banner]
    J -->|split_banner| M[Render HomeSection Split Banner]
    J -->|gallery| N[Render Gallery 3 Images]

    K -->|category| K1[Ambil produk dari category_id]
    K -->|best_seller| K2[Ambil produk terlaris dari order paid]
    K -->|latest| K3[Ambil produk terbaru]

    K1 --> O[Product Grid]
    K2 --> O
    K3 --> O

    O --> P{Slot product display berurutan dan sumber sama?}
    P -->|Ya| Q[Gabung menjadi baris tambahan 4x2 / 4x3]
    P -->|Tidak| R[Render section terpisah]
```

Catatan:
- Default layout yang disarankan: Best Seller, Latest Product, Full Banner, Motherboard 2x, RAM, Monitor.
- Group baru dibuat kosong agar admin bisa mengatur dari awal tanpa menimpa default layout.

## 7. Flow Produk, Kategori, Search, dan Brand

```mermaid
flowchart TD
    A[Produk Index / Category Page] --> B[Query Product aktif]
    B --> C{Ada search?}
    C -->|Ya| C1[Filter name, sku, category, parent category]
    C -->|Tidak| D[Skip search]

    B --> E{Ada category filter?}
    E -->|Ya| E1[Ambil category + descendants aktif]
    E1 --> E2[whereIn category_id]
    E -->|Tidak| F[Semua kategori]

    B --> G{Ada brand filter?}
    G -->|Ya| G1[Filter brand slug]
    G -->|Tidak| H[Semua brand]

    B --> I{Sort}
    I -->|latest| I1[order latest]
    I -->|price_low| I2[order price asc]
    I -->|price_high| I3[order price desc]

    I1 --> J[Paginate produk]
    I2 --> J
    I3 --> J
    J --> K[Render product card]
```

## 8. Flow Product Pricing dan Flash Sale Price

```mermaid
flowchart TD
    A[Product dimuat] --> B[ProductPricingService]
    B --> C{Event aktif sekarang?}
    C -->|Tidak| D[Gunakan harga regular / sale product]
    C -->|Ya| E[Cek EventFlashSaleItem aktif untuk product]

    E --> F{Flash sale item ditemukan?}
    F -->|Tidak| D
    F -->|Ya| G[Hitung event price]
    G --> H[Set is_event_price true]
    G --> I[Set price_label Flash Sale]
    G --> J[Hitung discount amount / percent]

    D --> K[formatted_final_price]
    H --> K
    I --> K
    J --> K

    K --> L[Dipakai di product card]
    K --> M[Dipakai di detail produk]
    K --> N[Dipakai di cart]
    K --> O[Dipakai di checkout snapshot]
```

## 9. Flow Stok Flash Sale dan Limit Pembelian

```mermaid
flowchart TD
    A[Product / Cart / Checkout cek stok] --> B[EventFlashSaleStockService]
    B --> C[Cari EventFlashSaleItem aktif by product]
    C --> D{Ada stock_limit?}

    D -->|Tidak| E[Limit mengikuti stock produk asli]
    D -->|Ya| F[Hitung reserved quantity]
    F --> G[Sum order_items dengan event_flash_sale_item_id]
    G --> H[Exclude order failed/expired/refunded/cancelled]
    H --> I[remaining_stock = stock_limit - reserved]

    I --> J[maxPurchasable]
    E --> J
    J --> K{maxPurchasable > 0?}
    K -->|Ya| L[Tombol beli aktif]
    K -->|Tidak| M[Tampilkan stok habis / limit habis]
```

## 10. Flow Wishlist

```mermaid
flowchart TD
    A[Customer klik wishlist] --> B[wishlist.toggle]
    B --> C[Ambil session wishlist]
    C --> D{Product sudah ada?}
    D -->|Ya| E[Remove product id]
    D -->|Tidak| F[Add product id]
    E --> G[Simpan session wishlist]
    F --> G
    G --> H[Header count update]
    H --> I[Wishlist Page render produk]
```

## 11. Flow Cart Produk dan Paket Kombo

```mermaid
flowchart TD
    A[Customer klik Tambah Keranjang] --> B{Jenis item}

    B -->|Produk| C[CartService::addProduct]
    B -->|Paket Kombo| D[CartService::addComboPackage]

    C --> C1[Cek product aktif]
    C1 --> C2[Hitung price via ProductPricingService]
    C2 --> C3[Cek stock / flash sale limit]
    C3 --> C4{Stock cukup?}
    C4 -->|Tidak| C5[Return error]
    C4 -->|Ya| C6[Simpan session cart product:id]

    D --> D1[Cek EventSetting activeNow]
    D1 --> D2[Cek combo package aktif]
    D2 --> D3[Cek items package]
    D3 --> D4[Cek stock tiap child product]
    D4 --> D5{Semua cukup?}
    D5 -->|Tidak| D6[Return error]
    D5 -->|Ya| D7[Simpan session cart combo:id]

    C6 --> E[Cart Page]
    D7 --> E
    E --> F[CartService::items]
    F --> G[Build item product / combo]
    G --> H[Update qty / remove / clear]
```

## 12. Flow Checkout sampai Order Created

```mermaid
flowchart TD
    A[Customer buka Checkout] --> B[Middleware customer.auth]
    B --> C{Customer login?}
    C -->|Tidak| D[Redirect customer login]
    C -->|Ya| E[Checkout Page]

    E --> F[Load cartItems dari CartService]
    F --> G{Cart kosong?}
    G -->|Ya| H[Redirect cart]
    G -->|Tidak| I[Isi data customer dan alamat]

    I --> J[Pilih shipping method]
    J --> K[Hitung ongkir by ShippingMethod + ShippingSetting]
    K --> L[Pilih payment method]
    L --> M[Submit placeOrder]

    M --> N[Validasi form]
    N --> O{Ada cart item tidak available?}
    O -->|Ya| P[Tampilkan error cart]
    O -->|Tidak| Q[DB Transaction]

    Q --> R[Create Order]
    R --> S[Create OrderItem snapshot]
    S --> T[Decrement stock produk / children combo]
    T --> U[Commit transaction]
    U --> V[Load order items, paymentMethod, shippingMethod]
    V --> W[Masuk payment branch]
```

## 13. Flow Payment Branch: WhatsApp, Midtrans, Manual

```mermaid
flowchart TD
    A[Order selesai dibuat] --> B{Payment Method}

    B -->|type whatsapp| C[WhatsAppOrderMessageService]
    C --> C1[Generate WhatsApp URL]
    C1 --> C2[Update payment_type whatsapp]
    C2 --> C3[Update payment_redirect_url]
    C3 --> Z[Fonnte notification]
    C3 --> C4{auto_redirect?}
    C4 -->|Ya| C5[Redirect ke WhatsApp]
    C4 -->|Tidak| C6[Redirect checkout.payment]

    B -->|api provider midtrans| D[MidtransPaymentService]
    D --> D1[Create Snap Transaction]
    D1 --> D2{redirect_url tersedia?}
    D2 -->|Ya| D3[Update payment_type midtrans_snap]
    D3 --> D4[Update payment_reference token]
    D4 --> D5[Update payment_redirect_url Midtrans]
    D5 --> Z
    D5 --> D6[Redirect ke Midtrans]
    D2 -->|Tidak / error| D7[Report error]
    D7 --> D8[Update payment_reference order_number]
    D8 --> D9[Fallback checkout.payment]
    D9 --> Z

    B -->|manual / qr / url| E[Manual Payment]
    E --> E1[Update payment_redirect_url checkout.payment]
    E1 --> Z
    E1 --> E2[Redirect checkout.payment]

    Z --> Z1[FonnteMessageService]
    Z1 --> Y[CartService::clear]
```

## 14. Flow Fonnte Notification

```mermaid
flowchart TD
    A[notifyOrderCreated order] --> B[FonnteMessageService]
    B --> C[Load FonnteSetting::current]
    C --> D{is_active true?}
    D -->|Tidak| D1[Skip / log disabled jika debug]
    D -->|Ya| E{Token tersedia?}

    E -->|Tidak| E1[Create log failed config_error]
    E -->|Ya| F[Load order items payment shipping]

    F --> G{send_customer_order_created aktif dan customer_phone ada?}
    G -->|Ya| G1[Render customer template]
    G1 --> G2[Normalize customer phone]
    G2 --> G3[POST Fonnte API]
    G3 --> G4[Log success/failed]
    G -->|Tidak| H[Skip customer]

    F --> I{send_admin_order_created aktif dan admin_phone ada?}
    I -->|Ya| I1[Render admin template]
    I1 --> I2[Normalize admin phone]
    I2 --> I3[POST Fonnte API]
    I3 --> I4[Log success/failed]
    I -->|Tidak| J[Skip admin]

    G4 --> K[End]
    I4 --> K
    H --> K
    J --> K
```

Catatan:
- Untuk localhost, error SSL cURL 60/77 berarti masalah CA certificate lokal, bukan flow Fonnte.
- Webhook Fonnte tidak wajib untuk kirim pesan keluar. Webhook dibutuhkan kalau ingin status pesan/incoming message masuk ke Laravel.
- Token lebih aman disimpan encrypted di database dan tidak ditampilkan ulang ke public property Livewire.

## 15. Flow Payment Page

```mermaid
flowchart TD
    A[Customer diarahkan ke checkout.payment] --> B[Load Order]
    B --> C{Order milik customer login?}
    C -->|Tidak| D[Abort 403]
    C -->|Ya| E[Tampilkan detail pembayaran]

    E --> F{Payment Type / Method}
    F -->|Midtrans| G[Tombol lanjut bayar Midtrans]
    F -->|WhatsApp| H[Tombol chat admin WhatsApp]
    F -->|QR| I[Tampilkan QR Image]
    F -->|Manual Transfer| J[Tampilkan instruksi pembayaran]
    F -->|URL| K[Tombol buka Payment URL]

    E --> L[Tampilkan ringkasan order]
    L --> M[Items, subtotal, ongkir, total]
```

## 16. Flow Admin Orders dan Restore Stock

```mermaid
flowchart TD
    A[Admin buka Orders] --> B[List order]
    B --> C[Search/filter/paginate]
    C --> D[Admin buka detail order]
    D --> E[Load order, items, paymentMethod, shippingMethod, user]

    E --> F[Update payment_status]
    E --> G[Update order_status]
    F --> H[saveStatuses]
    G --> H
    H --> I[Order status tersimpan]

    E --> J{canDeleteOrder?}
    J -->|payment pending/failed/expired atau order pending/cancelled| K[Tampilkan tombol hapus]
    J -->|paid/processing/shipped/completed| L[Hapus langsung ditolak]

    K --> M[deleteOrder]
    M --> N[DB Transaction]
    N --> O[Load items]
    O --> P{item_type}
    P -->|product| Q[Increment stock product]
    P -->|event_flash_sale| R[Increment stock product]
    P -->|combo_package| S[Ambil children dari snapshot_data]
    S --> T[Increment stock tiap child]
    Q --> U[Delete order]
    R --> U
    T --> U
    U --> V[Redirect orders]
```

## 17. Flow Event Frontend

```mermaid
flowchart TD
    A[Customer buka Event Page] --> B[EventSetting::activeNow]
    B --> C{Event aktif dan waktu valid?}

    C -->|Tidak| D[Render event-empty]
    C -->|Ya| E[Render countdown bar]

    E --> F{show_hero_section?}
    F -->|Ya| F1[Load EventHeroImage aktif]
    F1 --> F2[Render main + side hero]
    F -->|Tidak| F3[Skip hero]

    E --> G{show_flash_sale_section?}
    G -->|Ya| G1[Load EventFlashSaleItem + group aktif]
    G1 --> G2[Filter product aktif]
    G2 --> G3[Filter stock available]
    G3 --> G4[Render flash grid]
    G -->|Tidak| G5[Skip flash sale]

    E --> H{show_full_banner_section?}
    H -->|Ya| H1[Load EventFullBanner aktif]
    H1 --> H2{image ada?}
    H2 -->|Ya| H3[Render full banner]
    H2 -->|Tidak| H4[Skip]
    H -->|Tidak| H5[Skip full banner]

    E --> I{show_combo_package_section?}
    I -->|Ya| I1[Load ComboPackage aktif]
    I1 --> I2[Preload pricing]
    I2 --> I3[Render paket bundling]
    I -->|Tidak| I4[Skip combo]
```

## 18. Flow Admin Event Management

```mermaid
flowchart TD
    A[Admin Event] --> B[Atur Event]
    A --> C[Image Hero]
    A --> D[Flash Sale]
    A --> E[Full Banner]
    A --> F[Paket Kombo]

    B --> B1[title/subtitle]
    B --> B2[starts_at/ends_at]
    B --> B3[is_active]
    B --> B4[Toggle visibility section]
    B4 --> B41[Hero]
    B4 --> B42[Flash Sale]
    B4 --> B43[Full Banner]
    B4 --> B44[Combo Package]

    C --> C1[Create/Edit EventHeroImage]
    C1 --> C2[position main/side_top/side_bottom]
    C2 --> C3[image, title, subtitle, link_url, sort_order, is_active]

    D --> D1[Create/Edit EventFlashSaleGroup]
    D1 --> D2[Create/Edit EventFlashSaleItem]
    D2 --> D3[product_id, discount_type, discount_value, stock_limit]
    D3 --> D4[group active + item active]

    E --> E1[Create/Edit EventFullBanner]
    E1 --> E2[image, button_url, sort_order, is_active]

    F --> F1[Create/Edit ComboPackage]
    F1 --> F2[Pilih minimal 2 produk]
    F2 --> F3[Set qty tiap item]
    F3 --> F4[Set diskon percent/amount]
    F4 --> F5[Hitung original_total]
    F5 --> F6[Hitung discount_amount]
    F6 --> F7[Hitung package_price]
    F7 --> F8[Simpan ComboPackage + Items]
```

## 19. Flow Combo Package Detail dan Cart

```mermaid
flowchart TD
    A[Customer buka detail Paket Bundling] --> B[Route event.packages.show]
    B --> C[Load ComboPackage aktif]
    C --> D{Event aktif sekarang?}
    D -->|Tidak| E[Tampilkan event-empty]
    D -->|Ya| F[Tampilkan media + info paket]

    F --> G[Harga total barang]
    F --> H[Diskon paket]
    F --> I[Harga paket]
    F --> J[Produk dalam paket]

    F --> K[Customer klik Beli Paket]
    K --> L[cart.add.combo]
    L --> M[CartService addComboPackage]
    M --> N[Cek stok children]
    N --> O{Stok cukup?}
    O -->|Ya| P[Masuk cart sebagai satu item combo]
    O -->|Tidak| Q[Tolak dengan pesan]

    P --> R[Cart menampilkan parent combo]
    R --> S[Children produk ditampilkan di bawahnya]
    S --> T[Checkout snapshot menyimpan children]
```

## 20. Flow Admin Dashboard Data

```mermaid
flowchart TD
    A[Admin buka Dashboard] --> B[Load statistik]
    B --> C[Total Orders]
    B --> D[Total Customers]
    B --> E[Total Revenue]
    B --> F[Total Products]
    B --> G[Total Admin]

    C --> C1[Order valid tidak termasuk failed/expired/cancelled]
    D --> D1[users role customer]
    E --> E1[orders payment_status paid/settlement/capture]
    F --> F1[products count]
    G --> G1[users role admin]

    H[Top Selling Products] --> H1[Join order_items dengan orders]
    H1 --> H2[Filter paid orders]
    H2 --> H3[Group by product_name]
    H3 --> H4[Sum quantity dan total]

    I[Revenue Chart] --> I1[Loop 12 bulan terakhir]
    I1 --> I2[Sum paid revenue per month]

    J[Recent Orders] --> J1[Order valid terbaru]
```

## 21. Flow Newsletter

```mermaid
flowchart TD
    A[Customer isi email newsletter di footer] --> B[POST newsletter.subscribe]
    B --> C[Validasi email]
    C --> D{Valid?}
    D -->|Tidak| E[Back dengan error]
    D -->|Ya| F[Normalize email lowercase]
    F --> G[NewsletterSubscriber::firstOrCreate]
    G --> H{Subscriber baru?}
    H -->|Ya| I[Simpan source footer, customer_id, ip, user_agent]
    I --> J[Flash success]
    H -->|Tidak| K[Flash info sudah terdaftar]

    L[Admin Customer > Newsletter] --> M[List subscriber]
    M --> N[Search]
    M --> O[Limit pagination]
```

## 22. Flow Database / ERD Ringkas

```mermaid
flowchart LR
    User[users] -->|has many| Order[orders]
    Order -->|has many| OrderItem[order_items]

    Category[categories] -->|has many| Product[products]
    Brand[brands] -->|has many| Product
    Product -->|has many| OrderItem

    PaymentMethod[payment_methods] -->|has many| Order
    ShippingMethod[shipping_methods] -->|has many| Order

    EventSetting[event_settings] --> EventRuntime[activeNow]
    EventRuntime --> EventHeroImage[event_hero_images]
    EventRuntime --> EventFlashSaleGroup[event_flash_sale_groups]
    EventFlashSaleGroup --> EventFlashSaleItem[event_flash_sale_items]
    EventFlashSaleItem --> Product

    ComboPackage[combo_packages] -->|has many| ComboPackageItem[combo_package_items]
    ComboPackageItem --> Product
    ComboPackage --> OrderItem

    HomeLayoutGroup[home_layout_groups] --> HomeLayoutSlot[home_layout_slots]
    HomeLayoutSlot --> Category
    HomeLayoutSlot --> HomeSection[home_sections]

    HomeCategoryGridSetting[home_category_grid_settings] --> HomeCategoryGridItem[home_category_grid_items]
    HomeCategoryGridItem --> Category

    FonnteSetting[fonnte_settings] --> FonnteMessageLog[fonnte_message_logs]
    FonnteMessageLog --> Order

    NewsletterSubscriber[newsletter_subscribers] --> User
```

## 23. Flow Migrations / Modul Database

```mermaid
flowchart TD
    A[Migrations] --> B[Core Laravel]
    A --> C[Catalog]
    A --> D[Shop Content]
    A --> E[Checkout]
    A --> F[Event]
    A --> G[Home Layout]
    A --> H[Customer]
    A --> I[Fonnte]

    B --> B1[users, cache, jobs]
    C --> C1[categories, brands, products]
    D --> D1[banners, home_sections, about_sections]
    E --> E1[orders, order_items]
    E --> E2[payment_methods, shipping_methods, shipping_settings]
    F --> F1[event_settings]
    F --> F2[event_hero_images]
    F --> F3[event_flash_sale_groups/items]
    F --> F4[event_full_banners]
    F --> F5[combo_packages/items]
    G --> G1[home_layout_groups]
    G --> G2[home_layout_slots]
    G --> G3[home_category_grid_settings/items]
    H --> H1[newsletter_subscribers]
    I --> I1[fonnte_settings]
    I --> I2[fonnte_message_logs]
```

## 24. Full Flow Pembelian dari Awal sampai Akhir

```mermaid
flowchart TD
    A[Customer masuk website] --> B[Lihat Home / Produk / Event]
    B --> C[Pilih produk atau paket]
    C --> D{Item type}

    D -->|Produk| E[Cek harga regular / promo / flash sale]
    D -->|Paket Combo| F[Cek event aktif dan paket aktif]

    E --> G[Cek stock / limit flash sale]
    F --> H[Cek stok semua produk paket]

    G --> I[Add to Cart]
    H --> I
    I --> J[Cart Session]
    J --> K[Cart Page]
    K --> L[Update qty / hapus / checkout]
    L --> M[Login customer jika perlu]

    M --> N[Checkout]
    N --> O[Isi data customer dan alamat]
    O --> P[Pilih shipping]
    P --> Q[Pilih payment]
    Q --> R[Place Order]

    R --> S[Validasi cart dan form]
    S --> T[Create Order]
    T --> U[Create OrderItem snapshot]
    U --> V[Kurangi stok]
    V --> W[Payment branch]

    W -->|WhatsApp| X[Generate WA URL]
    W -->|Midtrans| Y[Generate Snap URL]
    W -->|Manual/QR/URL| Z[Payment Page]

    X --> AA[Fonnte notif order created]
    Y --> AA
    Z --> AA

    AA --> AB[Clear cart]
    AB --> AC[Customer bayar / konfirmasi]
    AC --> AD[Admin cek order]
    AD --> AE[Admin update payment_status / order_status]
    AE --> AF[Order processing / shipped / completed]
```

## 25. Known Gaps / Hal yang Perlu Dicek Lagi

Bagian ini bukan error wajib, tapi daftar area yang perlu dipastikan supaya dokumentasi dan implementasi makin rapi.

1. Route Fonnte Settings. Layout admin sudah punya menu Fonnte Settings, jadi routes/web.php harus punya route admin.settings.fonnte.
2. Google customer callback. Pastikan login Google customer tidak menimpa akun admin menjadi role customer.
3. Midtrans webhook/status callback. Snap redirect sudah ada, tetapi status paid/expired/failed idealnya otomatis lewat webhook atau status checker.
4. Token Fonnte. Simpan encrypted di database, jangan tampilkan token lama di public property Livewire.
5. SSL localhost. Error cURL 60/77 di localhost diselesaikan dengan cacert.pem, bukan perubahan flow aplikasi.
6. Stock restore. Karena order langsung mengurangi stok saat dibuat, hapus order harus restore stok dari order_items snapshot.

```mermaid
flowchart TD
    A[Checklist Final] --> B[Route Fonnte ada]
    A --> C[Google login aman untuk admin]
    A --> D[Midtrans webhook/status checker]
    A --> E[Fonnte token encrypted]
    A --> F[SSL localhost fix]
    A --> G[Restore stock saat hapus order]

    B --> H[Flow production lebih stabil]
    C --> H
    D --> H
    E --> H
    F --> H
    G --> H
```
