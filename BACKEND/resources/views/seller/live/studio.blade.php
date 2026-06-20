@extends('layouts.app')
@section('title', 'Live Studio')

@section('content')
<style>
    /* Paksa video Agora agar tidak di-mirror otomatis oleh SDK */
    #agora-local-player video {
        transform: rotateY(0deg) !important;
    }
</style>
<div class="min-h-[calc(100vh-100px)] lg:h-[calc(100vh-100px)] flex flex-col lg:flex-row gap-4">
    <!-- Main Studio (Camera & Info) -->
    <div class="w-full lg:flex-1 flex flex-col gap-4 h-[50vh] lg:h-auto relative">
        <!-- Header -->
        <div class="bg-white rounded-2xl shadow-sm border border-blue-50 p-4 flex items-center justify-between">
            <div>
                <div class="flex items-center gap-2 mb-1">
                    <span class="flex items-center gap-1.5 bg-red-100 text-red-600 text-xs font-bold px-3 py-1 rounded-full">
                        <span class="w-1.5 h-1.5 bg-red-500 rounded-full animate-pulse"></span> LIVE
                    </span>
                    <h2 class="font-bold text-lg text-slate-800">{{ $session->title }}</h2>
                </div>
                <p class="text-sm text-slate-500">{{ $session->description ?? 'Tidak ada deskripsi' }}</p>
            </div>
            <form action="{{ route('seller.live.end', $session->id) }}" method="POST" onsubmit="return confirm('Akhiri siaran live ini?')">
                @csrf @method('PATCH')
                <button type="submit" class="bg-red-50 hover:bg-red-100 text-red-600 font-bold px-5 py-2.5 rounded-xl transition-colors shadow-sm flex items-center gap-2">
                    <ion-icon name="stop-circle" class="text-xl"></ion-icon> Akhiri Live
                </button>
            </form>
        </div>

        <!-- Camera View (Simulated Video feed) -->
        <div id="cameraContainer" class="bg-black rounded-2xl overflow-hidden relative shadow-inner group" style="aspect-ratio: 4/3; width: 100%;">
            <!-- Wadah khusus untuk video Agora -->
            <div id="agora-local-player" class="absolute inset-0 z-0"></div>
            
            <div class="absolute inset-0 flex flex-col items-center justify-center text-white/50 group-hover:text-white/80 transition-colors pointer-events-none" style="z-index: 50; display: none;" id="cameraOffMsg">
                <ion-icon name="videocam-off-outline" class="text-6xl mb-2"></ion-icon>
                <p class="font-medium">Kamera Mati</p>
            </div>

            <!-- STATUS AGORA -->
            <div id="agoraStatus" class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 bg-black/70 text-yellow-300 font-bold px-4 py-2 rounded-xl" style="z-index: 60;">
                Menghubungkan ke Server...
            </div>
            
            <div class="absolute top-4 left-4 flex gap-2" style="z-index: 50;">
                <div class="bg-black/50 backdrop-blur-md px-3 py-1.5 rounded-lg text-white text-xs font-medium flex items-center gap-2">
                    <ion-icon name="eye"></ion-icon> <span id="viewerCount">0</span>
                </div>
                <div class="bg-black/50 backdrop-blur-md px-3 py-1.5 rounded-lg text-white text-xs font-medium flex items-center gap-2">
                    <ion-icon name="time"></ion-icon> <span id="liveDuration">00:00:00</span>
                </div>
            </div>

            <div class="absolute bottom-4 left-1/2 -translate-x-1/2 flex items-center gap-3" style="z-index: 50;">
                <button onclick="toggleMic()" id="micBtn" class="w-12 h-12 rounded-full bg-white/20 hover:bg-white/30 backdrop-blur-md text-white flex items-center justify-center transition-all shadow-lg" title="Mute/Unmute">
                    <ion-icon name="mic" class="text-2xl"></ion-icon>
                </button>
                <button onclick="toggleCam()" id="camBtn" class="w-12 h-12 rounded-full bg-white/20 hover:bg-white/30 backdrop-blur-md text-white flex items-center justify-center transition-all shadow-lg" title="Matikan/Nyalakan Kamera">
                    <ion-icon name="videocam" class="text-2xl"></ion-icon>
                </button>
                <button onclick="switchCamera()" class="w-12 h-12 rounded-full bg-white/20 hover:bg-white/30 backdrop-blur-md text-white flex items-center justify-center transition-all shadow-lg" title="Putar Kamera Depan/Belakang">
                    <ion-icon name="camera-reverse" class="text-2xl"></ion-icon>
                </button>
                <button onclick="toggleRatio()" id="ratioBtn" class="px-4 h-12 rounded-full bg-white/20 hover:bg-white/30 backdrop-blur-md text-white flex items-center justify-center font-bold text-sm transition-all shadow-lg" title="Ganti Rasio Ukuran Layar">
                    4:3
                </button>
            </div>
        </div>
    </div>

    <!-- Right Sidebar (Products & Chat) -->
    <div class="w-full lg:w-96 flex flex-col gap-4 flex-1 lg:flex-none pb-4 lg:pb-0">
        <!-- Products to Pin -->
        <div class="bg-white rounded-2xl shadow-sm border border-blue-50 flex flex-col h-1/2">
            <div class="p-4 border-b border-slate-100 flex items-center justify-between">
                <h3 class="font-bold text-slate-800 flex items-center gap-2"><ion-icon name="pricetags" class="text-primary-500"></ion-icon> Produk</h3>
                <span class="text-xs bg-primary-50 text-primary-600 px-2 py-1 rounded-md font-semibold">{{ count($products) }} Produk</span>
            </div>
            <div class="p-2 overflow-y-auto flex-1 space-y-2">
                @forelse($products as $p)
                <div class="p-3 border border-slate-100 rounded-xl hover:border-primary-200 transition-colors flex items-center justify-between group">
                    <div class="flex items-center gap-3">
                        <img src="{{ $p->image_url ?? 'https://via.placeholder.com/150' }}" class="w-10 h-10 rounded-lg object-cover bg-slate-100">
                        <div>
                            <p class="text-sm font-semibold text-slate-800 line-clamp-1" title="{{ $p->name }}">{{ $p->name }}</p>
                            <p class="text-xs font-bold text-primary-600">Rp {{ number_format($p->price, 0, ',', '.') }}</p>
                        </div>
                    </div>
                    <button data-id="{{ $p->id }}" onclick="pinProduct({{ $p->id }}, this)" class="pin-btn {{ in_array($p->id, $session->pinned_products ?? []) ? 'bg-red-100 text-red-600 shadow-inner flex items-center gap-1' : 'bg-slate-100 hover:bg-primary-50 text-slate-600 hover:text-primary-600' }} text-xs font-bold px-3 py-1.5 rounded-lg transition-all shrink-0">
                        @if(in_array($p->id, $session->pinned_products ?? []))
                            <ion-icon name="pin"></ion-icon> Pinned
                        @else
                            Pin
                        @endif
                    </button>
                </div>
                @empty
                <div class="text-center text-slate-400 py-10 text-sm">Belum ada produk.</div>
                @endforelse
            </div>
            <div class="p-3 bg-blue-50/50 border-t border-slate-100 rounded-b-2xl">
                <p class="text-xs text-slate-500 text-center"><ion-icon name="information-circle-outline"></ion-icon> Produk yang di-Pin akan muncul di layar pembeli.</p>
            </div>
        </div>

        <!-- Realtime Chat -->
        <div class="bg-white rounded-2xl shadow-sm border border-blue-50 flex flex-col h-1/2">
            <div class="p-4 border-b border-slate-100">
                <h3 class="font-bold text-slate-800 flex items-center gap-2"><ion-icon name="chatbubbles" class="text-primary-500"></ion-icon> Live Chat</h3>
            </div>
            <div id="chatMessages" class="p-4 overflow-y-auto flex-1 space-y-3 bg-slate-50/50">
                <div class="text-center"><span class="text-[10px] bg-slate-200 text-slate-500 px-2 py-1 rounded-full font-medium">Selamat datang di Live Chat!</span></div>
            </div>
            <form id="chatForm" class="p-3 border-t border-slate-100 flex gap-2 bg-white rounded-b-2xl">
                <input type="text" id="chatInput" placeholder="Kirim pesan ke penonton..." class="flex-1 bg-slate-50 border border-slate-200 rounded-xl px-4 py-2 text-sm focus:outline-none focus:border-primary-300 focus:ring-1 focus:ring-primary-300 transition-all">
                <button type="submit" class="w-10 h-10 bg-primary-600 hover:bg-primary-700 text-white rounded-xl flex items-center justify-center transition-colors">
                    <ion-icon name="send" class="text-lg"></ion-icon>
                </button>
            </form>
        </div>
    </div>
