import { Component, OnInit, OnDestroy } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Router } from '@angular/router';
import { ToastController } from '@ionic/angular';
import { AuthService } from '../services/auth.service';
import { LiveSessionService } from '../services/live-session.service';
import { CartService } from '../services/cart.service';
import { NotificationService } from '../services/notification.service';
import { Subscription } from 'rxjs';
import { environment } from '../../environments/environment';

@Component({
  selector: 'app-home',
  templateUrl: 'home.page.html',
  styleUrls: ['home.page.scss'],
  standalone: false
})
export class HomePage implements OnInit, OnDestroy {
  liveSessions: any[] = [];
  scheduledSessions: any[] = [];
  isLoading = true;
  isSeller = false;
  pollingInterval: any;
  cartCount = 0;
  unreadNotifCount = 0;
  private cartSub!: Subscription;
  private notifSub!: Subscription;
  private userSub!: Subscription;

  constructor(
    private http: HttpClient,
    private auth: AuthService,
    private liveSessionService: LiveSessionService,
    private cartService: CartService,
    private notificationService: NotificationService,
    private router: Router,
    private toastController: ToastController
  ) { }

  ngOnInit() {
    this.cartSub = this.cartService.cartItems$.subscribe(items => {
      this.cartCount = this.cartService.getTotalItems();
    });
    this.notifSub = this.notificationService.unreadCount.subscribe(count => {
      this.unreadNotifCount = count;
    });
  }

  ngOnDestroy() {
    if (this.cartSub) this.cartSub.unsubscribe();
    if (this.notifSub) this.notifSub.unsubscribe();
    if (this.userSub) this.userSub.unsubscribe();
  }

  goToCart() {
    this.router.navigate(['/cart']);
  }

  scrollToLive() {
    const el = document.getElementById('live-section');
    if (el) el.scrollIntoView({ behavior: 'smooth', block: 'start' });
  }

  ionViewWillEnter() {
    if (this.userSub) this.userSub.unsubscribe();
    this.userSub = this.auth.user$.subscribe(user => {
      if (user) {
        this.isSeller = (user.role === 'seller');
      }
    });
    this.fetchLiveSessions();
  }

  ionViewDidEnter() {
    this.fetchRecommendations();
    // Poll for active live sessions every 5 seconds
    if (this.pollingInterval) clearInterval(this.pollingInterval);
    this.pollingInterval = setInterval(() => {
      this.fetchLiveSessions(false);
    }, 5000);
  }

  ionViewWillLeave() {
    if (this.pollingInterval) {
      clearInterval(this.pollingInterval);
    }
  }

  handleRefresh(event: any) {
    this.fetchLiveSessions(false);
    setTimeout(() => {
      event.target.complete();
    }, 1000);
  }

  fetchLiveSessions(showLoading = true) {
    if (showLoading) this.isLoading = true;
    this.liveSessionService.getSessions().subscribe({
      next: (res: any) => {
        // Support response with or without .data wrapping
        const sessions = res.data || res;
        this.processSessions(sessions, false);
      },
      error: (err) => {
        console.error('Failed to load live sessions from backend', err);
        this.processSessions([], true);
      }
    });
  }

