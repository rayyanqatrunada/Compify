<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>{{ $title ?? 'Admin Compify' }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="admin-body">
    <aside class="admin-sidebar">
        <h2>COMPIFY</h2>
        <a href="{{ route('admin.dashboard') }}" wire:navigate>Dashboard</a>
        <a href="{{ route('admin.categories') }}" wire:navigate>Kategori</a>
        <a href="{{ route('admin.products') }}" wire:navigate>Produk</a>
        <a href="{{ route('admin.banners') }}" wire:navigate>Banner Home</a>
        <a href="{{ route('home') }}" wire:navigate>Lihat Website</a>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit">Keluar</button>
        </form>
    </aside>

    <main class="admin-content">
        {{ $slot }}
    </main>

    @livewireScripts
</body>
</html>