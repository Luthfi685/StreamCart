<!DOCTYPE html>
<html>
<head>
    <title>Permintaan Refund</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <h2>Halo Admin StreamCart,</h2>
    <p>Pesanan dengan nomor <strong>TRX-{{ str_pad($order->id, 5, '0', STR_PAD_LEFT) }}</strong> telah dibatalkan dan memerlukan pengembalian dana (Refund).</p>
    
    <div style="background-color: #f9f9f9; padding: 15px; border-left: 4px solid #f59e0b; margin: 20px 0;">
        <p style="margin: 0;"><strong>Detail Pesanan:</strong></p>
        <ul style="margin-top: 10px;">
            <li><strong>Total Refund:</strong> Rp {{ number_format($order->total_price, 0, ',', '.') }}</li>
            <li><strong>Nama Pembeli:</strong> {{ $order->buyer->name }} ({{ $order->buyer->username }})</li>
            <li><strong>Seller:</strong> {{ $order->seller->store_name ?? $order->seller->name }}</li>
        </ul>
    </div>
    
    <p>Silakan segera proses transfer kembali ke rekening pembeli, lalu tandai sebagai <strong>Sudah Refund</strong> di menu Pengembalian Dana pada Dashboard Admin Anda.</p>
    
    <p>Terima kasih,<br>Sistem StreamCart</p>
</body>
</html>
