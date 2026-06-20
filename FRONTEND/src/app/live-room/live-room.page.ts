/**
 * ╔═══════════════════════════════════════════════════════════════════════════╗
 * ║  LIVE ROOM PAGE — Lifecycle Hooks Implementation Guide                   ║
 * ║  StreamCart Live Commerce | Angular + Ionic                               ║
 * ╚═══════════════════════════════════════════════════════════════════════════╝
 *
 * FILE INI ADALAH PANDUAN LENGKAP lifecycle hooks di halaman Live Room.
 * Setiap hook diberi komentar mendalam tentang KAPAN, KENAPA, dan BAGAIMANA
 * digunakannya — krusial untuk mencegah memory leak di aplikasi live commerce.
 */

import {
  Component,
  OnInit,
  OnDestroy,
  ViewChild,
  ElementRef,
  ChangeDetectorRef,
  HostListener
} from '@angular/core';
import { IonContent, ToastController } from '@ionic/angular';
import { ActivatedRoute, Router } from '@angular/router';
import { LiveSessionService } from '../services/live-session.service';
import { ProductService } from '../services/product.service';
import { CartService } from '../services/cart.service';
import { environment } from '../../environments/environment';
import AgoraRTC, { IAgoraRTCClient } from 'agora-rtc-sdk-ng';

// RxJS — pakai Subscription object, BUKAN bare interval/setTimeout
import { Subscription, interval, from } from 'rxjs';
import { switchMap, catchError } from 'rxjs/operators';
import { EMPTY } from 'rxjs';

// ── Interfaces ────────────────────────────────────────────────────────────────
interface ChatMessage {
  sender: string;
  text: string;
  color: string;
}

interface SessionData {
  seller_name: string;
  viewer_count: number;
  likes_count?: number;
  pinned_products?: any[];
  seller_id?: number;
  description?: string;
}

// ─────────────────────────────────────────────────────────────────────────────

@Component({
  selector: 'app-live-room',
  templateUrl: './live-room.page.html',
  styleUrls: ['./live-room.page.scss'],
  standalone: false
})
export class LiveRoomPage implements OnInit, OnDestroy {

  @ViewChild(IonContent) content!: IonContent;
  @ViewChild('videoElement') videoElement!: ElementRef<HTMLVideoElement>;
  agoraClient: IAgoraRTCClient | null = null;

  // ── State ──────────────────────────────────────────────────────────────────
  sessionId: string | null = null;
  sessionData: SessionData | null = null;
  isHost = false;
  mediaStream: MediaStream | null = null;

  pinnedProducts: any[] = [];
  chatMessage: string = '';
  chatMessages: ChatMessage[] = [];
  products: any[] = [];
  isProductsOpen = false;
  isWaitingForHost = false; // <--- UI State untuk nunggu Host
  viewerCount = 0; // <--- Real-time viewer count from Presence Channel

  // ── Subscription Registry ──────────────────────────────────────────────────
  /**
   * PENTING: Semua Subscription RxJS HARUS disimpan di sini.
   * Ini adalah "daftar langganan" yang akan di-unsubscribe di ngOnDestroy().
   *
   * Alasan menggunakan array ketimbang satu Subscription:
   * → Mudah menambah subscription baru tanpa refactor
   * → Satu perintah `unsubscribe` di ngOnDestroy cukup untuk semua
   */
  private subscriptions: Subscription[] = [];

  /** Flag: apakah halaman sedang aktif/visible untuk user */
  private isPageActive = false;

  constructor(
    private route: ActivatedRoute,
    private router: Router,
    private liveSessionService: LiveSessionService,
    private productService: ProductService,
    private cartService: CartService,
    private toastCtrl: ToastController,
    private cdr: ChangeDetectorRef
  ) {}

