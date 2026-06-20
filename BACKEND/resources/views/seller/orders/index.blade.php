@extends('layouts.app')
@section('title', 'Pesanan Masuk')
@section('page-title', 'Pesanan Masuk')
@section('page-subtitle', 'Kelola dan update status pengiriman pesanan')

@push('styles')
<style>
    @keyframes slideIn {
        from { opacity: 0; transform: translateY(-12px); }
        to   { opacity: 1; transform: translateY(0); }
    }
    .new-row { animation: slideIn 0.4s ease; }

    /* Toast notification */
    #order-toast {
        transition: transform 0.4s cubic-bezier(0.34,1.56,0.64,1), opacity 0.3s ease;
        transform: translateX(110%);
    }
    #order-toast.show {
        transform: translateX(0);
    }
</style>
@endpush

@section('content')


@if(session('success'))
<div class="mb-4 bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 rounded-xl text-sm font-medium flex items-center gap-2">
    <ion-icon name="checkmark-circle" class="text-lg shrink-0"></ion-icon> {{ session('success') }}
</div>
@endif

<div class="bg-white rounded-2xl shadow-sm border border-blue-50 overflow-hidden">
    {{-- Header --}}
    <div class="table-header px-6 py-4 flex items-center justify-between">
        <div>
            <h2 class="font-bold text-base">Daftar Pesanan</h2>
            <p class="text-blue-100 text-xs mt-0.5">Klik "Update Status" untuk memperbarui status pengiriman</p>
        </div>
        <div class="flex items-center gap-2 text-white text-xs font-medium bg-white/20 px-3 py-1.5 rounded-lg">
            <span class="w-2 h-2 bg-green-400 rounded-full animate-ping inline-block"></span>
            Live Update
        </div>
    </div>

    {{-- Table --}}
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-blue-50 border-b border-blue-100">
                <tr>
                    <th class="text-left px-5 py-3 text-xs font-bold text-primary-700 uppercase tracking-wider">ID Pesanan</th>
                    <th class="text-left px-4 py-3 text-xs font-bold text-primary-700 uppercase tracking-wider">Pembeli</th>
                    <th class="text-left px-4 py-3 text-xs font-bold text-primary-700 uppercase tracking-wider">Produk</th>
                    <th class="text-right px-4 py-3 text-xs font-bold text-primary-700 uppercase tracking-wider">Total</th>
                    <th class="text-center px-4 py-3 text-xs font-bold text-primary-700 uppercase tracking-wider">Status</th>
                    <th class="text-center px-4 py-3 text-xs font-bold text-primary-700 uppercase tracking-wider">Aksi</th>
                </tr>
            </thead>
            <tbody id="orders-tbody" class="divide-y divide-slate-100">
                @forelse($orders as $order)
                <tr class="hover:bg-blue-50/40 transition-colors">
                    <td class="px-5 py-3.5">
                        <p class="font-bold text-slate-700 text-xs">ORD-{{ str_pad($order->id, 3, '0', STR_PAD_LEFT) }}</p>
                        <p class="text-[11px] text-slate-400 mt-0.5">{{ \Carbon\Carbon::parse($order->created_at)->diffForHumans() }}</p>
                    </td>
                    <td class="px-4 py-3.5">
                        <div class="flex items-center gap-2 mb-1">
                            <div class="w-7 h-7 rounded-full bg-primary-100 flex items-center justify-center text-primary-700 text-xs font-bold flex-shrink-0">
                                {{ strtoupper(substr($order->buyer?->name ?? 'B', 0, 2)) }}
                            </div>
                            <span class="text-sm font-medium text-slate-700">{{ $order->buyer?->name ?? '—' }}</span>
                        </div>
                        @if($order->shipping_address)
                        <div class="pl-9">
                            @php
                                $buyerPhone = $order->buyer?->phone;
                                if (empty($buyerPhone)) {
                                    $buyerPhone = \App\Models\UserAddress::where('user_id', $order->buyer_id)->latest()->first()?->phone;
                                }
                                $buyerPhone = $buyerPhone ?? 'Tidak ada no. HP';
                            @endphp
                            <button onclick="openAddressModal('{{ addslashes($order->buyer?->name) }}', '{{ addslashes($order->shipping_address) }}', '{{ addslashes($buyerPhone) }}')" class="text-[10px] text-primary-600 font-bold hover:underline flex items-center gap-1">
                                <ion-icon name="location-outline"></ion-icon> Lihat Alamat
                            </button>
                        </div>
                        @endif
                    </td>
                    <td class="px-4 py-3.5">
                        <span class="text-sm text-slate-600">{{ $order->items->first()?->product?->name ?? '—' }}</span>
                        @if($order->items->count() > 1)
                            <span class="text-[10px] text-slate-400 block">+ {{ $order->items->count() - 1 }} produk lain</span>
                        @endif
                        <span class="text-xs text-slate-400 block">Total Qty: {{ $order->items->sum('quantity') }}</span>
                    </td>
                    <td class="px-4 py-3.5 text-right">
                        <span class="font-bold text-slate-800">Rp {{ number_format($order->total_price, 0, ',', '.') }}</span>
                    </td>
                    <td class="px-4 py-3.5 text-center">
                        @php $s = $order->status; @endphp
                        @if($s === 'completed')
                            <span class="badge-green text-[10px] font-bold px-2.5 py-1 rounded-full">Selesai</span>
                        @elseif($s === 'shipped')
                            <span class="badge-blue text-[10px] font-bold px-2.5 py-1 rounded-full">Dikirim</span>
                        @elseif($s === 'processed')
                            <span class="badge-blue text-[10px] font-bold px-2.5 py-1 rounded-full">Dikemas</span>
                        @elseif($s === 'success')
                            <span class="badge-blue text-[10px] font-bold px-2.5 py-1 rounded-full">Dikonfirmasi</span>
                        @elseif($s === 'checking_admin')
                            <span class="badge-yellow text-[10px] font-bold px-2.5 py-1 rounded-full">Menunggu Admin</span>
                        @elseif($s === 'pending')
                            <span class="badge-yellow text-[10px] font-bold px-2.5 py-1 rounded-full">Pending</span>
                        @elseif($s === 'pending_cancel')
                            <span class="bg-amber-100 text-amber-700 text-[10px] font-bold px-2.5 py-1 rounded-full border border-amber-200 animate-pulse">Permintaan Batal</span>
                        @elseif($s === 'fail')
                            <span class="bg-red-100 text-red-700 text-[10px] font-bold px-2.5 py-1 rounded-full">Gagal</span>
                        @else
                            <span class="bg-slate-100 text-slate-600 text-[10px] font-bold px-2.5 py-1 rounded-full">{{ ucfirst($s) }}</span>
                        @endif
                    </td>
                    <td class="px-4 py-3.5 text-center">
                        @if(in_array($order->status, ['success', 'checking_admin']))
                        <form action="{{ route('seller.orders.status', $order->id) }}" method="POST" class="inline-flex items-center gap-1">
                            @csrf @method('PATCH')
                            <input type="hidden" name="status" value="processed">
                            <button type="submit" class="bg-primary-600 hover:bg-primary-700 text-white text-[11px] font-bold px-3 py-1.5 rounded-lg transition-all hover:-translate-y-0.5">
                                Kemas Pesanan
                            </button>
                        </form>
                        @elseif($order->status === 'processed')
                        <button onclick="openShippingModal({{ $order->id }})" class="bg-amber-500 hover:bg-amber-600 text-white text-[11px] font-bold px-3 py-1.5 rounded-lg transition-all hover:-translate-y-0.5">
                            Kirim Barang
                        </button>
                        @elseif($order->status === 'pending_cancel')
                        <button onclick="openCancelModal({{ $order->id }}, this.getAttribute('data-reason'))" data-reason="{{ $order->cancel_reason }}" class="bg-amber-100 border border-amber-300 hover:bg-amber-200 text-amber-800 text-[11px] font-bold px-3 py-1.5 rounded-lg transition-all hover:-translate-y-0.5">
                            Lihat Pengajuan
                        </button>
                        @elseif($order->status === 'shipped')
                        <span class="text-[10px] text-slate-500 block">{{ $order->shipping_courier }}</span>
                        <span class="text-[11px] font-bold text-slate-800">{{ $order->shipping_tracking_number }}</span>
                        @else
                            <span class="text-slate-300 text-xs">—</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="text-center py-16 text-slate-400">
                        <ion-icon name="receipt-outline" class="text-5xl block mx-auto mb-3"></ion-icon>
                        <p class="font-medium">Belum ada pesanan masuk</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Pagination --}}
    @if($orders->hasPages())
    <div class="px-6 py-4 border-t border-slate-100">{{ $orders->links() }}</div>
    @endif
