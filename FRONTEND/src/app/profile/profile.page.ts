import { Component, OnInit, OnDestroy } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { IonicModule, ToastController } from '@ionic/angular';
import { Router, RouterModule } from '@angular/router';
import { Camera, CameraResultType, CameraSource } from '@capacitor/camera';
import { AuthService } from '../services/auth.service';
import { OrderService } from '../services/order.service';
import { CartService } from '../services/cart.service';
import { NotificationService } from '../services/notification.service';
import { environment } from '../../environments/environment';
import { Subscription } from 'rxjs';

@Component({
  selector: 'app-profile',
  templateUrl: './profile.page.html',
  styleUrls: ['./profile.page.scss'],
  standalone: true,
  imports: [IonicModule, CommonModule, FormsModule, RouterModule]
})
export class ProfilePage implements OnInit, OnDestroy {
  user: any = null;
  unpaidCount = 0;
  packedCount = 0;
  shippedCount = 0;
  completedCount = 0;
  walletBalance = 150000; // Mock balance
  cartCount = 0;
  unreadNotifCount = 0;
  private pollingInterval: any;
  private cartSub!: Subscription;
  private notifSub!: Subscription;

  constructor(
    private toastController: ToastController,
    private router: Router,
    private auth: AuthService,
    private orderService: OrderService,
    private cartService: CartService,
    private notificationService: NotificationService
  ) { }

  ngOnInit() {
    this.auth.user$.subscribe(user => {
      if (user) {
        this.user = user;
      } else {
        this.user = { name: 'Guest', role: 'buyer' };
      }
    });

    this.cartSub = this.cartService.cartItems$.subscribe(items => {
      this.cartCount = this.cartService.getTotalItems();
    });
    this.notifSub = this.notificationService.unreadCount.subscribe(count => {
      this.unreadNotifCount = count;
    });
  }

  ionViewWillEnter() {
    // Attempt to fetch fresh profile data every time page is entered
    if (this.auth.isLoggedIn) {
      this.auth.getProfile().subscribe();
    }

    this.fetchActiveOrders();
    // Poll every 10 seconds for real-time updates
    if (this.pollingInterval) clearInterval(this.pollingInterval);
    this.pollingInterval = setInterval(() => this.fetchActiveOrders(), 10000);
  }

  ionViewWillLeave() {
    if (this.pollingInterval) clearInterval(this.pollingInterval);
  }

  ngOnDestroy() {
    if (this.pollingInterval) clearInterval(this.pollingInterval);
    if (this.cartSub) this.cartSub.unsubscribe();
    if (this.notifSub) this.notifSub.unsubscribe();
  }

  fetchActiveOrders() {
    if (!this.auth.isLoggedIn) return;
    
    // Selalu refresh profil bersamaan dengan polling order 
    // agar update role dari Buyer ke Seller setelah daftar web bisa otomatis ketahuan.
    this.auth.getProfile().subscribe();

    this.orderService.getTransactionHistory().subscribe({
      next: (res: any) => {
        const transactions = res.data || res;
        
        this.unpaidCount = transactions.filter((t: any) => t.status === 'pending_payment' || t.status === 'pending').length;
        // checking_admin & success (pembayaran diverifikasi) & processed (dikemas) -> masuk ke Dikemas
        this.packedCount = transactions.filter((t: any) => t.status === 'checking_admin' || t.status === 'success' || t.status === 'processed').length;
        this.shippedCount = transactions.filter((t: any) => t.status === 'shipped').length;
        this.completedCount = transactions.filter((t: any) => t.status === 'completed').length;
      },
      error: () => { 
        this.unpaidCount = 0;
        this.packedCount = 0;
        this.shippedCount = 0;
        this.completedCount = 0;
      }
    });
  }

  async notAvailable() {
    const toast = await this.toastController.create({
      message: 'Fitur ini segera hadir!',
      duration: 2000,
      position: 'bottom',
      color: 'dark'
    });
    await toast.present();
  }

  async changeProfilePicture() {
    try {
      const image = await Camera.getPhoto({
        quality: 100,
        allowEditing: false,
        resultType: CameraResultType.DataUrl,
        source: CameraSource.Prompt
      });
      if (image.dataUrl) {
        // Convert dataUrl to Blob preserving full quality
        const res = await fetch(image.dataUrl);
        const blob = await res.blob();
        // Use original MIME type to avoid recompression
        const mimeType = blob.type || 'image/jpeg';
        const ext = mimeType.includes('png') ? 'png' : 'jpg';
        const file = new File([blob], `avatar.${ext}`, { type: mimeType });

        this.auth.updateProfile({ avatar: file }).subscribe({
          next: async (res: any) => {
            // Update local user data dengan response terbaru dari server
            if (res.user) {
              this.user = res.user;
            }
            // Refresh profile dari server untuk pastikan gambar terbaru tampil
            this.auth.getProfile().subscribe((fresh: any) => {
              this.user = fresh;
            });
            const toast = await this.toastController.create({
              message: 'Foto profil berhasil diperbarui!',
              duration: 2000,
              color: 'success'
            });
            toast.present();
          },
          error: async (err) => {
            const toast = await this.toastController.create({
              message: 'Gagal mengupload foto profil.',
              duration: 2000,
              color: 'danger'
            });
            toast.present();
          }
        });
      }
    } catch (e) {
      console.log('User cancelled photo', e);
    }
  }

  redirectToWebRegistration() {
    const token = localStorage.getItem('sc_token');
    window.open(`${environment.webUrl}/register-seller?token=${token}`, '_system');
  }

  openSellerDashboard() {
    const token = localStorage.getItem('sc_token');
    // /register-seller with a token automatically logs the user in and redirects to dashboard if already a seller
    window.open(`${environment.webUrl}/register-seller?token=${token}`, '_system');
  }

  getAvatarUrl(avatar: string | undefined): string {
    if (!avatar) return '';
    if (avatar.startsWith('http')) return avatar;
    // Construct full URL from backend base
    const base = environment.apiUrl.replace('/api/v1', '').replace('/api', '');
    return `${base}${avatar.startsWith('/') ? '' : '/'}${avatar}`;
  }

  goTo(path: string) {
    this.router.navigate([path], { replaceUrl: true });
  }

  logout() {
    this.auth.logout().subscribe({
      next: () => this.router.navigate(['/login']),
      error: () => this.router.navigate(['/login']) // Redirect anyway
    });
  }
}
