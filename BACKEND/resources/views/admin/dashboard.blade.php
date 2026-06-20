@extends('layouts.app')

@section('title', 'Admin Dashboard')
@section('page-title', 'Dashboard Admin')
@section('page-subtitle', 'Monitoring sistem StreamCart secara keseluruhan')

@push('styles')
<style>
    .stat-ring { stroke-dasharray: 220; stroke-dashoffset: 0; transition: stroke-dashoffset 1.5s ease; }
    .approve-btn { transition: all 0.2s ease; }
    .approve-btn:hover { transform: translateY(-1px); box-shadow: 0 4px 14px rgba(37,99,235,0.35); }
</style>
@endpush

@section('content')

{{-- ═══════════════════════════════════════════════════════
     GLOBAL MONITORING STATS — 4 widget, 3 di antaranya real-time
══════════════════════════════════════════════════════════ --}}
<div class="grid grid-cols-2 lg:grid-cols-4 gap-5 mb-8">

    {{-- Total User (static) --}}
    <div class="bg-white rounded-2xl shadow-sm border border-blue-50 p-5 card-hover">
        <div class="flex items-start justify-between mb-4">
            <div class="w-11 h-11 bg-primary-50 rounded-xl flex items-center justify-center">
                <ion-icon name="people-outline" class="text-2xl text-primary-600"></ion-icon>
            </div>
            <span class="badge-green text-[10px] font-bold px-2 py-0.5 rounded-full">Platform</span>
        </div>
        <p class="text-3xl font-extrabold text-slate-800 tracking-tight">{{ $totalUsers ?? 0 }}</p>
        <p class="text-xs text-slate-500 mt-1 font-medium">Total Pengguna Terdaftar</p>
    </div>

    {{-- REALTIME: Live Aktif --}}
    <div class="bg-white rounded-2xl shadow-sm border border-blue-50 p-5 card-hover relative">
        <div class="flex items-start justify-between mb-4">
            <div class="w-11 h-11 bg-red-50 rounded-xl flex items-center justify-center">
                <ion-icon name="radio-outline" class="text-2xl text-red-500"></ion-icon>
            </div>
            <span id="rt-live-badge" class="badge-yellow text-[10px] font-bold px-2 py-0.5 rounded-full">—</span>
        </div>
        <p id="rt-active-lives" class="text-3xl font-extrabold text-slate-800 tracking-tight">{{ $activeLives ?? 0 }}</p>
        <p class="text-xs text-slate-500 mt-1 font-medium">Sesi Live Aktif Sekarang</p>
        <span class="absolute top-3 right-3 w-2 h-2 rounded-full bg-green-400 animate-ping opacity-60"></span>
    </div>

    {{-- REALTIME: Transaksi Hari Ini --}}
    <div class="bg-white rounded-2xl shadow-sm border border-blue-50 p-5 card-hover relative">
        <div class="flex items-start justify-between mb-4">
            <div class="w-11 h-11 bg-emerald-50 rounded-xl flex items-center justify-center">
                <ion-icon name="swap-horizontal-outline" class="text-2xl text-emerald-600"></ion-icon>
            </div>
            <span class="badge-blue text-[10px] font-bold px-2 py-0.5 rounded-full">Hari Ini</span>
        </div>
        <p id="rt-today-transactions" class="text-3xl font-extrabold text-slate-800 tracking-tight">{{ $totalEscrowTransactions ?? 0 }}</p>
        <p class="text-xs text-slate-500 mt-1 font-medium">Transaksi Masuk Hari Ini</p>
        <span class="absolute top-3 right-3 w-2 h-2 rounded-full bg-green-400 animate-ping opacity-60"></span>
    </div>

    {{-- REALTIME: Komisi Platform (5%) --}}
    <div class="bg-white rounded-2xl shadow-sm border border-blue-50 p-5 card-hover relative">
        <div class="flex items-start justify-between mb-4">
            <div class="w-11 h-11 bg-violet-50 rounded-xl flex items-center justify-center">
                <ion-icon name="cash-outline" class="text-2xl text-violet-600"></ion-icon>
            </div>
            <span class="bg-violet-100 text-violet-700 text-[10px] font-bold px-2 py-0.5 rounded-full">5% Fee</span>
        </div>
        <p id="rt-commission" class="text-xl font-extrabold text-slate-800 tracking-tight">Rp {{ number_format(($totalPlatformRevenue ?? 0), 0, ',', '.') }}</p>
        <p class="text-xs text-slate-500 mt-1 font-medium">Komisi Platform Hari Ini</p>
        <span class="absolute top-3 right-3 w-2 h-2 rounded-full bg-green-400 animate-ping opacity-60"></span>
    </div>