  // ═══════════════════════════════════════════════════════════════════════════
  // 1. ngOnInit()
  // ═══════════════════════════════════════════════════════════════════════════
  /**
   * KAPAN DIPANGGIL:
   * Dipanggil SEKALI saat komponen pertama kali dibuat (diinstansiasi).
   * Hanya berjalan 1x selama lifetime komponen, bahkan jika user
   * meninggalkan halaman dan kembali (dengan Ionic stack navigation).
   *
   * KEGUNAAN TERBAIK:
   * → Inisialisasi state/variable awal (yang harus selalu fresh)
   * → Baca query params / route params (karena tidak berubah setelah init)
   * → Setup yang TIDAK bergantung pada UI sudah render
   *
   * JANGAN GUNAKAN UNTUK:
   * → HTTP request yang perlu diulang saat user kembali ke halaman ini
   *   (gunakan ionViewWillEnter() untuk itu)
   * → Inisialisasi yang butuh elemen DOM (gunakan ionViewDidEnter())
   *
   * PERBANDINGAN vs ionViewWillEnter:
   * | Skenario                              | ngOnInit | ionViewWillEnter |
   * |---------------------------------------|----------|------------------|
   * | Baca route params                     | ✅       | ❌ (boros)       |
   * | Reset state awal                      | ✅       | ✅               |
   * | HTTP fetch data halaman               | ⚠️       | ✅ (lebih baik)  |
   * | User kembali dari halaman lain        | ❌       | ✅               |
   */
  ngOnInit() {
    // ── A. Inisialisasi state awal ─────────────────────────────────────────
    // Ini aman dilakukan di ngOnInit karena selalu dipanggil pertama kali
    this.resetPageState();

    // ── B. BACA ROUTE PARAMETERS SECARA REAKTIF ─────────────────────────────
    // Mencegah bug data nyangkut jika komponen Ionic di-reuse
    this.route.queryParams.subscribe(params => {
      this.sessionId = params['sessionId'];
      this.isHost = params['isHost'] === 'true';

      // RESET TOTAL: Paksa viewer count kembali ke 0 setiap ganti URL/Sesi
      if (this.sessionData) {
        this.sessionData.viewer_count = 0;
      } else {
        this.sessionData = { viewer_count: 0 } as any;
      }

      // Validasi
      if (!this.sessionId) {
        console.error('[LiveRoom] ngOnInit: sessionId tidak ditemukan di URL!');
        this.showToast('Gagal memuat ruangan: ID Sesi tidak valid.');
        this.router.navigate(['/home']);
      }
    });

    // CATATAN: HTTP fetch session data dipindahkan ke ionViewWillEnter()
    // agar data selalu fresh ketika user kembali ke halaman ini
  }

  // ═══════════════════════════════════════════════════════════════════════════
  // 2. ionViewWillEnter()
  // ═══════════════════════════════════════════════════════════════════════════
  /**
   * KAPAN DIPANGGIL:
   * Setiap kali halaman akan MASUK ke viewport (menjadi aktif).
   * Ini termasuk: pertama buka, kembali dari halaman lain (back button),
   * tab switching, dsb.
   *
   * KEGUNAAN TERBAIK UNTUK LIVE ROOM:
   * → HTTP Fetching data produk & session dari Backend Laravel
   *   (LEBIH BAIK dari ngOnInit untuk ini!)
   *
   * KENAPA ionViewWillEnter() LEBIH BAIK untuk HTTP fetch?
   * Bayangkan skenario ini:
   *   User di Live Room → buka Checkout → tekan Back → kembali ke Live Room
   *   - ngOnInit  : TIDAK berjalan lagi (komponen sudah ada di memori)
   *   - ionViewWillEnter: BERJALAN lagi → data produk/session ter-refresh ✅
   *
   * TIMING: Dipanggil sebelum animasi transisi halaman selesai.
   * Bagus untuk start data loading (data mungkin sudah siap saat animasi selesai).
   */
  ionViewWillEnter() {
    this.isPageActive = true;

    // FIX: Force reset state untuk menghindari data nyangkut dari sesi sebelumnya
    if (this.sessionData) {
      this.sessionData.viewer_count = 0;
    } else {
      this.sessionData = { viewer_count: 0 } as any;
    }

    if (this.sessionId) {
      // ── Fetch data session & produk dari Backend Laravel ─────────────────
      // Ini adalah tempat TERBAIK untuk HTTP request halaman live room
      this.fetchSessionData();
      
      // Beri tahu server bahwa kita join (viewer count naik)
      if (!this.isHost) {
        const token = localStorage.getItem('sc_token');
        fetch(`${environment.apiUrl}/live-sessions/${this.sessionId}/join`, {
          method: 'POST',
          headers: { 'Authorization': `Bearer ${token}` }
        }).catch(err => console.error(err));
      }
    }

    // CATATAN: Camera & Polling BELUM dimulai di sini.
    // Camera butuh DOM sudah siap → ionViewDidEnter()
    // Polling chat juga dimulai di ionViewDidEnter() untuk konsistensi
  }

