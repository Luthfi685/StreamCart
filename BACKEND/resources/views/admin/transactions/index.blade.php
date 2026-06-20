@extends('layouts.app')
@section('title','Transaksi & Escrow')
@section('page-title','Transaksi & Escrow')
@section('page-subtitle','Kelola konfirmasi pembayaran dan riwayat transaksi buyer')

@section('content')

@if(session('success'))
<div class="mb-4 bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 rounded-xl text-sm font-medium flex items-center gap-2">
    <ion-icon name="checkmark-circle" class="text-lg shrink-0"></ion-icon> {{ session('success') }}
</div>
@endif
@if($errors->any())
<div class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl text-sm font-medium flex items-center gap-2">
    <ion-icon name="alert-circle" class="text-lg shrink-0"></ion-icon> {{ $errors->first() }}
</div>
@endif

{{-- Summary Cards --}}
<div class="grid grid-cols-2 gap-5 mb-6">
    <div class="bg-white rounded-2xl border border-blue-50 shadow-sm p-5">
        <p class="text-xs text-slate-500 font-medium mb-1">Total Dana Menunggu Konfirmasi</p>
        <p class="text-3xl font-extrabold text-slate-800">Rp {{ number_format($totalEscrow ?? 0, 0, ',', '.') }}</p>
    </div>
    <div class="bg-white rounded-2xl border border-blue-50 shadow-sm p-5">
        <p class="text-xs text-slate-500 font-medium mb-1">Total Transaksi Selesai</p>
        <p class="text-3xl font-extrabold text-slate-800">{{ $totalCompleted ?? 0 }}</p>
    </div>
</div>

{{-- Real-time indicator --}}
<div class="flex items-center gap-2 mb-3">
    <span class="w-2 h-2 rounded-full bg-green-400 animate-ping inline-block"></span>
    <span class="text-xs text-slate-500 font-medium">Data diperbarui secara otomatis setiap 15 detik</span>
</div>

