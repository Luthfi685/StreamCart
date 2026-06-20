import { Component, OnInit } from '@angular/core';
import { ToastController } from '@ionic/angular';
import { Router } from '@angular/router';
import { AuthService } from '../services/auth.service';
import { LoadingController } from '@ionic/angular';

@Component({
  selector: 'app-forgot-password',
  templateUrl: './forgot-password.page.html',
  styleUrls: ['./forgot-password.page.scss'],
  standalone: false
})
export class ForgotPasswordPage implements OnInit {
  step = 1;
  email = '';
  otpCode = '';
  newPassword = '';

  constructor(
    private toastCtrl: ToastController, 
    private router: Router,
    private auth: AuthService,
    private loadingCtrl: LoadingController
  ) { }

  ngOnInit() {
  }

  async requestOtp() {
    if (!this.email) {
      this.showToast('Masukkan email Anda terlebih dahulu.', 'warning');
      return;
    }

    const loading = await this.loadingCtrl.create({ message: 'Memproses...' });
    await loading.present();

    this.auth.forgotPassword(this.email).subscribe({
      next: async (res: any) => {
        await loading.dismiss();
        this.showToast(res.message, 'success');
        this.step = 2; // Pindah ke form OTP
      },
      error: async (err) => {
        await loading.dismiss();
        this.showToast(err.error?.message || err.error?.error || 'Email tidak ditemukan.', 'danger');
      }
    });
  }

  async resetPassword() {
    if (!this.otpCode || !this.newPassword) {
      this.showToast('Lengkapi OTP dan Password baru.', 'warning');
      return;
    }

    const loading = await this.loadingCtrl.create({ message: 'Menyimpan password baru...' });
    await loading.present();

    this.auth.resetPassword({ 
      email: this.email,
      token: this.otpCode,
      password: this.newPassword,
      password_confirmation: this.newPassword
    }).subscribe({
      next: async (res: any) => {
        await loading.dismiss();
        this.showToast(res.message, 'success');
        this.router.navigate(['/login']);
      },
      error: async (err) => {
        await loading.dismiss();
        this.showToast(err.error?.message || err.error?.error || 'OTP tidak valid.', 'danger');
      }
    });
  }

  async showToast(msg: string, color: string = 'dark') {
    const toast = await this.toastCtrl.create({
      message: msg,
      duration: 2500,
      color: color
    });
    toast.present();
  }
}
