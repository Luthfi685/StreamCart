<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Platform Commission Fee
    |--------------------------------------------------------------------------
    | Persentase komisi yang dipotong dari setiap transaksi yang selesai.
    | Ubah nilai di .env: PLATFORM_FEE_PERCENT=5
    */
    'platform_fee_percent' => env('PLATFORM_FEE_PERCENT', 5.0),

    /*
    |--------------------------------------------------------------------------
    | Minimum Withdrawal Amount (dalam Rupiah)
    |--------------------------------------------------------------------------
    */
    'minimum_withdrawal' => env('MINIMUM_WITHDRAWAL', 50000),

    /*
    |--------------------------------------------------------------------------
    | Admin Bank Account (Rekening Tujuan Pembayaran Buyer)
    |--------------------------------------------------------------------------
    | Info rekening ini akan ditampilkan ke Buyer saat checkout.
    | Ubah di .env sesuai rekening asli admin/platform.
    */
    'admin_bank' => [
        'name'         => env('ADMIN_BANK_NAME', 'BCA'),
        'account'      => env('ADMIN_BANK_ACCOUNT', '1234567890'),
        'account_name' => env('ADMIN_BANK_ACCOUNT_NAME', 'StreamCart Official'),
    ],
];