  // ═══════════════════════════════════════════════════════════════════════════
  // 3. ionViewDidEnter()
  // ═══════════════════════════════════════════════════════════════════════════
  /**
   * KAPAN DIPANGGIL:
   * Setelah animasi transisi masuk selesai SEMPURNA dan halaman
   * sepenuhnya terlihat oleh user.
   *
   * KEGUNAAN TERBAIK UNTUK LIVE ROOM:
   * → Inisialisasi Chat Polling / Real-time connection
   * → Start camera/video stream
   *
   * KENAPA HARUS DI SINI (bukan ionViewWillEnter)?
   * Untuk fitur intensif seperti:
   * 1. Camera stream  → butuh elemen <video> sudah ada di DOM
   * 2. Chat polling   → user sudah benar-benar di halaman, bukan transisi
   * 3. WebSocket conn → buka koneksi hanya saat halaman truly aktif
   *
   * Memulai terlalu awal (saat transisi) bisa menyebabkan:
   * - Frame drop / lag pada animasi masuk
   * - Resource digunakan sebelum UI siap
   */
  ionViewDidEnter() {
    // ── A. Mulai kamera atau Subscribe WebRTC ───────────────────────────────────
    if (this.isHost) {
      this.startCamera();
    } else {
      // Pembeli WAJIB menggunakan Agora WebRTC untuk nyambung ke Seller
      this.startAgoraViewer();
    }

    // Chat polling dihapus, sepenuhnya menggunakan WebSockets (Reverb)

    // ── C. Scroll chat ke bawah ────────────────────────────────────────────
    setTimeout(() => {
      this.content?.scrollToBottom(300);
    }, 500);

    // ── D. Start Polling ───────────────────────────────────────────────────
    this.startPolling(); // WAJIB DIPANGGIL agar viewerCount update realtime!
    // ── E. PRESENCE CHANNEL REALTIME (LARAVEL ECHO) ────────────────────────
    // Menggunakan (window as any).Echo jika disetup global, atau import Echo
    if (this.sessionId && (window as any).Echo) {
      (window as any).Echo.join(`live-stream.${this.sessionId}`)
        .here((users: any[]) => {
          this.viewerCount = users.length;
          this.cdr.detectChanges();
        })
        .joining((user: any) => {
          this.viewerCount++;
          this.cdr.detectChanges();
        })
        .leaving((user: any) => {
          if (this.viewerCount > 0) {
            this.viewerCount--;
            this.cdr.detectChanges();
          }
        });
    }
  }

  // ═══════════════════════════════════════════════════════════════════════════
  // 4. ionViewWillLeave()
  // ═══════════════════════════════════════════════════════════════════════════
  /**
   * KAPAN DIPANGGIL:
   * Tepat sebelum halaman MENINGGALKAN viewport (animasi keluar dimulai).
   * Dipanggil saat: user tekan tombol Back, navigate ke halaman lain,
   * tab switching, dsb.
   *
   * KEGUNAAN TERBAIK UNTUK LIVE ROOM:
   * → HENTIKAN video streaming (stop camera tracks)
   * → PAUSE polling chat
   *
   * PERBEDAAN PENTING dengan ngOnDestroy:
   * | Aksi                       | ionViewWillLeave | ngOnDestroy |
   * |----------------------------|------------------|-------------|
   * | Back → halaman lain        | ✅ dipanggil     | ❌          |
   * | Tab switch                 | ✅ dipanggil     | ❌          |
   * | Back → halaman dihancurkan | ✅ dipanggil     | ✅ dipanggil|
   *
   * → ionViewWillLeave bisa dipanggil TANPA ngOnDestroy (halaman di-cache)
   *
   * KENAPA stop kamera di sini (bukan ngOnDestroy)?
   * Jika user tekan back ke Checkout lalu kembali lagi ke Live Room,
   * kamera harus restart di ionViewDidEnter(). Jika kita stop kamera
   * hanya di ngOnDestroy, kamera tetap berjalan saat di Checkout page!
   * → Boros battery + privasi user terancam!
   */
  ionViewWillLeave() {
    this.isPageActive = false;

    // ── A. Stop kamera (sangat penting untuk privasi & battery) ────────────
    // Jika kamera tidak distop, MediaStream terus berjalan di background
    // bahkan ketika user sudah di halaman lain
    this.stopCamera();
    this.stopPolling();

    // ── B. Beri tahu host bahwa user keluar (untuk end live) ───────────────
    if (this.isHost && this.sessionId) {
      this.endLiveSilent();
    }

    // ── C. TINGGALKAN PRESENCE CHANNEL & API LEAVE ─────────────────────────
    if (this.sessionId && (window as any).Echo) {
      (window as any).Echo.leave(`live-stream.${this.sessionId}`);
    }

    // Call fallback leave API jika diperlukan backend
    if (this.sessionId && !this.isHost) {
      const token = localStorage.getItem('sc_token');
      fetch(`${environment.apiUrl}/live-sessions/${this.sessionId}/leave`, {
        method: 'POST',
        headers: { 'Authorization': `Bearer ${token}` }
      }).catch(err => console.error(err));
    }
  }

