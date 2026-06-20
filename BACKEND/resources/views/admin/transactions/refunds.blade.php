@extends('layouts.app')
@section('title','Pengembalian Dana (Refund)')
@section('page-title','Pengembalian Dana')
@section('page-subtitle','Kelola antrean pengembalian dana ke pembeli atas pesanan yang dibatalkan')

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

<div class="bg-white rounded-2xl shadow-sm border border-blue-50 overflow-hidden">
    <div class="table-header px-6 py-4 flex items-center justify-between">
        <div>
            <h2 class="font-bold text-base">Antrean Refund</h2>
            <p class="text-blue-100 text-xs mt-0.5">Segera transfer ke rekening pembeli, lalu tandai sebagai selesai.</p>
        </div>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-blue-50 border-b border-blue-100">
                <tr>
                    <th class="text-left px-5 py-3 text-[11px] font-bold text-primary-700 uppercase tracking-wider">ID & Pembeli</th>
                    <th class="text-left px-4 py-3 text-[11px] font-bold text-primary-700 uppercase tracking-wider">Seller</th>
                    <th class="text-left px-4 py-3 text-[11px] font-bold text-primary-700 uppercase tracking-wider">Alasan Batal</th>
                    <th class="text-right px-4 py-3 text-[11px] font-bold text-primary-700 uppercase tracking-wider">Total Refund</th>
                    <th class="text-center px-4 py-3 text-[11px] font-bold text-primary-700 uppercase tracking-wider">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($refunds as $order)
                <tr class="hover:bg-slate-50 transition-colors">
                    <td class="px-5 py-3.5">
                        <p class="font-bold text-slate-700 text-xs">TRX-{{ str_pad($order->id, 5, '0', STR_PAD_LEFT) }}</p>
                        <p class="text-[11px] text-slate-500 mt-0.5">{{ $order->buyer?->name }} ({{ $order->buyer?->username }})</p>
                        <p class="text-[10px] text-slate-400">{{ \Carbon\Carbon::parse($order->updated_at)->diffForHumans() }}</p>
                    </td>
                    <td class="px-4 py-3.5">
                        <p class="text-xs font-semibold text-slate-600">{{ $order->seller?->store_name ?? $order->seller?->name ?? '—' }}</p>
                    </td>
                    <td class="px-4 py-3.5">
                        <p class="text-xs text-slate-600 max-w-[200px] truncate" title="{{ $order->cancel_reason }}">{{ $order->cancel_reason ?? 'Tidak ada alasan' }}</p>
                    </td>
                    <td class="px-4 py-3.5 text-right">
                        <p class="text-sm font-bold text-red-600">Rp {{ number_format($order->total_price, 0, ',', '.') }}</p>
                    </td>
                    <td class="px-4 py-3.5 text-center">
                        <form action="{{ route('admin.refunds.process', $order->id) }}" method="POST" class="inline"
                              onsubmit="return confirm('Apakah Anda sudah transfer balik dana ke pembeli ini? \n\nTindakan ini tidak bisa dibatalkan.')">
                            @csrf @method('PATCH')
                            <button type="submit" class="bg-emerald-500 hover:bg-emerald-600 text-white text-[11px] font-bold px-3 py-1.5 rounded-lg flex items-center gap-1 mx-auto transition-all">
                                <ion-icon name="checkmark-done-outline" class="text-sm"></ion-icon> Tandai Selesai
                            </button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="text-center py-12 text-slate-400">
                        <ion-icon name="happy-outline" class="text-4xl mb-2 block mx-auto"></ion-icon>
                        <p class="text-sm font-medium">Hore! Antrean refund kosong.</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($refunds->hasPages())
    <div class="px-6 py-4 border-t border-slate-100">{{ $refunds->links() }}</div>
    @endif
</div>

@endsection
