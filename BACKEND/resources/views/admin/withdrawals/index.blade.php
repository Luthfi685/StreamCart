@extends('layouts.app')
@section('title','Penarikan Dana')
@section('page-title','Pengajuan Penarikan Dana')
@section('page-subtitle','Proses permintaan pencairan saldo dari seller')

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

{{-- Summary --}}
<div class="bg-gradient-to-r from-primary-600 to-blue-500 rounded-2xl p-6 mb-6 text-white relative overflow-hidden">
    <div class="absolute right-4 top-4 w-16 h-16 bg-white/10 rounded-2xl flex items-center justify-center">
        <ion-icon name="cash-outline" class="text-4xl text-white/80"></ion-icon>
    </div>
    <p class="text-sm font-medium text-blue-100 mb-1">Total Dana Pending Penarikan</p>
    <p id="pending-total" class="text-4xl font-extrabold tracking-tight">Rp {{ number_format($pendingTotal ?? 0, 0, ',', '.') }}</p>
</div>

{{-- Realtime indicator --}}
<div class="flex items-center gap-2 mb-3">
    <span class="w-2 h-2 rounded-full bg-green-400 animate-ping inline-block"></span>
    <span class="text-xs text-slate-500 font-medium">Data diperbarui otomatis setiap 15 detik</span>
</div>

{{-- Withdrawals Table --}}
<div class="bg-white rounded-2xl shadow-sm border border-blue-50 overflow-hidden">
    <div class="table-header px-6 py-4">
        <h2 class="font-bold text-base">Daftar Pengajuan</h2>
    </div>
    <div id="withdrawals-list" class="divide-y divide-slate-100">
        @forelse($withdrawals as $wd)
        <div class="px-6 py-4 flex items-center justify-between hover:bg-blue-50/50 transition-colors">
            <div class="flex items-center gap-4">
                <div class="w-10 h-10 bg-blue-50 rounded-xl flex items-center justify-center flex-shrink-0">
                    <ion-icon name="wallet-outline" class="text-xl text-primary-600"></ion-icon>
                </div>
                <div>
                    <p class="text-sm font-bold text-slate-800">{{ $wd->seller?->store_name ?? $wd->seller?->name ?? '—' }}</p>
                    <p class="text-[12px] text-slate-500 mt-0.5">
                        <span class="font-medium">{{ $wd->bank_name }}</span>
                        — {{ $wd->bank_account_number }}
                        (a/n {{ $wd->bank_account_name }})
                    </p>
                    <p class="text-base font-extrabold text-slate-800 mt-1">Rp {{ number_format($wd->amount, 0, ',', '.') }}</p>
                </div>
            </div>
            <div class="flex items-center gap-3 flex-shrink-0">
                <div class="text-right mr-1">
                    <p class="text-[11px] text-slate-400">{{ \Carbon\Carbon::parse($wd->created_at)->diffForHumans() }}</p>
                </div>
                @if($wd->status === 'pending')
                    <form action="{{ route('admin.withdrawals.process', $wd->id) }}" method="POST"
                          onsubmit="return confirm('Setujui penarikan Rp {{ number_format($wd->amount, 0) }} untuk {{ addslashes($wd->seller?->name ?? 'seller') }}?')">
                        @csrf @method('PATCH')
                        <button type="submit"
                            class="approve-btn bg-primary-600 hover:bg-primary-700 text-white text-[12px] font-bold px-4 py-2 rounded-xl flex items-center gap-2 transition-all hover:-translate-y-0.5 shadow-sm hover:shadow-primary-200">
                            <ion-icon name="checkmark-outline"></ion-icon> Proses Sekarang
                        </button>
                    </form>
                @elseif($wd->status === 'approved')
                    <span class="inline-flex items-center gap-1.5 badge-green text-[11px] font-bold px-3 py-1.5 rounded-full">
                        <ion-icon name="checkmark-circle-outline" class="text-sm"></ion-icon> Sudah Diproses
                    </span>
                @elseif($wd->status === 'rejected')
                    <span class="bg-red-100 text-red-700 text-[11px] font-bold px-3 py-1.5 rounded-full">Ditolak</span>
                @elseif($wd->status === 'completed')
                    <span class="badge-green text-[11px] font-bold px-3 py-1.5 rounded-full">✓ Selesai</span>
                @endif
            </div>
        </div>
        @empty
        <div class="text-center py-16 text-slate-400">
            <ion-icon name="wallet-outline" class="text-5xl mb-3 block mx-auto"></ion-icon>
            <p class="text-sm font-medium">Tidak ada pengajuan penarikan dana</p>
        </div>
        @endforelse
    </div>
    @if($withdrawals->hasPages())
    <div class="px-6 py-4 border-t border-slate-100">{{ $withdrawals->links() }}</div>
    @endif