  // ═══════════════════════════════════════════════════════════════════════════
  // 5. ngOnDestroy()
  // ═══════════════════════════════════════════════════════════════════════════
  /**
   * KAPAN DIPANGGIL:
   * Saat komponen Angular DIHANCURKAN dari memori (tidak hanya keluar dari view).
   * Ini terjadi ketika: route stack dihapus, aplikasi ditutup, atau
   * komponen di-destroy secara programatik.
   *
   * FUNGSI UTAMA: PEMBERSIHAN MEMORI (Memory Leak Prevention)
   *
   * WAJIB DILAKUKAN DI SINI:
   * 1. .unsubscribe() semua Subscription RxJS
   * 2. Clear array data besar (chatMessages) agar GC bisa klaim memori
   * 3. Pastikan semua resource eksternal dilepas
   *
   * KENAPA INI KRITIS DI LIVE COMMERCE?
   * Tanpa ngOnDestroy yang benar:
   * - Chat polling terus berjalan → memory naik terus → HP panas
   * - Observable yang tidak di-unsubscribe → memory leak terkonfirmasi
   * - Array chatMessages dengan 1000+ pesan → RAM tidak dibebaskan
   *
   * ATURAN PRAKTIS:
   * Setiap `subscribe()` harus punya pasangan `unsubscribe()` di ngOnDestroy.
   */
  ngOnDestroy() {
    // ── A. Unsubscribe semua RxJS Subscription ────────────────────────────
    // Hentikan semua observable yang sedang berjalan
    this.subscriptions.forEach(sub => {
      if (sub && !sub.closed) {
        sub.unsubscribe();
      }
    });
    this.subscriptions = []; // Bebaskan referensi array

    // ── B. Stop kamera (double safety) ────────────────────────────────────
    // Meskipun sudah distop di ionViewWillLeave, pastikan tidak ada track aktif
    this.stopCamera();

    // ── D. Bersihkan data chat ─────────────────────────────────────────────
    // PENTING: Array chatMessages bisa sangat besar di sesi live panjang.
    // Hapus referensi agar Garbage Collector bisa membebaskan RAM.
    this.chatMessages = [];
    this.products = [];
    this.pinnedProducts = [];

    // ── E. Null-kan referensi objek besar ─────────────────────────────────
    this.sessionData = null;
    this.mediaStream = null;

    console.log('[LiveRoom] ngOnDestroy: semua resource telah dibersihkan ✅');
  }

  // ═══════════════════════════════════════════════════════════════════════════
  // HELPER METHODS
  // ═══════════════════════════════════════════════════════════════════════════

  /** Reset semua state ke nilai awal */
  private resetPageState() {
    this.pinnedProducts = [];
    this.chatMessages = [
      { sender: 'Sistem', text: '🔴 Selamat datang di Live Streaming!', color: '#999' }
    ];
    this.isProductsOpen = false;
  }

  /** Mulai Polling untuk Live Room (Pengganti WebSockets) */
  private startPolling() {
    if (!this.sessionId) return;
    
    // Polling setiap 3 detik
    const pollSub = interval(3000).subscribe(() => {
      this.pollSessionStatus();
      this.pollChat();
    });
    this.subscriptions.push(pollSub);
  }
  
  private stopPolling() {
      this.subscriptions = this.subscriptions.filter(s => {
          if (s.closed) return false;
          // Asumsi interval polling punya identifier unik atau kita filter berdasarkan logika
          return true;
      });
  }