{{-- Transactions Table --}}
<div class="bg-white rounded-2xl shadow-sm border border-blue-50 overflow-hidden">
    <div class="table-header px-6 py-4">
        <h2 class="font-bold text-base">Daftar Transaksi</h2>
        <p class="text-blue-100 text-xs mt-0.5">Pembayaran masuk dari buyer yang perlu dikonfirmasi</p>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm" id="transactions-table">
            <thead class="bg-blue-50 border-b border-blue-100">
                <tr>
                    <th class="text-left px-5 py-3 text-[11px] font-bold text-primary-700 uppercase tracking-wider">ID & Pembeli</th>
                    <th class="text-left px-4 py-3 text-[11px] font-bold text-primary-700 uppercase tracking-wider">Seller</th>
                    <th class="text-right px-4 py-3 text-[11px] font-bold text-primary-700 uppercase tracking-wider">Jumlah</th>
                    <th class="text-center px-4 py-3 text-[11px] font-bold text-primary-700 uppercase tracking-wider">Status</th>
                    <th class="text-center px-4 py-3 text-[11px] font-bold text-primary-700 uppercase tracking-wider">Bukti Bayar</th>
                    <th class="text-center px-4 py-3 text-[11px] font-bold text-primary-700 uppercase tracking-wider">Aksi</th>
                </tr>
            </thead>
            <tbody id="transactions-tbody" class="divide-y divide-slate-100">
                @forelse($transactions as $trx)
                <tr class="table-row-hover transition-colors">
                    <td class="px-5 py-3.5">
                        <p class="font-bold text-slate-700 text-xs">TRX-{{ str_pad($trx->id, 5, '0', STR_PAD_LEFT) }}</p>
                        <p class="text-[11px] text-slate-400 mt-0.5">{{ $trx->buyer?->name ?? '—' }} &bull; {{ \Carbon\Carbon::parse($trx->created_at)->format('d M, H:i') }}</p>
                    </td>
                    <td class="px-4 py-3.5">
                        <p class="text-xs font-semibold text-slate-600">{{ $trx->seller?->store_name ?? $trx->seller?->name ?? '—' }}</p>
                    </td>
                    <td class="px-4 py-3.5 text-right">
                        <p class="text-sm font-bold text-slate-800">Rp {{ number_format($trx->total_price, 0, ',', '.') }}</p>
                    </td>
                    <td class="px-4 py-3.5 text-center">
                        @if($trx->status === 'checking_admin')
                            <span class="inline-flex items-center gap-1 badge-blue text-[10px] font-bold px-2.5 py-1 rounded-full">
                                <span class="w-1.5 h-1.5 bg-primary-500 rounded-full animate-pulse"></span> Menunggu
                            </span>
                        @elseif($trx->status === 'success')
                            <span class="badge-green text-[10px] font-bold px-2.5 py-1 rounded-full">✓ Dikonfirmasi</span>
                        @elseif($trx->status === 'completed')
                            <span class="badge-green text-[10px] font-bold px-2.5 py-1 rounded-full">✓ Selesai</span>
                        @elseif($trx->status === 'fail')
                            <span class="bg-red-100 text-red-700 text-[10px] font-bold px-2.5 py-1 rounded-full">✗ Gagal</span>
                        @elseif($trx->status === 'pending')
                            <span class="badge-yellow text-[10px] font-bold px-2.5 py-1 rounded-full">Pending</span>
                        @else
                            <span class="bg-slate-100 text-slate-600 text-[10px] font-bold px-2.5 py-1 rounded-full">{{ ucfirst($trx->status) }}</span>
                        @endif
                    </td>
                    <td class="px-4 py-3.5 text-center">
                        @if($trx->payment_proof_url)
                            <a href="{{ $trx->payment_proof_url }}" target="_blank"
                               class="inline-flex items-center gap-1 text-primary-600 hover:text-primary-800 text-[11px] font-semibold">
                                <ion-icon name="image-outline"></ion-icon> Lihat
                            </a>
                        @else
                            <span class="text-slate-300 text-xs">—</span>
                        @endif
                    </td>
                    <td class="px-4 py-3.5 text-center">
                        @if($trx->status === 'checking_admin')
                            <div class="flex items-center justify-center gap-2">
                                <form action="{{ route('admin.transactions.approve', $trx->id) }}" method="POST" class="inline"
                                      onsubmit="return confirm('Konfirmasi pembayaran TRX-{{ str_pad($trx->id, 5, '0', STR_PAD_LEFT) }}?')">
                                    @csrf @method('PATCH')
                                    <button type="submit"
                                        class="approve-btn bg-primary-600 hover:bg-primary-700 text-white text-[11px] font-bold px-3 py-1.5 rounded-lg flex items-center gap-1">
                                        <ion-icon name="checkmark-outline" class="text-sm"></ion-icon> Approve
                                    </button>
                                </form>
                                <form action="{{ route('admin.transactions.reject', $trx->id) }}" method="POST" class="inline"
                                      onsubmit="return confirm('Tolak pembayaran TRX-{{ str_pad($trx->id, 5, '0', STR_PAD_LEFT) }} karena palsu/tidak valid?')">
                                    @csrf @method('PATCH')
                                    <button type="submit"
                                        class="reject-btn bg-red-100 hover:bg-red-200 text-red-700 text-[11px] font-bold px-3 py-1.5 rounded-lg flex items-center gap-1">
                                        <ion-icon name="close-outline" class="text-sm"></ion-icon> Tolak
                                    </button>
                                </form>
                            </div>
                        @else
                            <span class="text-slate-300 text-xs">—</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="text-center py-12 text-slate-400">
                        <ion-icon name="receipt-outline" class="text-4xl mb-2 block mx-auto"></ion-icon>
                        <p class="text-sm font-medium">Belum ada transaksi</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="px-6 py-4 border-t border-slate-100">{{ $transactions->links() }}</div>
</div>

@endsection

