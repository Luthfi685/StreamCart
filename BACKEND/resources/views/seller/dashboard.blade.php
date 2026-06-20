@extends('layouts.app')

@section('title', 'Dashboard Seller')
@section('page-title', 'Dashboard Penjual')
@section('page-subtitle', 'Selamat datang kembali, ' . ($user->store_name ?? $user->name))

@push('styles')
<style>
    .stat-card-gradient-blue { background: linear-gradient(135deg, #1d4ed8, #3b82f6); }
    .stat-card-gradient-indigo { background: linear-gradient(135deg, #4338ca, #6366f1); }
    .stat-card-gradient-emerald { background: linear-gradient(135deg, #059669, #10b981); }
    .modal-overlay { backdrop-filter: blur(4px); }
    .product-row:hover { background: #f0f7ff; }
</style>
@endpush

@section('content')

{{-- ═══════════════════════════════════════════════════════
     TOP STAT CARDS
══════════════════════════════════════════════════════════ --}}
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5 mb-8">

    {{-- Total Penjualan --}}
    <div class="stat-card-gradient-blue text-white rounded-2xl p-5 relative overflow-hidden card-hover shadow-lg shadow-blue-200">
        <div class="absolute -right-8 -top-8 w-32 h-32 bg-white/10 rounded-full"></div>
        <div class="relative z-10">
            <div class="flex items-center justify-between mb-4">
                <span class="text-sm font-medium text-blue-100">Total Penjualan (Bersih)</span>
                <div class="w-10 h-10 bg-white/20 rounded-xl flex items-center justify-center">
                    <ion-icon name="trending-up-outline" class="text-xl text-white"></ion-icon>
                </div>
            </div>
            <p id="rt-total-revenue" class="text-3xl font-bold tracking-tight">Rp {{ number_format($totalRevenue, 0, ',', '.') }}</p>
            <p class="text-xs text-blue-100 mt-1">Pendapatan keseluruhan (95%)</p>
            <div class="flex gap-4 mt-3">
                <div>
                    <p id="rt-products-sold" class="text-xl font-bold">{{ (int)$totalProductsSold }}</p>
                    <p class="text-[10px] text-blue-200">Produk Terjual</p>
                </div>
                <div class="w-px bg-white/20"></div>
                <div>
                    <p id="rt-orders-in" class="text-xl font-bold">{{ $totalOrdersIn }}</p>
                    <p class="text-[10px] text-blue-200">Pesanan Masuk</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Live Session Berjalan --}}
    <div class="stat-card-gradient-indigo text-white rounded-2xl p-5 relative overflow-hidden card-hover shadow-lg shadow-indigo-200">
        <div class="absolute -right-8 -top-8 w-32 h-32 bg-white/10 rounded-full"></div>
        <div class="relative z-10">
            <div class="flex items-center justify-between mb-4">
                <span class="text-sm font-medium text-indigo-100">Live Session Aktif</span>
                <div class="w-10 h-10 bg-white/20 rounded-xl flex items-center justify-center relative">
                    <ion-icon name="radio-outline" class="text-xl text-white"></ion-icon>
                    @if($activeLives > 0)
                        <span class="absolute -top-1 -right-1 w-3 h-3 bg-red-400 rounded-full border-2 border-indigo-500 animate-pulse"></span>
                    @endif
                </div>
            </div>
            <p class="text-3xl font-bold tracking-tight">{{ $activeLives }}</p>
            <p class="text-xs text-indigo-100 mt-1">Sesi live streaming aktif</p>
        </div>
    </div>

    {{-- Saldo Wallet --}}
    <div class="stat-card-gradient-emerald text-white rounded-2xl p-5 relative overflow-hidden card-hover shadow-lg shadow-emerald-200">
        <div class="absolute -right-8 -top-8 w-32 h-32 bg-white/10 rounded-full"></div>
        <div class="relative z-10">
            <div class="flex items-center justify-between mb-4">
                <span class="text-sm font-medium text-emerald-100">Saldo Dompet Escrow</span>
                <div class="w-10 h-10 bg-white/20 rounded-xl flex items-center justify-center">
                    <ion-icon name="wallet-outline" class="text-xl text-white"></ion-icon>
                </div>
            </div>
            <p id="rt-wallet-balance" class="text-3xl font-bold tracking-tight">Rp {{ number_format($wallet->balance ?? 0, 0, ',', '.') }}</p>
            <p class="text-xs text-emerald-100 mt-1">Saldo siap dicairkan</p>
        </div>
    </div>

</div>

{{-- ═══════════════════════════════════════════════════════
     MAIN CONTENT GRID
══════════════════════════════════════════════════════════ --}}
<div class="grid grid-cols-1 xl:grid-cols-3 gap-6">

    {{-- LEFT: Products Table --}}
    <div class="xl:col-span-2 bg-white rounded-2xl shadow-sm border border-blue-50 overflow-hidden">
        <div class="table-header px-6 py-4 flex items-center justify-between">
            <div>
                <h2 class="font-bold text-base">Produk Saya</h2>
                <p class="text-blue-100 text-xs mt-0.5">{{ $totalProducts }} produk terdaftar</p>
            </div>
            <div class="flex gap-2">
                <button id="create-live-btn" onclick="document.getElementById('live-modal').classList.remove('hidden')"
                    class="flex items-center gap-2 bg-white text-primary-700 font-bold text-sm px-4 py-2 rounded-lg hover:bg-blue-50 transition-colors shadow-sm">
                    <ion-icon name="radio" class="text-[16px] text-red-500"></ion-icon>
                    Buat Sesi Live
                </button>
                <a href="/seller/products/create" class="flex items-center gap-2 bg-white/20 text-white font-semibold text-sm px-4 py-2 rounded-lg hover:bg-white/30 transition-colors">
                    <ion-icon name="add-outline" class="text-[18px]"></ion-icon>
                    Tambah Produk
                </a>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-blue-50 border-b border-blue-100">
                    <tr>
                        <th class="text-left px-6 py-3 text-xs font-bold text-primary-700 uppercase tracking-wider">Produk</th>
                        <th class="text-center px-4 py-3 text-xs font-bold text-primary-700 uppercase tracking-wider">Stok</th>
                        <th class="text-right px-4 py-3 text-xs font-bold text-primary-700 uppercase tracking-wider">Harga</th>
                        <th class="text-center px-4 py-3 text-xs font-bold text-primary-700 uppercase tracking-wider">Status</th>
                        <th class="text-center px-4 py-3 text-xs font-bold text-primary-700 uppercase tracking-wider">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($products ?? [] as $product)
                    <tr class="product-row transition-colors">
                        <td class="px-6 py-3">
                            <div class="flex items-center gap-3">
                                <div class="w-11 h-11 rounded-xl bg-blue-50 border border-blue-100 overflow-hidden shrink-0 flex items-center justify-center">
                                    @if($product->image_url)
                                        <img src="{{ $product->image_url }}" alt="{{ $product->name }}" class="w-full h-full object-cover">
                                    @else
                                        <ion-icon name="image-outline" class="text-primary-300 text-xl"></ion-icon>
                                    @endif
                                </div>
                                <div>
                                    <p class="font-semibold text-slate-800 text-sm leading-tight">{{ $product->name }}</p>
                                    <p class="text-xs text-slate-400 mt-0.5">{{ $product->category ?? 'Umum' }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <span class="font-semibold {{ $product->stock < 5 ? 'text-red-500' : 'text-slate-700' }}">
                                {{ $product->stock ?? 0 }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right font-semibold text-slate-700">
                            Rp {{ number_format($product->price ?? 0, 0, ',', '.') }}
                        </td>
                        <td class="px-4 py-3 text-center">
                            <span class="inline-block px-2.5 py-1 rounded-full text-[11px] font-bold badge-blue">Aktif</span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <div class="flex items-center justify-center gap-1.5">
                                <a href="/seller/products/{{ $product->id }}/edit"
                                   class="w-8 h-8 rounded-lg bg-primary-50 hover:bg-primary-100 flex items-center justify-center text-primary-600 transition-colors" title="Edit">
                                    <ion-icon name="pencil-outline" class="text-sm"></ion-icon>
                                </a>
                                <form action="/seller/products/{{ $product->id }}" method="POST" class="inline" onsubmit="return confirm('Hapus produk ini?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="w-8 h-8 rounded-lg bg-red-50 hover:bg-red-100 flex items-center justify-center text-red-500 transition-colors" title="Hapus">
                                        <ion-icon name="trash-outline" class="text-sm"></ion-icon>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="text-center py-16">
                            <div class="flex flex-col items-center gap-3 text-slate-400">
                                <div class="w-16 h-16 bg-blue-50 rounded-2xl flex items-center justify-center">
                                    <ion-icon name="cube-outline" class="text-4xl text-primary-300"></ion-icon>
                                </div>
                                <p class="font-medium">Belum ada produk</p>
                                <a href="/seller/products/create" class="text-sm font-semibold text-primary-600 hover:text-primary-700">+ Tambah Produk Pertama</a>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- RIGHT: Stats & Recent Orders --}}
    <div class="flex flex-col gap-5">

        {{-- Analytics Mini Chart --}}
        <div class="bg-white rounded-2xl shadow-sm border border-blue-50 p-5">
            <div class="flex items-center justify-between mb-4">
                <h3 class="font-bold text-sm text-slate-800">Tren Pesanan (7 Hari)</h3>
                <span class="text-xs font-semibold badge-blue px-2.5 py-1 rounded-full">Minggu Ini</span>
            </div>
            <div class="h-36">
                <canvas id="miniOrderChart"></canvas>
            </div>
        </div>

        {{-- Pesanan Terbaru --}}
        <div class="bg-white rounded-2xl shadow-sm border border-blue-50 overflow-hidden flex-1">
            <div class="table-header px-5 py-3.5">
                <h3 class="font-bold text-sm">Pesanan Terbaru</h3>
            </div>
        <div id="rt-recent-orders" class="divide-y divide-slate-100">
                @forelse($recentOrders ?? [] as $order)
                <div class="px-5 py-3 flex items-center justify-between hover:bg-blue-50/50 transition-colors">
                    <div>
                        <p class="text-sm font-semibold text-slate-700">{{ $order->buyer?->name ?? '—' }}</p>
                        <p class="text-xs text-slate-400">ORD-{{ str_pad($order->id, 3, '0', STR_PAD_LEFT) }} &bull; {{ \Carbon\Carbon::parse($order->created_at)->diffForHumans() }}</p>
                    </div>
                    <div class="text-right">
                        <p class="text-sm font-bold text-slate-800">Rp {{ number_format($order->total_price, 0, ',', '.') }}</p>
                        @if($order->status === 'completed')
                            <span class="text-[10px] font-bold badge-green px-2 py-0.5 rounded-full">Selesai</span>
                        @elseif(in_array($order->status, ['processed', 'success']))
                            <span class="text-[10px] font-bold badge-blue px-2 py-0.5 rounded-full">Diproses</span>
                        @elseif($order->status === 'checking_admin')
                            <span class="text-[10px] font-bold badge-blue px-2 py-0.5 rounded-full">Menunggu</span>
                        @else
                            <span class="text-[10px] font-bold badge-yellow px-2 py-0.5 rounded-full">{{ ucfirst($order->status) }}</span>
                        @endif
                    </div>
                </div>
                @empty
                <div class="py-10 text-center text-slate-400 text-sm">
                    <ion-icon name="receipt-outline" class="text-4xl block mx-auto mb-2 text-slate-300"></ion-icon>
                    Belum ada pesanan
                </div>
                @endforelse
            </div>
        </div>

    </div>
</div>

{{-- ═══════════════════════════════════════════════════════
     LIVE SESSION MODAL
══════════════════════════════════════════════════════════ --}}
<div id="live-modal" class="fixed inset-0 z-50 hidden flex items-center justify-center p-4 modal-overlay bg-slate-900/50">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg fade-in flex flex-col max-h-[90vh]">
        <!-- Modal Header -->
        <div class="table-header px-6 py-5 rounded-t-2xl flex items-center justify-between shrink-0">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 bg-white/20 rounded-xl flex items-center justify-center">
                    <ion-icon name="radio" class="text-xl text-white"></ion-icon>
                </div>
                <div>
                    <h2 class="font-bold text-base">Buat Sesi Live Baru</h2>
                    <p class="text-blue-100 text-xs">Isi detail sesi live streaming Anda</p>
                </div>
            </div>
            <button onclick="document.getElementById('live-modal').classList.add('hidden')" class="w-8 h-8 bg-white/20 hover:bg-white/30 rounded-lg flex items-center justify-center text-white transition-colors">
                <ion-icon name="close" class="text-xl"></ion-icon>
            </button>
        </div>

        <!-- Modal Body -->
        <form action="/seller/live-sessions" method="POST" class="p-6 space-y-4 overflow-y-auto">
            @csrf
            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-1.5">Judul Sesi Live <span class="text-red-500">*</span></label>
                <input type="text" name="title" placeholder="Contoh: Flash Sale Baju Musim Panas!"
                    class="w-full border border-slate-200 rounded-xl px-4 py-3 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-primary-300 focus:border-primary-400 transition-all">
            </div>
            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-1.5">Deskripsi (opsional)</label>
                <textarea name="description" rows="3" placeholder="Jelaskan produk apa saja yang akan dipromosikan..."
                    class="w-full border border-slate-200 rounded-xl px-4 py-3 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-primary-300 focus:border-primary-400 transition-all resize-none"></textarea>
            </div>

            <!-- TIPE RILIS -->
            <div class="mt-4">
                <label class="block text-sm font-semibold text-slate-700 mb-2">Tipe Rilis <span class="text-red-500">*</span></label>
                <div class="flex gap-3">
                    <label class="flex-1 cursor-pointer">
                        <input type="radio" name="release_type" value="now" class="peer sr-only" checked onchange="toggleSchedule(this.value)">
                        <div class="border border-slate-200 rounded-xl p-3 flex items-center justify-center gap-2 hover:bg-slate-50 peer-checked:border-primary-500 peer-checked:bg-primary-50 peer-checked:text-primary-700 transition-all">
                            <ion-icon name="flash" class="text-lg"></ion-icon>
                            <span class="font-semibold text-sm">Live Sekarang</span>
                        </div>
                    </label>
                    <label class="flex-1 cursor-pointer">
                        <input type="radio" name="release_type" value="schedule" class="peer sr-only" onchange="toggleSchedule(this.value)">
                        <div class="border border-slate-200 rounded-xl p-3 flex items-center justify-center gap-2 hover:bg-slate-50 peer-checked:border-primary-500 peer-checked:bg-primary-50 peer-checked:text-primary-700 transition-all">
                            <ion-icon name="calendar-outline" class="text-lg"></ion-icon>
                            <span class="font-semibold text-sm">Jadwalkan</span>
                        </div>
                    </label>
                </div>
            </div>

            <!-- DYNAMIC SCHEDULING FIELDS -->
            <div id="schedule-fields" class="hidden mt-3 grid grid-cols-2 gap-3 fade-in">
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">Tanggal Live</label>
                    <input type="date" name="schedule_date" class="w-full border border-slate-200 rounded-xl px-4 py-3 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-primary-300 transition-all">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">Waktu / Jam</label>
                    <input type="time" name="schedule_time" class="w-full border border-slate-200 rounded-xl px-4 py-3 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-primary-300 transition-all">
                </div>
            </div>

            <div class="border-t border-slate-100 pt-4 mt-4">
                <p class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-3">Informasi Rekening Bank untuk Pembayaran</p>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1.5">Nama Bank <span class="text-red-500">*</span></label>
                        <select name="bank_name" class="w-full border border-slate-200 rounded-xl px-4 py-3 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-primary-300 transition-all bg-white">
                            <option value="">Pilih Bank / E-Wallet</option>
                            @foreach([
                                'BCA', 'Mandiri', 'BNI', 'BRI', 'BTN', 'BSI', 'CIMB Niaga', 'Permata', 'Danamon', 'Mega', 'Bukopin', 'Panin', 'OCBC NISP', 'Maybank', 'UOB', 'Muamalat',
                                'Blu by BCA Digital', 'Bank Jago', 'SeaBank', 'Jenius', 'NeoBank', 'Allo Bank', 'Superbank',
                                'Bank BJB', 'Bank DKI', 'Bank Jatim', 'Bank Jateng',
                                'DANA', 'GoPay', 'OVO', 'ShopeePay', 'LinkAja', 'AstraPay', 'i.Saku'
                            ] as $b)
                            <option value="{{ $b }}">{{ $b }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1.5">No. Rekening <span class="text-red-500">*</span></label>
                        <input type="text" name="bank_account" placeholder="Contoh: 1234567890"
                            class="w-full border border-slate-200 rounded-xl px-4 py-3 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-primary-300 transition-all">
                    </div>
                </div>
                <div class="mt-3">
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">Atas Nama Rekening <span class="text-red-500">*</span></label>
                    <input type="text" name="bank_account_name" placeholder="Nama sesuai rekening"
                        class="w-full border border-slate-200 rounded-xl px-4 py-3 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-primary-300 transition-all">
                </div>
            </div>
            <div class="flex gap-3 pt-2">
                <button type="button" onclick="document.getElementById('live-modal').classList.add('hidden')"
                    class="flex-1 border border-slate-200 text-slate-600 font-semibold text-sm py-3 rounded-xl hover:bg-slate-50 transition-colors">
                    Batal
                </button>
                <button type="submit" id="submit-live-btn"
                    class="flex-1 bg-primary-600 hover:bg-primary-700 text-white font-bold text-sm py-3 rounded-xl shadow-md shadow-blue-200 transition-colors flex items-center justify-center gap-2">
                    <ion-icon name="radio" class="text-red-300"></ion-icon>
                    Mulai Sesi Live
                </button>
            </div>
        </form>
    </div>
</div>

@endsection

@push('scripts')
<script>
    // ── Mini Chart ───────────────────────────────────────────────────────────────
    const chartData = @json($chartData);
    const ctx = document.getElementById('miniOrderChart')?.getContext('2d');
    let miniChart = null;

    if (ctx) {
        const gradient = ctx.createLinearGradient(0, 0, 0, 144);
        gradient.addColorStop(0, 'rgba(37, 99, 235, 0.3)');
        gradient.addColorStop(1, 'rgba(37, 99, 235, 0.0)');
        miniChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: chartData.labels,
                datasets: [{
                    data: chartData.orders,
                    borderColor: '#2563eb',
                    backgroundColor: gradient,
                    borderWidth: 2.5,
                    pointBackgroundColor: '#fff',
                    pointBorderColor: '#2563eb',
                    pointBorderWidth: 2,
                    pointRadius: 3,
                    fill: true, tension: 0.4
                }]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    x: { grid: { display: false }, border: { display: false }, ticks: { font: { size: 10, family: 'Inter' } } },
                    y: { display: false, beginAtZero: true }
                }
            }
        });
    }

    // ── Realtime Polling (setiap 5 detik) ─────────────────────────────────────────
    const STATS_URL = '{{ route("seller.api.dashboard-stats") }}';

    function flash(el) {
        el.style.transition = 'opacity 0.2s, transform 0.2s';
        el.style.opacity = '0.4';
        el.style.transform = 'scale(0.95)';
        setTimeout(() => { el.style.opacity = '1'; el.style.transform = 'scale(1)'; }, 250);
    }

    function statusBadge(status) {
        const map = {
            'completed':     '<span class="text-[10px] font-bold badge-green px-2 py-0.5 rounded-full">Selesai</span>',
            'processed':     '<span class="text-[10px] font-bold badge-blue px-2 py-0.5 rounded-full">Diproses</span>',
            'success':       '<span class="text-[10px] font-bold badge-blue px-2 py-0.5 rounded-full">Dikonfirmasi</span>',
            'checking_admin':'<span class="text-[10px] font-bold badge-yellow px-2 py-0.5 rounded-full">Menunggu</span>',
            'pending':       '<span class="text-[10px] font-bold badge-yellow px-2 py-0.5 rounded-full">Pending</span>',
        };
        return map[status] ?? `<span class="text-[10px] font-bold bg-slate-100 text-slate-600 px-2 py-0.5 rounded-full">${status}</span>`;
    }

    function fetchDashboardStats() {
        fetch(STATS_URL, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
            .then(r => r.ok ? r.json() : Promise.reject(r))
            .then(data => {
                // Update stat cards
                const revenueEl = document.getElementById('rt-total-revenue');
                const soldEl    = document.getElementById('rt-products-sold');
                const ordersEl  = document.getElementById('rt-orders-in');
                const walletEl  = document.getElementById('rt-wallet-balance');

                if (revenueEl && revenueEl.textContent !== data.total_revenue_label) {
                    flash(revenueEl);
                    revenueEl.textContent = data.total_revenue_label;
                }
                if (soldEl) {
                    if (parseInt(soldEl.textContent) !== data.total_products_sold) flash(soldEl);
                    soldEl.textContent = data.total_products_sold;
                }
                if (ordersEl) {
                    if (parseInt(ordersEl.textContent) !== data.total_orders_in) flash(ordersEl);
                    ordersEl.textContent = data.total_orders_in;
                }
                if (walletEl && walletEl.textContent !== data.wallet_balance_label) {
                    flash(walletEl);
                    walletEl.textContent = data.wallet_balance_label;
                }

                // Update chart
                if (miniChart && data.chart_orders) {
                    miniChart.data.datasets[0].data = data.chart_orders;
                    miniChart.update('none');
                }

                // Update pesanan terbaru list
                const listEl = document.getElementById('rt-recent-orders');
                if (listEl && data.recent_orders?.length > 0) {
                    listEl.innerHTML = data.recent_orders.map(o => `
                        <div class="px-5 py-3 flex items-center justify-between hover:bg-blue-50/50 transition-colors">
                            <div>
                                <p class="text-sm font-semibold text-slate-700">${o.buyer_name}</p>
                                <p class="text-xs text-slate-400">${o.code} &bull; ${o.time_ago}</p>
                            </div>
                            <div class="text-right">
                                <p class="text-sm font-bold text-slate-800">${o.amount_label}</p>
                                ${statusBadge(o.status)}
                            </div>
                        </div>
                    `).join('');
                }
            })
            .catch(() => {});
    }

    fetchDashboardStats();
    setInterval(fetchDashboardStats, 5000);

    // Close modal on backdrop click
    document.getElementById('live-modal')?.addEventListener('click', function(e) {
        if (e.target === this) this.classList.add('hidden');
    });

    // ── Schedule Live Toggle ───────────────────────────────────────────────────────
    function toggleSchedule(type) {
        const fields = document.getElementById('schedule-fields');
        const submitBtn = document.getElementById('submit-live-btn');
        if (type === 'schedule') {
            fields.classList.remove('hidden');
            submitBtn.innerHTML = '<ion-icon name="calendar" class="text-red-300"></ion-icon> Simpan Jadwal Live';
        } else {
            fields.classList.add('hidden');
            submitBtn.innerHTML = '<ion-icon name="radio" class="text-red-300"></ion-icon> Mulai Sesi Live';
        }
    }
</script>
@endpush
