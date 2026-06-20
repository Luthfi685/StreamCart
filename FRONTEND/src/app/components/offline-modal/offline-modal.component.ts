import { Component } from '@angular/core';
import { ModalController } from '@ionic/angular';
import { NetworkService } from '../../services/network.service';

/**
 * OfflineModalComponent
 * ─────────────────────────────────────────────────────────────────────────────
 * Modal global yang ditampilkan secara otomatis oleh AppComponent
 * ketika koneksi internet terputus. Tidak bisa ditutup manual oleh user
 * (backdropDismiss = false di AppComponent).
 *
 * Tombol "Coba Lagi" memicu pengecekan ulang status jaringan.
 */
@Component({
  selector: 'app-offline-modal',
  templateUrl: './offline-modal.component.html',
  styleUrls: ['./offline-modal.component.scss'],
  standalone: false,
})
export class OfflineModalComponent {

  constructor(
    private modalCtrl: ModalController,
    private networkService: NetworkService
  ) {}

  /**
   * Tombol "Coba Lagi":
   * Cek status jaringan saat ini. Jika sudah online, tutup modal.
   * Jika masih offline, tampilkan visual "sedang mengecek..."
   */
  retryConnection() {
    if (this.networkService.isCurrentlyOnline()) {
      // Sudah online — modal akan ditutup otomatis oleh AppComponent
      // via subscription isOnline$, tapi kita bisa juga dismiss manual
      this.modalCtrl.dismiss({ retried: true });
    } else {
      // Masih offline — beri feedback visual (animasi tombol)
      this.animateRetry();
    }
  }

  /** Efek visual saat tombol retry diklik tapi masih offline */
  private animateRetry() {
    const btn = document.getElementById('btn-retry-connection');
    if (btn) {
      btn.classList.add('shake');
      setTimeout(() => btn.classList.remove('shake'), 600);
    }
  }
}
