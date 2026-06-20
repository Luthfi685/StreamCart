@extends('layouts.app')
@section('title', 'Refunds')
@section('page-title', 'Pengajuan Refund')
@section('page-subtitle', 'Proses pengembalian dana untuk pesanan yang dibatalkan')

@section('content')

@if(session('success'))
<div class="mb-4 bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 rounded-xl text-sm font-medium flex items-center gap-2">
    <ion-icon name="checkmark-circle" class="text-lg shrink-0"></ion-icon> {{ session('success') }}
</div>
@endif
@if(session('error'))
<div class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl text-sm font-medium flex items-center gap-2">
    <ion-icon name="alert-circle" class="text-lg shrink-0"></ion-icon> {{ session('error') }}
</div>
@endif

<div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
    <div class="table-header px-6 py-4 border-b border-slate-100 bg-slate-50">
        <h2 class="font-bold text-slate-800 text-base">Daftar Pengajuan Refund</h2>
    </div>
    
    <div class="divide-y divide-slate-100">
        @forelse($refunds as $order)
        <div class="px-6 py-4 flex flex-col md:flex-row md:items-center justify-between hover:bg-slate-50 transition-colors gap-4">
            <div class="flex items-start gap-4">
                <div class="w-10 h-10 bg-red-50 rounded-xl flex items-center justify-center flex-shrink-0 mt-1">
                    <ion-icon name="cash-outline" class="text-xl text-red-600"></ion-icon>
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <p class="text-sm font-bold text-slate-800">TRX-{{ $order->id }}</p>
                        <span class="px-2 py-0.5 rounded-full bg-red-100 text-red-700 text-[10px] font-bold tracking-wide uppercase">Cancelled</span>
                    </div>
                    <p class="text-[12px] text-slate-500 mt-1">
                        Pembeli: <span class="font-semibold text-slate-700">{{ $order->buyer->name }}</span>
                    </p>
                    <p class="text-[12px] text-slate-500">
                        Alasan: {{ $order->cancel_reason }}
                    </p>
                    <div class="mt-3 p-3 bg-slate-50 rounded-lg border border-slate-100">
                        <p class="text-xs text-slate-500 mb-1">Tujuan Transfer:</p>
                        <p class="text-sm font-bold text-slate-800">
                            {{ $order->refund_bank_name }} - {{ $order->refund_bank_account }}
                        </p>
                        <p class="text-xs text-slate-600">a.n. {{ $order->refund_bank_account_name }}</p>
                    </div>
                </div>
            </div>
            
            <div class="flex flex-col items-end gap-2 flex-shrink-0">
                <p class="text-lg font-extrabold text-slate-800">Rp {{ number_format($order->total_price, 0, ',', '.') }}</p>
                
                @if(!$order->is_refunded)
                    <button onclick="document.getElementById('modal-{{ $order->id }}').showModal()" 
                        class="mt-2 bg-blue-600 hover:bg-blue-700 text-white text-[12px] font-bold px-4 py-2.5 rounded-xl flex items-center gap-2 transition-all shadow-sm">
                        <ion-icon name="checkmark-done-outline" class="text-base"></ion-icon> Proses Refund
                    </button>

                    <dialog id="modal-{{ $order->id }}" class="p-0 rounded-2xl shadow-2xl backdrop:bg-slate-900/50 open:animate-fade-in m-auto w-[90%] max-w-md border-0">
                        <div class="bg-white p-6">
                            <div class="flex justify-between items-center mb-4">
                                <h3 class="font-bold text-lg text-slate-800">Proses Refund TRX-{{ $order->id }}</h3>
                                <button onclick="document.getElementById('modal-{{ $order->id }}').close()" class="text-slate-400 hover:text-slate-600">
                                    <ion-icon name="close" class="text-xl"></ion-icon>
                                </button>
                            </div>
                            <form action="{{ route('admin.refunds.process', $order->id) }}" method="POST" enctype="multipart/form-data">
                                @csrf @method('PATCH')
                                <div class="mb-4 bg-blue-50 text-blue-800 p-4 rounded-xl text-sm border border-blue-100">
                                    <p class="mb-2">Transfer dana ke rekening berikut:</p>
                                    <p class="font-bold text-lg mb-1">Rp {{ number_format($order->total_price, 0, ',', '.') }}</p>
                                    <div class="text-blue-900 bg-white/60 p-2 rounded mt-2 text-xs">
                                        {{ $order->refund_bank_name }}<br>
                                        <strong>{{ $order->refund_bank_account }}</strong><br>
                                        {{ $order->refund_bank_account_name }}
                                    </div>
                                </div>
                                <div class="mb-5">
                                    <label class="block text-sm font-medium text-slate-700 mb-2">Unggah Bukti Transfer (Resi)</label>
                                    <input type="file" name="refund_proof" required accept="image/*,application/pdf"
                                        class="w-full text-sm text-slate-500 file:mr-4 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 border border-slate-200 rounded-xl p-1 cursor-pointer">
                                </div>
                                <div class="flex gap-2 justify-end">
                                    <button type="button" onclick="document.getElementById('modal-{{ $order->id }}').close()" class="px-4 py-2 text-sm font-semibold text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-xl">Batal</button>
                                    <button type="submit" class="px-4 py-2 text-sm font-semibold text-white bg-blue-600 hover:bg-blue-700 rounded-xl">Konfirmasi Selesai</button>
                                </div>
                            </form>
                        </div>
                    </dialog>
                @else
                    <div class="flex flex-col items-end gap-1 mt-2">
                        <span class="inline-flex items-center gap-1.5 text-green-700 bg-green-100 border border-green-200 text-[11px] font-bold px-3 py-1.5 rounded-full">
                            <ion-icon name="checkmark-circle" class="text-sm"></ion-icon> Selesai Diproses
                        </span>
                        <a href="{{ asset('storage/' . $order->refund_proof) }}" target="_blank" class="text-[11px] text-blue-600 hover:underline mt-1">Lihat Bukti</a>
                    </div>
                @endif
            </div>
        </div>
        @empty
        <div class="text-center py-16 text-slate-400">
            <ion-icon name="document-text-outline" class="text-5xl mb-3 block mx-auto text-slate-300"></ion-icon>
            <p class="text-sm font-medium">Belum ada antrean refund.</p>
        </div>
        @endforelse
    </div>
    
    @if($refunds->hasPages())
    <div class="px-6 py-4 border-t border-slate-100">
        {{ $refunds->links() }}
    </div>
    @endif
</div>

<style>
    dialog::backdrop {
        background: rgba(15, 23, 42, 0.6);
        backdrop-filter: blur(4px);
    }
</style>
@endsection