</div>

@push('modals')
{{-- Toast Notification --}}
<div id="order-toast"
     class="fixed top-6 right-6 z-50 flex items-center gap-3 bg-white border border-primary-200 shadow-xl rounded-2xl px-5 py-4 max-w-sm">
    <div class="w-10 h-10 bg-primary-50 rounded-xl flex items-center justify-center flex-shrink-0">
        <ion-icon name="bag-check-outline" class="text-2xl text-primary-600"></ion-icon>
    </div>
    <div>
        <p class="text-sm font-bold text-slate-800">Pesanan Baru Masuk! 🎉</p>
        <p id="toast-message" class="text-xs text-slate-500 mt-0.5">dari buyer baru</p>
    </div>
    <button onclick="hideToast()" class="ml-auto text-slate-300 hover:text-slate-500 transition-colors">
        <ion-icon name="close-outline" class="text-lg"></ion-icon>
    </button>
</div>

{{-- Shipping Modal --}}
<div id="shipping-modal" class="fixed inset-0 z-50 hidden bg-slate-900/50 backdrop-blur-sm flex items-center justify-center">
    <div class="bg-white rounded-2xl w-full max-w-md shadow-2xl overflow-hidden transform scale-95 opacity-0 transition-all duration-300" id="shipping-modal-content">
        <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
            <h3 class="font-bold text-lg text-slate-800">Kirim Barang</h3>
            <button type="button" onclick="closeShippingModal()" class="text-slate-400 hover:text-slate-600 transition-colors">
                <ion-icon name="close-outline" class="text-2xl"></ion-icon>
            </button>
        </div>
        <form id="shipping-form" method="POST" action="">
            @csrf @method('PATCH')
            <input type="hidden" name="status" value="shipped">
            <div class="p-6 space-y-4">
                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-1">Kurir Pengiriman <span class="text-red-500">*</span></label>
                    <select name="shipping_courier" required class="w-full border border-gray-300 rounded-xl p-3 bg-white text-gray-700 focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                        <option value="" disabled selected>Pilih Kurir...</option>
                        <optgroup label="Pengiriman Reguler">
                            <option value="JNE">JNE Express</option>
                            <option value="J&T">J&T Express</option>
                            <option value="SiCepat">SiCepat Ekspres</option>
                            <option value="POS">POS Indonesia</option>
                            <option value="Anteraja">Anteraja</option>
                        </optgroup>
                        <optgroup label="Pengiriman Kargo (Barang Besar)">
                            <option value="J&T Cargo">J&T Cargo</option>
                            <option value="JNE JTR">JNE Trucking / JTR</option>
                        </optgroup>
                        <optgroup label="Pengiriman Instan / Sameday">
                            <option value="GoSend">GoSend</option>
                            <option value="GrabExpress">GrabExpress</option>
                        </optgroup>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-1">Nomor Resi <span class="text-red-500">*</span></label>
                    <input type="text" name="shipping_tracking_number" required placeholder="Masukkan nomor resi..." 
                           class="w-full border border-gray-300 rounded-xl p-3 bg-white text-gray-700 focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                </div>
            </div>
            <div class="px-6 py-4 bg-slate-50 border-t border-slate-100 flex items-center justify-end gap-3">
                <button type="button" onclick="closeShippingModal()" class="px-4 py-2 text-sm font-bold text-slate-500 hover:text-slate-700 transition-colors">
                    Batal
                </button>
                <button type="submit" class="bg-primary-600 hover:bg-primary-700 text-white text-sm font-bold px-6 py-2 rounded-xl shadow-sm hover:shadow-md transition-all">
                    Simpan & Kirim
                </button>
            </div>
        </form>
    </div>