</div>


{{-- ═══════════════════════════════════════════════════════
     MAIN TABLES
══════════════════════════════════════════════════════════ --}}
<div class="grid grid-cols-1 xl:grid-cols-5 gap-6">

    {{-- LEFT: Escrow & Transactions (3/5 width) --}}
    <div class="xl:col-span-3 bg-white rounded-2xl shadow-sm border border-blue-50 overflow-hidden">
        <!-- Card Header -->
        <div class="table-header px-6 py-4 flex items-center justify-between">
            <div>
                <h2 class="font-bold text-base">Transaksi & Escrow</h2>
                <p class="text-blue-100 text-xs mt-0.5">Daftar pembayaran masuk dari buyer yang perlu diproses</p>
            </div>
            <div class="flex items-center gap-2 bg-white/20 px-3 py-1.5 rounded-lg text-white text-xs font-medium">
                <ion-icon name="filter-outline"></ion-icon>
                Filter
            </div>
        </div>

        <!-- Action Link -->
        <div class="px-6 py-3 border-b border-slate-100 flex justify-end">
            <a href="{{ route('admin.transactions.index') }}" class="text-xs font-bold text-primary-600 hover:text-primary-700 bg-primary-50 px-3 py-1.5 rounded-lg transition-colors">Lihat Semua Transaksi &rarr;</a>
        </div>

        <!-- Table -->
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-blue-50 border-b border-blue-100">
                    <tr>
                        <th class="text-left px-5 py-3 text-[11px] font-bold text-primary-700 uppercase tracking-wider">ID & Pembeli</th>
                        <th class="text-left px-4 py-3 text-[11px] font-bold text-primary-700 uppercase tracking-wider">Seller</th>
                        <th class="text-right px-4 py-3 text-[11px] font-bold text-primary-700 uppercase tracking-wider">Jumlah</th>
                        <th class="text-center px-4 py-3 text-[11px] font-bold text-primary-700 uppercase tracking-wider">Status</th>
                        <th class="text-center px-4 py-3 text-[11px] font-bold text-primary-700 uppercase tracking-wider">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($transactions ?? [] as $trx)
                    <tr class="table-row-hover transition-colors">
                        <td class="px-5 py-3.5">
                            <p class="font-bold text-slate-700 text-xs">{{ '#TRX-'.str_pad($trx->id, 5, '0', STR_PAD_LEFT) }}</p>
                            <p class="text-[11px] text-slate-400 mt-0.5">{{ $trx->buyer?->name ?? 'Buyer' }} &bull; {{ \Carbon\Carbon::parse($trx->created_at)->format('d M, H:i') }}</p>
                        </td>
                        <td class="px-4 py-3.5">
                            <p class="text-xs font-semibold text-slate-600">{{ $trx->seller?->store_name ?? $trx->seller?->name ?? 'Toko' }}</p>
                        </td>
                        <td class="px-4 py-3.5 text-right">
                            <p class="text-sm font-bold text-slate-800">Rp {{ number_format($trx->total_price ?? 0, 0, ',', '.') }}</p>
                        </td>
                        <td class="px-4 py-3.5 text-center">
                            @php $status = $trx->status ?? 'pending'; @endphp
                            @if($status === 'checking_admin')
                                <span class="inline-flex items-center gap-1 badge-blue text-[10px] font-bold px-2.5 py-1 rounded-full">
                                    <span class="w-1.5 h-1.5 bg-primary-500 rounded-full animate-pulse"></span>
                                    Menunggu Konfirmasi
                                </span>
                            @elseif($status === 'success' || $status === 'completed')
                                <span class="inline-flex items-center gap-1 badge-green text-[10px] font-bold px-2.5 py-1 rounded-full">
                                    <ion-icon name="checkmark-circle" class="text-emerald-600 text-sm"></ion-icon>
                                    Selesai
                                </span>
                            @elseif($status === 'pending')
                                <span class="badge-yellow text-[10px] font-bold px-2.5 py-1 rounded-full">Pending</span>
                            @elseif($status === 'fail')
                                <span class="badge-red text-[10px] font-bold px-2.5 py-1 rounded-full">Gagal</span>
                            @else
                                <span class="bg-slate-100 text-slate-600 text-[10px] font-bold px-2.5 py-1 rounded-full">{{ ucfirst($status) }}</span>
                            @endif
                        </td>
                        <td class="px-4 py-3.5 text-center">
                            @if(($trx->status ?? '') === 'checking_admin')
                            <form action="{{ route('admin.transactions.approve', $trx->id) }}" method="POST" class="inline" onsubmit="return confirm('Konfirmasi pembayaran dari buyer ini?')">
                                @csrf @method('PATCH')
                                <button type="submit" class="approve-btn bg-primary-600 hover:bg-primary-700 text-white text-[11px] font-bold px-3 py-1.5 rounded-lg flex items-center gap-1 mx-auto">
                                    <ion-icon name="checkmark-outline" class="text-sm"></ion-icon>
                                    Approve
                                </button>
                            </form>
                            @else
                            <span class="text-slate-300 text-xs">—</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    {{-- Dummy data for demonstration --}}
                    @foreach([
                        ['#TRX-00123', 'Budi Santoso', 'Toko Baju Rina', 350000, 'escrow'],
                        ['#TRX-00124', 'Siti Rahayu', 'Elektronik Jaya', 1200000, 'completed'],
                        ['#TRX-00125', 'Ahmad Fauzi', 'Toko Baju Rina', 89000, 'pending'],
                        ['#TRX-00126', 'Dewi Anjani', 'Kosmetik Cantik', 450000, 'escrow'],
                        ['#TRX-00127', 'Rudi Hartono', 'Elektronik Jaya', 2100000, 'completed'],
                    ] as $i => $dummy)
                    <tr class="table-row-hover transition-colors">
                        <td class="px-5 py-3.5">
                            <p class="font-bold text-slate-700 text-xs">{{ $dummy[0] }}</p>
                            <p class="text-[11px] text-slate-400 mt-0.5">{{ $dummy[1] }} &bull; {{ now()->subHours($i * 3)->format('d M, H:i') }}</p>
                        </td>
                        <td class="px-4 py-3.5">
                            <p class="text-xs font-semibold text-slate-600">{{ $dummy[2] }}</p>
                        </td>
                        <td class="px-4 py-3.5 text-right">
                            <p class="text-sm font-bold text-slate-800">Rp {{ number_format($dummy[3], 0, ',', '.') }}</p>
                        </td>
                        <td class="px-4 py-3.5 text-center">
                            @if($dummy[4] === 'escrow')
                                <span class="inline-flex items-center gap-1 badge-blue text-[10px] font-bold px-2.5 py-1 rounded-full">
                                    <span class="w-1.5 h-1.5 bg-primary-500 rounded-full"></span>
                                    Escrow / Paid
                                </span>
                            @elseif($dummy[4] === 'completed')
                                <span class="inline-flex items-center gap-1 badge-green text-[10px] font-bold px-2.5 py-1 rounded-full">
                                    <ion-icon name="checkmark-circle" class="text-emerald-600 text-sm"></ion-icon>
                                    Selesai
                                </span>
                            @else
                                <span class="badge-yellow text-[10px] font-bold px-2.5 py-1 rounded-full">Menunggu</span>
                            @endif
                        </td>
                        <td class="px-4 py-3.5 text-center">
                            @if($dummy[4] === 'escrow')
                            <button class="approve-btn bg-primary-600 hover:bg-primary-700 text-white text-[11px] font-bold px-3 py-1.5 rounded-lg flex items-center gap-1 mx-auto">
                                <ion-icon name="checkmark-outline" class="text-sm"></ion-icon>
                                Approve
                            </button>
                            @else
                            <span class="text-slate-300 text-xs">—</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                    @endforelse
                </tbody>
            </table>
        </div>


    </div>

    {{-- RIGHT: Live Monitor + User List (2/5 width) --}}
    <div class="xl:col-span-2 flex flex-col gap-5">

        {{-- Active Live Sessions Monitor --}}
        <div class="bg-white rounded-2xl shadow-sm border border-blue-50 overflow-hidden">
            <div class="table-header px-5 py-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="font-bold text-sm">Monitor Live Aktif</h3>
                        <p class="text-blue-100 text-xs mt-0.5">Sesi streaming berjalan saat ini</p>
                    </div>
                    <div class="flex items-center gap-1.5 bg-red-500 text-white text-[10px] font-bold px-2.5 py-1 rounded-full">
                        <span class="w-1.5 h-1.5 bg-white rounded-full animate-pulse"></span>
                        LIVE NOW
                    </div>
                </div>
            </div>
            <div id="rt-live-list" class="divide-y divide-slate-100">
                @forelse($liveSessions ?? [] as $live)
                <div class="px-5 py-3.5 flex items-center justify-between hover:bg-blue-50/50 transition-colors">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-lg bg-red-100 flex items-center justify-center flex-shrink-0">
                            <ion-icon name="radio-outline" class="text-sm text-red-500"></ion-icon>
                        </div>
                        <div>
                            <p class="text-xs font-bold text-slate-700 leading-tight">{{ $live->title }}</p>
                            <p class="text-[11px] text-slate-400">{{ $live->seller?->store_name ?? $live->seller?->name ?? '—' }}</p>
                        </div>
                    </div>
                    <div class="text-right">
                        <p class="text-xs font-bold text-slate-600">{{ number_format($live->current_viewers ?? 0) }}</p>
                        <p class="text-[10px] text-slate-400">penonton</p>
                    </div>
                </div>
                @empty
                <div class="px-5 py-4 text-center">
                    <p class="text-[11px] text-slate-400">Tidak ada sesi live aktif.</p>
                </div>
                @endforelse
            </div>
        </div>

        {{-- Analytics Chart --}}
        <div class="bg-white rounded-2xl shadow-sm border border-blue-50 p-5">
            <div class="flex items-center justify-between mb-4">
                <h3 class="font-bold text-sm text-slate-800">Tren Transaksi Mingguan</h3>
                <span class="badge-blue text-[10px] font-bold px-2.5 py-1 rounded-full">7 Hari</span>
            </div>
            <div class="h-40">
                <canvas id="adminChart"></canvas>
            </div>
        </div>

        {{-- Withdrawal Requests --}}
        <div class="bg-white rounded-2xl shadow-sm border border-blue-50 overflow-hidden">
            <div class="table-header px-5 py-4">
                <h3 class="font-bold text-sm">Pengajuan Penarikan Dana</h3>
            </div>
            <div id="rt-withdraw-list" class="divide-y divide-slate-100">
                @forelse($withdrawals ?? [] as $wd)
                <div class="px-5 py-3 flex items-center justify-between hover:bg-blue-50/50 transition-colors">
                    <div>
                        <p class="text-xs font-bold text-slate-700">{{ $wd->seller?->store_name ?? $wd->seller?->name ?? '—' }}</p>
                        <p class="text-[11px] text-slate-400">{{ $wd->bank_name }} - {{ $wd->bank_account_number }}</p>
                        <p class="text-xs font-semibold text-slate-600 mt-0.5">Rp {{ number_format($wd->amount, 0, ',', '.') }}</p>
                    </div>
                    <div>
                        <a href="{{ route('admin.withdrawals.index') }}" class="approve-btn bg-primary-600 hover:bg-primary-700 text-white text-[11px] font-bold px-3 py-1.5 rounded-lg flex items-center gap-1">
                            <ion-icon name="checkmark-outline" class="text-sm"></ion-icon>
                            Proses
                        </a>
                    </div>
                </div>
                @empty
                <div class="px-5 py-4 text-center">
                    <p class="text-[11px] text-slate-400">Tidak ada pengajuan penarikan dana.</p>
                </div>
                @endforelse
            </div>
        </div>

    </div>
