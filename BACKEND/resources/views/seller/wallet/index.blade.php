@extends('layouts.app')
@section('title', 'Dompet Escrow')
@section('page-title', 'Dompet Escrow')
@section('page-subtitle', 'Kelola saldo dari penjualan dan pengajuan penarikan dana Anda')

@push('styles')
<style>
    .wallet-card { background: linear-gradient(135deg, #1d4ed8 0%, #3b82f6 60%, #06b6d4 100%); }
    .wallet-circle-1 { position:absolute; width:180px; height:180px; border-radius:50%; background:rgba(255,255,255,0.08); top:-40px; right:-40px; }
    .wallet-circle-2 { position:absolute; width:120px; height:120px; border-radius:50%; background:rgba(255,255,255,0.05); bottom:-30px; left:30px; }

    /* Status badge color for withdrawal */
    .wd-pending  { background:#fefce8; color:#a16207; }
    .wd-approved { background:#f0fdf4; color:#15803d; }
    .wd-rejected { background:#fef2f2; color:#b91c1c; }
    .wd-completed{ background:#f0fdf4; color:#15803d; }

    @keyframes balancePulse { 0%,100%{opacity:1} 50%{opacity:0.6} }
    .balance-updating { animation: balancePulse 0.5s ease; }
</style>
@endpush

@section('content')

@if(session('success'))
<div class="mb-5 bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 rounded-xl text-sm font-medium flex items-center gap-2">
    <ion-icon name="checkmark-circle" class="text-lg shrink-0"></ion-icon> {{ session('success') }}
</div>
@endif
@if($errors->any())
<div class="mb-5 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl text-sm font-medium flex items-center gap-2">
    <ion-icon name="alert-circle" class="text-lg shrink-0"></ion-icon> {{ $errors->first() }}
</div>
@endif

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">

    {{-- ── Wallet Balance Card ──────────────────────────────────── --}}
    <div class="lg:col-span-2">
        <div class="wallet-card text-white rounded-2xl p-6 relative overflow-hidden shadow-xl shadow-blue-200/60">
            <div class="wallet-circle-1"></div>
            <div class="wallet-circle-2"></div>
            <div class="relative z-10">
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <p class="text-sm font-medium text-blue-200">Saldo Tersedia</p>
                        <p id="rt-balance" class="text-4xl font-extrabold tracking-tight mt-1">
                            Rp {{ number_format($wallet->balance ?? 0, 0, ',', '.') }}
                        </p>
                    </div>
                    <div class="w-14 h-14 bg-white/15 rounded-2xl flex items-center justify-center">
                        <ion-icon name="wallet-outline" class="text-3xl text-white"></ion-icon>
                    </div>
                </div>

                @if($pendingBalance > 0)
                <div class="flex items-center gap-2 bg-white/10 rounded-xl px-4 py-2.5 mb-4 w-fit">
                    <ion-icon name="time-outline" class="text-blue-200 text-sm"></ion-icon>
                    <span id="rt-pending-label" class="text-sm text-blue-100 font-medium">
                        Menunggu konfirmasi Admin: Rp {{ number_format($pendingBalance, 0, ',', '.') }}
                    </span>
                </div>
                @else
                <div id="rt-pending-wrap" class="hidden flex items-center gap-2 bg-white/10 rounded-xl px-4 py-2.5 mb-4 w-fit">
                    <ion-icon name="time-outline" class="text-blue-200 text-sm"></ion-icon>
                    <span id="rt-pending-label" class="text-sm text-blue-100 font-medium"></span>
                </div>
                @endif

                <button onclick="openWithdrawModal()"
                    class="flex items-center gap-2 bg-white text-primary-700 font-bold text-sm px-5 py-2.5 rounded-xl hover:bg-blue-50 transition-all hover:-translate-y-0.5 shadow-md">
                    <ion-icon name="arrow-up-circle-outline" class="text-lg"></ion-icon>
                    Tarik Dana
                </button>
            </div>
        </div>
    </div>

    {{-- ── Bank Account Info ────────────────────────────────────── --}}
    <div class="bg-white rounded-2xl shadow-sm border border-blue-50 p-6">
        <h3 class="font-bold text-slate-800 text-base mb-4">Informasi Rekening</h3>
        <div class="space-y-3">
            <div class="flex justify-between items-center text-sm">
                <span class="text-slate-500">Bank:</span>
                <span class="font-semibold text-slate-800">{{ $user->bank_name ?? '—' }}</span>
            </div>
            <div class="flex justify-between items-center text-sm">
                <span class="text-slate-500">No. Rekening:</span>
                <span class="font-semibold text-slate-800">{{ $user->bank_account ?? '—' }}</span>
            </div>
            <div class="flex justify-between items-center text-sm">
                <span class="text-slate-500">Atas Nama:</span>
                <span class="font-semibold text-slate-800">{{ $user->bank_account_name ?? '—' }}</span>
            </div>
        </div>
        <div class="mt-4 pt-4 border-t border-slate-100">
            <a href="{{ route('seller.store.index') }}" class="text-sm font-semibold text-primary-600 hover:text-primary-800 flex items-center gap-1">
                Edit Rekening <ion-icon name="arrow-forward-outline" class="text-sm"></ion-icon>
            </a>
        </div>
    </div>
</div>

{{-- ── Riwayat Transaksi Dompet ────────────────────────────────── --}}
<div class="bg-white rounded-2xl shadow-sm border border-blue-50 overflow-hidden mb-6">
    <div class="table-header px-6 py-4">
        <h2 class="font-bold text-base">Riwayat Transaksi Dompet</h2>
    </div>
    <div class="divide-y divide-slate-100">
        @forelse($transactions as $tx)
        <div class="px-6 py-4 flex items-center justify-between hover:bg-blue-50/30 transition-colors">
            <div class="flex items-center gap-4">
                <div class="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0
                    {{ $tx->type === 'credit' ? 'bg-emerald-50' : 'bg-red-50' }}">
                    <ion-icon name="{{ $tx->type === 'credit' ? 'arrow-down-circle-outline' : 'arrow-up-circle-outline' }}"
                        class="text-xl {{ $tx->type === 'credit' ? 'text-emerald-600' : 'text-red-500' }}"></ion-icon>
                </div>
                <div>
                    <p class="text-sm font-semibold text-slate-800">{{ $tx->description }}</p>
                    <p class="text-xs text-slate-400 mt-0.5">{{ \Carbon\Carbon::parse($tx->created_at)->format('d M Y, H:i') }}</p>
                </div>
            </div>
            <div class="text-right">
                <p class="text-base font-extrabold {{ $tx->type === 'credit' ? 'text-emerald-600' : 'text-red-500' }}">
                    {{ $tx->type === 'credit' ? '+' : '-' }} Rp {{ number_format($tx->amount, 0, ',', '.') }}
                </p>
            </div>
        </div>
        @empty
        <div class="text-center py-14 text-slate-400">
            <ion-icon name="receipt-outline" class="text-5xl block mx-auto mb-2"></ion-icon>
            <p class="text-sm font-medium">Belum ada riwayat transaksi</p>
        </div>
        @endforelse
    </div>
</div>

{{-- ── Riwayat Penarikan Dana ──────────────────────────────────── --}}
<div class="bg-white rounded-2xl shadow-sm border border-blue-50 overflow-hidden">
    <div class="table-header px-6 py-4 flex items-center justify-between">
        <h2 class="font-bold text-base">Riwayat Penarikan Dana</h2>
        <div class="flex items-center gap-1.5 text-white text-xs font-medium">
            <span class="w-2 h-2 bg-green-400 rounded-full animate-ping"></span>
            Realtime
        </div>
    </div>
    <div id="rt-withdrawal-list" class="divide-y divide-slate-100">
        @forelse($withdrawalHistory as $wd)
        <div class="px-6 py-4 flex items-center justify-between hover:bg-blue-50/30 transition-colors">
            <div>
                <p class="text-sm font-semibold text-slate-800">{{ $wd->bank_name }} — {{ $wd->bank_account_number }}</p>
                <p class="text-xs text-slate-400 mt-0.5">{{ \Carbon\Carbon::parse($wd->created_at)->diffForHumans() }}</p>
            </div>
            <div class="flex items-center gap-3">
                <p class="text-base font-extrabold text-slate-800">- Rp {{ number_format($wd->amount, 0, ',', '.') }}</p>
                @if($wd->status === 'pending')
                    <span class="wd-pending text-[10px] font-bold px-3 py-1 rounded-full">Menunggu</span>
                @elseif($wd->status === 'approved')
                    <span class="wd-approved text-[10px] font-bold px-3 py-1 rounded-full">✓ Disetujui</span>
                @elseif($wd->status === 'completed')
                    <span class="wd-completed text-[10px] font-bold px-3 py-1 rounded-full">✓ Selesai</span>
                @else
                    <span class="wd-rejected text-[10px] font-bold px-3 py-1 rounded-full">✗ Ditolak</span>
                @endif
            </div>
        </div>
        @empty
        <div class="text-center py-12 text-slate-400">
            <ion-icon name="cash-outline" class="text-4xl block mx-auto mb-2"></ion-icon>
            <p class="text-sm font-medium">Belum ada riwayat penarikan</p>
        </div>
        @endforelse
    </div>
</div>

{{-- ── Withdrawal Modal ────────────────────────────────────────── --}}
<div id="withdraw-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/50 backdrop-blur-sm">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg mx-4">
        <div class="table-header px-6 py-5 rounded-t-2xl flex items-center justify-between">
            <div>
                <h2 class="font-bold text-base">Tarik Dana</h2>
                <p class="text-blue-100 text-xs mt-0.5">Saldo tersedia: <span id="modal-balance">Rp {{ number_format($wallet->balance ?? 0, 0, ',', '.') }}</span></p>
            </div>
            <button onclick="closeWithdrawModal()" class="w-8 h-8 bg-white/20 hover:bg-white/30 rounded-lg flex items-center justify-center text-white transition-colors">
                <ion-icon name="close" class="text-xl"></ion-icon>
            </button>
        </div>

        <form action="{{ route('seller.wallet.withdraw') }}" method="POST" class="p-6 space-y-4">
            @csrf
            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-1.5">Jumlah Penarikan <span class="text-red-500">*</span></label>
                <div class="relative">
                    <span class="absolute left-4 top-1/2 -translate-y-1/2 text-sm font-medium text-slate-500">Rp</span>
                    <input type="number" name="amount" min="50000" placeholder="Minimal Rp 50.000"
                        class="w-full border border-slate-200 rounded-xl pl-10 pr-4 py-3 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-primary-300 focus:border-primary-400 transition-all">
                </div>
            </div>
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
                        <option value="{{ $b }}" {{ $user->bank_name === $b ? 'selected' : '' }}>{{ $b }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">No. Rekening <span class="text-red-500">*</span></label>
                    <input type="text" name="bank_account_number" placeholder="1234567890"
                        value="{{ $user->bank_account ?? '' }}"
                        class="w-full border border-slate-200 rounded-xl px-4 py-3 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-primary-300 transition-all">
                </div>
            </div>
            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-1.5">Atas Nama Rekening <span class="text-red-500">*</span></label>
                <input type="text" name="bank_account_name" placeholder="Nama sesuai rekening"
                    value="{{ $user->bank_account_name ?? '' }}"
                    class="w-full border border-slate-200 rounded-xl px-4 py-3 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-primary-300 transition-all">
            </div>
            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-1.5">Catatan <span class="text-slate-400 font-normal">(opsional)</span></label>
                <input type="text" name="seller_note" placeholder="Catatan untuk admin..."
                    class="w-full border border-slate-200 rounded-xl px-4 py-3 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-primary-300 transition-all">
            </div>
            <div class="flex gap-3 pt-2">
                <button type="button" onclick="closeWithdrawModal()"
                    class="flex-1 border border-slate-200 text-slate-600 font-semibold text-sm py-3 rounded-xl hover:bg-slate-50 transition-colors">
                    Batal
                </button>
                <button type="submit"
                    class="flex-1 bg-primary-600 hover:bg-primary-700 text-white font-bold text-sm py-3 rounded-xl shadow-md shadow-blue-200 transition-all hover:-translate-y-0.5 flex items-center justify-center gap-2">
                    <ion-icon name="arrow-up-circle-outline" class="text-lg"></ion-icon>
                    Kirim Pengajuan
                </button>
            </div>
        </form>
    </div>
</div>

@endsection

@push('scripts')
<script>
    const WALLET_URL = '{{ route("seller.api.wallet-stats") }}';

    function openWithdrawModal() {
        const m = document.getElementById('withdraw-modal');
        m.classList.remove('hidden');
        m.classList.add('flex');
    }

    function closeWithdrawModal() {
        const m = document.getElementById('withdraw-modal');
        m.classList.add('hidden');
        m.classList.remove('flex');
    }

    document.getElementById('withdraw-modal')?.addEventListener('click', function(e) {
        if (e.target === this) closeWithdrawModal();
    });

    // ── Realtime Wallet Polling (setiap 5 detik) ─────────────────────────────
    function fetchWalletStats() {
        fetch(WALLET_URL, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
            .then(r => r.ok ? r.json() : Promise.reject(r))
            .then(data => {
                // Update balance
                const balEl = document.getElementById('rt-balance');
                if (balEl && balEl.textContent.trim() !== data.balance_label) {
                    balEl.classList.add('balance-updating');
                    setTimeout(() => { balEl.textContent = data.balance_label; balEl.classList.remove('balance-updating'); }, 250);
                }

                // Update modal balance text
                const modalBalEl = document.getElementById('modal-balance');
                if (modalBalEl) modalBalEl.textContent = data.balance_label;

                // Update pending label
                const pendingWrap  = document.getElementById('rt-pending-wrap');
                const pendingLabel = document.getElementById('rt-pending-label');
                if (pendingLabel && data.pending_balance > 0) {
                    pendingLabel.textContent = `Menunggu konfirmasi Admin: ${data.pending_balance_label}`;
                    if (pendingWrap) { pendingWrap.classList.remove('hidden'); pendingWrap.classList.add('flex'); }
                } else if (pendingWrap) {
                    pendingWrap.classList.add('hidden');
                    pendingWrap.classList.remove('flex');
                }

                // Update withdrawal list
                const listEl = document.getElementById('rt-withdrawal-list');
                if (listEl && data.withdrawals?.length > 0) {
                    const statusMap = {
                        'pending'  : '<span class="wd-pending text-[10px] font-bold px-3 py-1 rounded-full">Menunggu</span>',
                        'approved' : '<span class="wd-approved text-[10px] font-bold px-3 py-1 rounded-full">✓ Disetujui</span>',
                        'completed': '<span class="wd-completed text-[10px] font-bold px-3 py-1 rounded-full">✓ Selesai</span>',
                        'rejected' : '<span class="wd-rejected text-[10px] font-bold px-3 py-1 rounded-full">✗ Ditolak</span>',
                    };
                    listEl.innerHTML = data.withdrawals.map(w => `
                        <div class="px-6 py-4 flex items-center justify-between hover:bg-blue-50/30 transition-colors">
                            <div>
                                <p class="text-sm font-semibold text-slate-800">${w.bank_name} — ${w.bank_account}</p>
                                <p class="text-xs text-slate-400 mt-0.5">${w.time_ago}</p>
                            </div>
                            <div class="flex items-center gap-3">
                                <p class="text-base font-extrabold text-slate-800">- ${w.amount_label}</p>
                                ${statusMap[w.status] ?? '<span class="bg-slate-100 text-slate-600 text-[10px] font-bold px-3 py-1 rounded-full">' + w.status + '</span>'}
                            </div>
                        </div>
                    `).join('');
                }
            })
            .catch(() => {});
    }

    fetchWalletStats();
    setInterval(fetchWalletStats, 5000);
</script>
@endpush