</div>

{{-- Cancel Request Modal --}}
<div id="cancel-modal" class="fixed inset-0 z-50 hidden bg-slate-900/50 backdrop-blur-sm flex items-center justify-center">
    <div class="bg-white rounded-2xl w-full max-w-md shadow-2xl overflow-hidden transform scale-95 opacity-0 transition-all duration-300" id="cancel-modal-content">
        <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
            <h3 class="font-bold text-lg text-amber-600 flex items-center gap-2">
                <ion-icon name="warning"></ion-icon> Pengajuan Pembatalan
            </h3>
            <button type="button" onclick="closeCancelModal()" class="text-slate-400 hover:text-slate-600 transition-colors">
                <ion-icon name="close-outline" class="text-2xl"></ion-icon>
            </button>
        </div>
        <div class="p-6">
            <p class="text-sm font-medium text-slate-700 mb-2">Alasan Pembeli:</p>
            <div class="bg-amber-50 border border-amber-200 text-amber-900 p-4 rounded-xl text-sm italic mb-6">
                "<span id="cancel-reason-text"></span>"
            </div>
            
            <p class="text-xs text-slate-500 mb-4">
                Pilih keputusan Anda. Jika disetujui, pesanan akan dibatalkan secara permanen. Jika ditolak, pesanan akan kembali ke status Sedang Dikemas dan pembeli diharapkan menerima pesanan tersebut.
            </p>
        </div>
        <div class="px-6 py-4 bg-slate-50 border-t border-slate-100 flex items-center justify-end gap-3">
            <form id="cancel-reject-form" method="POST" action="" class="m-0 p-0 inline">
                @csrf @method('PATCH')
                <input type="hidden" name="action" value="reject">
                <button type="submit" class="bg-red-50 hover:bg-red-100 text-red-600 border border-red-200 text-sm font-bold px-5 py-2 rounded-xl shadow-sm hover:shadow-md transition-all">
                    Tolak Pembatalan
                </button>
            </form>
            <form id="cancel-approve-form" method="POST" action="" class="m-0 p-0 inline">
                @csrf @method('PATCH')
                <input type="hidden" name="action" value="approve">
                <button type="submit" class="bg-emerald-500 hover:bg-emerald-600 text-white text-sm font-bold px-5 py-2 rounded-xl shadow-sm hover:shadow-md transition-all">
                    Setujui Pembatalan
                </button>
            </form>
        </div>
    </div>
