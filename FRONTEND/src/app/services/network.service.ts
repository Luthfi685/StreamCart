import { Injectable, OnDestroy } from '@angular/core';
import { BehaviorSubject, fromEvent, merge, Subscription } from 'rxjs';
import { map, distinctUntilChanged } from 'rxjs/operators';

/**
 * NetworkService — Global Internet Status Monitor
 * ─────────────────────────────────────────────────────────────────────────────
 * Memantau status koneksi internet menggunakan Web API standard:
 *  - `navigator.onLine`          → nilai awal
 *  - `window` event "online"     → kembali terhubung
 *  - `window` event "offline"    → koneksi terputus
 *
 * Kenapa Web API (bukan @capacitor/network)?
 *  → Bekerja di browser, emulator, DAN perangkat Capacitor.
 *  → Tidak membutuhkan instalasi plugin tambahan.
 *
 * Cara pakai di komponen lain:
 * ```ts
 * constructor(private network: NetworkService) {}
 *
 * ngOnInit() {
 *   this.network.isOnline$.subscribe(online => {
 *     console.log('Status jaringan:', online ? 'ONLINE' : 'OFFLINE');
 *   });
 * }
 * ```
 */
@Injectable({
  providedIn: 'root'   // Singleton di seluruh aplikasi
})
export class NetworkService implements OnDestroy {

  /**
   * BehaviorSubject — memancarkan status koneksi saat ini.
   * - Nilai awal: `navigator.onLine` (true/false langsung dari browser)
   * - Setiap subscriber mendapat nilai TERBARU saat subscribe (replay 1 nilai)
   */
  private readonly _isOnline$ = new BehaviorSubject<boolean>(navigator.onLine);

  /** Observable publik — subscribe dari komponen/service lain */
  readonly isOnline$ = this._isOnline$.asObservable().pipe(
    distinctUntilChanged()  // Hanya emit jika status BENAR-BENAR berubah
  );

  /** Subscription ke event browser — WAJIB unsubscribe saat service destroyed */
  private eventSub!: Subscription;

  constructor() {
    this.startMonitoring();
  }

  /**
   * Mulai memantau event jaringan dari browser.
   * Menggunakan RxJS `merge` untuk menggabungkan 2 stream event menjadi 1.
   */
  private startMonitoring(): void {
    const online$  = fromEvent(window, 'online').pipe(map(() => true));
    const offline$ = fromEvent(window, 'offline').pipe(map(() => false));

    this.eventSub = merge(online$, offline$).subscribe((status: boolean) => {
      console.log(`[NetworkService] Status jaringan berubah → ${status ? 'ONLINE' : 'OFFLINE'}`);
      this._isOnline$.next(status);
    });
  }

  /**
   * Ambil status koneksi SAAT INI secara synchronous.
   * Berguna jika tidak perlu reactive, cukup cek sekali saat diperlukan.
   *
   * @example
   * if (!this.network.isCurrentlyOnline()) {
   *   this.showCachedData();
   * }
   */
  isCurrentlyOnline(): boolean {
    return this._isOnline$.getValue();
  }

  /** Cleanup — dipanggil Angular saat service di-destroy (jarang terjadi untuk root service) */
  ngOnDestroy(): void {
    if (this.eventSub) {
      this.eventSub.unsubscribe();
    }
    this._isOnline$.complete();
  }
}