</div>

<script src="https://download.agora.io/sdk/release/AgoraRTC_N-4.18.2.js"></script>
<script>
// --- Agora RTC Logic ---
const CHANNEL = "live_{{ $session->id }}";

let client = AgoraRTC.createClient({ mode: "live", codec: "vp8" });
let localTracks = {
    videoTrack: null,
    audioTrack: null
};
let isVideoOn = false;
let isAudioOn = false;

let currentFacingMode = "user"; // "user" (depan) atau "environment" (belakang)
let currentRatio = "4:3"; // "16:9" atau "4:3"

async function startCamera() {
    try {
        await client.setClientRole("host");
        
        // Ambil token dinamis dari backend Laravel kita
        const response = await fetch(`/api/agora/token?channelName=${CHANNEL}&role=publisher`);
        const tokenData = await response.json();
        
        if (!response.ok || tokenData.error || !tokenData.token) {
            let errMsg = tokenData.error || tokenData.message || "Unknown error from token server";
            alert("Gagal mengambil token Agora dari server Backend: " + errMsg);
            return;
        }

        // Ambil APP_ID langsung dari backend untuk menghindari bug cache .env di frontend
        const APP_ID = tokenData.app_id;
        const uid = await client.join(APP_ID, CHANNEL, tokenData.token, null);
        console.log("Agora Joined with UID:", uid);

        // Buat track lokal dengan setting rasio & kamera
        const tracks = await AgoraRTC.createMicrophoneAndCameraTracks({}, {
            encoderConfig: currentRatio === "16:9" ? "720p_1" : "480p_9",
            facingMode: currentFacingMode
        });
        localTracks.audioTrack = tracks[0];
        localTracks.videoTrack = tracks[1];

        // Mainkan video lokal di elemen <video id="localVideo"> tapi Agora pakai div container biasanya.
        // Biar gampang, kita play ke DOM (tapi harus pakai element parent karena Agora bikin tag <video> sendiri)
        const parentDiv = document.getElementById('agora-local-player');
        
        // Bersihkan div sebelum play agar tidak double
        parentDiv.innerHTML = '';
        localTracks.videoTrack.play(parentDiv, { mirror: false }); // Disable mirror agar tulisan tidak terbalik

        // Fix object-fit agar pas layaknya HP
        setTimeout(() => {
            const videoEl = parentDiv.querySelector('video');
            if(videoEl) {
                videoEl.style.objectFit = 'cover'; // Ubah ke cover agar menuhin kotak hitam tanpa bar
                videoEl.style.backgroundColor = '#000';
            }
        }, 500);

        await client.publish(Object.values(localTracks));
        console.log("Berhasil publish ke Agora!");
        
        document.getElementById('agoraStatus').innerText = "LIVE (Terhubung)";
        setTimeout(() => { document.getElementById('agoraStatus').style.display = 'none'; }, 2000);

        document.getElementById('cameraOffMsg').style.display = 'none';
        isVideoOn = true;
        isAudioOn = true;
    } catch (err) {
        console.error("Gagal akses kamera/Agora:", err);
        document.getElementById('agoraStatus').innerText = "ERROR: Gagal Publish";
        document.getElementById('agoraStatus').classList.replace('text-yellow-300', 'text-red-500');
        alert("Gagal konek ke Live Server. Pastikan ada kamera/mic atau token aman. Error: " + err);
    }
}

