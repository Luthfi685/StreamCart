import { Component, OnInit } from '@angular/core';
import { AuthService } from '../services/auth.service';
import { Router, ActivatedRoute } from '@angular/router';
import { environment } from '../../environments/environment';
import { ToastController, LoadingController } from '@ionic/angular';

@Component({
  selector: 'app-login',
  templateUrl: './login.page.html',
  styleUrls: ['./login.page.scss'],
  standalone: false
})
export class LoginPage implements OnInit {
  email = '';
  password = '';

  constructor(
    private auth: AuthService,
    private router: Router,
    private toastCtrl: ToastController,
    private loadingCtrl: LoadingController,
    private route: ActivatedRoute
  ) { }

  ngOnInit() {
    // Cek apakah ada token dari Google OAuth callback
    this.route.queryParams.subscribe(params => {
      if (params['token'] && params['user']) {
        try {
          const user = JSON.parse(decodeURIComponent(params['user']));
          localStorage.setItem('sc_token', params['token']);
          localStorage.setItem('sc_user', JSON.stringify(user));
          this.showToast('Login dengan Google berhasil!');
          setTimeout(() => {
            this.router.navigate(['/home']);
          }, 300);
        } catch (e) {
          this.showToast('Gagal memproses login Google.');
        }
      } else if (params['error']) {
        this.showToast('Login Google gagal, coba lagi.');
      }
    });
  }

  loginWithGoogle() {
    window.location.href = `${environment.webUrl}/api/auth/google/redirect`;
  }



  async login() {
    if (!this.email || !this.password) {
      this.showToast('Silahkan masukkan email dan password');
      return;
    }

    const loading = await this.loadingCtrl.create({
      message: 'Loading...',
    });
    await loading.present();

    this.auth.login({
      email: this.email,
      password: this.password
    }).subscribe({
      next: (res: any) => {
        loading.dismiss();
        this.showToast('Login berhasil!');
        this.router.navigate(['/home']);
      },
      error: (err) => {
        loading.dismiss();

        // Cek apakah error karena akun belum diverifikasi
        const errors = err.error?.errors;
        const isUnverified = errors?.email?.includes('unverified');

        if (isUnverified) {
          // Redirect ke halaman OTP dengan membawa email
          this.showToast('Akun belum diverifikasi. Silakan cek email Anda untuk kode OTP.');
          this.router.navigate(['/register'], {
            queryParams: { email: this.email, step: 'otp' }
          });
          return;
        }

        // Ambil pesan error dari berbagai kemungkinan format Laravel
        const errorMsg =
          errors?.email?.[0] ||
          errors?.message?.[0] ||
          err.error?.message ||
          err.error?.error ||
          'Login gagal, periksa koneksi';

        this.showToast(errorMsg);
      }
    });
  }

  async showToast(msg: string) {
    const toast = await this.toastCtrl.create({
      message: msg,
      duration: 2000,
      color: 'dark'
    });
    toast.present();
  }

  async notAvailable() {
    const toast = await this.toastCtrl.create({
      message: 'Fitur registrasi akan segera hadir!',
      duration: 2000,
      position: 'bottom',
      color: 'dark'
    });
    await toast.present();
  }
}
