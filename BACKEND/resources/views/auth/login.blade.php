<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - StreamCart</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        * { font-family: 'Outfit', sans-serif; box-sizing: border-box; margin: 0; padding: 0; }

        body {
            min-height: 100vh;
            height: 100vh;
            display: flex;
            background: #f0f6ff;
            overflow: hidden; /* body itself locked, panels scroll internally */
        }

        /* Hide scrollbars but keep functionality */
        .hide-scrollbar::-webkit-scrollbar {
            display: none;
        }
        .hide-scrollbar {
            -ms-overflow-style: none;
            scrollbar-width: none;
        }

        /* ═══════════════════════════════════════
           LEFT PANEL
        ═══════════════════════════════════════ */
        .left-panel {
            flex: 1;
            background: linear-gradient(145deg, #0a4fd4 0%, #0369d1 40%, #0284c7 70%, #0ea5e9 100%);
            position: relative;
            display: flex;
            flex-direction: column;
            padding: 60px 56px;
            overflow-y: auto;
            overflow-x: hidden;
        }

        /* animated background circles */
        .left-panel::before {
            content: '';
            position: absolute;
            width: 500px; height: 500px;
            border-radius: 50%;
            background: rgba(255,255,255,0.06);
            top: -150px; right: -150px;
            animation: drift 10s ease-in-out infinite alternate;
        }
        .left-panel::after {
            content: '';
            position: absolute;
            width: 350px; height: 350px;
            border-radius: 50%;
            background: rgba(255,255,255,0.05);
            bottom: -100px; left: -80px;
            animation: drift 12s ease-in-out infinite alternate-reverse;
        }

        @keyframes drift {
            from { transform: translate(0, 0) scale(1); }
            to   { transform: translate(30px, 20px) scale(1.08); }
        }

        /* Floating bubbles */
        .bubble {
            position: absolute;
            border-radius: 50%;
            background: rgba(255,255,255,0.08);
            animation: float-up linear infinite;
        }
        @keyframes float-up {
            0%   { transform: translateY(0) scale(1); opacity: 0; }
            10%  { opacity: 1; }
            90%  { opacity: 0.6; }
            100% { transform: translateY(-120vh) scale(0.5); opacity: 0; }
        }

        /* Floating feature cards */
        .feature-card {
            background: rgba(255,255,255,0.13);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255,255,255,0.2);
            border-radius: 16px;
            padding: 16px 20px;
            display: flex;
            align-items: center;
            gap: 14px;
            animation: float-card 6s ease-in-out infinite;
        }
        .feature-card:nth-child(2) { animation-delay: -2s; }
        .feature-card:nth-child(3) { animation-delay: -4s; }

        @keyframes float-card {
            0%, 100% { transform: translateY(0px); }
            50%       { transform: translateY(-10px); }
        }

        .feature-icon {
            width: 44px; height: 44px;
            background: rgba(255,255,255,0.2);
            border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0;
        }

        /* Stats row */
        .stat-box {
            background: rgba(255,255,255,0.12);
            border: 1px solid rgba(255,255,255,0.18);
            border-radius: 14px;
            padding: 14px 20px;
            flex: 1;
            text-align: center;
        }

        /* ═══════════════════════════════════════
           RIGHT PANEL (Login Form)
        ═══════════════════════════════════════ */
        .right-panel {
            width: 480px;
            background: #ffffff;
            display: flex;
            flex-direction: column;
            padding: 48px 52px;
            position: relative;
            overflow-y: auto;
            overflow-x: hidden;
            box-shadow: -20px 0 60px rgba(3,105,209,0.08);
        }

        /* Wave decoration */
        .right-panel::before {
            content: '';
            position: absolute;
            top: 0; left: -1px; bottom: 0;
            width: 3px;
            background: linear-gradient(to bottom, transparent, #0369d1, #0ea5e9, transparent);
        }

        /* Input fields */
        .form-input {
            width: 100%;
            border: 1.5px solid #e2e8f0;
            border-radius: 12px;
            padding: 13px 16px;
            font-size: 0.92rem;
            color: #1e293b;
            outline: none;
            transition: all 0.25s ease;
            background: #f8faff;
            font-family: 'Outfit', sans-serif;
        }
        .form-input:focus {
            border-color: #0369d1;
            background: #fff;
            box-shadow: 0 0 0 4px rgba(3,105,209,0.08);
        }
        .form-input::placeholder { color: #94a3b8; }

        /* Password wrapper */
        .input-wrapper { position: relative; }
        .eye-btn {
            position: absolute;
            right: 14px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            color: #94a3b8;
            display: flex;
            align-items: center;
            padding: 4px;
            border-radius: 6px;
            transition: color 0.2s;
        }
        .eye-btn:hover { color: #0369d1; }

        /* Primary button */
        .btn-primary {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #0a4fd4 0%, #0369d1 60%, #0ea5e9 100%);
            color: white;
            border: none;
            border-radius: 12px;
            font-weight: 700;
            font-size: 0.95rem;
            cursor: pointer;
            letter-spacing: 0.03em;
            transition: all 0.3s ease;
            box-shadow: 0 4px 20px rgba(3,105,209,0.35);
            font-family: 'Outfit', sans-serif;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 30px rgba(3,105,209,0.5);
        }
        .btn-primary:active { transform: translateY(0); }

        /* Google button */
        .btn-google {
            width: 100%;
            padding: 13px;
            background: #fff;
            color: #374151;
            border: 1.5px solid #e2e8f0;
            border-radius: 12px;
            font-weight: 600;
            font-size: 0.9rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            transition: all 0.25s ease;
            text-decoration: none;
            font-family: 'Outfit', sans-serif;
        }
        .btn-google:hover {
            background: #f8faff;
            border-color: #0369d1;
            box-shadow: 0 4px 15px rgba(3,105,209,0.12);
            transform: translateY(-1px);
        }

        /* Divider */
        .divider {
            display: flex;
            align-items: center;
            gap: 12px;
            margin: 20px 0;
        }
        .divider::before, .divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: #e8eef7;
        }
        .divider span {
            font-size: 0.78rem;
            color: #94a3b8;
            letter-spacing: 0.08em;
            font-weight: 500;
        }

        /* Error */
        .error-box {
            background: #fef2f2;
            border: 1px solid #fecaca;
            border-left: 3px solid #ef4444;
            border-radius: 10px;
            padding: 12px 16px;
            color: #dc2626;
            font-size: 0.85rem;
            margin-bottom: 20px;
            display: flex;
            gap: 8px;
            align-items: flex-start;
        }

        /* Label */
        .field-label {
            display: block;
            font-size: 0.8rem;
            font-weight: 600;
            color: #475569;
            margin-bottom: 8px;
            letter-spacing: 0.05em;
            text-transform: uppercase;
        }

        /* Logo pulse animation */
        @keyframes logo-pulse {
            0%, 100% { box-shadow: 0 0 0 0 rgba(3,105,209,0.3); }
            50%       { box-shadow: 0 0 0 12px rgba(3,105,209,0); }
        }
        .logo-icon {
            animation: logo-pulse 3s ease-in-out infinite;
        }

        /* Responsive */
        @media (max-width: 900px) {
            .left-panel { display: none; }
            .right-panel { width: 100%; padding: 40px 28px; }
        }

        /* Slide-in animation for right panel */
        .right-panel {
            animation: slide-in 0.7s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }
        @keyframes slide-in {
            from { opacity: 0; transform: translateX(30px); }
            to   { opacity: 1; transform: translateX(0); }
        }

        @keyframes live-pulse {
            0%, 100% { opacity: 1; transform: scale(1); box-shadow: 0 0 0 0 rgba(74,222,128,0.5); }
            50%       { opacity: 0.8; transform: scale(1.2); box-shadow: 0 0 0 6px rgba(74,222,128,0); }
        }
    </style>
</head>
<body>

    <!-- ══════════ LEFT PANEL ══════════ -->
    <div class="left-panel hide-scrollbar">
        <!-- Floating bubbles -->
        <div id="bubbles"></div>

        <div style="margin: auto 0; width: 100%; position: relative; z-index: 10;">
            <!-- Brand -->
            <div style="display:flex; align-items:center; gap:14px; margin-bottom:48px;">
                <div style="width:48px;height:48px;background:rgba(255,255,255,0.2);border-radius:14px;display:flex;align-items:center;justify-content:center;overflow:hidden;">
                    <img src="{{ asset('images/logo.png') }}" style="width:100%; height:100%; object-fit:cover;" alt="Logo StreamCart">
                </div>
                <span style="font-size:1.5rem;font-weight:800;color:white;letter-spacing:-0.02em;">StreamCart</span>
            </div>

            <!-- Hero headline -->
            <h1 style="font-size:2.6rem;font-weight:900;color:white;line-height:1.15;letter-spacing:-0.03em;margin-bottom:16px;">
                Platform Live<br>Commerce<br>
                <span style="color:rgba(255,255,255,0.6);">Terbaik Indonesia</span>
            </h1>
            <p style="color:rgba(255,255,255,0.65);font-size:1rem;line-height:1.7;margin-bottom:40px;max-width:360px;">
                Kelola toko, live streaming, dan pantau transaksi Anda dari satu dashboard yang powerful dan mudah digunakan.
            </p>

            <!-- Feature cards -->
            <div style="display:flex;flex-direction:column;gap:14px;margin-bottom:40px;">
                <div class="feature-card">
                    <div class="feature-icon">
                        <svg width="20" height="20" fill="white" viewBox="0 0 24 24"><path d="M15 10l4.553-2.069A1 1 0 0121 8.845v6.31a1 1 0 01-1.447.894L15 14M3 8a2 2 0 012-2h8a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2V8z"/></svg>
                    </div>
                    <div>
                        <p style="color:white;font-weight:700;font-size:0.95rem;">Live Streaming Commerce</p>
                        <p style="color:rgba(255,255,255,0.6);font-size:0.82rem;margin-top:2px;">Jual produk langsung saat live dengan checkout real-time</p>
                    </div>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <svg width="20" height="20" fill="none" stroke="white" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                    </div>
                    <div>
                        <p style="color:white;font-weight:700;font-size:0.95rem;">Chat Real-time dengan Pembeli</p>
                        <p style="color:rgba(255,255,255,0.6);font-size:0.82rem;margin-top:2px;">Interaksi langsung dengan ratusan pembeli sekaligus</p>
                    </div>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <svg width="20" height="20" fill="none" stroke="white" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                    </div>
                    <div>
                        <p style="color:white;font-weight:700;font-size:0.95rem;">Sistem Escrow Aman</p>
                        <p style="color:rgba(255,255,255,0.6);font-size:0.82rem;margin-top:2px;">Dana tersimpan aman, cair otomatis setelah pesanan diterima</p>
                    </div>
                </div>
            </div>

            <!-- Stats (Real-time from DB) -->
            <div style="display:flex;gap:12px;">
                <div class="stat-box">
                    <p id="stat-sellers" style="font-size:1.6rem;font-weight:900;color:white;">{{ number_format($stats['sellers']) }}</p>
                    <p style="font-size:0.78rem;color:rgba(255,255,255,0.6);margin-top:2px;">Seller Aktif</p>
                </div>
                <div class="stat-box">
                    <p id="stat-products" style="font-size:1.6rem;font-weight:900;color:white;">{{ number_format($stats['products']) }}</p>
                    <p style="font-size:0.78rem;color:rgba(255,255,255,0.6);margin-top:2px;">Total Produk</p>
                </div>
                <div class="stat-box">
                    <p id="stat-buyers" style="font-size:1.6rem;font-weight:900;color:white;">{{ number_format($stats['buyers']) }}</p>
                    <p style="font-size:0.78rem;color:rgba(255,255,255,0.6);margin-top:2px;">Buyer Aktif</p>
                </div>
            </div>

            <!-- Live indicator -->
            <div style="margin-top:16px;display:flex;align-items:center;gap:8px;">
                <span id="live-dot" style="width:8px;height:8px;border-radius:50%;background:#4ade80;display:inline-block;animation:live-pulse 1.5s ease-in-out infinite;"></span>
                <span style="font-size:0.78rem;color:rgba(255,255,255,0.5);">Live diperbarui tiap 10 detik</span>
                <span id="live-sessions" style="margin-left:auto;font-size:0.78rem;color:rgba(255,255,255,0.8);font-weight:600;">{{ $stats['lives'] }} Sesi Live</span>
            </div>
        </div>
    </div>

    <!-- ══════════ RIGHT PANEL (LOGIN) ══════════ -->
    <div class="right-panel hide-scrollbar">
        <div style="margin: auto 0; width: 100%;">
            <!-- Logo for mobile -->
            <div style="display:none;align-items:center;gap:10px;margin-bottom:32px;" class="mobile-logo">
                <div style="width:36px;height:36px;background:linear-gradient(135deg,#0369d1,#0ea5e9);border-radius:10px;display:flex;align-items:center;justify-content:center;overflow:hidden;">
                    <img src="{{ asset('images/logo.png') }}" style="width:100%; height:100%; object-fit:cover;" alt="Logo StreamCart">
                </div>
            <span style="font-size:1.3rem;font-weight:800;color:#0369d1;">StreamCart</span>
        </div>

            <!-- Header -->
            <div style="margin-bottom:36px;">
                <div class="logo-icon" style="width:56px;height:56px;background:linear-gradient(135deg,#0a4fd4,#0ea5e9);border-radius:16px;display:flex;align-items:center;justify-content:center;margin-bottom:20px;overflow:hidden;">
                    <img src="{{ asset('images/logo.png') }}" style="width:100%; height:100%; object-fit:cover;" alt="Logo StreamCart">
                </div>
            <h2 style="font-size:1.75rem;font-weight:800;color:#0f172a;letter-spacing:-0.02em;">Selamat Datang</h2>
            <p style="color:#64748b;font-size:0.92rem;margin-top:6px;">Masuk ke Dashboard Seller & Admin</p>
        </div>

        <!-- Error -->
        @if($errors->any())
        <div class="error-box">
            <svg style="width:16px;height:16px;flex-shrink:0;margin-top:1px;" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
            </svg>
            <span>{{ $errors->first() }}</span>
        </div>
        @endif

        @if(session('status'))
        <div style="background:#f0fdf4;border:1px solid #bbf7d0;border-left:3px solid #22c55e;border-radius:10px;padding:12px 16px;color:#16a34a;font-size:0.85rem;margin-bottom:20px;">
            {{ session('status') }}
        </div>
        @endif

        <!-- Form -->
        <form action="{{ route('login') }}" method="POST" id="loginForm">
            @csrf

            <!-- Email -->
            <div style="margin-bottom:20px;">
                <label class="field-label" for="email">Alamat Email</label>
                <input type="email"
                       name="email"
                       id="email"
                       value="{{ old('email') }}"
                       placeholder="seller@example.com"
                       class="form-input"
                       autocomplete="email"
                       required>
            </div>

            <!-- Password -->
            <div style="margin-bottom:28px;">
                <label class="field-label" for="password">Password</label>
                <div class="input-wrapper">
                    <input type="password"
                           name="password"
                           id="password"
                           placeholder="Masukkan password Anda"
                           class="form-input"
                           style="padding-right:48px;"
                           autocomplete="current-password"
                           required>
                    <button type="button" class="eye-btn" id="togglePassword" title="Tampilkan/Sembunyikan Password">
                        <!-- Eye open icon -->
                        <svg id="eyeOpen" xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                            <circle cx="12" cy="12" r="3"/>
                        </svg>
                        <!-- Eye closed icon (hidden by default) -->
                        <svg id="eyeClosed" xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" style="display:none;">
                            <path d="M17.94 17.94A10.07 10.07 0 0112 20c-7 0-11-8-11-8a18.45 18.45 0 015.06-5.94"/>
                            <path d="M9.9 4.24A9.12 9.12 0 0112 4c7 0 11 8 11 8a18.5 18.5 0 01-2.16 3.19"/>
                            <line x1="1" y1="1" x2="23" y2="23"/>
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Submit -->
            <button type="submit" class="btn-primary" id="submitBtn">
                Masuk ke Dashboard
            </button>
        </form>

        <!-- Divider -->
        <div class="divider">
            <span>ATAU LANJUTKAN DENGAN</span>
        </div>

        <!-- Google Button -->
        <a href="{{ route('auth.google') }}" class="btn-google">
            <svg width="20" height="20" viewBox="0 0 24 24">
                <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
            </svg>
            Masuk dengan Google
        </a>

            <!-- Bottom note -->
            <p style="text-align:center;color:#94a3b8;font-size:0.75rem;margin-top:32px;line-height:1.6;">
                Pembeli? Gunakan aplikasi mobile
                <span style="color:#0369d1;font-weight:600;">StreamCart</span>
            </p>
        </div>
    </div>

    <script>
        // ── Toggle Password Visibility ──
        const toggleBtn = document.getElementById('togglePassword');
        const passwordInput = document.getElementById('password');
        const eyeOpen = document.getElementById('eyeOpen');
        const eyeClosed = document.getElementById('eyeClosed');

        toggleBtn.addEventListener('click', () => {
            const isPassword = passwordInput.type === 'password';
            passwordInput.type = isPassword ? 'text' : 'password';
            eyeOpen.style.display  = isPassword ? 'none' : 'block';
            eyeClosed.style.display = isPassword ? 'block' : 'none';
        });

        // ── Submit loading state ──
        document.getElementById('loginForm').addEventListener('submit', function () {
            const btn = document.getElementById('submitBtn');
            btn.innerHTML = `<svg style="display:inline;width:18px;height:18px;margin-right:8px;animation:spin 1s linear infinite;" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2"><path d="M21 12a9 9 0 11-6.219-8.56"/></svg>Memproses...`;
            btn.disabled = true;
        });

        // ── Spin keyframe ──
        const style = document.createElement('style');
        style.textContent = '@keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }';
        document.head.appendChild(style);

        // ── Floating bubbles for left panel ──
        const bubblesContainer = document.getElementById('bubbles');
        const colors = [
            'rgba(255,255,255,0.07)',
            'rgba(255,255,255,0.05)',
            'rgba(255,255,255,0.09)',
            'rgba(14,165,233,0.15)',
        ];
        for (let i = 0; i < 18; i++) {
            const size = 20 + Math.random() * 80;
            const b = document.createElement('div');
            b.className = 'bubble';
            b.style.cssText = `
                width: ${size}px;
                height: ${size}px;
                left: ${Math.random() * 100}%;
                bottom: ${-size}px;
                background: ${colors[Math.floor(Math.random() * colors.length)]};
                animation-duration: ${10 + Math.random() * 20}s;
                animation-delay: ${Math.random() * -25}s;
            `;
            bubblesContainer.appendChild(b);
        }

        // ── Real-time stats polling every 10 seconds ──
        function animateNumber(el, newVal) {
            const oldVal = parseInt(el.textContent.replace(/\D/g, '')) || 0;
            if (oldVal === newVal) return;
            const duration = 800;
            const start = performance.now();
            const step = (now) => {
                const progress = Math.min((now - start) / duration, 1);
                const eased = 1 - Math.pow(1 - progress, 3);
                el.textContent = Math.round(oldVal + (newVal - oldVal) * eased).toLocaleString('id-ID');
                if (progress < 1) requestAnimationFrame(step);
            };
            requestAnimationFrame(step);
        }

        function fetchStats() {
            fetch('/api/login-stats')
                .then(r => r.json())
                .then(data => {
                    animateNumber(document.getElementById('stat-sellers'),  data.sellers);
                    animateNumber(document.getElementById('stat-products'), data.products);
                    animateNumber(document.getElementById('stat-buyers'),   data.buyers);
                    document.getElementById('live-sessions').textContent = data.lives + ' Sesi Live';
                })
                .catch(() => {}); // silent fail
        }

        // Poll every 10 seconds
        setInterval(fetchStats, 10000);
    </script>
</body>
</html>