async function switchCamera() {
    if(!localTracks.videoTrack) return;
    try {
        currentFacingMode = currentFacingMode === "user" ? "environment" : "user";
        document.getElementById('agoraStatus').innerText = "Memutar Kamera...";
        document.getElementById('agoraStatus').style.display = 'block';

        // Unpublish track lama
        await client.unpublish([localTracks.videoTrack]);
        localTracks.videoTrack.close();

        // Buat track baru
        localTracks.videoTrack = await AgoraRTC.createCameraVideoTrack({
            encoderConfig: currentRatio === "16:9" ? "720p_1" : "480p_9",
            facingMode: currentFacingMode
        });

        const parentDiv = document.getElementById('agora-local-player');
        parentDiv.innerHTML = '';
        localTracks.videoTrack.play(parentDiv, { mirror: false });
        
        setTimeout(() => {
            const videoEl = parentDiv.querySelector('video');
            if(videoEl) {
                videoEl.style.objectFit = 'cover';
                videoEl.style.backgroundColor = '#000';
            }
        }, 500);

        await client.publish([localTracks.videoTrack]);
        document.getElementById('agoraStatus').style.display = 'none';
    } catch(err) {
        console.error("Gagal putar kamera", err);
        alert("Kamera lain tidak ditemukan atau sedang digunakan.");
        document.getElementById('agoraStatus').style.display = 'none';
        // Revert to user
        currentFacingMode = "user";
    }
}

