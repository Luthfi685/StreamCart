@extends('layouts.app')
@section('title','Monitor Live Session')
@section('page-title','Monitor Live Session')
@section('page-subtitle','Pantau semua sesi live streaming yang sedang berjalan')

@section('content')
<div class="mb-6 flex items-center gap-3">
    @if($activeCount > 0)
    <div class="flex items-center gap-2 bg-red-100 text-red-600 text-sm font-bold px-4 py-2 rounded-full">
        <span class="w-2 h-2 bg-red-500 rounded-full animate-pulse"></span>
        {{ $activeCount }} Live Aktif Sekarang
    </div>
    @else
    <div class="flex items-center gap-2 bg-slate-100 text-slate-500 text-sm font-medium px-4 py-2 rounded-full">
        Tidak ada sesi live aktif
    </div>
    @endif
</div>

<div class="bg-white rounded-2xl shadow-sm border border-blue-50 overflow-hidden">
    <div class="table-header px-6 py-4"><h2 class="font-bold text-base">Semua Sesi Live</h2></div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-blue-50 border-b border-blue-100">
                <tr>
                    <th class="text-left px-6 py-3 text-xs font-bold text-primary-700 uppercase tracking-wider">Judul Sesi</th>
                    <th class="text-left px-4 py-3 text-xs font-bold text-primary-700 uppercase tracking-wider">Seller</th>
                    <th class="text-center px-4 py-3 text-xs font-bold text-primary-700 uppercase tracking-wider">Status</th>
                    <th class="text-right px-4 py-3 text-xs font-bold text-primary-700 uppercase tracking-wider">Waktu Mulai</th>
                    <th class="text-center px-4 py-3 text-xs font-bold text-primary-700 uppercase tracking-wider">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($sessions as $s)
                <tr class="table-row-hover transition-colors">
                    <td class="px-6 py-3.5">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-lg {{ $s->status === 'live' ? 'bg-red-100' : 'bg-slate-100' }} flex items-center justify-center shrink-0">
                                <ion-icon name="radio-outline" class="text-sm {{ $s->status === 'live' ? 'text-red-500' : 'text-slate-400' }}"></ion-icon>
                            </div>
                            <p class="font-semibold text-slate-800">{{ $s->title }}</p>
                        </div>
                    </td>
                    <td class="px-4 py-3.5 text-sm text-slate-600">{{ $s->seller->store_name ?? $s->seller->name ?? 'N/A' }}</td>
                    <td class="px-4 py-3.5 text-center">
                        @if($s->status === 'live')
                            <span class="inline-flex items-center gap-1.5 bg-red-100 text-red-600 text-[10px] font-bold px-2.5 py-1 rounded-full"><span class="w-1.5 h-1.5 bg-red-500 rounded-full animate-pulse"></span>LIVE</span>
                        @else
                            <span class="bg-slate-100 text-slate-500 text-[10px] font-bold px-2.5 py-1 rounded-full">Selesai</span>
                        @endif
                    </td>
                    <td class="px-4 py-3.5 text-right text-xs text-slate-400">{{ \Carbon\Carbon::parse($s->created_at)->format('d M Y, H:i') }}</td>
                    <td class="px-4 py-3.5 text-center">
                        @if($s->status === 'live')
                        <form action="{{ route('admin.live.stop', $s->id) }}" method="POST" onsubmit="return confirm('Hentikan sesi live ini?')">
                            @csrf @method('PATCH')
                            <button class="bg-red-50 hover:bg-red-100 text-red-600 text-xs font-bold px-3 py-1.5 rounded-lg transition-colors">Stop Live</button>
                        </form>
                        @else<span class="text-slate-300 text-xs">—</span>@endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="5" class="py-16 text-center text-slate-400">
                    <ion-icon name="radio-outline" class="text-5xl text-slate-300 block mx-auto mb-3"></ion-icon>
                    <p>Belum ada sesi live</p>
                </td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="px-6 py-4 border-t border-slate-100">{{ $sessions->links() }}</div>
</div>
@endsection
