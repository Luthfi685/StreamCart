<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'StreamCart') — Seller Studio</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                        serif: ['Playfair Display', 'serif'],
                    },
                    colors: {
                        primary: {
                            50:  '#eff6ff',
                            100: '#dbeafe',
                            200: '#bfdbfe',
                            300: '#93c5fd',
                            400: '#60a5fa',
                            500: '#3b82f6',
                            600: '#2563eb',
                            700: '#1d4ed8',
                            800: '#1e40af',
                            900: '#1e3a8a',
                        }
                    }
                }
            }
        }
    </script>
    <style>
        body { background-color: #f8f9fa; color: #1e293b; }
        .sidebar-link { transition: background 0.2s ease, color 0.2s ease; }
        .sidebar-link:hover, .sidebar-link.active {
            background-color: #eff6ff;
            color: #1d4ed8;
        }
        .sidebar-link.active { font-weight: 600; border-right: 3px solid #2563eb; }
        .sidebar-link.active ion-icon { color: #2563eb; }
        .sidebar-link:hover ion-icon { color: #1d4ed8; }
        .card-hover { transition: box-shadow 0.25s ease, transform 0.25s ease; }
        .card-hover:hover { box-shadow: 0 8px 30px rgba(37,99,235,0.12); transform: translateY(-2px); }
        .badge-blue { background: #dbeafe; color: #1d4ed8; }
        .badge-green { background: #dcfce7; color: #15803d; }
        .badge-yellow { background: #fef9c3; color: #a16207; }
        .badge-red { background: #fee2e2; color: #b91c1c; }
        .table-header { background: #2563eb; color: white; }
        .table-row-hover:hover { background: #f0f7ff; }
        /* Scrollbar */
        ::-webkit-scrollbar { width: 5px; }
        ::-webkit-scrollbar-track { background: #f1f5f9; }
        ::-webkit-scrollbar-thumb { background: #93c5fd; border-radius: 10px; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: translateY(0); } }
        .fade-in { animation: fadeIn 0.4s ease forwards; }
    </style>
    @stack('styles')
</head>
<body class="font-sans antialiased">

    <!-- Sidebar -->
    <aside id="sidebar" class="fixed inset-y-0 left-0 w-64 bg-white border-r border-blue-100 z-40 flex flex-col shadow-sm transition-transform duration-300 -translate-x-full lg:translate-x-0">
        <!-- Logo -->
        <div class="h-16 flex items-center px-6 border-b border-blue-50 flex-shrink-0">
            <a href="/" class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-lg bg-primary-600 flex items-center justify-center shadow-md shadow-blue-200">
                    <ion-icon name="storefront" class="text-white text-[18px]"></ion-icon>
                </div>
                <span class="font-serif text-xl font-bold text-primary-700 tracking-tight">StreamCart</span>
            </a>
        </div>

        <!-- Role Badge -->
        <div class="px-4 py-3 border-b border-blue-50">
            <div class="flex items-center gap-2 bg-primary-50 rounded-lg px-3 py-2">
                @php $role = auth()->user()->role ?? 'seller'; @endphp
                <div class="w-7 h-7 rounded-full bg-primary-600 flex items-center justify-center flex-shrink-0">
                    <ion-icon name="{{ $role === 'admin' ? 'shield-checkmark' : 'storefront-outline' }}" class="text-white text-sm"></ion-icon>
                </div>
                <div>
                    <p class="text-xs text-primary-500 leading-none">Masuk sebagai</p>
                    <p class="text-sm font-semibold text-primary-800 leading-tight capitalize">{{ $role }}</p>
                </div>
            </div>
        </div>

        <!-- Navigation -->
        <nav class="flex-1 px-3 py-4 overflow-y-auto space-y-0.5">
            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest px-3 mb-2">Menu Utama</p>

            @if(auth()->user()->role === 'admin')
                {{-- ADMIN MENU --}}
                <a href="/admin/dashboard" class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-slate-600 text-sm {{ request()->is('admin/dashboard') ? 'active' : '' }}">
                    <ion-icon name="grid-outline" class="text-[18px] text-slate-500 shrink-0"></ion-icon>
                    <span>Dashboard</span>
                </a>
                <a href="/admin/users" class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-slate-600 text-sm {{ request()->is('admin/users*') ? 'active' : '' }}">
                    <ion-icon name="people-outline" class="text-[18px] text-slate-500 shrink-0"></ion-icon>
                    <span>Kelola Pengguna</span>
                </a>
                <a href="/admin/live-sessions" class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-slate-600 text-sm {{ request()->is('admin/live-sessions*') ? 'active' : '' }}">
                    <ion-icon name="radio-outline" class="text-[18px] text-slate-500 shrink-0"></ion-icon>
                    <span>Monitor Live</span>
                </a>
                <a href="/admin/transactions" class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-slate-600 text-sm {{ request()->is('admin/transactions*') ? 'active' : '' }}">
                    <ion-icon name="swap-horizontal-outline" class="text-[18px] text-slate-500 shrink-0"></ion-icon>
                    <span>Transaksi & Escrow</span>
                </a>
                <a href="/admin/withdrawals" class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-slate-600 text-sm {{ request()->is('admin/withdrawals*') ? 'active' : '' }}">
                    <ion-icon name="cash-outline" class="text-[18px] text-slate-500 shrink-0"></ion-icon>
                    <span>Penarikan Dana</span>
                </a>
                <a href="/admin/refunds" class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-slate-600 text-sm {{ request()->is('admin/refunds*') ? 'active' : '' }}">
                    <ion-icon name="return-up-back-outline" class="text-[18px] text-slate-500 shrink-0"></ion-icon>
                    <span>Pengembalian Dana</span>
                </a>
                <div class="pt-3 pb-1">
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest px-3">Dukungan</p>
                </div>
                <a href="/admin/support" class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-slate-600 text-sm {{ request()->is('admin/support*') ? 'active' : '' }}">
                    <ion-icon name="chatbubbles-outline" class="text-[18px] text-slate-500 shrink-0"></ion-icon>
                    <span>Live Chat Support</span>
                </a>
                <a href="/admin/tickets" class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-slate-600 text-sm {{ request()->is('admin/tickets*') ? 'active' : '' }}">
                    <ion-icon name="mail-unread-outline" class="text-[18px] text-slate-500 shrink-0"></ion-icon>
                    <span>Tiket Pengaduan</span>
                </a>

            @else
                {{-- SELLER MENU --}}
                <a href="/seller/dashboard" class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-slate-600 text-sm {{ request()->is('seller/dashboard') ? 'active' : '' }}">
                    <ion-icon name="grid-outline" class="text-[18px] text-slate-500 shrink-0"></ion-icon>
                    <span>Dashboard</span>
                </a>
                <a href="/seller/products" class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-slate-600 text-sm {{ request()->is('seller/products*') ? 'active' : '' }}">
                    <ion-icon name="cube-outline" class="text-[18px] text-slate-500 shrink-0"></ion-icon>
                    <span>Produk Saya</span>
                </a>
                <a href="/seller/live-sessions" class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-slate-600 text-sm {{ request()->is('seller/live-sessions*') ? 'active' : '' }}">
                    <ion-icon name="radio-outline" class="text-[18px] text-slate-500 shrink-0"></ion-icon>
                    <span>Sesi Live</span>
                </a>
                <a href="/seller/orders" class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-slate-600 text-sm {{ request()->is('seller/orders*') ? 'active' : '' }}">
                    <ion-icon name="receipt-outline" class="text-[18px] text-slate-500 shrink-0"></ion-icon>
                    <span>Pesanan Masuk</span>
                </a>
                <a href="/seller/wallet" class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-slate-600 text-sm {{ request()->is('seller/wallet*') ? 'active' : '' }}">
                    <ion-icon name="wallet-outline" class="text-[18px] text-slate-500 shrink-0"></ion-icon>
                    <span>Dompet Escrow</span>
                </a>
                <div class="pt-3 pb-1">
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest px-3">Toko</p>
                </div>
                <a href="/seller/store-profile" class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-slate-600 text-sm {{ request()->is('seller/store-profile*') ? 'active' : '' }}">
                    <ion-icon name="storefront-outline" class="text-[18px] text-slate-500 shrink-0"></ion-icon>
                    <span>Profil Toko</span>
                </a>
                <a href="/seller/support" class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-slate-600 text-sm {{ request()->is('seller/support*') ? 'active' : '' }}">
                    <ion-icon name="chatbubble-ellipses-outline" class="text-[18px] text-slate-500 shrink-0"></ion-icon>
                    <span>Bantuan & Support</span>
                </a>
            @endif
        </nav>

        <!-- Logout Button -->
        <div class="p-4 border-t border-blue-50 flex-shrink-0">
            <form action="{{ route('logout') }}" method="POST">
                @csrf
                <button type="submit" class="w-full flex items-center justify-center gap-2 text-sm font-medium text-red-500 hover:bg-red-50 px-4 py-2.5 rounded-lg transition-colors">
                    <ion-icon name="log-out-outline" class="text-[18px]"></ion-icon>
                    Keluar
                </button>
            </form>
        </div>
    </aside>

    <!-- Main Wrapper -->
    <div class="lg:pl-64 min-h-screen flex flex-col">

        <!-- Top Navbar -->
        <header class="h-16 bg-white border-b border-blue-100 sticky top-0 z-30 flex items-center justify-between px-6 shadow-sm">
            <!-- Left: Mobile menu toggle + Page Title -->
            <div class="flex items-center gap-4">
                <button id="sidebar-toggle" class="lg:hidden text-slate-500 hover:text-primary-600">
                    <ion-icon name="menu-outline" class="text-2xl"></ion-icon>
                </button>
                <div>
                    <h1 class="text-base font-bold text-slate-800 leading-none">@yield('page-title', 'Dashboard')</h1>
                    <p class="text-xs text-slate-400 mt-0.5">@yield('page-subtitle', now()->translatedFormat('l, d F Y'))</p>
                </div>
            </div>

            <!-- Right: Notifications + Profile -->
            <div class="flex items-center gap-3">
                <!-- Notification Bell -->
                <div class="relative group">
                    <button class="relative w-9 h-9 rounded-lg hover:bg-primary-50 flex items-center justify-center text-slate-500 hover:text-primary-600 transition-colors">
                        <ion-icon name="notifications-outline" class="text-xl"></ion-icon>
                    </button>
                    <!-- Dropdown -->
                    <div class="absolute right-0 mt-2 w-64 bg-white rounded-xl shadow-lg border border-slate-100 opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200 z-50">
                        <div class="p-4 text-center">
                            <ion-icon name="notifications-off-outline" class="text-3xl text-slate-300 mb-2"></ion-icon>
                            <p class="text-sm font-medium text-slate-800">Belum ada notifikasi baru</p>
                            <p class="text-xs text-slate-500 mt-1">Notifikasi pesanan dikirim ke email</p>
                        </div>
                    </div>
                </div>

                <!-- Profile Dropdown -->
                <div class="relative" x-data="{ open: false }">
                    <button class="flex items-center gap-2.5 pl-3 border-l border-slate-200 hover:opacity-80 transition-opacity">
                        <div class="text-right hidden sm:block">
                            <p class="text-sm font-semibold text-slate-700 leading-none">{{ auth()->user()->store_name ?? auth()->user()->name ?? 'User' }}</p>
                            <p class="text-xs text-primary-500 mt-0.5 capitalize">{{ auth()->user()->role ?? 'seller' }}</p>
                        </div>
                        <img
                            src="{{ auth()->user()->avatar ?? 'https://ui-avatars.com/api/?name='.urlencode(auth()->user()->name ?? 'U').'&background=2563eb&color=fff&bold=true' }}"
                            alt="Avatar"
                            class="w-8 h-8 rounded-full ring-2 ring-primary-200 object-cover shrink-0"
                        >
                    </button>
                </div>
            </div>
        </header>

        <!-- Page Content -->
        <main class="flex-1 p-6 fade-in">
            @yield('content')
        </main>

        <!-- Footer -->
        <footer class="px-6 py-4 text-center">
        </footer>
    </div>

    <!-- Mobile Sidebar Overlay -->
    <div id="overlay" class="fixed inset-0 bg-slate-900/40 z-30 hidden lg:hidden backdrop-blur-sm"></div>

    <script>
        const toggle = document.getElementById('sidebar-toggle');
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('overlay');
        if (toggle) {
            toggle.addEventListener('click', () => {
                sidebar.classList.toggle('-translate-x-full');
                overlay.classList.toggle('hidden');
            });
            overlay.addEventListener('click', () => {
                sidebar.classList.add('-translate-x-full');
                overlay.classList.add('hidden');
            });
        }
    </script>
    @stack('modals')
    @stack('scripts')

    @if(Auth::check() && Auth::user()->role === 'admin')
    <div id="admin-toast-container" class="fixed top-4 right-4 z-50 flex flex-col gap-2 pointer-events-none"></div>
    <!-- Load Pusher and Echo dynamically for Admin Notifications -->
    <script src="https://js.pusher.com/8.2.0/pusher.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/laravel-echo@1.16.1/dist/echo.iife.js"></script>
    <script>
        window.Pusher = Pusher;
        window.Echo = new Echo({
            broadcaster: 'reverb',
            key: '{{ env("REVERB_APP_KEY") }}',
            wsHost: '{{ env("REVERB_HOST", "127.0.0.1") }}',
            wsPort: {{ env("REVERB_PORT", 8080) }},
            wssPort: {{ env("REVERB_PORT", 8080) }},
            forceTLS: false,
            enabledTransports: ['ws', 'wss'],
        });

        window.Echo.channel('admin-notifications')
            .listen('.withdrawal.requested', (e) => {
                const container = document.getElementById('admin-toast-container');
                const toast = document.createElement('div');
                toast.className = 'bg-white border-l-4 border-yellow-400 p-4 rounded shadow-lg flex items-center gap-3 animate-fade-in-up pointer-events-auto';
                
                const amount = new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR' }).format(e.amount);
                
                toast.innerHTML = `
                    <ion-icon name="cash" class="text-yellow-500 text-3xl"></ion-icon>
                    <div>
                        <p class="font-bold text-slate-800 text-sm">Penarikan Dana Baru!</p>
                        <p class="text-xs text-slate-500">${e.sellerName} mengajukan ${amount}</p>
                    </div>
                    <button onclick="this.parentElement.remove()" class="ml-4 text-slate-400 hover:text-slate-600">
                        <ion-icon name="close"></ion-icon>
                    </button>
                `;
                
                container.appendChild(toast);
                
                // Auto remove after 5 seconds
                setTimeout(() => {
                    if(toast.parentElement) {
                        toast.remove();
                    }
                }, 5000);
            });
    </script>
    @endif
</body>
</html>