</div>

@endsection

@push('scripts')
<script>
    const WD_API_URL = '{{ route("admin.api.withdrawals") }}';
    const CSRF = '{{ csrf_token() }}';

    function refreshWithdrawals() {
        fetch(WD_API_URL, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
            .then(r => r.ok ? r.json() : Promise.reject(r))
            .then(data => {
                if (!data.length) return;

                let pendingTotal = 0;
                const items = data.map(w => {
                    if (w.status === 'pending') pendingTotal += w.amount;

                    let actionHtml = '';
                    if (w.status === 'pending') {
                        actionHtml = `
                            <form action="/admin/withdrawals/${w.id}/process" method="POST"
                                  onsubmit="return confirm('Setujui penarikan ${w.amount_label}?')">
                                <input type="hidden" name="_token" value="${CSRF}">
                                <input type="hidden" name="_method" value="PATCH">
                                <button type="submit"
                                    class="approve-btn bg-primary-600 hover:bg-primary-700 text-white text-[12px] font-bold px-4 py-2 rounded-xl flex items-center gap-2">
                                    ✓ Proses Sekarang
                                </button>
                            </form>`;
                    } else if (w.status === 'approved' || w.status === 'completed') {
                        actionHtml = `<span class="badge-green text-[11px] font-bold px-3 py-1.5 rounded-full">✓ Sudah Diproses</span>`;
                    } else if (w.status === 'rejected') {
                        actionHtml = `<span class="bg-red-100 text-red-700 text-[11px] font-bold px-3 py-1.5 rounded-full">Ditolak</span>`;
                    }

                    return `
                        <div class="px-6 py-4 flex items-center justify-between hover:bg-blue-50/50 transition-colors">
                            <div class="flex items-center gap-4">
                                <div class="w-10 h-10 bg-blue-50 rounded-xl flex items-center justify-center flex-shrink-0">
                                    <ion-icon name="wallet-outline" class="text-xl text-primary-600"></ion-icon>
                                </div>
                                <div>
                                    <p class="text-sm font-bold text-slate-800">${w.seller_name}</p>
                                    <p class="text-[12px] text-slate-500 mt-0.5">
                                        <span class="font-medium">${w.bank_name}</span> — ${w.bank_account} (a/n ${w.bank_account_name})
                                    </p>
                                    <p class="text-base font-extrabold text-slate-800 mt-1">${w.amount_label}</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-3 flex-shrink-0">
                                <p class="text-[11px] text-slate-400 mr-1">${w.created_at}</p>
                                ${actionHtml}
                            </div>
                        </div>`;
                }).join('<hr class="border-slate-100">');

                document.getElementById('withdrawals-list').innerHTML = items;

                const totalEl = document.getElementById('pending-total');
                if (totalEl) totalEl.textContent = 'Rp ' + new Intl.NumberFormat('id-ID').format(pendingTotal);
            })
            .catch(() => {});
    }

    setInterval(refreshWithdrawals, 15000);
</script>
@endpush
