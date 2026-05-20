<?php

namespace Database\Seeders;

use App\Models\Banner;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\Testimonial;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $admin = User::create([
            'name' => 'Admin Compify',
            'email' => 'admin@compify.test',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'phone' => '0812-1000-2026',
            'avatar' => 'https://ui-avatars.com/api/?background=0284c7&color=ffffff&name=Admin+Compify',
        ]);

        $users = User::factory(10)->create([
            'password' => Hash::make('password'),
            'role' => 'user',
        ]);

        $categories = collect([
            ['Mechanical Keyboard', 'Switch, keycap, dan keyboard compact untuk setup kerja maupun gaming.', '#38bdf8'],
            ['Gaming Mouse', 'Mouse ringan, presisi tinggi, dan nyaman untuk sesi panjang.', '#60a5fa'],
            ['Monitor', 'Monitor produktivitas dan gaming dengan panel tajam dan refresh rate tinggi.', '#22d3ee'],
            ['Audio & Headset', 'Headset, microphone, dan perangkat audio untuk meeting atau streaming.', '#a78bfa'],
            ['Desk Essentials', 'Stand, deskmat, dock, webcam, dan aksesori meja modern.', '#34d399'],
        ])->mapWithKeys(function (array $category) {
            $model = Category::create([
                'name' => $category[0],
                'slug' => Str::slug($category[0]),
                'description' => $category[1],
                'image' => 'https://images.unsplash.com/photo-1618477388954-7852f32655ec?auto=format&fit=crop&w=900&q=80',
                'accent_color' => $category[2],
                'is_active' => true,
            ]);

            return [$model->slug => $model];
        });

        $products = collect([
            ['Mechanical Keyboard', 'AeroKey Pro 75', 'Keyboard 75% dengan gasket mount, knob aluminium, dan RGB soft glow.', 1299000, 1599000, 42, true, 'https://images.unsplash.com/photo-1587829741301-dc798b83add3?auto=format&fit=crop&w=900&q=80'],
            ['Mechanical Keyboard', 'FluxType Low Profile', 'Keyboard tipis hot-swap untuk kerja mobile dan setup minimalis.', 1099000, 1399000, 35, true, 'https://images.unsplash.com/photo-1618384887929-16ec33fab9ef?auto=format&fit=crop&w=900&q=80'],
            ['Mechanical Keyboard', 'NeoKey TKL Carbon', 'Tenkeyless keyboard dengan foam dampening dan keycap PBT double-shot.', 1499000, null, 21, false, 'https://images.unsplash.com/photo-1595225476474-87563907a212?auto=format&fit=crop&w=900&q=80'],
            ['Mechanical Keyboard', 'MiniSwitch 65 Frost', 'Layout 65% dengan switch linear halus untuk meja ringkas.', 899000, 1099000, 64, false, 'https://images.unsplash.com/photo-1618384887929-16ec33fab9ef?auto=format&fit=crop&w=900&q=80'],
            ['Gaming Mouse', 'NovaClick Wireless', 'Mouse wireless 58 gram dengan sensor 26K DPI dan charging dock.', 749000, 899000, 58, true, 'https://images.unsplash.com/photo-1527814050087-3793815479db?auto=format&fit=crop&w=900&q=80'],
            ['Gaming Mouse', 'PulseAim X2', 'Mouse gaming ergonomis dengan polling rate tinggi dan grip matte.', 529000, null, 80, false, 'https://images.unsplash.com/photo-1615663245857-ac93bb7c39e7?auto=format&fit=crop&w=900&q=80'],
            ['Gaming Mouse', 'GlidePad Control', 'Mousepad control weave untuk tracking stabil dan presisi.', 189000, 249000, 120, false, 'https://images.unsplash.com/photo-1612198188060-c7c2a3b66eae?auto=format&fit=crop&w=900&q=80'],
            ['Monitor', 'VisionCurve 27 QHD', 'Monitor curved QHD 165Hz untuk gaming, desain, dan coding nyaman.', 3899000, 4399000, 18, true, 'https://images.unsplash.com/photo-1593640495253-23196b27a87f?auto=format&fit=crop&w=900&q=80'],
            ['Monitor', 'StudioView 4K 28', 'Panel IPS 4K warna akurat untuk creator dan produktivitas harian.', 5999000, 6499000, 12, true, 'https://images.unsplash.com/photo-1527443224154-c4a3942d3acf?auto=format&fit=crop&w=900&q=80'],
            ['Monitor', 'FocusBar Monitor Light', 'Screen bar dengan suhu warna adjustable untuk meja kerja malam.', 499000, null, 47, false, 'https://images.unsplash.com/photo-1547082299-de196ea013d6?auto=format&fit=crop&w=900&q=80'],
            ['Audio & Headset', 'PulseWave Headset', 'Headset wireless low latency dengan mic detachable dan cushion breathable.', 1199000, 1499000, 39, true, 'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?auto=format&fit=crop&w=900&q=80'],
            ['Audio & Headset', 'StreamMic Mini', 'USB condenser microphone compact untuk podcast, kelas online, dan streaming.', 699000, 849000, 55, true, 'https://images.unsplash.com/photo-1590602847861-f357a9332bbc?auto=format&fit=crop&w=900&q=80'],
            ['Audio & Headset', 'WaveArm Boom', 'Arm microphone aluminium dengan cable channel tersembunyi.', 329000, null, 68, false, 'https://images.unsplash.com/photo-1516280440614-37939bbacd81?auto=format&fit=crop&w=900&q=80'],
            ['Desk Essentials', 'LiftDock Laptop Stand', 'Laptop stand aluminium dengan sudut ergonomis dan base anti-slip.', 399000, 499000, 76, true, 'https://images.unsplash.com/photo-1611078489935-0cb964de46d6?auto=format&fit=crop&w=900&q=80'],
            ['Desk Essentials', 'Orbit Deskmat XL', 'Deskmat extended dengan stitched edge dan surface smooth-control.', 249000, 329000, 140, true, 'https://images.unsplash.com/photo-1593305841991-05c297ba4575?auto=format&fit=crop&w=900&q=80'],
            ['Desk Essentials', 'FocusCam 2K', 'Webcam 2K autofocus dengan privacy cover dan noise reduction mic.', 899000, 1099000, 25, false, 'https://images.unsplash.com/photo-1587614295999-6c1c136751e8?auto=format&fit=crop&w=900&q=80'],
            ['Desk Essentials', 'CableHub Thunder 8-in-1', 'USB-C hub aluminium dengan HDMI, SD reader, LAN, dan fast charging.', 649000, null, 43, false, 'https://images.unsplash.com/photo-1625842268584-8f3296236761?auto=format&fit=crop&w=900&q=80'],
            ['Desk Essentials', 'CoolPad Alloy', 'Cooling pad metal mesh dengan airflow senyap untuk laptop gaming.', 459000, 549000, 33, false, 'https://images.unsplash.com/photo-1593642702749-b7d2a804fbcf?auto=format&fit=crop&w=900&q=80'],
            ['Desk Essentials', 'MagCable Kit', 'Kit cable management magnetik untuk setup meja bersih.', 159000, null, 160, false, 'https://images.unsplash.com/photo-1558618666-fcd25c85cd64?auto=format&fit=crop&w=900&q=80'],
            ['Desk Essentials', 'HaloLight RGB Strip', 'Ambient light strip untuk backlight monitor dan mood setup.', 299000, 389000, 92, false, 'https://images.unsplash.com/photo-1550745165-9bc0b252726f?auto=format&fit=crop&w=900&q=80'],
        ])->map(function (array $product, int $index) use ($categories) {
            $category = $categories[Str::slug($product[0])];

            return Product::create([
                'category_id' => $category->id,
                'name' => $product[1],
                'slug' => Str::slug($product[1]),
                'sku' => 'CMP-'.str_pad((string) ($index + 1), 4, '0', STR_PAD_LEFT),
                'short_description' => $product[2],
                'description' => $product[2].' Produk dummy ini dibuat untuk simulasi katalog e-commerce Compify, lengkap dengan data stok, rating, harga promo, dan relasi kategori.',
                'price' => $product[3],
                'compare_price' => $product[4],
                'stock' => $product[5],
                'thumbnail' => $product[7],
                'gallery' => [
                    $product[7],
                    'https://images.unsplash.com/photo-1542393545-10f5cde2c810?auto=format&fit=crop&w=900&q=80',
                    'https://images.unsplash.com/photo-1618477388954-7852f32655ec?auto=format&fit=crop&w=900&q=80',
                ],
                'specs' => [
                    'Garansi' => fake()->randomElement(['1 tahun resmi', '2 tahun resmi']),
                    'Koneksi' => fake()->randomElement(['USB-C', 'Bluetooth 5.3', '2.4GHz Wireless', 'HDMI/USB-C']),
                    'Material' => fake()->randomElement(['Aluminium', 'ABS premium', 'Polycarbonate', 'Fabric weave']),
                    'Cocok untuk' => fake()->randomElement(['Gaming', 'Produktivitas', 'Creator', 'Belajar PPLG']),
                ],
                'is_featured' => $product[6],
                'status' => 'active',
                'sold_count' => fake()->numberBetween(24, 780),
                'rating' => fake()->randomFloat(1, 4.5, 5.0),
            ]);
        });

        Banner::insert([
            [
                'title' => 'Upgrade setup jadi lebih fokus',
                'subtitle' => 'Diskon hingga 30% untuk keyboard, monitor, dan desk essentials pilihan.',
                'badge' => 'Setup Week',
                'image' => 'https://images.unsplash.com/photo-1618477388954-7852f32655ec?auto=format&fit=crop&w=1400&q=80',
                'cta_label' => 'Belanja promo',
                'cta_url' => '/products',
                'is_active' => true,
                'starts_at' => now()->subDays(2),
                'ends_at' => now()->addDays(18),
                'sort_order' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'Creator kit untuk kelas dan streaming',
                'subtitle' => 'Microphone, webcam, dan headset dengan kualitas audio visual jernih.',
                'badge' => 'Creator Picks',
                'image' => 'https://images.unsplash.com/photo-1590602847861-f357a9332bbc?auto=format&fit=crop&w=1400&q=80',
                'cta_label' => 'Lihat audio',
                'cta_url' => '/products?category=audio-headset',
                'is_active' => true,
                'starts_at' => now()->subDays(1),
                'ends_at' => now()->addDays(14),
                'sort_order' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        collect([
            ['Raka Pratama', 'Siswa PPLG', 'SMK Nusantara', 'Produk dummy-nya lengkap banget buat latihan CRUD dan presentasi tugas akhir.'],
            ['Nadia Syahira', 'UI Designer', 'Freelance Studio', 'Landing page Compify terasa modern, bersih, dan enak dipakai di mobile.'],
            ['Dimas Arya', 'Streamer', 'DeskLab', 'Katalognya cocok untuk simulasi toko setup desk, dari headset sampai microphone.'],
            ['Alya Maharani', 'Software Developer', 'CodeSpace', 'Struktur admin Filament-nya membantu memahami relasi produk, kategori, dan order.'],
            ['Fajar Rizki', 'Guru Produktif', 'SMK PPLG', 'Proyek ini pas untuk bahan portfolio siswa karena fiturnya nyata dan terukur.'],
            ['Tania Putri', 'Content Creator', 'Studio Mini', 'Section testimonial, FAQ, dan newsletter membuat website terlihat siap demo.'],
            ['Bagas Firmansyah', 'Gamer', 'Night Setup', 'Tema hitam biru neonnya cocok untuk toko aksesori komputer modern.'],
            ['Intan Lestari', 'Mahasiswa', 'Tech Club', 'Filter produk dan detail produk membuat pengalaman belanjanya terasa lengkap.'],
            ['Kevin Saputra', 'IT Support', 'Office Lab', 'Data seedernya realistis, jadi admin dashboard langsung punya konten.'],
            ['Mira Anjani', 'Founder', 'StartDesk', 'Compify terlihat seperti startup kecil yang rapi dan profesional.'],
        ])->each(function (array $testimonial) {
            Testimonial::create([
                'name' => $testimonial[0],
                'role' => $testimonial[1],
                'company' => $testimonial[2],
                'avatar' => 'https://ui-avatars.com/api/?background=0f172a&color=38bdf8&name='.urlencode($testimonial[0]),
                'quote' => $testimonial[3],
                'rating' => 5,
                'is_featured' => true,
            ]);
        });

        foreach (range(1, 12) as $number) {
            $customer = $users->random();
            $items = $products->random(fake()->numberBetween(1, 3));
            $subtotal = 0;

            $order = Order::create([
                'user_id' => $customer->id,
                'order_number' => 'CMP-'.now()->format('ym').'-'.str_pad((string) $number, 5, '0', STR_PAD_LEFT),
                'status' => fake()->randomElement(['pending', 'processing', 'shipped', 'completed', 'completed', 'cancelled']),
                'customer_name' => $customer->name,
                'customer_email' => $customer->email,
                'customer_phone' => fake()->phoneNumber(),
                'shipping_address' => fake()->address(),
                'subtotal' => 0,
                'shipping_cost' => fake()->numberBetween(15000, 65000),
                'discount' => fake()->boolean(35) ? fake()->numberBetween(25000, 250000) : 0,
                'total' => 0,
                'payment_method' => fake()->randomElement(['Bank Transfer', 'Virtual Account', 'E-Wallet']),
                'notes' => fake()->optional()->sentence(8),
                'ordered_at' => now()->subDays(fake()->numberBetween(1, 45)),
            ]);

            foreach ($items as $product) {
                $quantity = fake()->numberBetween(1, 2);
                $lineTotal = $product->price * $quantity;
                $subtotal += $lineTotal;

                $order->items()->create([
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'quantity' => $quantity,
                    'price' => $product->price,
                    'total' => $lineTotal,
                ]);

                $product->increment('sold_count', $quantity);
            }

            $order->update([
                'subtotal' => $subtotal,
                'total' => max(0, $subtotal + $order->shipping_cost - $order->discount),
            ]);
        }

        $demoOrder = $admin->orders()->create([
            'order_number' => 'CMP-DEMO-ADMIN',
            'status' => 'completed',
            'customer_name' => $admin->name,
            'customer_email' => $admin->email,
            'customer_phone' => $admin->phone,
            'shipping_address' => 'Jl. Demo Portfolio No. 13, Jakarta',
            'subtotal' => 1299000,
            'shipping_cost' => 0,
            'discount' => 0,
            'total' => 1299000,
            'payment_method' => 'Demo Checkout',
            'notes' => 'Order contoh untuk memastikan relasi admin dan order terisi.',
            'ordered_at' => now(),
        ]);

        $demoOrder->items()->create([
            'product_id' => $products->first()->id,
            'product_name' => $products->first()->name,
            'quantity' => 1,
            'price' => 1299000,
            'total' => 1299000,
        ]);
    }
}