  processSessions(backendSessions: any[], isOffline: boolean) {
    let mockSessions: any[] = [];

    // Jika offline (backend gagal), baru kita coba pakai mock dari localStorage
    if (isOffline) {
      const mockSessionsStr = localStorage.getItem('mockLiveSessions');
      if (mockSessionsStr) {
        try {
          mockSessions = JSON.parse(mockSessionsStr).filter((s: any) => s.status === 'active');
        } catch (e) {
          console.error(e);
        }
      }
    }

    const allSessions = isOffline ? mockSessions : backendSessions;
    const uniqueSessions = Array.from(new Map(allSessions.map((item: any) => [item.id, item])).values());

    const now = new Date().getTime();

    // Pisahkan berdasarkan status
    const lives = uniqueSessions.filter((s: any) => s.status === 'live' || s.status === 'active');
    const scheduled = uniqueSessions.filter((s: any) => {
      if (s.status !== 'scheduled') return false;
      const scheduledTime = new Date(s.scheduled_at || s.created_at).getTime();
      // Only include if scheduled time is in the future, or passed by less than 2 hours
      return scheduledTime >= now - (2 * 60 * 60 * 1000);
    });

    this.liveSessions = lives.map((session: any) => {
      let image = session.thumbnail || session.image_url || 'https://images.unsplash.com/photo-1607082348824-0a96f2a4b9da?q=80&w=2070&auto=format&fit=crop';
      if (image && !image.startsWith('http')) {
         image = image.startsWith('/') ? environment.webUrl + image : environment.webUrl + '/storage/' + image;
      }
      return {
        id: session.id,
        sellerName: session.seller?.name || session.seller_name || 'Seller',
        title: session.title,
        viewerCount: session.viewer_count || 0,
        image: image,
        sellerAvatar: this.getSellerAvatar(session)
      };
    });

    this.scheduledSessions = scheduled.map((session: any) => {
      let image = session.thumbnail || session.image_url || 'https://images.unsplash.com/photo-1607082348824-0a96f2a4b9da?q=80&w=2070&auto=format&fit=crop';
      if (image && !image.startsWith('http')) {
         image = image.startsWith('/') ? environment.webUrl + image : environment.webUrl + '/storage/' + image;
      }
      return {
        id: session.id,
        sellerName: session.seller?.name || session.seller_name || 'Seller',
        title: session.title,
        scheduledAt: session.scheduled_at || session.created_at, // fallback to created_at
        image: image,
        sellerAvatar: this.getSellerAvatar(session),
        reminderActive: false // default false
      };
    });

    this.isLoading = false;
  }

  getSellerAvatar(session: any): string {
    const avatar = session.seller?.avatar;
    if (avatar) {
      if (avatar.startsWith('http')) return avatar;
      return avatar.startsWith('/') ? environment.webUrl + avatar : environment.webUrl + '/storage/' + avatar;
    }
    const name = session.seller?.name || session.seller_name || 'Seller';
    return `https://ui-avatars.com/api/?name=${encodeURIComponent(name)}&background=dbeafe&color=1d4ed8`;
  }

  async toggleReminder(session: any) {
    session.reminderActive = !session.reminderActive;
    const toast = await this.toastController.create({
      message: session.reminderActive ? 'Pengingat diaktifkan! Anda akan dinotifikasi saat live dimulai.' : 'Pengingat dimatikan.',
      duration: 2000,
      position: 'bottom',
      color: session.reminderActive ? 'success' : 'dark'
    });
    toast.present();
  }

  // --- Recommendations ---
  recommendedProducts: any[] = [];
  isLoadingRecommendations = true;

  fetchRecommendations() {
    this.isLoadingRecommendations = true;
    // Gunakan httpclient langsung atau lewat product service
    this.http.get(environment.apiUrl + '/v1/buyer/recommendations').subscribe({
      next: (res: any) => {
        this.recommendedProducts = res;
        this.isLoadingRecommendations = false;
      },
      error: (err) => {
        console.error('Failed to load recommendations', err);
        this.isLoadingRecommendations = false;
      }
    });
  }

  getProductImage(prod: any): string {
    if (prod.image_url) {
      if (prod.image_url.startsWith('http')) {
          // If it's via.placeholder.com and it's timing out, we can replace it with Unsplash
          if (prod.image_url.includes('via.placeholder.com')) {
              return 'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?w=500&auto=format&fit=crop';
          }
          return prod.image_url;
      }
      return prod.image_url.startsWith('/') ? environment.webUrl + prod.image_url : environment.webUrl + '/storage/' + prod.image_url;
    }
    
    // Check if prod.images is an array or valid JSON string
    let imagesArr = prod.images;
    if (typeof imagesArr === 'string') {
      try {
        imagesArr = JSON.parse(imagesArr);
      } catch (e) {
        imagesArr = null;
      }
    }

    if (imagesArr && Array.isArray(imagesArr) && imagesArr.length > 0) {
      const firstImg = imagesArr[0];
      if (firstImg.startsWith('http')) {
          if (firstImg.includes('via.placeholder.com')) {
              return 'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?w=500&auto=format&fit=crop';
          }
          return firstImg;
      }
      return firstImg.startsWith('/') ? environment.webUrl + firstImg : environment.webUrl + '/storage/' + firstImg;
    }
    
    return 'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?w=500&auto=format&fit=crop';
  }
}