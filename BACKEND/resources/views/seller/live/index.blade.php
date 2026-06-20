@extends('layouts.app')
@section('title','Sesi Live')
@section('page-title','Sesi Live Streaming')
@section('page-subtitle','Buat dan kelola sesi live streaming Anda')

@section('content')
@if(session('success'))
<div class="mb-4 bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 rounded-xl text-sm font-medium flex items-center gap-2">
    <ion-icon name="checkmark-circle" class="text-lg shrink-0"></ion-icon> {{ session('success') }}
</div>
@endif

<div class="flex items-center justify-between mb-6">
    <div class="flex items-center gap-3">
        @if($activeLives > 0)
        <div class="flex items-center gap-2 bg-red-100 text-red-600 text-sm font-bold px-3 py-1.5 rounded-full">
            <span class="w-2 h-2 bg-red-500 rounded-full animate-pulse"></span>
            {{ $activeLives }} Live Aktif
        </div>
        @endif
    </div>
    <button onclick="document.getElementById('live-modal').classList.remove('hidden')"
        class="flex items-center gap-2 bg-primary-600 hover:bg-primary-700 text-white font-semibold text-sm px-5 py-2.5 rounded-xl shadow-md shadow-blue-200 transition-all hover:-translate-y-0.5">
        <ion-icon name="radio" class="text-red-200"></ion-icon> Buat Sesi Live
    </button>
</div>

<div class="bg-white rounded-2xl shadow-sm border border-blue-50 overflow-hidden">
    <div class="table-header px-6 py-4"><h2 class="font-bold text-base">Riwayat Sesi Live</h2></div>
    <div class="divide-y divide-slate-100">
        @forelse($sessions as $s)
        <div class="px-6 py-4 flex items-center justify-between hover:bg-blue-50/40 transition-colors">
            <div class="flex items-center gap-4">
                <div class="w-10 h-10 rounded-xl {{ $s->status === 'live' ? 'bg-red-100' : ($s->status === 'scheduled' ? 'bg-yellow-100' : 'bg-slate-100') }} flex items-center justify-center shrink-0">
                    <ion-icon name="radio-outline" class="text-xl {{ $s->status === 'live' ? 'text-red-500' : ($s->status === 'scheduled' ? 'text-yellow-600' : 'text-slate-400') }}"></ion-icon>
                </div>
                <div>
                    <p class="font-semibold text-slate-800">{{ $s->title }}</p>
                    @if($s->status === 'scheduled')
                        <p class="text-xs text-slate-400 mt-0.5">Jadwal: {{ \Carbon\Carbon::parse($s->scheduled_at)->translatedFormat('d M Y, H:i') }}</p>
                    @else
                        <p class="text-xs text-slate-400 mt-0.5">{{ \Carbon\Carbon::parse($s->created_at)->translatedFormat('d M Y, H:i') }}</p>
                    @endif
                    @if($s->bank_name ?? null)
                    <p class="text-xs text-slate-400">{{ $s->bank_name }} - {{ $s->bank_account }}</p>
                    @endif
                </div>
            </div>
            <div class="flex items-center gap-3">
                @if($s->status === 'live')
                    <span class="flex items-center gap-1.5 bg-red-100 text-red-600 text-xs font-bold px-3 py-1 rounded-full">
                        <span class="w-1.5 h-1.5 bg-red-500 rounded-full animate-pulse"></span> LIVE
                    </span>
                    <a href="{{ route('seller.live.studio', $s->id) }}" class="bg-primary-600 hover:bg-primary-700 text-white font-bold px-4 py-1.5 rounded-lg transition-colors shadow-sm flex items-center gap-1.5 text-xs">
                        <ion-icon name="videocam"></ion-icon> Studio
                    </a>
                    <form action="{{ route('seller.live.end', $s->id) }}" method="POST" onsubmit="return confirm('Akhiri siaran live ini?')">
                        @csrf @method('PATCH')
                        <button class="bg-red-50 hover:bg-red-100 text-red-600 text-xs font-bold px-3 py-1.5 rounded-lg transition-colors">Akhiri Live</button>
                    </form>
                @elseif($s->status === 'scheduled')
                    @php
                        $isExpired = \Carbon\Carbon::parse($s->scheduled_at)->addHours(2)->isPast();
                    @endphp
                    @if($isExpired)
                        <span class="bg-gray-100 text-gray-500 text-xs font-bold px-3 py-1 rounded-full">Kadaluarsa</span>
                    @else
                        <span class="bg-yellow-100 text-yellow-700 text-xs font-bold px-3 py-1 rounded-full">Dijadwalkan</span>
                        <form action="{{ route('seller.live.start_scheduled', $s->id) }}" method="POST">
                            @csrf
                            <button class="bg-primary-600 hover:bg-primary-700 text-white font-bold px-4 py-1.5 rounded-lg transition-colors shadow-sm flex items-center gap-1.5 text-xs">
                                Mulai Live
                            </button>
                        </form>
                    @endif
                @else
                    <span class="bg-slate-100 text-slate-500 text-xs font-bold px-3 py-1 rounded-full">Selesai</span>
                @endif
            </div>
        </div>
        @empty
        <div class="py-20 text-center text-slate-400">
            <ion-icon name="radio-outline" class="text-5xl text-slate-300 block mx-auto mb-3"></ion-icon>
            <p class="font-medium">Belum ada sesi live</p>
            <p class="text-sm mt-1">Mulai sesi live pertama Anda sekarang!</p>
        </div>
        @endforelse
    </div>
    <div class="px-6 py-4 border-t border-slate-100">{{ $sessions->links() }}</div>
