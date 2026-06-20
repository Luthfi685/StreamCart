@extends('layouts.app')
@section('title','Kelola Pengguna')
@section('page-title','Kelola Pengguna')
@section('page-subtitle','Daftar semua pengguna terdaftar di platform StreamCart')

@section('content')

{{-- Flash Messages --}}
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

{{-- Stats --}}
<div class="grid grid-cols-3 gap-5 mb-6">
    <div class="bg-white rounded-2xl border border-blue-50 shadow-sm p-5 card-hover">
        <div class="flex items-center gap-3 mb-2"><div class="w-9 h-9 bg-primary-50 rounded-xl flex items-center justify-center"><ion-icon name="people-outline" class="text-primary-600 text-xl"></ion-icon></div></div>
        <p class="text-3xl font-extrabold text-slate-800">{{ $totalUsers }}</p>
        <p class="text-xs text-slate-500 mt-1">Total Pengguna</p>
    </div>
    <div class="bg-white rounded-2xl border border-blue-50 shadow-sm p-5 card-hover">
        <div class="flex items-center gap-3 mb-2"><div class="w-9 h-9 bg-blue-50 rounded-xl flex items-center justify-center"><ion-icon name="storefront-outline" class="text-primary-600 text-xl"></ion-icon></div></div>
        <p class="text-3xl font-extrabold text-slate-800">{{ $totalSellers }}</p>
        <p class="text-xs text-slate-500 mt-1">Total Seller</p>
    </div>
    <div class="bg-white rounded-2xl border border-blue-50 shadow-sm p-5 card-hover">
        <div class="flex items-center gap-3 mb-2"><div class="w-9 h-9 bg-emerald-50 rounded-xl flex items-center justify-center"><ion-icon name="person-outline" class="text-emerald-600 text-xl"></ion-icon></div></div>
        <p class="text-3xl font-extrabold text-slate-800">{{ $totalBuyers }}</p>
        <p class="text-xs text-slate-500 mt-1">Total Buyer</p>
    </div>
</div>

{{-- Table --}}
<div class="bg-white rounded-2xl shadow-sm border border-blue-50 overflow-hidden">
    <div class="table-header px-6 py-4"><h2 class="font-bold text-base">Daftar Pengguna</h2></div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-blue-50 border-b border-blue-100">
                <tr>
                    <th class="text-left px-6 py-3 text-xs font-bold text-primary-700 uppercase tracking-wider">Pengguna</th>
                    <th class="text-left px-4 py-3 text-xs font-bold text-primary-700 uppercase tracking-wider">Email</th>
                    <th class="text-center px-4 py-3 text-xs font-bold text-primary-700 uppercase tracking-wider">Role</th>
                    <th class="text-center px-4 py-3 text-xs font-bold text-primary-700 uppercase tracking-wider">Status</th>
                    <th class="text-right px-4 py-3 text-xs font-bold text-primary-700 uppercase tracking-wider">Bergabung</th>
                    <th class="text-center px-4 py-3 text-xs font-bold text-primary-700 uppercase tracking-wider">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach($users as $u)
                <tr class="table-row-hover transition-colors {{ $u->is_banned ? 'bg-red-50/40' : '' }}">
                    <td class="px-6 py-3">
                        <div class="flex items-center gap-3">
                            <div class="relative">
                                <img src="{{ $u->avatar ?? 'https://ui-avatars.com/api/?name='.urlencode($u->name).'&background=dbeafe&color=1d4ed8&size=32' }}"
                                    class="w-8 h-8 shrink-0 rounded-full ring-1 ring-blue-100 {{ $u->is_banned ? 'grayscale opacity-60' : '' }}">
                                @if($u->is_banned)
                                <span class="absolute -top-0.5 -right-0.5 w-3 h-3 bg-red-500 rounded-full border border-white"></span>
                                @endif
                            </div>
                            <div>
                                <p class="font-semibold text-slate-700 {{ $u->is_banned ? 'line-through text-slate-400' : '' }}">{{ $u->name }}</p>
                                <p class="text-[11px] text-slate-400">{{ $u->store_name ?? '—' }}</p>
                            </div>
                        </div>
                    </td>
                    <td class="px-4 py-3 text-xs text-slate-500">{{ $u->email }}</td>
                    <td class="px-4 py-3 text-center">
                        @if($u->role === 'admin')<span class="bg-violet-100 text-violet-700 text-[10px] font-bold px-2.5 py-1 rounded-full">Admin</span>
                        @elseif($u->role === 'seller')<span class="badge-blue text-[10px] font-bold px-2.5 py-1 rounded-full">Seller</span>
                        @else<span class="bg-slate-100 text-slate-600 text-[10px] font-bold px-2.5 py-1 rounded-full">Buyer</span>@endif
                    </td>
                    <td class="px-4 py-3 text-center">
                        @if($u->is_banned)
                            <span class="inline-flex items-center gap-1 bg-red-100 text-red-700 text-[10px] font-bold px-2.5 py-1 rounded-full">
                                <ion-icon name="ban-outline" class="text-xs"></ion-icon> Ditangguhkan
                            </span>
                        @else
                            <span class="badge-green text-[10px] font-bold px-2.5 py-1 rounded-full">Aktif</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-right text-xs text-slate-400">{{ \Carbon\Carbon::parse($u->created_at)->format('d M Y') }}</td>
                    <td class="px-4 py-3 text-center">
                        @if($u->role !== 'admin')
                            @if($u->is_banned)
                                {{-- UNBAN button --}}
                                <form action="{{ route('admin.users.unban', $u->id) }}" method="POST" class="inline"
                                      onsubmit="return confirm('Pulihkan akun {{ addslashes($u->name) }}?')">
                                    @csrf
                                    <button type="submit"
                                        class="inline-flex items-center gap-1 bg-emerald-600 hover:bg-emerald-700 text-white text-[11px] font-bold px-3 py-1.5 rounded-lg transition-all hover:-translate-y-0.5 shadow-sm hover:shadow-emerald-200">
                                        <ion-icon name="checkmark-circle-outline" class="text-sm"></ion-icon>
                                        Unban
                                    </button>
                                </form>
                            @else
                                {{-- BAN button --}}
                                <button type="button"
                                    onclick="openBanModal({{ $u->id }}, '{{ addslashes($u->name) }}')"
                                    class="inline-flex items-center gap-1 bg-red-500 hover:bg-red-600 text-white text-[11px] font-bold px-3 py-1.5 rounded-lg transition-all hover:-translate-y-0.5 shadow-sm hover:shadow-red-200">
                                    <ion-icon name="ban-outline" class="text-sm"></ion-icon>
                                    Ban
                                </button>
                            @endif
                        @else
                            <span class="text-slate-300 text-xs">—</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="px-6 py-4 border-t border-slate-100">{{ $users->links() }}</div>