async function toggleRatio() {
    if(!localTracks.videoTrack) return;
    try {
        currentRatio = currentRatio === "16:9" ? "4:3" : "16:9";
        document.getElementById('ratioBtn').innerText = currentRatio;
        document.getElementById('agoraStatus').innerText = "Mengganti Rasio...";
        document.getElementById('agoraStatus').style.display = 'block';

        // Ubah ukuran container kamera secara visual
        const container = document.getElementById('cameraContainer');
        if (currentRatio === "16:9") {
            container.style.aspectRatio = '16/9';
        } else {
            container.style.aspectRatio = '4/3';
        }

        // Unpublish track lama
        await client.unpublish([localTracks.videoTrack]);
        localTracks.videoTrack.close();

        // Buat track baru dengan resolusi baru
        localTracks.videoTrack = await AgoraRTC.createCameraVideoTrack({
            encoderConfig: currentRatio === "16:9" ? "720p_1" : "480p_9",
            facingMode: currentFacingMode
        });

        const parentDiv = document.getElementById('agora-local-player');
        parentDiv.innerHTML = '';
        localTracks.videoTrack.play(parentDiv, { mirror: false });
        
        setTimeout(() => {
            const videoEl = parentDiv.querySelector('video');
            if(videoEl) {
                videoEl.style.objectFit = 'cover';
                videoEl.style.backgroundColor = '#000';
            }
        }, 500);

        await client.publish([localTracks.videoTrack]);
        document.getElementById('agoraStatus').style.display = 'none';
    } catch(err) {
        console.error("Gagal ganti rasio", err);
        document.getElementById('agoraStatus').style.display = 'none';
    }
}

function toggleCam() {
    if(!localTracks.videoTrack) return;
    isVideoOn = !isVideoOn;
    localTracks.videoTrack.setEnabled(isVideoOn);
    document.getElementById('camBtn').innerHTML = `<ion-icon name="videocam${isVideoOn ? '' : '-off'}" class="text-2xl ${isVideoOn ? 'text-white' : 'text-red-400'}"></ion-icon>`;
    document.getElementById('cameraOffMsg').style.display = isVideoOn ? 'none' : 'flex';
}

function toggleMic() {
    if(!localTracks.audioTrack) return;
    isAudioOn = !isAudioOn;
    localTracks.audioTrack.setEnabled(isAudioOn);
    document.getElementById('micBtn').innerHTML = `<ion-icon name="mic${isAudioOn ? '' : '-off'}" class="text-2xl ${isAudioOn ? 'text-white' : 'text-red-400'}"></ion-icon>`;
}

// Start camera on load
window.addEventListener('load', startCamera);

// --- Live Duration Timer ---
let startTime = Date.now();
setInterval(() => {
    let diff = Math.floor((Date.now() - startTime) / 1000);
    let h = String(Math.floor(diff / 3600)).padStart(2, '0');
    let m = String(Math.floor((diff % 3600) / 60)).padStart(2, '0');
    let s = String(diff % 60).padStart(2, '0');
    document.getElementById('liveDuration').innerText = `${h}:${m}:${s}`;
}, 1000);

// --- Pin Product Logic ---
function pinProduct(productId, btnElement) {
    const isPinned = btnElement.innerText.includes('Unpin');
    btnElement.innerHTML = '<ion-icon name="sync" class="animate-spin"></ion-icon>';

    const endpoint = isPinned 
        ? `/api/live-sessions/{{ $session->id }}/unpin-product`
        : `/api/live-sessions/{{ $session->id }}/pin-product`;

    fetch(endpoint, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json'
        },
        body: JSON.stringify({ product_id: productId })
    })
    .then(res => res.json())
    .then(data => {
        // Toggle UI directly for this button
        if(isPinned) {
            btnElement.innerHTML = 'Pin';
            btnElement.className = 'pin-btn bg-slate-100 hover:bg-primary-50 text-slate-600 hover:text-primary-600 text-xs font-bold px-3 py-1.5 rounded-lg transition-all shrink-0';
        } else {
            // Remove unpin from others if only 1 pin is allowed, but based on prompt we just toggle this one
            btnElement.innerHTML = '<ion-icon name="pin"></ion-icon> Unpin';
            btnElement.className = 'pin-btn bg-red-100 text-red-600 text-xs font-bold px-3 py-1.5 rounded-lg shrink-0 flex items-center gap-1 shadow-inner';
        }
    })
    .catch(err => {
        console.error(err);
        btnElement.innerText = isPinned ? 'Unpin' : 'Pin';
    });
}

