import { Component, OnInit } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { ToastController } from '@ionic/angular';
import { AuthService } from '../services/auth.service';
import { environment } from '../../environments/environment';

@Component({
  selector: 'app-help-center',
  templateUrl: './help-center.page.html',
  styleUrls: ['./help-center.page.scss'],
  standalone: false
})
export class HelpCenterPage implements OnInit {
  ticket = {
    issue_title: '',
    issue_category: '',
    description: ''
  };
  isSubmitting = false;
  tickets: any[] = [];
  isLoadingTickets = false;

  currentUser: any = null;

  constructor(
    private http: HttpClient,
    private toastCtrl: ToastController,
    private auth: AuthService
  ) { }

  ngOnInit() {
    this.auth.user$.subscribe(user => {
      this.currentUser = user;
      if (user) {
        this.loadTickets();
      }
    });
  }

  loadTickets() {
    this.isLoadingTickets = true;
    this.http.get<{data: any[]}>(`${environment.apiUrl}/tickets`).subscribe({
      next: (res) => {
        this.tickets = res.data;
        this.isLoadingTickets = false;
      },
      error: () => {
        this.isLoadingTickets = false;
      }
    });
  }

  openWhatsApp() {
    const user = this.currentUser;
    const name = user?.name || 'Pengguna';
    const id = user?.id || 'Unknown';
    
    const message = `Halo Admin StreamCart, saya ${name} dengan ID [${id}] butuh bantuan terkait kendala aplikasi.`;
    const encodedMessage = encodeURIComponent(message);
    const url = `https://api.whatsapp.com/send?phone=6283126435560&text=${encodedMessage}`;
    
    window.open(url, '_blank');
  }

  async submitTicket() {
    if (!this.ticket.issue_title || !this.ticket.issue_category || !this.ticket.description) {
      const toast = await this.toastCtrl.create({
        message: 'Mohon lengkapi semua field',
        duration: 2000,
        color: 'warning'
      });
      return toast.present();
    }

    this.isSubmitting = true;
    this.http.post(`${environment.apiUrl}/tickets`, this.ticket).subscribe({
      next: async () => {
        this.isSubmitting = false;
        this.ticket = { issue_title: '', issue_category: '', description: '' };
        
        const toast = await this.toastCtrl.create({
          message: 'Tiket berhasil dikirim. Tim kami akan segera menindaklanjuti.',
          duration: 3000,
          color: 'success'
        });
        toast.present();
        
        // Reload tickets
        this.loadTickets();
      },
      error: async () => {
        this.isSubmitting = false;
        const toast = await this.toastCtrl.create({
          message: 'Gagal mengirim tiket. Silakan coba lagi.',
          duration: 3000,
          color: 'danger'
        });
        toast.present();
      }
    });
  }
}