</div>

{{-- ── BAN MODAL ────────────────────────────────────────────────────────────── --}}
<div id="banModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/50 backdrop-blur-sm">
    <div class="bg-white rounded-2xl shadow-2xl p-6 w-full max-w-md mx-4 animate-fade-in">
        <div class="flex items-center gap-3 mb-4">
            <div class="w-11 h-11 bg-red-100 rounded-xl flex items-center justify-center">
                <ion-icon name="ban-outline" class="text-2xl text-red-600"></ion-icon>
            </div>
            <div>
                <h3 class="font-bold text-slate-800 text-base">Tangguhkan Akun</h3>
                <p id="banModalUserName" class="text-sm text-slate-500"></p>
            </div>
        </div>

        <form id="banForm" method="POST">
            @csrf
            <div class="mb-4">
                <label class="block text-sm font-semibold text-slate-700 mb-2">Alasan Penangguhan <span class="text-slate-400 font-normal">(opsional)</span></label>
                <textarea name="ban_reason" rows="3" placeholder="Contoh: Melanggar ketentuan layanan, spam, dll."
                    class="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-red-300 focus:border-red-400 resize-none"></textarea>
            </div>
            <div class="flex gap-3">
                <button type="button" onclick="closeBanModal()"
                    class="flex-1 border border-slate-200 text-slate-600 font-semibold py-2.5 rounded-xl hover:bg-slate-50 transition-colors text-sm">
                    Batal
                </button>
                <button type="submit"
                    class="flex-1 bg-red-600 hover:bg-red-700 text-white font-bold py-2.5 rounded-xl transition-colors text-sm flex items-center justify-center gap-2">
                    <ion-icon name="ban-outline"></ion-icon> Tangguhkan Akun
                </button>
            </div>
        </form>
    </div>
</div>

@endsection

@push('scripts')
<script>
    const banModal = document.getElementById('banModal');

    function openBanModal(userId, userName) {
        document.getElementById('banModalUserName').textContent = userName;
        document.getElementById('banForm').action = `/admin/users/${userId}/ban`;
        banModal.classList.remove('hidden');
        banModal.classList.add('flex');
    }

    function closeBanModal() {
        banModal.classList.add('hidden');
        banModal.classList.remove('flex');
    }

    // Close on backdrop click
    banModal.addEventListener('click', function(e) {
        if (e.target === banModal) closeBanModal();
    });
</script>
@endpush
