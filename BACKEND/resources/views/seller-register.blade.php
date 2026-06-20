<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buka Toko - StreamCart</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center p-4">
    <div class="bg-white p-8 rounded-2xl shadow-md w-full max-w-2xl mx-auto border-t-4 border-blue-600">
        
        <!-- HEADER ACCOMMODATION -->
        <div class="text-center mb-8">
            <h1 class="text-2xl font-bold text-gray-800">Buka Toko Anda</h1>
            <p class="text-gray-500 mt-2 text-sm">Lengkapi data toko untuk mulai berjualan di StreamCart.</p>
        </div>

        @if ($errors->any())
            <div class="mb-6 bg-red-50 border-l-4 border-red-500 p-4 rounded-r-lg">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-red-800">Pendaftaran Gagal:</h3>
                        <ul class="mt-1 text-sm text-red-700 list-disc list-inside">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        @endif

        <form action="/register-seller" method="POST" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">

            <div class="space-y-6">
                
                <!-- DOCKING INPUT LOGO TOKO -->
                <div class="flex flex-col items-center justify-center mb-6">
                    <label for="store_logo" class="w-24 h-24 bg-gray-100 rounded-full flex items-center justify-center border-2 border-dashed border-gray-300 cursor-pointer hover:bg-gray-200 transition relative overflow-hidden group">
                        <img id="logo_preview" class="absolute inset-0 w-full h-full object-cover hidden" alt="Preview Logo">
                        <div id="logo_placeholder" class="flex flex-col items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-gray-400 group-hover:text-blue-500 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                        </div>
                        <!-- Overlay on hover if image exists -->
                        <div class="absolute inset-0 bg-black/40 hidden group-hover:flex items-center justify-center transition-opacity opacity-0 group-hover:opacity-100" id="logo_overlay">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                            </svg>
                        </div>
                    </label>
                    <input type="file" id="store_logo" name="store_logo" accept="image/*" class="hidden" onchange="previewLogo(event)">
                    <span class="text-xs text-gray-500 mt-2 font-medium">Unggah Logo Toko</span>
                </div>

                <!-- OPTIMASI INPUT DATA TOKO -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Nama Toko</label>
                    <input type="text" name="store_name" class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none transition" placeholder="Contoh: Digital Market Hub" required>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Deskripsi Toko</label>
                    <textarea name="store_description" rows="3" class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none transition resize-none" placeholder="Ceritakan tentang toko Anda..." required></textarea>
                </div>

                <div class="pt-6 mt-6 border-t border-gray-100">
                    <h3 class="text-sm font-bold text-gray-800 mb-4 uppercase tracking-wider">Informasi Rekening Bank</h3>
                </div>

                <!-- GRID REKENING -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Nama Bank / E-Wallet</label>
                        <select name="bank_name" class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none transition bg-white" required>
                            <option value="" disabled selected>Pilih Bank / E-Wallet</option>
                            <option value="BCA">BCA (Bank Central Asia)</option>
                            <option value="Mandiri">Bank Mandiri</option>
                            <option value="BNI">BNI (Bank Negara Indonesia)</option>
                            <option value="BRI">BRI (Bank Rakyat Indonesia)</option>
                            <option value="BTN">BTN (Bank Tabungan Negara)</option>
                            <option value="BSI">BSI (Bank Syariah Indonesia)</option>
                            <option value="CIMB">CIMB Niaga</option>
                            <option value="Permata">Bank Permata</option>
                            <option value="Danamon">Bank Danamon</option>
                            <option value="Mega">Bank Mega</option>
                            <option value="Bukopin">Bank Bukopin</option>
                            <option value="Panin">Panin Bank</option>
                            <option value="OCBC">OCBC NISP</option>
                            <option value="Maybank">Maybank Indonesia</option>
                            <option value="UOB">UOB Indonesia</option>
                            <option value="Muamalat">Bank Muamalat</option>

                            <!-- Digital Banks -->
                            <option value="Blu">Blu by BCA Digital</option>
                            <option value="Jago">Bank Jago</option>
                            <option value="SeaBank">SeaBank</option>
                            <option value="Jenius">Jenius (BTPN)</option>
                            <option value="Neo">Bank Neo Commerce (NeoBank)</option>
                            <option value="Allo">Allo Bank</option>
                            <option value="Superbank">Superbank</option>

                            <!-- BPD -->
                            <option value="BJB">Bank BJB</option>
                            <option value="DKI">Bank DKI</option>
                            <option value="Jatim">Bank Jatim</option>
                            <option value="Jateng">Bank Jateng</option>

                            <!-- E-Wallet -->
                            <option value="DANA">DANA</option>
                            <option value="GoPay">GoPay</option>
                            <option value="OVO">OVO</option>
                            <option value="ShopeePay">ShopeePay</option>
                            <option value="LinkAja">LinkAja</option>
                            <option value="AstraPay">AstraPay</option>
                            <option value="iSaku">i.Saku</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Nomor Rekening / No. HP E-Wallet</label>
                        <input type="number" name="bank_account" class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none transition" required>
                    </div>
                </div>

                <!-- NAMA PEMILIK PENUH DI BAWAH GRID -->
                <div class="mt-4">
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Nama Pemilik Rekening</label>
                    <input type="text" name="bank_account_name" class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none transition" placeholder="Sesuai buku tabungan" required>
                </div>
            </div>

            <!-- RE-DESIGN BANNER AKTIVASI INSTAN -->
            <div class="mt-8 bg-blue-50/70 border-l-4 border-blue-500 p-4 rounded-r-xl flex items-start gap-4">
                <div class="bg-blue-100 p-2 rounded-full">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                </div>
                <div>
                    <p class="font-bold text-gray-800 text-sm">Aktivasi Instan</p>
                    <p class="text-gray-600 text-xs mt-1 leading-relaxed">Setelah klik Daftar, akun Anda akan otomatis menjadi Seller dan Anda bisa langsung mulai berjualan hari ini juga.</p>
                </div>
            </div>

            <!-- BUTTON & FOOTER ACTION -->
            <div class="mt-8 text-center">
                <p class="text-xs text-gray-400 mb-3">Dengan mendaftar, Anda menyetujui <a href="javascript:void(0)" onclick="alert('Syarat & Ketentuan StreamCart:\n\n1. Penjual dilarang menjual barang ilegal.\n2. Dana akan diteruskan setelah pesanan selesai.\n3. Dilarang melakukan spamming saat live streaming.')" class="text-blue-500 hover:underline">Syarat & Ketentuan</a> serta <a href="javascript:void(0)" onclick="alert('Kebijakan Privasi StreamCart:\n\n1. Kami menjaga data pribadi Anda.\n2. Data bank hanya digunakan untuk penarikan dana.\n3. Kami tidak menjual data pengguna ke pihak ketiga.')" class="text-blue-500 hover:underline">Kebijakan Privasi</a> kami.</p>
                <button type="submit" class="w-full bg-[#0058be] hover:bg-[#004a9f] text-white font-bold py-4 px-4 rounded-xl transition-all duration-300 shadow-lg shadow-blue-500/30 hover:shadow-blue-500/50 hover:-translate-y-0.5 tracking-wide text-[15px]">
                    SIMPAN & DAFTAR TOKO
                </button>
            </div>
        </form>
    </div>

    <script>
        function previewLogo(event) {
            const file = event.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const preview = document.getElementById('logo_preview');
                    const placeholder = document.getElementById('logo_placeholder');
                    const overlay = document.getElementById('logo_overlay');
                    
                    preview.src = e.target.result;
                    preview.classList.remove('hidden');
                    placeholder.classList.add('hidden');
                    overlay.classList.remove('hidden');
                }
                reader.readAsDataURL(file);
            }
        }
    </script>
</body>
</html>
