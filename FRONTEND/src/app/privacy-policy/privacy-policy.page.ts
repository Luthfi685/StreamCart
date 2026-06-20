import { Component, ViewChild } from '@angular/core';
import { IonContent } from '@ionic/angular';
import { Router } from '@angular/router';

/**
 * PrivacyPolicyPage — Kebijakan Privasi
 * ─────────────────────────────────────────────────────────────────────────────
 *
 * Fitur Utama:
 * 1. Menampilkan dokumen kebijakan privasi lengkap dengan HTML semantik
 * 2. Memantau posisi scroll via `(ionScroll)` event dari IonContent
 * 3. Tombol "Setuju & Lanjutkan" di footer HANYA aktif setelah user
 *    scroll sampai ke bagian paling bawah dokumen
 *
 * Logika Deteksi Scroll:
 * - `scrollTop`   : posisi scroll saat ini dari atas
 * - `scrollHeight`: total tinggi konten (seluruh dokumen)
 * - `clientHeight`: tinggi viewport yang terlihat
 *
 * Formula: scrollTop + clientHeight >= scrollHeight - threshold
 * Jika TRUE → user sudah di bagian bawah → aktifkan tombol
 */
@Component({
  selector: 'app-privacy-policy',
  templateUrl: './privacy-policy.page.html',
  styleUrls: ['./privacy-policy.page.scss'],
  standalone: false,
})
export class PrivacyPolicyPage {

  /** Referensi ke IonContent untuk bisa membaca scroll position */
  @ViewChild('policyContent', { read: IonContent }) content!: IonContent;

  /**
   * Flag utama: apakah user sudah scroll ke bagian paling bawah?
   * Mengendalikan state [disabled] pada tombol footer.
   */
  hasScrolledToBottom = false;

  /**
   * Progress scroll dalam persentase (0–100).
   * Digunakan untuk progress bar di header.
   */
  scrollProgress = 0;

  /**
   * Threshold piksel dari bawah — anggap "sudah di bawah"
   * jika posisi scroll dalam jangkauan ini dari ujung dokumen.
   * Nilai 80px memberikan UX yang nyaman (tidak perlu scroll persis ke pixel terakhir).
   */
  private readonly BOTTOM_THRESHOLD = 80;

  private scrollElement: HTMLElement | null = null;

  constructor(private router: Router) {}

  /**
   * Cache scroll element setelah view selesai dimuat
   * agar tidak perlu di-await berkali-kali di dalam event scroll
   */
  async ionViewDidEnter() {
    if (this.content) {
      this.scrollElement = await this.content.getScrollElement();
    }
  }

  /**
   * Handler event `(ionScroll)` dari IonContent.
   */
  onContentScroll(event: CustomEvent) {
    if (!this.scrollElement) return;

    const scrollTop    = event.detail.scrollTop as number;
    const scrollHeight = this.scrollElement.scrollHeight;
    const clientHeight = this.scrollElement.clientHeight;

    // ── Hitung progress bar ───────────────────────────────────────
    const maxScrollable = scrollHeight - clientHeight;
    if (maxScrollable > 0) {
      this.scrollProgress = Math.min(100, (scrollTop / maxScrollable) * 100);
    }

    // ── Deteksi apakah sudah di bagian bawah ─────────────────────
    // Sudah di bawah jika: scrollTop + clientHeight >= scrollHeight - THRESHOLD
    const distanceFromBottom = scrollHeight - scrollTop - clientHeight;

    if (!this.hasScrolledToBottom && distanceFromBottom <= this.BOTTOM_THRESHOLD) {
      this.hasScrolledToBottom = true;
      console.log('[PrivacyPolicy] User sudah scroll ke bawah — tombol diaktifkan');
    }
  }

  /**
   * Aksi tombol "Setuju & Lanjutkan".
   * Simpan consent ke localStorage lalu kembali ke halaman sebelumnya.
   */
  onAgree() {
    // Simpan timestamp persetujuan user
    localStorage.setItem('sc_privacy_agreed', new Date().toISOString());

    // Navigasi kembali ke halaman sebelumnya menggunakan browser history
    // fallback ke /login jika tidak ada history
    if (window.history.length > 1) {
      window.history.back();
    } else {
      this.router.navigate(['/login']);
    }
  }
}