// --- Chat Logic ---
const chatMessages = document.getElementById('chatMessages');
const chatForm = document.getElementById('chatForm');
const chatInput = document.getElementById('chatInput');

function appendMessage(name, text, isSelf = false) {
    const msgDiv = document.createElement('div');
    if (isSelf) {
        msgDiv.className = 'flex flex-col items-end w-full animate-fade-in-up';
        msgDiv.innerHTML = `
            <div class="bg-primary-100 border border-primary-200 text-primary-900 px-3 py-2 rounded-2xl rounded-tr-sm text-sm max-w-[85%] shadow-sm">
                ${text}
            </div>
        `;
    } else {
        msgDiv.className = 'flex flex-col items-start w-full animate-fade-in-up';
        msgDiv.innerHTML = `
            <span class="text-[10px] text-slate-500 font-semibold mb-0.5 ml-1">${name}</span>
            <div class="bg-white border border-slate-200 text-slate-700 px-3 py-2 rounded-2xl rounded-tl-sm text-sm max-w-[85%] shadow-sm">
                ${text}
            </div>
        `;
    }
    chatMessages.appendChild(msgDiv);
    chatMessages.scrollTop = chatMessages.scrollHeight;
}

chatForm.addEventListener('submit', (e) => {
    e.preventDefault();
    const text = chatInput.value.trim();
    if(!text) return;
    
    appendMessage('Anda (Seller)', text, true);
    chatInput.value = '';

    // Send to API
    fetch(`/api/live-sessions/{{ $session->id }}/send-chat`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json'
        },
        body: JSON.stringify({ message: text })
    }).catch(err => console.error("Chat error:", err));
});
// --- Heart Animation ---
let heartIdCounter = 0;
function spawnHeart() {
    const heart = document.createElement('div');
    heart.innerHTML = '<ion-icon name="heart" class="text-red-500 text-3xl"></ion-icon>';
    heart.className = 'absolute bottom-20 right-10 animate-fade-in-up pointer-events-none transition-all duration-1000';
    
    // Randomize initial position slightly
    const offset = Math.floor(Math.random() * 40) - 20;
    heart.style.transform = `translateX(${offset}px) scale(${0.8 + Math.random() * 0.5})`;
    
    const container = document.getElementById('agora-local-player');
    if (container) {
        container.appendChild(heart);
    }
    
    // Float up
    setTimeout(() => {
        heart.style.transform = `translate(${offset * 2}px, -300px) scale(1.5)`;
        heart.style.opacity = '0';
    }, 50);

    // Remove from DOM
    setTimeout(() => {
        heart.remove();
    }, 1000);
}

// --- Realtime Polling (Pengganti WebSocket) ---
// Load chat history first
let lastMessageId = 0;

function fetchChat() {
    fetch(`/api/live-sessions/{{ $session->id }}/chat`)
        .then(r => r.json())
        .then(data => {
            if (Array.isArray(data) && data.length > 0) {
                const latestServerMsg = data[data.length - 1];
                if (latestServerMsg.id !== lastMessageId || data.length > chatMessages.children.length) {
                    lastMessageId = latestServerMsg.id;
                    
                    // Clear existing (except welcome message)
                    chatMessages.innerHTML = '<div class="text-center"><span class="text-[10px] bg-slate-200 text-slate-500 px-2 py-1 rounded-full font-medium">Selamat datang di Live Chat!</span></div>';
                    
                    data.forEach(msg => {
                        let senderName = msg.user?.name || msg.user?.username || 'Penonton';
                        appendMessage(senderName, msg.message, msg.user?.id == {{ $user->id }});
                    });
                }
            }
        }).catch(e => console.error("Chat polling error:", e));
}

// Initial fetch
fetchChat();

// Poll every 3 seconds
setInterval(fetchChat, 3000);

// Untuk Animasi Like (Bisa ditarik dari status API)
let lastLikesCount = {{ $session->likes_count ?? 0 }};
setInterval(() => {
    fetch(`/api/live-sessions/{{ $session->id }}/live-status`)
        .then(r => r.json())
        .then(data => {
            if (data) {
                if (data.viewer_count !== undefined) {
                    document.getElementById('viewerCount').innerText = data.viewer_count;
                }
                if (data.likes_count !== undefined && data.likes_count > lastLikesCount) {
                    let newLikes = data.likes_count - lastLikesCount;
                    for (let i = 0; i < Math.min(newLikes, 5); i++) {
                        setTimeout(spawnHeart, i * 200);
                    }
                    lastLikesCount = data.likes_count;
                }
            }
        })
        .catch(e => console.error('Error fetching live status:', e));
}, 3000);
</script>
@endsection
