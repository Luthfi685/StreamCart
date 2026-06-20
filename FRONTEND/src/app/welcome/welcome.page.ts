import { Component, ViewChild, ElementRef, CUSTOM_ELEMENTS_SCHEMA } from '@angular/core';
import { Router } from '@angular/router';

/**
 * WelcomePage — Onboarding Slides
 * ─────────────────────────────────────────────────────────────────────────────
 * Menggunakan <swiper-container> Web Component (Swiper 11 / Ionic 8).
 *
 * CATATAN PENTING untuk developer:
 * IonSlides telah DIHAPUS sejak Ionic 7. Solusinya adalah menggunakan
 * Swiper sebagai Web Component langsung. Perlu:
 *   1. `CUSTOM_ELEMENTS_SCHEMA` di module (sudah ada di welcome.module.ts)
 *   2. Register Swiper elements di main.ts (lihat instruksi di bawah)
 *
 * Cara register Swiper di main.ts:
 * ```ts
 * import { register } from 'swiper/element/bundle';
 * register();
 * ```
 */
@Component({
  selector: 'app-welcome',
  templateUrl: './welcome.page.html',
  styleUrls: ['./welcome.page.scss'],
  standalone: false,
})
export class WelcomePage {

  /** Referensi ke elemen <swiper-container> */
  @ViewChild('swiper') swiperRef!: ElementRef;

  /** Apakah user sedang di slide terakhir (slide ke-3, index 2) */
  isLastSlide = false;
  /** Apakah user sedang di slide pertama */
  isFirstSlide = true;

  /** Total jumlah slide */
  private readonly TOTAL_SLIDES = 3;

  constructor(private router: Router) {}

  /**
   * Handler event `swiperslidechange` yang dipancarkan swiper-container.
   * Cek apakah slide saat ini adalah slide terakhir.
   */
  onSlideChange(event: any) {
    // Swiper Web Component memancarkan event custom.
    // Index aktif ada di event.detail[0].activeIndex
    const swiper = event.detail[0];
    if (swiper) {
      const activeIndex = swiper.activeIndex;
      this.isFirstSlide = activeIndex === 0;
      this.isLastSlide = activeIndex === this.TOTAL_SLIDES - 1;
    }
  }

  /**
   * Arahkan ke halaman Login.
   * Tandai bahwa user sudah selesai onboarding agar
   * tidak ditampilkan lagi di sesi berikutnya.
   */
  goToLogin() {
    localStorage.setItem('sc_onboarded', 'true');
    this.router.navigate(['/login'], { replaceUrl: true });
  }

  // ── Kontrol Desktop ────────────────────────────────────────────────────────


  slideNext() {
    if (this.swiperRef?.nativeElement?.swiper) {
      this.swiperRef.nativeElement.swiper.slideNext();
    }
  }

  slidePrev() {
    if (this.swiperRef?.nativeElement?.swiper) {
      this.swiperRef.nativeElement.swiper.slidePrev();
    }
  }
}
