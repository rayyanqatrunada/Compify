<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Compify - Modern Computer Store')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-screen bg-[#05070b] text-white antialiased">
    <div
        x-data="{ mobileOpen: false, toast: @js(session('status')) }"
        x-init="if (toast) setTimeout(() => toast = null, 3600)"
        class="relative min-h-screen overflow-x-hidden"
    >
        <header class="sticky top-0 z-50 border-b border-white/10 bg-[#05070b]/82 backdrop-blur-xl">
            <nav class="mx-auto flex h-16 w-full max-w-7xl items-center justify-between px-4 sm:px-6 lg:px-8">
                <a href="{{ route('home') }}" class="flex items-center gap-3" aria-label="Compify home">
                    <span class="grid size-9 place-items-center rounded-lg border border-sky-300/30 bg-sky-400/12 text-sm font-black text-sky-200 shadow-[0_0_24px_rgba(56,189,248,.22)]">C</span>
                    <span class="text-lg font-semibold tracking-[0.18em] text-white">COMPIFY</span>
                </a>

                <div class="hidden items-center gap-8 text-sm text-slate-300 md:flex">
                    <a class="nav-link {{ request()->routeIs('home') ? 'text-white' : '' }}" href="{{ route('home') }}">Home</a>
                    <a class="nav-link {{ request()->routeIs('products.*') ? 'text-white' : '' }}" href="{{ route('products.index') }}">Products</a>
                    <a class="nav-link" href="{{ route('home') }}#categories">Categories</a>
                    <a class="nav-link" href="{{ route('home') }}#faq">FAQ</a>
                </div>

                <div class="hidden items-center gap-3 md:flex">
                    @auth
                        @if (auth()->user()->role === 'admin')
                            <a href="{{ url('/admin') }}" class="soft-button">Admin</a>
                        @endif
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="ghost-button">Logout</button>
                        </form>
                    @else
                        <a href="{{ route('login') }}" class="ghost-button">Login</a>
                        <a href="{{ route('register') }}" class="neon-button">Register</a>
                    @endauth
                </div>

                <button type="button" class="md:hidden" x-on:click="mobileOpen = ! mobileOpen" aria-label="Toggle navigation">
                    <span class="block h-0.5 w-6 bg-white"></span>
                    <span class="mt-1.5 block h-0.5 w-6 bg-white"></span>
                    <span class="mt-1.5 block h-0.5 w-6 bg-white"></span>
                </button>
            </nav>

            <div x-cloak x-show="mobileOpen" x-transition class="border-t border-white/10 bg-[#05070b] px-4 py-4 md:hidden">
                <div class="grid gap-3 text-sm text-slate-200">
                    <a href="{{ route('home') }}">Home</a>
                    <a href="{{ route('products.index') }}">Products</a>
                    <a href="{{ route('home') }}#categories">Categories</a>
                    <a href="{{ route('home') }}#faq">FAQ</a>
                    @auth
                        @if (auth()->user()->role === 'admin')
                            <a href="{{ url('/admin') }}">Admin</a>
                        @endif
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="text-left">Logout</button>
                        </form>
                    @else
                        <a href="{{ route('login') }}">Login</a>
                        <a href="{{ route('register') }}">Register</a>
                    @endauth
                </div>
            </div>
        </header>

        @if (session('status'))
            <div
                x-cloak
                x-show="toast"
                x-transition
                class="fixed right-4 top-20 z-[60] max-w-sm rounded-lg border border-sky-300/30 bg-[#07111f]/95 px-4 py-3 text-sm text-sky-50 shadow-2xl shadow-sky-950/40 backdrop-blur"
            >
                {{ session('status') }}
            </div>
        @endif

        <main>
            @yield('content')
        </main>

        <footer class="border-t border-white/10 bg-[#05070b]">
            <div class="mx-auto grid max-w-7xl gap-10 px-4 py-12 sm:px-6 md:grid-cols-[1.4fr_.8fr_.8fr] lg:px-8">
                <div>
                    <div class="flex items-center gap-3">
                        <span class="grid size-9 place-items-center rounded-lg bg-sky-400/15 text-sm font-black text-sky-200">C</span>
                        <span class="text-lg font-semibold tracking-[0.18em]">COMPIFY</span>
                    </div>
                    <p class="mt-4 max-w-md text-sm leading-6 text-slate-400">Toko online dummy untuk perlengkapan komputer, setup desk, dan portfolio Laravel Livewire Filament.</p>
                </div>
                <div>
                    <p class="text-sm font-semibold text-white">Store</p>
                    <div class="mt-4 grid gap-2 text-sm text-slate-400">
                        <a href="{{ route('products.index') }}">All products</a>
                        <a href="{{ route('home') }}#categories">Categories</a>
                        <a href="{{ route('home') }}#promo">Promo</a>
                    </div>
                </div>
                <div>
                    <p class="text-sm font-semibold text-white">Admin Demo</p>
                    <div class="mt-4 grid gap-2 text-sm text-slate-400">
                        <span>admin@compify.test</span>
                        <span>password</span>
                    </div>
                </div>
            </div>
        </footer>
    </div>

    @livewireScripts
</body>
</html>