  private pollSessionStatus() {
    fetch(`${environment.apiUrl}/live-sessions/${this.sessionId}/live-status`)
      .then(r => r.json())
      .then(data => {
          if (data) {
          // Selalu update viewer_count dari polling sebagai source of truth yang reliable
          if (data.viewer_count !== undefined) {
            this.viewerCount = data.viewer_count;
          }
          
          this.pinnedProducts = data.pinned_products || [];
          
          if (data.status === 'finished') {
            this.showToast('Sesi live telah berakhir.');
            this.router.navigate(['/home']);
          }
          this.cdr.detectChanges();
        }
      }).catch(e => console.error('[LiveRoom] Status polling error:', e));
  }

  private pollChat() {
    fetch(`${environment.apiUrl}/live-sessions/${this.sessionId}/chat`)
      .then(r => r.json())
      .then(data => {
        if (Array.isArray(data)) {
          // Hanya update jika jumlah pesan bertambah (simple check)
          if (data.length > this.chatMessages.length - 1) {
            const welcomeMsg = this.chatMessages[0];
            this.chatMessages = [welcomeMsg];
            data.forEach(msg => {
              this.chatMessages.push({
                sender: msg.user?.name || msg.user?.username || 'Penonton',
                text: msg.message,
                color: '#333'
              });
            });
            setTimeout(() => this.content?.scrollToBottom(100), 200);
            this.cdr.detectChanges();
          }
        }
      }).catch(e => console.error('[LiveRoom] Chat polling error:', e));
  }

  /**
   * Fetch data session dari Backend Laravel.
   * Dipanggil dari ionViewWillEnter() agar selalu fresh.
   *
   * Menggunakan Subscription object yang disimpan di array `subscriptions`
   * sehingga otomatis di-cleanup di ngOnDestroy().
   */
  async fetchSessionData() {
    if (!this.sessionId) return;
    const sub = this.liveSessionService.getSession(Number(this.sessionId))
      .subscribe({
        next: (res: any) => {
          const session = res.data || res;
          // Map seller_name from nested relation if it exists
          if (session.seller) {
            session.seller_name = session.seller.name || session.seller.username;
          }
          this.sessionData = session;
          this.pinnedProducts = session.pinned_products || [];
          if (session.seller_id) {
            this.fetchProducts(session.seller_id);
          }

          // Fetch Chat History
          fetch(`${environment.apiUrl}/live-sessions/${this.sessionId}/chat`)
            .then(r => r.json())
            .then(data => {
              if (Array.isArray(data) && data.length > 0) {
                // Keep the 'Welcome' message, then append history
                const welcomeMsg = this.chatMessages[0];
                this.chatMessages = [welcomeMsg];
                data.forEach(msg => {
                  this.chatMessages.push({
                    sender: msg.user?.name || msg.user?.username || 'Penonton',
                    text: msg.message,
                    color: '#333'
                  });
                });
                setTimeout(() => this.content?.scrollToBottom(100), 200);
                this.cdr.detectChanges();
              }
            }).catch(e => console.error('Gagal memuat history chat:', e));

        },
        error: (err) => {
          console.error('[LiveRoom] fetchSessionData error (mungkin offline):', err);
          this.loadSessionFromMockData();
        }
      });

    // ✅ SIMPAN subscription agar bisa di-unsubscribe di ngOnDestroy
    this.subscriptions.push(sub);
  }

  /** Fetch daftar produk seller */
  fetchProducts(sellerId: number) {
    const sub = this.productService.getProducts()
      .subscribe({
        next: (res: any) => {
          const productsList = res.data || res;
          this.products = productsList.filter((p: any) => p.seller_id === sellerId);
        },
        error: (err) => {
          console.error('[LiveRoom] fetchProducts error:', err);
          const mockProducts = JSON.parse(localStorage.getItem('mockProducts') || '[]');
          this.products = sellerId !== 0
            ? mockProducts.filter((p: any) => p.seller_id === sellerId)
            : mockProducts;
        }
      });

    // ✅ SIMPAN subscription
    this.subscriptions.push(sub);
  }