</div>

{{-- Address Modal --}}
<div id="address-modal" class="fixed inset-0 z-50 hidden bg-slate-900/50 backdrop-blur-sm flex items-center justify-center">
    <div class="bg-white rounded-2xl w-full max-w-md shadow-2xl overflow-hidden transform scale-95 opacity-0 transition-all duration-300" id="address-modal-content">
        <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
            <h3 class="font-bold text-lg text-slate-800 flex items-center gap-2">
                <ion-icon name="location-outline" class="text-primary-600"></ion-icon> Alamat Pengiriman
            </h3>
            <button type="button" onclick="closeAddressModal()" class="text-slate-400 hover:text-slate-600 transition-colors">
                <ion-icon name="close-outline" class="text-2xl"></ion-icon>
            </button>
        </div>
        <div class="p-6">
            <p class="text-sm font-bold text-slate-700 mb-1">Penerima:</p>
            <p id="address-modal-name" class="text-sm text-slate-600 mb-1 font-medium"></p>
            
            <p class="text-sm font-bold text-slate-700 mb-1 mt-3 flex items-center gap-1">
                <ion-icon name="call-outline"></ion-icon> No. HP:
            </p>
            <p id="address-modal-phone" class="text-sm text-slate-600 mb-4 font-medium"></p>
            
            <p class="text-sm font-bold text-slate-700 mb-1">Alamat Lengkap:</p>
            <div class="bg-slate-50 border border-slate-200 text-slate-700 p-4 rounded-xl text-sm leading-relaxed whitespace-pre-wrap" id="address-modal-text"></div>
        </div>
        <div class="px-6 py-4 bg-slate-50 border-t border-slate-100 flex items-center justify-end">
            <button type="button" onclick="closeAddressModal()" class="bg-primary-600 hover:bg-primary-700 text-white text-sm font-bold px-6 py-2 rounded-xl shadow-sm hover:shadow-md transition-all">
                Tutup
            </button>
        </div>
    </div>
