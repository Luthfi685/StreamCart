@extends('layouts.app')

@section('title', 'Admin - Tiket Pengaduan')
@section('page-title', 'Tiket Pengaduan Dukungan')
@section('page-subtitle', 'Daftar tiket bantuan yang dikirimkan oleh pengguna')

@section('content')

@if(session('success'))
<div class="bg-green-50 text-green-700 p-4 rounded-lg mb-6 border border-green-200 flex items-center gap-2 fade-in">
    <ion-icon name="checkmark-circle" class="text-xl"></ion-icon>
    <p class="text-sm font-medium">{{ session('success') }}</p>
</div>
@endif

<div class="bg-white rounded-2xl shadow-sm border border-blue-50 overflow-hidden">
    <div class="table-header px-6 py-4 flex items-center justify-between">
        <div>
            <h2 class="font-bold text-base">Semua Tiket</h2>
            <p class="text-blue-100 text-xs mt-0.5">Daftar keluhan dan pertanyaan dari pengguna</p>
        </div>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-blue-50 border-b border-blue-100">
                <tr>
                    <th class="text-left px-6 py-3 text-[11px] font-bold text-primary-700 uppercase tracking-wider">Tiket & Kategori</th>
                    <th class="text-left px-4 py-3 text-[11px] font-bold text-primary-700 uppercase tracking-wider">Pengguna</th>
                    <th class="text-left px-4 py-3 text-[11px] font-bold text-primary-700 uppercase tracking-wider">Deskripsi</th>
                    <th class="text-center px-4 py-3 text-[11px] font-bold text-primary-700 uppercase tracking-wider">Status</th>
                    <th class="text-center px-4 py-3 text-[11px] font-bold text-primary-700 uppercase tracking-wider">Aksi Update</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($tickets as $ticket)
                <tr class="table-row-hover transition-colors">
                    <td class="px-6 py-4">
                        <p class="font-bold text-slate-800">{{ $ticket->issue_title }}</p>
                        <p class="text-[11px] font-medium text-primary-600 mt-1">{{ $ticket->issue_category }}</p>
                        <p class="text-[10px] text-slate-400 mt-0.5">{{ $ticket->created_at->format('d M Y, H:i') }}</p>
                    </td>
                    <td class="px-4 py-4">
                        <p class="text-sm font-semibold text-slate-700">{{ $ticket->user->name ?? 'Unknown' }}</p>
                        <p class="text-[11px] text-slate-500">{{ $ticket->user->email ?? '—' }}</p>
                    </td>
                    <td class="px-4 py-4 max-w-xs align-top">
                        <p class="text-xs text-slate-600 mb-2 whitespace-pre-wrap">{{ $ticket->description }}</p>
                        @if($ticket->admin_reply)
                            <div class="mt-2 bg-blue-50 border-l-2 border-blue-500 pl-2 py-1">
                                <p class="text-[10px] font-bold text-blue-700">Balasan Admin:</p>
                                <p class="text-xs text-blue-900 mt-0.5 whitespace-pre-wrap">{{ $ticket->admin_reply }}</p>
                            </div>
                        @endif
                    </td>
                    <td class="px-4 py-4 text-center align-top">
                        @if($ticket->status === 'open')
                            <span class="badge-red px-2.5 py-1 rounded-full text-[10px] font-bold uppercase">Open</span>
                        @elseif($ticket->status === 'in_progress')
                            <span class="badge-yellow px-2.5 py-1 rounded-full text-[10px] font-bold uppercase">Diproses</span>
                        @elseif($ticket->status === 'resolved' || $ticket->status === 'closed')
                            <span class="badge-green px-2.5 py-1 rounded-full text-[10px] font-bold uppercase">Selesai</span>
                        @else
                            <span class="bg-slate-100 text-slate-600 px-2.5 py-1 rounded-full text-[10px] font-bold uppercase">{{ $ticket->status }}</span>
                        @endif
                    </td>
                    <td class="px-4 py-4 align-top">
                        <form action="{{ route('admin.tickets.status', $ticket->id) }}" method="POST" class="flex flex-col gap-2">
                            @csrf @method('PATCH')
                            <textarea name="admin_reply" rows="2" class="text-xs border-gray-300 rounded focus:ring-primary-500 focus:border-primary-500 w-full p-2 bg-white" placeholder="Tulis balasan untuk user...">{{ $ticket->admin_reply }}</textarea>
                            <div class="flex gap-2 w-full">
                                <select name="status" class="text-xs border-gray-300 rounded focus:ring-primary-500 focus:border-primary-500 px-2 py-1 bg-white flex-1">
                                    <option value="open" {{ $ticket->status == 'open' ? 'selected' : '' }}>Open</option>
                                    <option value="in_progress" {{ $ticket->status == 'in_progress' ? 'selected' : '' }}>Diproses</option>
                                    <option value="closed" {{ $ticket->status == 'closed' || $ticket->status == 'resolved' ? 'selected' : '' }}>Selesai</option>
                                </select>
                                <button type="submit" class="bg-primary-600 hover:bg-primary-700 text-white text-[10px] font-bold px-3 py-1 rounded transition-colors whitespace-nowrap">Simpan</button>
                            </div>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="px-6 py-8 text-center text-slate-400 text-sm">
                        Belum ada tiket pengaduan masuk.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@endsection