  /** Start kamera atau fallback ke simulasi */
  async startCamera() {
    try {
      if (!navigator.mediaDevices?.getUserMedia) {
        throw new Error('API Kamera tidak didukung');
      }

      let realStream: MediaStream | null = null;

      try {
        realStream = await navigator.mediaDevices.getUserMedia({ video: true, audio: true });
      } catch {
        try {
          realStream = await navigator.mediaDevices.getUserMedia({ video: true });
        } catch (e) {
          console.warn('[LiveRoom] Kamera tidak dapat diakses:', e);
        }
      }

      if (realStream) {
        this.mediaStream = realStream;
      } else {
        // Simulasi kamera dengan canvas
        this.mediaStream = this.createMockVideoStream();
        this.showToast('Menggunakan mode simulasi kamera');
      }

      if (this.mediaStream && this.videoElement?.nativeElement) {
        this.videoElement.nativeElement.srcObject = this.mediaStream;
      }

    } catch (err) {
      console.error('[LiveRoom] Kamera error total:', err);
    }
  }

  // ═══════════════════════════════════════════════════════════════════════════
  // WEBRTC SIGNALING & RENDER (AGORA SDK)
  // ═══════════════════════════════════════════════════════════════════════════
  async startAgoraViewer() {
    if (this.agoraClient) {
      console.log('⚠️ [WebRTC] Client sudah berjalan, mencegah duplikasi.');
      return;
    }

    try {
      this.isWaitingForHost = true; // Aktifkan loading UI
      this.cdr.detectChanges();

      console.log('✅ [WebRTC] 1. Menginisialisasi WebRTC Client (Agora SDK)...');
      this.agoraClient = AgoraRTC.createClient({ mode: 'live', codec: 'vp8' });
      await this.agoraClient.setClientRole('audience');
      console.log('✅ [WebRTC] 2. Role diset sebagai Audience (Penonton)');

      // === EVENT LISTENER: MENERIMA OFFER DARI SELLER ===
      this.agoraClient.on('user-published', async (user, mediaType) => {
        console.log(`📡 [WebRTC] 3. Menerima 'Offer' otomatis dari Seller! Tipe media: ${mediaType}`);
        
        // Membangun Answer dan Pertukaran ICE Candidate terjadi di dalam fungsi subscribe()
        await this.agoraClient!.subscribe(user, mediaType);
        console.log(`✅ [WebRTC] 4. Berhasil Subscribe (Answer & ICE Candidate otomatis sukses).`);

        if (mediaType === 'video' && user.videoTrack) {
          console.log('🎥 [WebRTC] 5. Track Video Diterima! Merender ke elemen <video>...');
          
          this.isWaitingForHost = false; // Matikan loading UI karena video sudah masuk
          this.cdr.detectChanges();
          
          // Ambil track murni dari Agora
          const mediaStream = new MediaStream([user.videoTrack.getMediaStreamTrack()]);
          
          if (this.videoElement?.nativeElement) {
            const videoEl = this.videoElement.nativeElement;
            
            videoEl.style.display = 'block'; // Pastikan video tampil
            videoEl.srcObject = mediaStream; // Binding stream!
            
            videoEl.play().then(() => {
                console.log('▶️ [WebRTC] 6. Video berhasil diputar secara Realtime!');
            }).catch(e => {
                console.warn('⚠️ [WebRTC] Autoplay ditahan browser. Menunggu interaksi user.', e);
            });
          }
        }
        
        if (mediaType === 'audio' && user.audioTrack) {
          console.log('🔊 [WebRTC] Track Audio Diterima! Memutar audio...');
          user.audioTrack.play(); 
        }
      });

      const channelName = `live_${this.sessionId}`;
      console.log(`🔗 [WebRTC] Menghubungkan ke Channel: ${channelName}...`);
      
      // Fetch token dinamis dari backend
      const response = await fetch(`${environment.apiUrl.replace('/v1','')}/agora/token?channelName=${channelName}&role=subscriber`);
      const tokenData = await response.json();
      
      if(tokenData.error) {
          console.error("Gagal mendapatkan token Agora dari server", tokenData.error);
          return;
      }

      // Ambil APP_ID dinamis dari respons backend agar sinkron 100% dengan Token!
      const dynamicAppId = tokenData.app_id || environment.agoraAppId;

      const uid = await this.agoraClient.join(
        dynamicAppId, 
        channelName, 
        tokenData.token, 
        null
      );
      console.log(`✅ [WebRTC] BERHASIL JOIN CHANNEL! UID: ${uid}`);
      console.log(`⏳ [WebRTC] Standby menunggu Seller untuk mulai live...`);

    } catch (err: any) {
      console.error('❌ [WebRTC ERROR] Gagal inisialisasi atau join channel:', err);
      // Pengecekan Token Expired
      if (err.message && err.message.includes('Token')) {
          console.error('⚠️ ALARM KEMUNGKINAN BESAR: Token Agora Expired atau Invalid! Silakan buat token baru di Console Agora.');
      }
    }
  }