</div>
@endpush

@endsection

@push('scripts')
<script>
    // ── Cancel Modal Logic ──────────────────────────────────────────────
    function openCancelModal(orderId, reason) {
        const modal = document.getElementById('cancel-modal');
        const content = document.getElementById('cancel-modal-content');
        const approveForm = document.getElementById('cancel-approve-form');
        const rejectForm = document.getElementById('cancel-reject-form');
        const reasonText = document.getElementById('cancel-reason-text');
        
        // Setup form actions
        const url = `/seller/orders/${orderId}/respond-cancel`;
        approveForm.action = url;
        rejectForm.action = url;
        reasonText.textContent = reason || 'Tidak ada alasan';
        
        modal.classList.remove('hidden');
        setTimeout(() => {
            content.classList.remove('scale-95', 'opacity-0');
            content.classList.add('scale-100', 'opacity-100');
        }, 10);
    }

    function closeCancelModal() {
        const modal = document.getElementById('cancel-modal');
        const content = document.getElementById('cancel-modal-content');
        
        content.classList.remove('scale-100', 'opacity-100');
        content.classList.add('scale-95', 'opacity-0');
        
        setTimeout(() => {
            modal.classList.add('hidden');
        }, 300);
    }

    // Close modal on click outside
    document.getElementById('cancel-modal').addEventListener('click', function(e) {
        if (e.target === this) closeCancelModal();
    });

    // ── Address Modal Logic ──────────────────────────────────────────────
    function openAddressModal(name, address, phone) {
        const modal = document.getElementById('address-modal');
        const content = document.getElementById('address-modal-content');
        
        document.getElementById('address-modal-name').textContent = name;
        document.getElementById('address-modal-phone').textContent = phone || 'Tidak ada no. HP';
        document.getElementById('address-modal-text').textContent = address || 'Alamat tidak tersedia';
        
        modal.classList.remove('hidden');
        setTimeout(() => {
            content.classList.remove('scale-95', 'opacity-0');
            content.classList.add('scale-100', 'opacity-100');
        }, 10);
    }

    function closeAddressModal() {
        const modal = document.getElementById('address-modal');
        const content = document.getElementById('address-modal-content');
        
        content.classList.remove('scale-100', 'opacity-100');
        content.classList.add('scale-95', 'opacity-0');
        
        setTimeout(() => {
            modal.classList.add('hidden');
        }, 300);
    }

    document.getElementById('address-modal').addEventListener('click', function(e) {
        if (e.target === this) closeAddressModal();
    });

    // ── Shipping Modal Logic ──────────────────────────────────────────────
    function openShippingModal(orderId) {
        const modal = document.getElementById('shipping-modal');
        const content = document.getElementById('shipping-modal-content');
        const form = document.getElementById('shipping-form');
        
        // Setup form action correctly using base URL
        form.action = `/seller/orders/${orderId}/status`;
        
        modal.classList.remove('hidden');
        // Small delay to allow display:block to apply before animating opacity/transform
        setTimeout(() => {
            content.classList.remove('scale-95', 'opacity-0');
            content.classList.add('scale-100', 'opacity-100');
        }, 10);
    }

    function closeShippingModal() {
        const modal = document.getElementById('shipping-modal');
        const content = document.getElementById('shipping-modal-content');
        
        content.classList.remove('scale-100', 'opacity-100');
        content.classList.add('scale-95', 'opacity-0');
        
        setTimeout(() => {
            modal.classList.add('hidden');
        }, 300);
    }

    // Close modal on click outside
    document.getElementById('shipping-modal').addEventListener('click', function(e) {
        if (e.target === this) closeShippingModal();
    });
    // ── New Order Polling — setiap 5 detik ────────────────────────────────────
    const ORDERS_URL = '{{ route("seller.api.orders") }}';
    let latestOrderId = {{ $latestOrderId ?? 0 }};
    let toastTimer = null;

    function showToast(buyerName) {
        const toast   = document.getElementById('order-toast');
        const message = document.getElementById('toast-message');
        message.textContent = `Pesanan Baru dari ${buyerName}!`;
        toast.classList.add('show');

        // Play sound (simple beep via AudioContext)
        try {
            const ac  = new (window.AudioContext || window.webkitAudioContext)();
            const osc = ac.createOscillator();
            const gain = ac.createGain();
            osc.connect(gain); gain.connect(ac.destination);
            osc.frequency.value = 880;
            gain.gain.setValueAtTime(0.3, ac.currentTime);
            gain.gain.exponentialRampToValueAtTime(0.001, ac.currentTime + 0.4);
            osc.start(); osc.stop(ac.currentTime + 0.4);
        } catch(e) {}

        clearTimeout(toastTimer);
        toastTimer = setTimeout(hideToast, 6000);
    }

    function hideToast() {
        document.getElementById('order-toast')?.classList.remove('show');
    }

    function statusBadge(status) {
        const map = {
            'completed':     '<span class="badge-green text-[10px] font-bold px-2.5 py-1 rounded-full">Selesai</span>',
            'processed':     '<span class="badge-blue text-[10px] font-bold px-2.5 py-1 rounded-full">Dikemas</span>',
            'success':       '<span class="badge-blue text-[10px] font-bold px-2.5 py-1 rounded-full">Dikonfirmasi</span>',
            'checking_admin':'<span class="badge-yellow text-[10px] font-bold px-2.5 py-1 rounded-full">Menunggu Admin</span>',
            'pending':       '<span class="badge-yellow text-[10px] font-bold px-2.5 py-1 rounded-full">Pending</span>',
            'fail':          '<span class="bg-red-100 text-red-700 text-[10px] font-bold px-2.5 py-1 rounded-full">Gagal</span>',
        };
        return map[status] ?? `<span class="bg-slate-100 text-slate-600 text-[10px] font-bold px-2.5 py-1 rounded-full">${status}</span>`;
    }

    function checkNewOrders() {
        fetch(`${ORDERS_URL}?after=${latestOrderId}`, {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(r => r.ok ? r.json() : Promise.reject(r))
        .then(orders => {
            if (!orders.length) return;

            const tbody = document.getElementById('orders-tbody');
            // Remove "empty" row if present
            const emptyRow = tbody.querySelector('[colspan="6"]');
            if (emptyRow) emptyRow.closest('tr').remove();

            // Prepend new rows (newest first)
            orders.forEach(o => {
                // Prevent duplicates
                if (document.getElementById(`order-row-${o.id}`)) return;

                latestOrderId = Math.max(latestOrderId, o.id);

                const row = document.createElement('tr');
                row.id = `order-row-${o.id}`;
                row.className = 'new-row hover:bg-blue-50/40 transition-colors';
                row.innerHTML = `
                    <td class="px-5 py-3.5">
                        <p class="font-bold text-slate-700 text-xs">${o.code}</p>
                        <p class="text-[11px] text-slate-400 mt-0.5">${o.time_ago}</p>
                    </td>
                    <td class="px-4 py-3.5">
                        <div class="flex items-center gap-2">
                            <div class="w-7 h-7 rounded-full bg-primary-100 flex items-center justify-center text-primary-700 text-xs font-bold flex-shrink-0">
                                ${o.buyer_name.substring(0,2).toUpperCase()}
                            </div>
                            <span class="text-sm font-medium text-slate-700">${o.buyer_name}</span>
                        </div>
                    </td>
                    <td class="px-4 py-3.5">
                        <span class="text-sm text-slate-600">${o.product_name}</span>
                    </td>
                    <td class="px-4 py-3.5 text-right">
                        <span class="font-bold text-slate-800">${o.amount_label}</span>
                    </td>
                    <td class="px-4 py-3.5 text-center">${statusBadge(o.status)}</td>
                    <td class="px-4 py-3.5 text-center"><span class="text-slate-300 text-xs">—</span></td>
                `;
                tbody.prepend(row);
                showToast(o.buyer_name);
            });
        })
        .catch(() => {});
    }

    setInterval(checkNewOrders, 5000);
</script>
@endpush
