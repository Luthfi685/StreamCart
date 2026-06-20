import { Component, OnInit, OnDestroy } from '@angular/core';
import { Router } from '@angular/router';
import { ModalController, ToastController } from '@ionic/angular';
import { Subscription } from 'rxjs';
import { NetworkService } from './services/network.service';
import { OfflineModalComponent } from './components/offline-modal/offline-modal.component';
import { trigger, transition, style, animate } from '@angular/animations';

@Component({
  selector: 'app-root',
  templateUrl: 'app.component.html',
  styleUrls: ['app.component.scss'],
  standalone: false,
  animations: [
    trigger('splashAnimation', [
      transition(':leave', [
        animate('0.3s ease-out', style({ opacity: 0 }))
      ])
    ])
  ]
})
export class AppComponent implements OnInit, OnDestroy {

  /** Flag untuk mengontrol visibilitas splash screen */
  showSplash = true;

  private networkSub!: Subscription;
  private offlineModal: HTMLIonModalElement | null = null;

  constructor(
    private router: Router,
    private networkService: NetworkService,
    private modalCtrl: ModalController,
    private toastCtrl: ToastController
  ) {}

  ngOnInit() {
    this.initializeApp();
    this.setupNetworkListener();
  }

  /**
   * Inisialisasi app:
   * 1. Terapkan preferensi dark mode dari localStorage
   * 2. Tampilkan splash screen selama 3 detik, lalu arahkan ke /welcome
   */
  initializeApp() {
    // Preferensi dark mode
    const prefersDark = localStorage.getItem('theme') === 'dark';
    document.documentElement.classList.toggle('ion-palette-dark', prefersDark);
    if (prefersDark) {
      document.documentElement.classList.add('dark');
    } else {
      document.documentElement.classList.remove('dark');
    }

    // Splash screen — tampil 3 detik, lalu redirect ke welcome
    setTimeout(async () => {
      this.showSplash = false;

      // Cek apakah user sudah pernah onboarding sebelumnya
      const hasOnboarded = localStorage.getItem('sc_onboarded');
      const token = localStorage.getItem('sc_token');

      if (token) {
        // User sudah login, arahkan ke dashboard sesuai role
        const user = JSON.parse(localStorage.getItem('sc_user') || '{}');
        const role = user?.role || 'buyer';
        
        if (role === 'admin') {
          await this.router.navigate(['/admin-dashboard'], { replaceUrl: true });
        } else {
          // Seller and Buyer both use /home in Ionic
          await this.router.navigate(['/home'], { replaceUrl: true });
        }
      } else if (hasOnboarded) {
        // Langsung ke login jika sudah pernah onboarding tapi belum login
        await this.router.navigate(['/login'], { replaceUrl: true });
      } else {
        // Tampilkan welcome page untuk user baru
        await this.router.navigate(['/welcome'], { replaceUrl: true });
      }
    }, 3000);
  }

  /**
   * Pantau status jaringan secara global.
   * Offline  → tampilkan modal overlay "Koneksi Terputus"
   * Online   → tutup modal + tampilkan toast hijau
   */
  private setupNetworkListener() {
    this.networkSub = this.networkService.isOnline$.subscribe(async (isOnline) => {
      if (!isOnline) {
        await this.showOfflineModal();
      } else {
        await this.dismissOfflineModal();
        await this.showOnlineToast();
      }
    });
  }

  /** Tampilkan modal offline */
  private async showOfflineModal() {
    if (this.offlineModal) return; // Hindari duplikat modal

    this.offlineModal = await this.modalCtrl.create({
      component: OfflineModalComponent,
      backdropDismiss: false,        // User tidak bisa tutup dengan tap backdrop
      cssClass: 'offline-modal',
    });

    await this.offlineModal.present();
  }

  /** Tutup modal offline jika sedang terbuka */
  private async dismissOfflineModal() {
    if (this.offlineModal) {
      await this.offlineModal.dismiss();
      this.offlineModal = null;
    }
  }

  /** Toast hijau "Anda kembali online" */
  private async showOnlineToast() {
    const toast = await this.toastCtrl.create({
      message: '✅ Anda kembali online',
      duration: 3000,
      color: 'success',
      position: 'top',
      icon: 'wifi-outline',
      cssClass: 'online-toast',
    });
    await toast.present();
  }

  ngOnDestroy() {
    if (this.networkSub) {
      this.networkSub.unsubscribe();
    }
  }
}