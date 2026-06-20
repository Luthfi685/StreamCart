@extends('layouts.app')
@section('title','Produk Saya')
@section('page-title','Produk Saya')
@section('page-subtitle','Kelola semua produk toko Anda')

@section('content')
@if(session('success'))
<div class="mb-4 bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 rounded-xl text-sm font-medium flex items-center gap-2">
    <ion-icon name="checkmark-circle" class="text-lg shrink-0"></ion-icon> {{ session('success') }}
</div>
@endif

<div class="flex items-center justify-between mb-6">
    <p class="text-slate-500 text-sm">Total <strong class="text-slate-700">{{ $products->total() }}</strong> produk</p>
    <a href="{{ route('seller.products.create') }}" class="flex items-center gap-2 bg-primary-600 hover:bg-primary-700 text-white font-semibold text-sm px-5 py-2.5 rounded-xl shadow-md shadow-blue-200 transition-all hover:-translate-y-0.5">
        <ion-icon name="add-outline" class="text-lg"></ion-icon> Tambah Produk
    </a>
</div>

<div class="bg-white rounded-2xl shadow-sm border border-blue-50 overflow-hidden">
    <div class="table-header px-6 py-4">
        <h2 class="font-bold text-base">Daftar Produk</h2>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-blue-50 border-b border-blue-100">
                <tr>
                    <th class="text-left px-6 py-3 text-xs font-bold text-primary-700 uppercase tracking-wider">Produk</th>
                    <th class="text-center px-4 py-3 text-xs font-bold text-primary-700 uppercase tracking-wider">Stok</th>
                    <th class="text-center px-4 py-3 text-xs font-bold text-primary-700 uppercase tracking-wider">Rating</th>
                    <th class="text-right px-4 py-3 text-xs font-bold text-primary-700 uppercase tracking-wider">Harga</th>
                    <th class="text-center px-4 py-3 text-xs font-bold text-primary-700 uppercase tracking-wider">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($products as $product)
                <tr class="table-row-hover transition-colors">
                    <td class="px-6 py-3">
                        <div class="flex items-center gap-3">
                            <div class="w-11 h-11 rounded-xl bg-blue-50 border border-blue-100 overflow-hidden shrink-0 flex items-center justify-center">
                                @if($product->image_url)
                                    <img src="{{ $product->image_url }}" class="w-full h-full object-cover">
                                @else
                                    <ion-icon name="image-outline" class="text-primary-300 text-xl"></ion-icon>
                                @endif
                            </div>
                            <div>
                                <p class="font-semibold text-slate-800">{{ $product->name }}</p>
                                <p class="text-xs text-slate-400 mt-0.5 truncate max-w-[200px]">{{ Str::limit($product->description ?? '—', 40) }}</p>
                            </div>
                        </div>
                    </td>
                    <td class="px-4 py-3 text-center font-bold {{ ($product->stock ?? 0) < 5 ? 'text-red-500' : 'text-slate-700' }}">{{ $product->stock ?? 0 }}</td>
                    <td class="px-4 py-3 text-center">
                        <div class="flex items-center justify-center gap-1 text-amber-500 font-bold text-xs">
                            <ion-icon name="star"></ion-icon> {{ $product->average_rating > 0 ? $product->average_rating : '-' }}
                        </div>
                    </td>
                    <td class="px-4 py-3 text-right font-bold text-slate-700">Rp {{ number_format($product->price ?? 0, 0, ',', '.') }}</td>
                    <td class="px-4 py-3">
                        <div class="flex items-center justify-center gap-2">
                            <a href="{{ route('seller.products.edit', $product->id) }}" class="w-8 h-8 rounded-lg bg-primary-50 hover:bg-primary-100 flex items-center justify-center text-primary-600 transition-colors">
                                <ion-icon name="pencil-outline" class="text-sm"></ion-icon>
                            </a>
                            <form action="{{ route('seller.products.destroy', $product->id) }}" method="POST" onsubmit="return confirm('Hapus produk ini?')">
                                @csrf @method('DELETE')
                                <button class="w-8 h-8 rounded-lg bg-red-50 hover:bg-red-100 flex items-center justify-center text-red-500 transition-colors">
                                    <ion-icon name="trash-outline" class="text-sm"></ion-icon>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="5" class="py-20 text-center">
                    <div class="flex flex-col items-center gap-3 text-slate-400">
                        <div class="w-16 h-16 bg-blue-50 rounded-2xl flex items-center justify-center"><ion-icon name="cube-outline" class="text-4xl text-primary-300"></ion-icon></div>
                        <p class="font-medium">Belum ada produk</p>
                        <a href="{{ route('seller.products.create') }}" class="text-sm font-semibold text-primary-600">+ Tambah Produk Pertama</a>
                    </div>
                </td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="px-6 py-4 border-t border-slate-100">{{ $products->links() }}</div>
</div>
@endsection