</div>

{{-- ═══════════════════════════════════════════════════════
     DATA USER TABLE
══════════════════════════════════════════════════════════ --}}
<div class="bg-white rounded-2xl shadow-sm border border-blue-50 overflow-hidden mt-6">
    <div class="table-header px-6 py-4">
        <h2 class="font-bold text-base">Data Pengguna Terdaftar</h2>
        <p class="text-blue-100 text-xs mt-0.5">Seluruh pengguna yang telah mendaftar di platform StreamCart</p>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-blue-50 border-b border-blue-100">
                <tr>
                    <th class="text-left px-6 py-3 text-[11px] font-bold text-primary-700 uppercase tracking-wider">Pengguna</th>
                    <th class="text-left px-4 py-3 text-[11px] font-bold text-primary-700 uppercase tracking-wider">Email</th>
                    <th class="text-center px-4 py-3 text-[11px] font-bold text-primary-700 uppercase tracking-wider">Role</th>
                    <th class="text-center px-4 py-3 text-[11px] font-bold text-primary-700 uppercase tracking-wider">Status</th>
                    <th class="text-right px-4 py-3 text-[11px] font-bold text-primary-700 uppercase tracking-wider">Bergabung</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($users ?? [] as $u)
                <tr class="table-row-hover transition-colors">
                    <td class="px-6 py-3">
                        <div class="flex items-center gap-3">
                            <img src="{{ $u->avatar ?? 'https://ui-avatars.com/api/?name='.urlencode($u->name).'&background=2563eb&color=fff&size=32' }}"
                                 alt="{{ $u->name }}" class="w-8 h-8 rounded-full ring-1 ring-blue-100">
                            <div>
                                <p class="font-semibold text-slate-700 text-sm">{{ $u->name }}</p>
                                <p class="text-[11px] text-slate-400">{{ $u->store_name ?? '—' }}</p>
                            </div>
                        </div>
                    </td>
                    <td class="px-4 py-3 text-xs text-slate-500">{{ $u->email }}</td>
                    <td class="px-4 py-3 text-center">
                        @if($u->role === 'admin')
                            <span class="bg-violet-100 text-violet-700 text-[10px] font-bold px-2.5 py-1 rounded-full">Admin</span>
                        @elseif($u->role === 'seller')
                            <span class="badge-blue text-[10px] font-bold px-2.5 py-1 rounded-full">Seller</span>
                        @else
                            <span class="bg-slate-100 text-slate-600 text-[10px] font-bold px-2.5 py-1 rounded-full">Buyer</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-center">
                        <span class="badge-green text-[10px] font-bold px-2.5 py-1 rounded-full">Aktif</span>
                    </td>
                    <td class="px-4 py-3 text-right text-xs text-slate-400">{{ \Carbon\Carbon::parse($u->created_at)->format('d M Y') }}</td>
                </tr>
                @empty
                {{-- Dummy --}}
                @foreach([
                    ['Rina Oktaviana', 'rina@mail.com', 'seller', 'Toko Baju Rina'],
                    ['Joko Widodo', 'joko@mail.com', 'buyer', '—'],
                    ['Sinta Dewi', 'sinta@mail.com', 'seller', 'Kosmetik Cantik'],
                    ['Budi Santoso', 'budi@mail.com', 'buyer', '—'],
                    ['Ahmad Fauzi', 'ahmad@mail.com', 'seller', 'Elektronik Jaya'],
                ] as $i => $du)
                <tr class="table-row-hover transition-colors">
                    <td class="px-6 py-3">
                        <div class="flex items-center gap-3">
                            <img src="https://ui-avatars.com/api/?name={{ urlencode($du[0]) }}&background=2563eb&color=fff&size=32"
                                 alt="{{ $du[0] }}" class="w-8 h-8 rounded-full ring-1 ring-blue-100">
                            <div>
                                <p class="font-semibold text-slate-700 text-sm">{{ $du[0] }}</p>
                                <p class="text-[11px] text-slate-400">{{ $du[3] }}</p>
                            </div>
                        </div>
                    </td>
                    <td class="px-4 py-3 text-xs text-slate-500">{{ $du[1] }}</td>
                    <td class="px-4 py-3 text-center">
                        @if($du[2] === 'admin')
                            <span class="bg-violet-100 text-violet-700 text-[10px] font-bold px-2.5 py-1 rounded-full">Admin</span>
                        @elseif($du[2] === 'seller')
                            <span class="badge-blue text-[10px] font-bold px-2.5 py-1 rounded-full">Seller</span>
                        @else
                            <span class="bg-slate-100 text-slate-600 text-[10px] font-bold px-2.5 py-1 rounded-full">Buyer</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-center">
                        <span class="badge-green text-[10px] font-bold px-2.5 py-1 rounded-full">Aktif</span>
                    </td>
                    <td class="px-4 py-3 text-right text-xs text-slate-400">{{ now()->subDays($i * 7)->format('d M Y') }}</td>
                </tr>
                @endforeach
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@endsection