</div>

{{-- Live Modal --}}
<div id="live-modal" class="fixed inset-0 z-50 hidden flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-sm">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg fade-in flex flex-col max-h-[90vh]">
        <div class="table-header px-6 py-5 rounded-t-2xl flex items-center justify-between shrink-0">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 bg-white/20 rounded-xl flex items-center justify-center"><ion-icon name="radio" class="text-xl text-white"></ion-icon></div>
                <div><h2 class="font-bold text-base">Buat Sesi Live Baru</h2><p class="text-blue-100 text-xs">Isi detail sesi live Anda</p></div>
            </div>
            <button onclick="document.getElementById('live-modal').classList.add('hidden')" class="w-8 h-8 bg-white/20 rounded-lg flex items-center justify-center text-white hover:bg-white/30 transition-colors">
                <ion-icon name="close" class="text-xl"></ion-icon>
            </button>
        </div>
        <form action="{{ route('seller.live.store') }}" method="POST" class="p-6 space-y-4 overflow-y-auto">
            @csrf
            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-1.5">Judul Sesi Live <span class="text-red-500">*</span></label>
                <input type="text" name="title" placeholder="Contoh: Flash Sale Baju Musim Panas!"
                    class="w-full border border-slate-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-primary-300 transition-all">
            </div>
            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-1.5">Deskripsi</label>
                <textarea name="description" rows="2" placeholder="Jelaskan produk yang akan dipromosikan..."
                    class="w-full border border-slate-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-primary-300 transition-all resize-none"></textarea>
            </div>

            <!-- TIPE RILIS -->
            <div class="mt-4">
                <label class="block text-sm font-semibold text-slate-700 mb-2">Tipe Rilis <span class="text-red-500">*</span></label>
                <div class="flex gap-3">
                    <label class="flex-1 cursor-pointer">
                        <input type="radio" name="release_type" value="now" class="peer sr-only" checked onchange="toggleScheduleIndex(this.value)">
                        <div class="border border-slate-200 rounded-xl p-3 flex items-center justify-center gap-2 hover:bg-slate-50 peer-checked:border-primary-500 peer-checked:bg-primary-50 peer-checked:text-primary-700 transition-all">
                            <ion-icon name="flash" class="text-lg"></ion-icon>
                            <span class="font-semibold text-sm">Live Sekarang</span>
                        </div>
                    </label>
                    <label class="flex-1 cursor-pointer">
                        <input type="radio" name="release_type" value="schedule" class="peer sr-only" onchange="toggleScheduleIndex(this.value)">
                        <div class="border border-slate-200 rounded-xl p-3 flex items-center justify-center gap-2 hover:bg-slate-50 peer-checked:border-primary-500 peer-checked:bg-primary-50 peer-checked:text-primary-700 transition-all">
                            <ion-icon name="calendar-outline" class="text-lg"></ion-icon>
                            <span class="font-semibold text-sm">Jadwalkan</span>
                        </div>
                    </label>
                </div>
            </div>

            <!-- DYNAMIC SCHEDULING FIELDS -->
            <div id="schedule-fields-index" class="hidden mt-3 grid grid-cols-2 gap-3 fade-in">
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">Tanggal Live</label>
                    <input type="date" name="schedule_date" class="w-full border border-slate-200 rounded-xl px-4 py-3 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-primary-300 transition-all">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">Waktu / Jam</label>
                    <input type="time" name="schedule_time" class="w-full border border-slate-200 rounded-xl px-4 py-3 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-primary-300 transition-all">
                </div>
            </div>

            <div class="border-t border-slate-100 pt-4 mt-4">
                <p class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-3">Rekening Penerima Dana</p>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1.5">Bank <span class="text-red-500">*</span></label>
                        <select name="bank_name" class="w-full border border-slate-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-primary-300 transition-all bg-white">
                            <option value="">Pilih Bank / E-Wallet</option>
                            @foreach([
                                'BCA', 'Mandiri', 'BNI', 'BRI', 'BTN', 'BSI', 'CIMB Niaga', 'Permata', 'Danamon', 'Mega', 'Bukopin', 'Panin', 'OCBC NISP', 'Maybank', 'UOB', 'Muamalat',
                                'Blu by BCA Digital', 'Bank Jago', 'SeaBank', 'Jenius', 'NeoBank', 'Allo Bank', 'Superbank',
                                'Bank BJB', 'Bank DKI', 'Bank Jatim', 'Bank Jateng',
                                'DANA', 'GoPay', 'OVO', 'ShopeePay', 'LinkAja', 'AstraPay', 'i.Saku'
                            ] as $b)
                            <option value="{{ $b }}">{{ $b }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1.5">No. Rekening <span class="text-red-500">*</span></label>
                        <input type="text" name="bank_account" placeholder="1234567890"
                            class="w-full border border-slate-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-primary-300 transition-all">
                    </div>
                </div>
                <div class="mt-3">
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">Atas Nama <span class="text-red-500">*</span></label>
                    <input type="text" name="bank_account_name" placeholder="Nama sesuai rekening"
                        class="w-full border border-slate-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-primary-300 transition-all">
                </div>
            </div>
            <div class="flex gap-3 pt-2">
                <button type="button" onclick="document.getElementById('live-modal').classList.add('hidden')" class="flex-1 border border-slate-200 text-slate-600 font-semibold text-sm py-3 rounded-xl hover:bg-slate-50 transition-colors">Batal</button>
                <button type="submit" id="submit-live-btn-index" class="flex-1 bg-primary-600 hover:bg-primary-700 text-white font-bold text-sm py-3 rounded-xl shadow-md shadow-blue-200 transition-colors flex items-center justify-center gap-2">
                    <ion-icon name="radio" class="text-red-300"></ion-icon> Mulai Live!
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function toggleScheduleIndex(type) {
        const fields = document.getElementById('schedule-fields-index');
        const submitBtn = document.getElementById('submit-live-btn-index');
        if (type === 'schedule') {
            fields.classList.remove('hidden');
            submitBtn.innerHTML = '<ion-icon name="calendar" class="text-red-300"></ion-icon> Simpan Jadwal Live';
        } else {
            fields.classList.add('hidden');
            submitBtn.innerHTML = '<ion-icon name="radio" class="text-red-300"></ion-icon> Mulai Live!';
        }
    }
</script>
@endpush
