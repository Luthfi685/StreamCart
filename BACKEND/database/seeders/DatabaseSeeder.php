<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Product;
use App\Models\LiveSession;
use App\Models\Order;
use App\Models\ChatMessage;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Users
        $admin = User::create([
            'name'     => 'Administrator',
            'username' => 'admin',
            'email'    => 'admin@goingproject.com',
            'password' => bcrypt('admin123'),
            'role'     => 'admin',
        ]);

        $seller1 = User::create([
            'name'             => 'Toko Serba Ada',
            'username'         => 'tokoserba',
            'email'            => 'seller1@goingproject.com',
            'password'         => bcrypt('seller123'),
            'role'             => 'seller',
            'store_name'       => 'Serba Ada Store',
            'store_description'=> 'Menjual segala macam barang murah',
            'bank_name'        => 'BCA',
            'bank_account'     => '1234567890',
            'bank_account_name'=> 'Toko Serba Ada',
        ]);

        $seller2 = User::create([
            'name'             => 'Fashion Pria',
            'username'         => 'fashionpria',
            'email'            => 'seller2@goingproject.com',
            'password'         => bcrypt('seller123'),
            'role'             => 'seller',
            'store_name'       => 'Fashion Pria',
            'store_description'=> 'Pakaian pria kekinian',
            'bank_name'        => 'Mandiri',
            'bank_account'     => '0987654321',
            'bank_account_name'=> 'Fashion Pria Store',
        ]);

        $buyer1 = User::create([
            'name'     => 'Budi Pembeli',
            'username' => 'budi123',
            'email'    => 'buyer1@goingproject.com',
            'password' => bcrypt('buyer123'),
            'role'     => 'buyer',
        ]);

        $buyer2 = User::create([
            'name'     => 'Siti Pembeli',
            'username' => 'siti123',
            'email'    => 'buyer2@goingproject.com',
            'password' => bcrypt('password'),
            'role'     => 'buyer',
        ]);

        // 2. Products
        $p1 = Product::create(['seller_id' => $seller1->id, 'name' => 'Kipas Angin Mini', 'price' => 50000, 'stock' => 100, 'image_url' => 'https://via.placeholder.com/300']);
        $p2 = Product::create(['seller_id' => $seller1->id, 'name' => 'Powerbank 10000mAh', 'price' => 150000, 'stock' => 50, 'image_url' => 'https://via.placeholder.com/300']);
        
        $p3 = Product::create(['seller_id' => $seller2->id, 'name' => 'Kemeja Flanel', 'price' => 120000, 'stock' => 30, 'image_url' => 'https://via.placeholder.com/300']);
        $p4 = Product::create(['seller_id' => $seller2->id, 'name' => 'Celana Chino', 'price' => 140000, 'stock' => 20, 'image_url' => 'https://via.placeholder.com/300']);

        // 3. Live Sessions
        $live1 = LiveSession::create([
            'seller_id' => $seller1->id,
            'title'     => 'Flash Sale Elektronik Murah!',
            'status'    => 'live',
            'viewer_count' => 120,
            'pinned_product_id' => $p1->id,
            'stream_url' => 'https://www.youtube.com/embed/jfKfPfyJRdk' // Lofi hip hop radio
        ]);
        $live1->products()->sync([$p1->id => ['is_pinned' => true], $p2->id => ['is_pinned' => false]]);

        $live2 = LiveSession::create([
            'seller_id' => $seller2->id,
            'title'     => 'OOTD Keren Lebaran',
            'status'    => 'scheduled',
            'scheduled_at' => now()->addDays(1),
        ]);
        $live2->products()->sync([$p3->id, $p4->id]);

        // 4. Chat Messages
        ChatMessage::create(['live_session_id' => $live1->id, 'user_id' => $buyer1->id, 'message' => 'Wah murah banget!']);
        ChatMessage::create(['live_session_id' => $live1->id, 'user_id' => $buyer2->id, 'message' => 'Bisa COD min?']);
        ChatMessage::create(['live_session_id' => $live1->id, 'user_id' => $seller1->id, 'message' => 'Bisa kak, langsung checkout aja ya!']);

        // 5. Orders
        Order::create([
            'buyer_id'         => $buyer1->id,
            'seller_id'        => $seller1->id,
            'product_id'       => $p1->id,
            'live_session_id'  => $live1->id,
            'quantity'         => 2,
            'unit_price'       => 50000,
            'total_price'      => 100000,
            'status'           => 'processed',
            'shipping_address' => 'Jl. Sudirman No 1',
        ]);

        Order::create([
            'buyer_id'         => $buyer2->id,
            'seller_id'        => $seller2->id,
            'product_id'       => $p3->id,
            'quantity'         => 1,
            'unit_price'       => 120000,
            'total_price'      => 120000,
            'status'           => 'pending',
            'shipping_address' => 'Jl. Thamrin No 2',
        ]);
    }
}