@push('scripts')
<script>
    // ── Admin Chart ──────────────────────────────────────────────────────────────
    const adminCtx = document.getElementById('adminChart')?.getContext('2d');
    if (adminCtx) {
        const grad = adminCtx.createLinearGradient(0, 0, 0, 160);
        grad.addColorStop(0, 'rgba(37,99,235,0.25)');
        grad.addColorStop(1, 'rgba(37,99,235,0.0)');
        new Chart(adminCtx, {
            type: 'line',
            data: {
                labels: ['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'],
                datasets: [{
                    data: [12, 19, 14, 26, 22, 31, 28],
                    borderColor: '#2563eb',
                    backgroundColor: grad,
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
                    y: { grid: { color: '#f1f5f9', borderDash: [4,4] }, border: { display: false }, ticks: { font: { size: 10 }, maxTicksLimit: 4 } }
                }
            }
        });
    }

    // ── REALTIME POLLING — setiap 10 detik ───────────────────────────────────────
    const REALTIME_URL = '{{ route("admin.api.realtime-stats") }}';

    function flashUpdate(el) {
        el.classList.add('opacity-50', 'scale-95', 'transition-all', 'duration-200');
        setTimeout(() => el.classList.remove('opacity-50', 'scale-95'), 300);
    }

    function fetchRealtimeStats() {
        fetch(REALTIME_URL, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
            .then(r => r.ok ? r.json() : Promise.reject(r))
            .then(data => {
                // Update Live Aktif
                const livesEl = document.getElementById('rt-active-lives');
                const badgeEl = document.getElementById('rt-live-badge');
                if (livesEl) {
                    if (parseInt(livesEl.textContent) !== data.active_lives) flashUpdate(livesEl);
                    livesEl.textContent = data.active_lives;
                }
                if (badgeEl) {
                    if (data.active_lives > 0) {
                        badgeEl.textContent = 'LIVE';
                        badgeEl.className = 'bg-red-100 text-red-600 text-[10px] font-bold px-2 py-0.5 rounded-full flex items-center gap-1';
                    } else {
                        badgeEl.textContent = 'Offline';
                        badgeEl.className = 'badge-yellow text-[10px] font-bold px-2 py-0.5 rounded-full';
                    }
                }

                // Update Transaksi Hari Ini
                const trxEl = document.getElementById('rt-today-transactions');
                if (trxEl) {
                    if (parseInt(trxEl.textContent) !== data.today_transactions) flashUpdate(trxEl);
                    trxEl.textContent = data.today_transactions;
                }

                // Update Komisi
                const commEl = document.getElementById('rt-commission');
                if (commEl && commEl.textContent !== data.platform_commission_label) {
                    flashUpdate(commEl);
                    commEl.textContent = data.platform_commission_label;
                }

                // Update List Live
                const liveList = document.getElementById('rt-live-list');
                if (liveList && data.live_sessions) {
                    if (data.live_sessions.length > 0) {
                        liveList.innerHTML = data.live_sessions.map(live => `
                            <div class="px-5 py-3.5 flex items-center justify-between hover:bg-blue-50/50 transition-colors">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-lg bg-red-100 flex items-center justify-center flex-shrink-0">
                                        <ion-icon name="radio-outline" class="text-sm text-red-500"></ion-icon>
                                    </div>
                                    <div>
                                        <p class="text-xs font-bold text-slate-700 leading-tight">${live.title}</p>
                                        <p class="text-[11px] text-slate-400">${live.seller_name}</p>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <p class="text-xs font-bold text-slate-600">${live.viewers}</p>
                                    <p class="text-[10px] text-slate-400">penonton</p>
                                </div>
                            </div>
                        `).join('');
                    } else {
                        liveList.innerHTML = '<div class="px-5 py-4 text-center"><p class="text-[11px] text-slate-400">Tidak ada sesi live aktif.</p></div>';
                    }
                }

                // Update List Withdrawal
                const withdrawList = document.getElementById('rt-withdraw-list');
                if (withdrawList && data.withdrawals) {
                    if (data.withdrawals.length > 0) {
                        withdrawList.innerHTML = data.withdrawals.map(wd => `
                            <div class="px-5 py-3 flex items-center justify-between hover:bg-blue-50/50 transition-colors">
                                <div>
                                    <p class="text-xs font-bold text-slate-700">${wd.seller_name}</p>
                                    <p class="text-[11px] text-slate-400">${wd.bank_info}</p>
                                    <p class="text-xs font-semibold text-slate-600 mt-0.5">${wd.amount_label}</p>
                                </div>
                                <div>
                                    <a href="{{ route('admin.withdrawals.index') }}" class="approve-btn bg-primary-600 hover:bg-primary-700 text-white text-[11px] font-bold px-3 py-1.5 rounded-lg flex items-center gap-1">
                                        <ion-icon name="checkmark-outline" class="text-sm"></ion-icon> Proses
                                    </a>
                                </div>
                            </div>
                        `).join('');
                    } else {
                        withdrawList.innerHTML = '<div class="px-5 py-4 text-center"><p class="text-[11px] text-slate-400">Tidak ada pengajuan penarikan dana.</p></div>';
                    }
                }
            })
            .catch(() => console.warn('[StreamCart] Realtime stats fetch failed — retrying...'));
    }

    // Jalankan segera & ulangi setiap 10 detik
    fetchRealtimeStats();
    setInterval(fetchRealtimeStats, 10000);
</script>
@endpush
