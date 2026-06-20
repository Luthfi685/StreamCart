import { Component, OnInit } from '@angular/core';
import { AlertController, ToastController } from '@ionic/angular';
import { Router } from '@angular/router';
import { HttpClient } from '@angular/common/http';
import { AuthService } from '../services/auth.service';
import { environment } from '../../environments/environment';

@Component({
  selector: 'app-settings',
  templateUrl: './settings.page.html',
  styleUrls: ['./settings.page.scss'],
  standalone: false
})
export class SettingsPage implements OnInit {
  address: string = '';
  
  // Notification & Language
  isNotificationEnabled: boolean = true;
  
  // Transaction PIN
  hasTransactionPin: boolean = false;
  isPinModalOpen: boolean = false;
  pinForm = { password: '', pin: '', pinConfirmation: '' };

  isPasswordModalOpen = false;
  passwordForm = { oldPassword: '', newPassword: '', confirmPassword: '' };
  showOldPass = false;
  showNewPass = false;
  showConfPass = false;

  constructor(
    private alertController: AlertController,
    private toastController: ToastController,
    private router: Router,
    private auth: AuthService,
    private http: HttpClient
  ) { }

  ngOnInit() {
    this.auth.user$.subscribe(user => {
      if (user) {
        this.address = user.address || '';
        this.hasTransactionPin = (user as any).has_transaction_pin === true;
      }
    });

    const notifPref = localStorage.getItem('sc_notif');
    this.isNotificationEnabled = notifPref !== 'false'; // default true
  }

  toggleNotification() {
    localStorage.setItem('sc_notif', this.isNotificationEnabled ? 'true' : 'false');
  }



  openPinModal() {
    this.isPinModalOpen = true;
    this.pinForm = { password: '', pin: '', pinConfirmation: '' };
  }

  closePinModal() {
    this.isPinModalOpen = false;
  }

  saveTransactionPin() {
    if (!this.pinForm.pin || this.pinForm.pin.length !== 6 || this.pinForm.pin !== this.pinForm.pinConfirmation) {
      this.toastController.create({ message: 'PIN tidak sesuai atau tidak valid.', duration: 2000, color: 'danger' })
        .then(t => t.present());
      return;
    }

    this.http.post(`${environment.apiUrl}/user/transaction-pin`, { 
      password: this.pinForm.password,
      pin: this.pinForm.pin,
      pin_confirmation: this.pinForm.pinConfirmation
    }).subscribe({
      next: async (res: any) => {
        this.hasTransactionPin = true;
        this.auth.getProfile().subscribe(); // Refresh profile
        
        const toast = await this.toastController.create({
          message: 'PIN Transaksi berhasil diatur!',
          duration: 2000,
          color: 'success',
          position: 'bottom'
        });
        toast.present();
        this.closePinModal();
      },
      error: async (err) => {
        const toast = await this.toastController.create({
          message: err.error?.message || 'Gagal mengatur PIN.',
          duration: 2000,
          color: 'danger',
          position: 'bottom'
        });
        toast.present();
      }
    });
  }

  editAddress() {
    this.router.navigate(['/add-address']);
  }

  async saveAddress(newAddress: string) {
    this.auth.updateProfile({ address: newAddress } as any).subscribe({
      next: async () => {
        this.address = newAddress;
        const toast = await this.toastController.create({
          message: 'Alamat berhasil disimpan!',
          duration: 2000,
          color: 'success',
          position: 'bottom'
        });
        toast.present();
      },
      error: async (err) => {
        const toast = await this.toastController.create({
          message: 'Gagal menyimpan alamat.',
          duration: 2000,
          color: 'danger',
          position: 'bottom'
        });
        toast.present();
      }
    });
  }

  openChangePassword() {
    this.isPasswordModalOpen = true;
    this.passwordForm = { oldPassword: '', newPassword: '', confirmPassword: '' };
  }

  closeChangePassword() {
    this.isPasswordModalOpen = false;
  }

  async savePassword() {
    if (this.passwordForm.newPassword !== this.passwordForm.confirmPassword) {
      const toast = await this.toastController.create({
        message: 'Password baru dan konfirmasi tidak cocok',
        duration: 2000,
        color: 'danger',
        position: 'bottom'
      });
      toast.present();
      return;
    }

    if (!this.auth.isLoggedIn) return;

    this.auth.updatePassword({
      current_password: this.passwordForm.oldPassword,
      password: this.passwordForm.newPassword,
      password_confirmation: this.passwordForm.confirmPassword
    }).subscribe({
      next: async () => {
        const toast = await this.toastController.create({
          message: 'Password berhasil diubah!',
          duration: 2000,
          color: 'success',
          position: 'bottom'
        });
        toast.present();
        this.closeChangePassword();
      },
      error: async (err) => {
        const toast = await this.toastController.create({
          message: err.error?.message || err.error?.error || 'Gagal mengubah password',
          duration: 2000,
          color: 'danger',
          position: 'bottom'
        });
        toast.present();
      }
    });
  }

  openHelp() {
    this.router.navigate(['/help-center']);
  }
}
