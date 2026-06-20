<!DOCTYPE html>
<html>
<head>
    <title>Pesanan Baru Masuk</title>
</head>
<body style="font-family: Arial, sans-serif; color: #333; line-height: 1.6;">
    <div style="max-w: 600px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; border-radius: 8px;">
        <h2 style="color: #2563eb; text-align: center;">Pesanan Baru Masuk! 🎉</h2>
        
        <p>Halo <strong>{{ $order->seller->name }}</strong>,</p>
        <p>Hore! Ada pesanan baru dari <strong>{{ $order->buyer->name }}</strong>.</p>
        
        <div style="background-color: #f8fafc; padding: 15px; border-radius: 8px; margin: 20px 0;">
            <p style="margin: 0;"><strong>ID Pesanan:</strong> ORD-{{ str_pad($order->id, 5, '0', STR_PAD_LEFT) }}</p>
            <p style="margin: 5px 0 0;"><strong>Total Belanja:</strong> Rp {{ number_format($order->total_price, 0, ',', '.') }}</p>
        </div>

        <p>Segera periksa dashboard toko Anda untuk memproses pesanan ini.</p>
        
        <div style="text-align: center; margin-top: 30px;">
            <a href="{{ config('app.url') }}/seller/dashboard" style="background-color: #2563eb; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; font-weight: bold;">Ke Dashboard Seller</a>
        </div>
        
        <hr style="border: 0; border-top: 1px solid #eee; margin: 30px 0;" />
        <p style="font-size: 12px; color: #777; text-align: center;">Tim StreamCart &copy; {{ date('Y') }}</p>
    </div>
</body>
</html>
