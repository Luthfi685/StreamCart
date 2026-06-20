@extends('layouts.app')
@section('title','Tambah Produk')
@section('page-title','Tambah Produk Baru')
@section('page-subtitle','Isi detail produk yang ingin Anda jual')

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="bg-white rounded-2xl shadow-sm border border-blue-50 overflow-hidden">
        <div class="table-header px-6 py-4">
            <h2 class="font-bold text-base">Form Produk Baru</h2>
        </div>
        <form action="{{ route('seller.products.store') }}" method="POST" enctype="multipart/form-data" class="p-6 space-y-5">
            @csrf
            @if($errors->any())
            <div class="bg-red-50 border border-red-200 rounded-xl p-4 text-sm text-red-600">
                <ul class="list-disc pl-4 space-y-1">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
            </div>
            @endif
            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-1.5">Nama Produk <span class="text-red-500">*</span></label>
                <input type="text" name="name" value="{{ old('name') }}" placeholder="Contoh: Kemeja Batik Pria Lengan Panjang"
                    class="w-full border border-slate-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-primary-300 focus:border-primary-400 transition-all">
            </div>
            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-1.5">Deskripsi Produk</label>
                <textarea name="description" rows="4" placeholder="Jelaskan detail produk, bahan, ukuran tersedia, dll..."
                    class="w-full border border-slate-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-primary-300 focus:border-primary-400 transition-all resize-none">{{ old('description') }}</textarea>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">Harga (Rp) <span class="text-red-500">*</span></label>
                    <input type="number" name="price" value="{{ old('price') }}" placeholder="0" min="0"
                        class="w-full border border-slate-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-primary-300 transition-all">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">Stok <span class="text-red-500">*</span></label>
                    <input type="number" name="stock" value="{{ old('stock') }}" placeholder="0" min="0"
                        class="w-full border border-slate-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-primary-300 transition-all">
                </div>
            </div>

            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-1.5">Foto Produk <span class="text-xs text-slate-400 font-normal ml-1">(Bisa pilih lebih dari 1 foto)</span></label>
                <input type="file" name="images[]" multiple accept="image/*"
                    class="w-full border border-slate-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-primary-300 transition-all bg-white file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-xs file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
            </div>
            <div class="flex gap-3 pt-2">
                <a href="{{ route('seller.products.index') }}" class="flex-1 border border-slate-200 text-slate-600 font-semibold text-sm py-3 rounded-xl hover:bg-slate-50 transition-colors text-center">Batal</a>
                <button type="submit" class="flex-1 bg-primary-600 hover:bg-primary-700 text-white font-bold text-sm py-3 rounded-xl shadow-md shadow-blue-200 transition-all hover:-translate-y-0.5 flex items-center justify-center gap-2">
                    <ion-icon name="save-outline"></ion-icon> Simpan Produk
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