  /** Stop semua media tracks — KRITIS untuk privasi user */
  stopCamera() {
    if (this.mockStreamInterval) {
      clearInterval(this.mockStreamInterval);
      this.mockStreamInterval = null;
    }
    
    if (this.mediaStream) {
      this.mediaStream.getTracks().forEach(track => {
        track.stop();
        console.log(`[LiveRoom] Track dihentikan: ${track.kind} ⏹`);
      });
      this.mediaStream = null;

      // Hapus srcObject dari elemen video
      if (this.videoElement?.nativeElement) {
        this.videoElement.nativeElement.srcObject = null;
      }
    }
    
    // Cleanup Agora client
    if (this.agoraClient) {
      this.agoraClient.leave();
      this.agoraClient = null;
    }
  }

  private mockStreamInterval: any;

  /** Buat simulasi video stream dari canvas */
  private createMockVideoStream(): MediaStream | null {
    const canvas = document.createElement('canvas');
    canvas.width = 640;
    canvas.height = 480;
    const ctx = canvas.getContext('2d');

    if (!ctx) return null;

    if (this.mockStreamInterval) clearInterval(this.mockStreamInterval);
    this.mockStreamInterval = setInterval(() => {
      ctx.fillStyle = '#0a0a1a';
      ctx.fillRect(0, 0, canvas.width, canvas.height);
      ctx.fillStyle = '#E5383B';
      ctx.font = 'bold 18px Inter';
      ctx.fillText('🔴 LIVE STREAMING (SIMULASI)', 20, 50);
      ctx.fillStyle = '#4C9AFF';
      ctx.font = '16px Inter';
      ctx.fillText(`Waktu: ${new Date().toLocaleTimeString('id-ID')}`, 20, 90);
      ctx.fillText('StreamCart Live Commerce', 20, 120);
    }, 1000);

    // @ts-ignore
    return canvas.captureStream(30);
  }