@push('scripts')
<script>
    // Auto refresh table setiap 15 detik via AJAX
    const TRX_API_URL = '{{ route("admin.api.transactions") }}';

    function statusBadge(status) {
        const map = {
            'checking_admin': `<span class="inline-flex items-center gap-1 badge-blue text-[10px] font-bold px-2.5 py-1 rounded-full"><span class="w-1.5 h-1.5 bg-blue-500 rounded-full animate-pulse"></span>Menunggu</span>`,
            'success':        `<span class="badge-green text-[10px] font-bold px-2.5 py-1 rounded-full">✓ Dikonfirmasi</span>`,
            'completed':      `<span class="badge-green text-[10px] font-bold px-2.5 py-1 rounded-full">✓ Selesai</span>`,
            'fail':           `<span class="bg-red-100 text-red-700 text-[10px] font-bold px-2.5 py-1 rounded-full">✗ Gagal</span>`,
            'pending':        `<span class="badge-yellow text-[10px] font-bold px-2.5 py-1 rounded-full">Pending</span>`,
        };
        return map[status] ?? `<span class="bg-slate-100 text-slate-600 text-[10px] font-bold px-2.5 py-1 rounded-full">${status}</span>`;
    }

    function refreshTransactions() {
        fetch(TRX_API_URL, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
            .then(r => r.ok ? r.json() : Promise.reject(r))
            .then(data => {
                const tbody = document.getElementById('transactions-tbody');
                if (!tbody || data.length === 0) return;

                const rows = data.map(t => `
                    <tr class="table-row-hover transition-colors">
                        <td class="px-5 py-3.5">
                            <p class="font-bold text-slate-700 text-xs">${t.code}</p>
                            <p class="text-[11px] text-slate-400 mt-0.5">${t.buyer_name} &bull; ${t.created_at}</p>
                        </td>
                        <td class="px-4 py-3.5"><p class="text-xs font-semibold text-slate-600">${t.seller_name}</p></td>
                        <td class="px-4 py-3.5 text-right"><p class="text-sm font-bold text-slate-800">${t.amount_label}</p></td>
                        <td class="px-4 py-3.5 text-center">${statusBadge(t.status)}</td>
                        <td class="px-4 py-3.5 text-center">${t.proof_url ? `<a href="${t.proof_url}" target="_blank" class="text-primary-600 text-[11px] font-semibold">Lihat</a>` : '<span class="text-slate-300 text-xs">—</span>'}</td>
                        <td class="px-4 py-3.5 text-center">${t.status === 'checking_admin'
                            ? `<div class="flex items-center justify-center gap-2">
                                <form action="/admin/transactions/${t.id}/approve" method="POST" class="inline" onsubmit="return confirm('Konfirmasi pembayaran?')">
                                    <input type="hidden" name="_token" value="{{ csrf_token() }}">
                                    <input type="hidden" name="_method" value="PATCH">
                                    <button type="submit" class="approve-btn bg-primary-600 hover:bg-primary-700 text-white text-[11px] font-bold px-3 py-1.5 rounded-lg flex items-center gap-1">
                                        ✓ Approve
                                    </button>
                                </form>
                                <form action="/admin/transactions/${t.id}/reject" method="POST" class="inline" onsubmit="return confirm('Tolak pembayaran karena palsu/tidak valid?')">
                                    <input type="hidden" name="_token" value="{{ csrf_token() }}">
                                    <input type="hidden" name="_method" value="PATCH">
                                    <button type="submit" class="reject-btn bg-red-100 hover:bg-red-200 text-red-700 text-[11px] font-bold px-3 py-1.5 rounded-lg flex items-center gap-1">
                                        ✗ Tolak
                                    </button>
                                </form>
                               </div>`
                            : '<span class="text-slate-300 text-xs">—</span>'
                        }</td>
                    </tr>`).join('');

                tbody.innerHTML = rows;
            })
            .catch(() => {}); // silent fail
    }

    setInterval(refreshTransactions, 15000);
</script>
@endpush
