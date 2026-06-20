@extends('layouts.app')
@section('title', 'Profil Toko')
@section('page-title', 'Profil & Pengaturan Toko')
@section('page-subtitle', 'Kelola informasi toko, rekening, dan pengaturan keamanan Anda')

@push('styles')
<style>
    .card-header-gradient { background: linear-gradient(135deg, #1e3a8a, #3b82f6); }
</style>
@endpush

@section('content')

@if(session('success'))
<div class="mb-5 bg-emerald-50 border border-emerald-200 text-emerald-700 px-5 py-4 rounded-2xl text-sm font-medium flex items-center gap-3 shadow-sm">
    <ion-icon name="checkmark-circle" class="text-xl shrink-0"></ion-icon> {{ session('success') }}
</div>
@endif

@if($errors->any())
<div class="mb-5 bg-red-50 border border-red-200 text-red-700 px-5 py-4 rounded-2xl text-sm flex gap-3 shadow-sm">
    <ion-icon name="alert-circle" class="text-xl shrink-0 mt-0.5"></ion-icon>
    <ul class="list-disc pl-4 space-y-1 font-medium">
        @foreach($errors->all() as $e)
            <li>{{ $e }}</li>
        @endforeach
    </ul>
</div>
@endif

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    {{-- ── LEFT COLUMN: Store Profile & Bank Info ────────────────────────────── --}}
    <div class="lg:col-span-2 space-y-6">

        {{-- Store Profile Card --}}
        <div class="bg-white rounded-2xl shadow-sm border border-blue-50 overflow-hidden">
            <div class="card-header-gradient px-6 py-5 flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-white/20 flex items-center justify-center backdrop-blur-sm">
                        <ion-icon name="storefront" class="text-2xl text-white"></ion-icon>
                    </div>
                    <div>
                        <h2 class="font-bold text-white text-base">Informasi Toko</h2>
                        <p class="text-blue-100 text-xs mt-0.5">Identitas utama toko Anda</p>
                    </div>
                </div>
                <span class="bg-emerald-500 text-white text-[10px] font-bold px-3 py-1.5 rounded-full flex items-center gap-1.5">
                    <span class="w-1.5 h-1.5 bg-white rounded-full animate-pulse"></span> Seller Aktif
                </span>
            </div>

            <form action="{{ route('seller.store.update') }}" method="POST" enctype="multipart/form-data" class="p-6">
                @csrf @method('PUT')

                <!-- Avatar Section -->
                <div class="flex items-center gap-6 pb-6 mb-6 border-b border-slate-100">
                    <div class="relative">
                        <img id="avatarPreview" src="{{ $user->avatar ?? 'https://ui-avatars.com/api/?name='.urlencode($user->store_name ?? $user->name).'&background=eff6ff&color=1d4ed8&size=100' }}"
                            class="w-24 h-24 rounded-2xl ring-4 ring-blue-50 object-cover shadow-sm">
                        <button type="button" onclick="document.getElementById('avatarInput').click()" class="absolute -bottom-2 -right-2 w-8 h-8 bg-primary-600 hover:bg-primary-700 text-white rounded-lg flex items-center justify-center shadow-md transition-colors">
                            <ion-icon name="camera" class="text-sm"></ion-icon>
                        </button>
                        <input type="file" name="avatar" id="avatarInput" accept="image/*" class="hidden" onchange="document.getElementById('avatarPreview').src = window.URL.createObjectURL(this.files[0])">
                    </div>
                    <div>
                        <p class="font-extrabold text-slate-800 text-lg">{{ $user->store_name ?? $user->name }}</p>
                        <p class="text-sm font-medium text-slate-500">{{ $user->email }}</p>
                        <p class="text-xs text-slate-400 mt-2">Bergabung sejak {{ \Carbon\Carbon::parse($user->created_at)->translatedFormat('F Y') }}</p>
                    </div>
                </div>

                <div class="space-y-5">
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-1.5">Nama Toko <span class="text-red-500">*</span></label>
                        <div class="relative">
                            <span class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"><ion-icon name="pricetag-outline"></ion-icon></span>
                            <input type="text" name="store_name" value="{{ old('store_name', $user->store_name ?? $user->name) }}"
                                class="w-full border border-slate-200 rounded-xl pl-10 pr-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-primary-300 focus:border-primary-400 transition-all text-slate-700 font-medium">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-1.5">Deskripsi Toko</label>
                        <textarea name="store_description" rows="4" placeholder="Ceritakan sedikit tentang toko Anda..."
                            class="w-full border border-slate-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-primary-300 focus:border-primary-400 transition-all resize-none text-slate-700 leading-relaxed">{{ old('store_description', $user->store_description ?? '') }}</textarea>
                    </div>
                </div>

                <div class="mt-8 pt-6 border-t border-slate-100">
                    <h3 class="text-sm font-extrabold text-slate-800 mb-4 flex items-center gap-2">
                        <ion-icon name="card-outline" class="text-primary-500 text-lg"></ion-icon> Rekening Bank Utama
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Nama Bank</label>
                            <select name="bank_name" class="w-full border border-slate-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-primary-300 transition-all bg-white font-medium text-slate-700">
                                <option value="">Pilih Bank / E-Wallet</option>
                                @foreach([
                                    'BCA', 'Mandiri', 'BNI', 'BRI', 'BTN', 'BSI', 'CIMB Niaga', 'Permata', 'Danamon', 'Mega', 'Bukopin', 'Panin', 'OCBC NISP', 'Maybank', 'UOB', 'Muamalat',
                                    'Blu by BCA Digital', 'Bank Jago', 'SeaBank', 'Jenius', 'NeoBank', 'Allo Bank', 'Superbank',
                                    'Bank BJB', 'Bank DKI', 'Bank Jatim', 'Bank Jateng',
                                    'DANA', 'GoPay', 'OVO', 'ShopeePay', 'LinkAja', 'AstraPay', 'i.Saku'
                                ] as $b)
                                <option value="{{ $b }}" {{ old('bank_name', $user->bank_name ?? '') === $b ? 'selected' : '' }}>{{ $b }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Nomor Rekening</label>
                            <input type="text" name="bank_account" value="{{ old('bank_account', $user->bank_account ?? '') }}" placeholder="Contoh: 1234567890"
                                class="w-full border border-slate-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-primary-300 transition-all font-medium text-slate-700">
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Atas Nama Rekening</label>
                            <input type="text" name="bank_account_name" value="{{ old('bank_account_name', $user->bank_account_name ?? '') }}" placeholder="Sesuai buku tabungan"
                                class="w-full border border-slate-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-primary-300 transition-all font-medium text-slate-700">
                        </div>
                    </div>
                </div>

                <div class="mt-8">
                    <button type="submit" class="bg-primary-600 hover:bg-primary-700 text-white font-bold text-sm px-6 py-3.5 rounded-xl shadow-md shadow-blue-200 transition-all hover:-translate-y-0.5 flex items-center justify-center gap-2">
                        <ion-icon name="save-outline" class="text-lg"></ion-icon> Simpan Perubahan Profil
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- ── RIGHT COLUMN: Security & Password ─────────────────────────────── --}}
    <div class="space-y-6">

        <div class="bg-white rounded-2xl shadow-sm border border-blue-50 overflow-hidden">
            <div class="px-6 py-5 border-b border-slate-100 flex items-center gap-3 bg-slate-50/50">
                <div class="w-10 h-10 rounded-xl bg-orange-100 flex items-center justify-center text-orange-500">
                    <ion-icon name="lock-closed" class="text-xl"></ion-icon>
                </div>
                <div>
                    <h2 class="font-bold text-slate-800 text-sm">Keamanan Akun</h2>
                    <p class="text-slate-400 text-[11px] mt-0.5">Perbarui password Anda secara berkala</p>
                </div>
            </div>

            <form action="{{ route('seller.store.password') }}" method="POST" class="p-6 space-y-5">
                @csrf @method('PUT')

                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Password Saat Ini</label>
                    <div class="relative">
                        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"><ion-icon name="key-outline"></ion-icon></span>
                        <input type="password" name="current_password" required placeholder="••••••••"
                            class="w-full border border-slate-200 rounded-xl pl-10 pr-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-orange-300 focus:border-orange-400 transition-all text-slate-700">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Password Baru</label>
                    <div class="relative">
                        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"><ion-icon name="lock-closed-outline"></ion-icon></span>
                        <input type="password" name="password" required placeholder="Minimal 8 karakter"
                            class="w-full border border-slate-200 rounded-xl pl-10 pr-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-orange-300 focus:border-orange-400 transition-all text-slate-700">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Ulangi Password Baru</label>
                    <div class="relative">
                        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"><ion-icon name="shield-checkmark-outline"></ion-icon></span>
                        <input type="password" name="password_confirmation" required placeholder="••••••••"
                            class="w-full border border-slate-200 rounded-xl pl-10 pr-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-orange-300 focus:border-orange-400 transition-all text-slate-700">
                    </div>
                </div>

                <div class="pt-2">
                    <button type="submit" class="w-full bg-slate-800 hover:bg-slate-900 text-white font-bold text-sm py-3.5 rounded-xl shadow-md transition-all hover:-translate-y-0.5 flex items-center justify-center gap-2">
                        <ion-icon name="refresh-outline" class="text-lg"></ion-icon> Update Password
                    </button>
                </div>
            </form>
        </div>

        {{-- Help Card --}}
        <div class="bg-gradient-to-br from-blue-50 to-primary-50 rounded-2xl border border-blue-100 p-6 relative overflow-hidden">
            <ion-icon name="help-buoy" class="absolute -right-4 -bottom-4 text-8xl text-primary-600/5"></ion-icon>
            <div class="relative z-10">
                <h3 class="font-bold text-slate-800 text-sm mb-2">Butuh Bantuan?</h3>
                <p class="text-xs text-slate-600 leading-relaxed mb-4">Tim support StreamCart siap membantu menyelesaikan kendala seputar akun, toko, atau pencairan dana Anda.</p>
                <a href="{{ route('seller.support') }}" class="text-primary-600 text-xs font-bold bg-white px-4 py-2 rounded-lg shadow-sm border border-primary-100 inline-block hover:bg-primary-50 transition-colors">
                    Hubungi Support
                </a>
            </div>
        </div>

    </div>

</div>

@endsection