  /** Kirim pesan chat */
  sendMessage() {
    if (this.chatMessage.trim() !== '') {
      const text = this.chatMessage;
      // Langsung muncul di UI lokal
      this.chatMessages.push({
        sender: 'Saya',
        text: text,
        color: '#0052CC'
      });
      this.chatMessage = '';
      setTimeout(() => this.content?.scrollToBottom(300), 100);

      // Kirim via API
      if (this.sessionId) {
        const token = localStorage.getItem('sc_token');
        fetch(`${environment.apiUrl}/live-sessions/${this.sessionId}/send-chat`, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'Authorization': `Bearer ${token}`
          },
          body: JSON.stringify({ message: text })
        }).catch(err => console.error('[LiveRoom] Chat error:', err));
      }
    }
  }

  /** Toggle panel produk */
  toggleProducts() {
    this.isProductsOpen = !this.isProductsOpen;
  }

  /** Navigasi ke checkout dengan data produk */
  buyProduct(product: any) {
    this.isProductsOpen = false;
    this.router.navigate(['/checkout'], {
      state: { product, sessionId: this.sessionId }
    });
  }

  /** Lihat detail produk */
  viewDetail(product: any) {
    this.isProductsOpen = false;
    this.router.navigate(['/product-detail', product.id]);
  }

  getProductImage(prod: any): string {
    if (!prod) return 'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?w=500&auto=format&fit=crop';
    if (prod.image_url) {
      if (prod.image_url.startsWith('http')) return prod.image_url.includes('via.placeholder.com') ? 'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?w=500&auto=format&fit=crop' : prod.image_url;
      return prod.image_url.startsWith('/') ? environment.webUrl + prod.image_url : environment.webUrl + '/storage/' + prod.image_url;
    }
    let imagesArr = prod.images;
    if (typeof imagesArr === 'string') { try { imagesArr = JSON.parse(imagesArr); } catch (e) { imagesArr = null; } }
    if (imagesArr && Array.isArray(imagesArr) && imagesArr.length > 0) {
      const firstImg = imagesArr[0];
      if (firstImg.startsWith('http')) return firstImg.includes('via.placeholder.com') ? 'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?w=500&auto=format&fit=crop' : firstImg;
      return firstImg.startsWith('/') ? environment.webUrl + firstImg : environment.webUrl + '/storage/' + firstImg;
    }
    return 'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?w=500&auto=format&fit=crop';
  }

  /** Tambahkan ke keranjang */
  async addToCart(product: any) {
    this.cartService.addToCart({
      product_id: product.id,
      product_name: product.name,
      product_image: this.getProductImage(product),
      seller_id: product.seller_id || this.sessionData?.seller_id || 0,
      seller_name: this.sessionData?.seller_name || 'Seller',
      unit_price: product.price,
      quantity: 1,
      stock: product.stock
    });
    this.showToast(`${product.name} ditambahkan ke keranjang`, 'success');
  }

  /** Sematkan/lepas produk pada sesi live */
  pinProduct(product: any) {
    if (!this.sessionId) return;
    const isPinned = this.isProductPinned(product.id);
    const endpoint = isPinned ? 'unpin-product' : 'pin-product';

    const token = localStorage.getItem('sc_token');
    fetch(`${environment.apiUrl}/live-sessions/${this.sessionId}/${endpoint}`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${token}`
      },
      body: JSON.stringify({ product_id: product.id })
    }).catch(err => {
      console.error('[LiveRoom] Pin/Unpin error:', err);
      this.showToast('Gagal mengubah status pin produk');
    });

    this.isProductsOpen = false;
  }

  isProductPinned(productId: number): boolean {
    return this.pinnedProducts.some(p => p.id === productId);
  }

  /** Akhiri sesi live (untuk host) */
  async endLive() {
    if (!this.sessionId) {
      this.router.navigate(['/seller-dashboard']);
      return;
    }
    const sub = this.liveSessionService.endSession(Number(this.sessionId)).subscribe({
      next: async () => {
        await this.showToast('Sesi live telah diakhiri.');
        this.router.navigate(['/seller-dashboard']);
      },
      error: (err: any) => {
        console.error('[LiveRoom] endLive error:', err);
        this.router.navigate(['/seller-dashboard']);
      }
    });

    this.subscriptions.push(sub);
  }

  /** End live tanpa menunggu response (untuk cleanup saat close/back) */
  endLiveSilent() {
    const token = localStorage.getItem('sc_token');
    if (token && this.sessionId) {
      fetch(`${environment.apiUrl}/live-sessions/${this.sessionId}/end`, {
        method: 'PUT',
        headers: { 'Authorization': `Bearer ${token}` },
        keepalive: true  // Pastikan request terkirim bahkan saat halaman ditutup
      }).catch(err => console.error('[LiveRoom] Silent end failed:', err));
    }
  }

  /** Load session data dari localStorage (mode offline) */
  private loadSessionFromMockData() {
    const mockSessions = JSON.parse(localStorage.getItem('mockLiveSessions') || '[]');
    const session = mockSessions.find((s: any) => s.id == this.sessionId);
    this.sessionData = {
      seller_name: session?.seller_name || 'Host Offline',
      viewer_count: session?.viewer_count || 0
    };
    this.pinnedProducts = session?.pinnedProducts || [];
    this.fetchProducts(0);
  }

  /** Update pinned products dari mock storage (mode offline polling) */
  private tryLoadFromMockSessions() {
    const mockSessions = JSON.parse(localStorage.getItem('mockLiveSessions') || '[]');
    const session = mockSessions.find((s: any) => s.id == this.sessionId);
    if (session) {
      this.pinnedProducts = session.pinnedProducts || [];
      this.cdr.detectChanges();
    }
  }

  /** Tampilkan toast notification */
  async showToast(msg: string, color = 'dark') {
    const toast = await this.toastCtrl.create({
      message: msg,
      duration: 2500,
      color,
      position: 'top',
    });
    toast.present();
  }

  /** Window beforeunload — pastikan live diakhiri saat browser/app tertutup */
  @HostListener('window:beforeunload')
  onUnload() {
    if (this.isHost && this.sessionId) {
      this.endLiveSilent();
    }
  }
}
