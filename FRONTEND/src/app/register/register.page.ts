import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { ToastController, LoadingController } from '@ionic/angular';
import { AuthService } from '../services/auth.service';

@Component({
  selector: 'app-register',
  templateUrl: './register.page.html',
  styleUrls: ['./register.page.scss'],
  standalone: false
})
export class RegisterPage implements OnInit {
  name = '';
  email = '';
  password = '';
  isOtpModalOpen = false;
  otpCode = '';

  /** Wajib dicentang sebelum tombol Daftar aktif */
  agreePrivacy = false;

  showPassword = false;
  
  resendCountdown = 0;
  countdownInterval: any;

  constructor(
    private router: Router,
    private toastCtrl: ToastController,
    private loadingCtrl: LoadingController,
    private auth: AuthService
  ) { }

  ngOnInit() {
  }

  togglePassword() {
    this.showPassword = !this.showPassword;
  }

  async register() {
    if (!this.name || !this.email || !this.password) {
      this.showToast('Harap lengkapi semua bidang data (Nama, Email, Password).');
      return;
    }

    if (!this.agreePrivacy) {
      this.showToast('Anda harus menyetujui Kebijakan Privasi untuk mendaftar.');
      return;
    }

    const loading = await this.loadingCtrl.create({
      message: 'Mendaftarkan akun dan mengirim email...',
    });
    await loading.present();

    this.auth.register({
      name: this.name,
      email: this.email,
      password: this.password,
      password_confirmation: this.password
    }).subscribe({
      next: async (res: any) => {
        await loading.dismiss();
        this.showToast('Kode OTP telah dikirim ke email Anda.');
        this.isOtpModalOpen = true; // Buka modal OTP
      },
      error: async (err) => {
        await loading.dismiss();
        // Cek error dari validasi laravel
        const errorMsg = err.error?.message || err.error?.error || 'Terjadi kesalahan saat pendaftaran.';
        this.showToast(errorMsg);
      }
    });
  }

  async verifyOtp() {
    if (!this.otpCode || this.otpCode.length !== 6) {
      this.showToast('Masukkan 6 digit kode OTP.');
      return;
    }

    const loading = await this.loadingCtrl.create({
      message: 'Memverifikasi...',
    });
    await loading.present();

    this.auth.verifyOtp(this.email, this.otpCode).subscribe({
      next: async (res: any) => {
        await loading.dismiss();
        this.showToast('Verifikasi berhasil!');
        this.isOtpModalOpen = false;
        
        // Beri waktu sejenak agar animasi modal tertutup sebelum pindah halaman
        setTimeout(() => {
          this.router.navigate(['/home']);
        }, 300);
      },
      error: async (err) => {
        await loading.dismiss();
        const errorMsg = err.error?.message || err.error?.error || 'Kode OTP tidak valid.';
        this.showToast(errorMsg);
      }
    });
  }

  async resendOtp() {
    if (this.resendCountdown > 0) return;

    const loading = await this.loadingCtrl.create({
      message: 'Mengirim ulang OTP...',
    });
    await loading.present();

    this.auth.resendOtp(this.email).subscribe({
      next: async (res: any) => {
        await loading.dismiss();
        this.showToast(res.message || 'OTP baru telah dikirim.');
        this.startCountdown(60); // 60 detik cooldown
      },
      error: async (err) => {
        await loading.dismiss();
        const errorMsg = err.error?.message || err.error?.error || 'Gagal mengirim OTP.';
        this.showToast(errorMsg);
      }
    });
  }

  startCountdown(seconds: number) {
    this.resendCountdown = seconds;
    if (this.countdownInterval) clearInterval(this.countdownInterval);
    this.countdownInterval = setInterval(() => {
      this.resendCountdown--;
      if (this.resendCountdown <= 0) {
        clearInterval(this.countdownInterval);
      }
    }, 1000);
  }

  async showToast(msg: string) {
    const toast = await this.toastCtrl.create({
      message: msg,
      duration: 2000,
      color: 'dark'
    });
    toast.present();
  }
}
