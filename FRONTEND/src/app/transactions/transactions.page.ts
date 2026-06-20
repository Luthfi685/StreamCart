import { Component, OnInit, OnDestroy } from '@angular/core';
import { Router } from '@angular/router';
import { OrderService } from '../services/order.service';
import { AuthService } from '../services/auth.service';
import { CartService } from '../services/cart.service';
import { ToastController } from '@ionic/angular';
import { Subscription } from 'rxjs';
import { environment } from '../../environments/environment';

@Component({
  selector: 'app-transactions',
  templateUrl: './transactions.page.html',
  styleUrls: ['./transactions.page.scss'],
  standalone: false
})
export class TransactionsPage implements OnInit, OnDestroy {
  transactions: any[] = [];
  groupedTransactions: { sessionName: string, transactions: any[] }[] = [];
  userRole = 'buyer';
  currentUser: any = null;
  cartCount = 0;
  private cartSub!: Subscription;

  constructor(
    private orderService: OrderService,
    private auth: AuthService,
    private toastCtrl: ToastController,
    private cartService: CartService,
    private router: Router
  ) { }

  ngOnInit() {
    this.cartSub = this.cartService.cartItems$.subscribe(() => {
      this.cartCount = this.cartService.getTotalItems();
    });
  }

  ngOnDestroy() {
    if (this.cartSub) this.cartSub.unsubscribe();
  }

  goToCart() {
    this.router.navigate(['/cart']);
  }

  getImage(url: string): string {
    if (!url) return 'https://via.placeholder.com/150';
    if (url.startsWith('http')) return url;
    if (url.startsWith('/')) return environment.webUrl + url;
    return environment.webUrl + '/' + url;
  }

  ionViewWillEnter() {
    this.auth.user$.subscribe(user => {
      if (user) {
        this.currentUser = user;
        this.userRole = user.role;
      }
    });
    this.loadTransactions();
  }

  isLoading = true;
  currentFilter: string = 'all';

  setFilter(filter: string) {
    this.currentFilter = filter;
    this.loadTransactions();
  }

  loadTransactions() {
    if (!this.auth.isLoggedIn) {
      console.warn('loadTransactions: User is not logged in!');
      this.isLoading = false;
      return;
    }

    this.isLoading = true;
    console.log('loadTransactions: Fetching history...');
    this.orderService.getTransactionHistory().subscribe({
      next: (res: any) => {
        this.isLoading = false;
        console.log('loadTransactions: API Response:', res);
        const ordersList = res.data || res;
        const raw = ordersList.map((t: any) => ({
            id: t.id,
            date: new Date(t.created_at),
            productName: t.items?.[0]?.product?.name || t.product?.name || t.product_name || 'Produk',
            productImage: t.items?.[0]?.product?.image_url || t.items?.[0]?.product?.image || t.product?.image || t.product_image || 'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?w=300',
            extraItems: t.items?.length > 1 ? t.items.length - 1 : 0,
            sellerName: t.seller?.name || t.seller_name || 'Seller',
            quantity: t.items?.reduce((sum: number, i: any) => sum + i.quantity, 0) || 1,
            price: t.total_price,
            status: t.status,
            buyerName: t.buyer?.name || t.buyer_name || 'Pembeli',
            paymentProof: t.payment_proof_url || t.payment_proof,
            sessionTitle: t.live_session?.title || t.session_title || 'Pesanan Reguler',
            shippingCourier: t.shipping_courier,
            shippingTrackingNumber: t.shipping_tracking_number,
            hasReviewed: t.reviews && t.reviews.length > 0,
            items: t.items || [],
            payment_verified_at: t.payment_verified_at,
            is_refunded: t.is_refunded,
            refund_bank_account: t.refund_bank_account,
            refund_proof_url: t.refund_proof ? environment.apiUrl.replace('/api', '/storage/') + t.refund_proof : null,
            refund_processed_at: t.refund_processed_at
          }));
          let filteredRaw = raw;
          if (this.currentFilter === 'last30') {
            const thirtyDaysAgo = new Date();
            thirtyDaysAgo.setDate(thirtyDaysAgo.getDate() - 30);
            filteredRaw = raw.filter((t: any) => t.date >= thirtyDaysAgo);
          } else if (this.currentFilter === 'completed') {
            filteredRaw = raw.filter((t: any) => t.status === 'completed' || t.status === 'success');
          } else if (this.currentFilter === 'canceled') {
            filteredRaw = raw.filter((t: any) => t.status === 'cancelled' || t.status === 'fail');
          }
          
          this.transactions = filteredRaw; 
        },
        error: (err) => {
          this.isLoading = false;
          console.error('Failed to load transactions', err);
        }
      });
  }

  confirmReceived(transactionId: number) {
    if (!this.auth.isLoggedIn) return;

    this.orderService.confirmComplete(transactionId).subscribe({
      next: async (res: any) => {
        const toast = await this.toastCtrl.create({
          message: res.message || 'Pesanan berhasil diselesaikan. Dana diteruskan ke Penjual.',
          duration: 3000,
          color: 'success'
        });
        toast.present();
        this.loadTransactions();
      },
      error: async (err) => {
        console.error('Failed to confirm received', err);
        const toast = await this.toastCtrl.create({
          message: err.error?.message || 'Gagal menyelesaikan pesanan.',
          duration: 2000,
          color: 'danger'
        });
        toast.present();
      }
    });
  }

  updateStatus(transactionId: number, status: string) {
    if (!this.auth.isLoggedIn) return;
    
    this.orderService.updateOrderStatus(transactionId, status).subscribe({
      next: async () => {
          const toast = await this.toastCtrl.create({
            message: 'Status pesanan berhasil diperbarui.',
            duration: 2000,
            color: 'success'
          });
          toast.present();
          this.loadTransactions();
        },
        error: (err) => console.error('Failed to update status', err)
      });
  }

  buyAgain(trx: any) {
    // Cari ID produk dari item pertama yang dibeli
    const productId = trx.items?.[0]?.product?.id || trx.items?.[0]?.product_id || trx.product_id;
    
    if (productId) {
      this.router.navigate(['/product-detail', productId]);
    } else {
      console.warn('buyAgain: Product ID not found for transaction', trx);
      this.toastCtrl.create({
        message: 'Tidak dapat menemukan data produk untuk dibeli lagi.',
        duration: 2000,
        color: 'danger'
      }).then(t => t.present());
    }
  }

  // --- Pembatalan Pesanan ---
  isCancelModalOpen = false;
  selectedCancelOrderId: number | null = null;
  cancelReasonCategory = '';
  cancelReasonDetail = '';

  openCancelModal(orderId: number) {
    this.selectedCancelOrderId = orderId;
    this.cancelReasonCategory = '';
    this.cancelReasonDetail = '';
    this.isCancelModalOpen = true;
  }

  closeCancelModal() {
    this.isCancelModalOpen = false;
    this.selectedCancelOrderId = null;
  }

  submitCancelRequest() {
    if (!this.selectedCancelOrderId || !this.cancelReasonCategory) {
      return;
    }
    
    this.orderService.requestCancelOrder(this.selectedCancelOrderId, {
      reason_category: this.cancelReasonCategory,
      reason_detail: this.cancelReasonDetail
    }).subscribe({
      next: async (res: any) => {
        this.closeCancelModal();
        const toast = await this.toastCtrl.create({
          message: res.message || 'Pengajuan pembatalan berhasil dikirim.',
          duration: 3000,
          color: 'success'
        });
        toast.present();
        this.loadTransactions();
      },
      error: async (err) => {
        const toast = await this.toastCtrl.create({
          message: err.error?.message || 'Gagal mengajukan pembatalan.',
          duration: 2000,
          color: 'danger'
        });
        toast.present();
      }
    });
  }

  // --- Ulasan ---
  isReviewModalOpen = false;
  selectedTrx: any = null;
  reviewItems: any[] = [];
  
  openReviewModal(trx: any) {
    this.selectedTrx = trx;
    this.reviewItems = trx.items.map((item: any) => ({
      product_id: item.product_id,
      product_name: item.product?.name || 'Produk',
      product_image: item.product?.image_url || 'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?w=200',
      rating: 5,
      comment: ''
    }));
    this.isReviewModalOpen = true;
  }

  closeReviewModal() {
    this.isReviewModalOpen = false;
    this.selectedTrx = null;
  }

  setRating(item: any, star: number) {
    item.rating = star;
  }

  submitReview() {
    if (!this.selectedTrx) return;

    this.orderService.submitReviews(this.selectedTrx.id, this.reviewItems).subscribe({
      next: async (res: any) => {
        this.closeReviewModal();
        const toast = await this.toastCtrl.create({
          message: res.message || 'Ulasan berhasil disimpan.',
          duration: 3000,
          color: 'success'
        });
        toast.present();
        this.loadTransactions();
      },
      error: async (err: any) => {
        const toast = await this.toastCtrl.create({
          message: err.error?.message || 'Gagal mengirim ulasan.',
          duration: 2000,
          color: 'danger'
        });
        toast.present();
      }
    });
  }

  // --- Pembayaran ---
  isPaymentModalOpen = false;
  selectedPaymentOrderId: number | null = null;
  paymentProofFile: File | null = null;
  paymentProofUrl: string | null = null;
  adminBankName = 'Memuat...';
  adminBankAccount = '—';
  adminBankAccountName = '—';
  paymentTotalAmount = 0;

  openPaymentModal(trx: any) {
    this.selectedPaymentOrderId = trx.id;
    this.selectedTrx = trx;
    this.paymentTotalAmount = trx.price;
    this.paymentProofFile = null;
    this.paymentProofUrl = null;
    this.isPaymentModalOpen = true;

    this.orderService.getPaymentInstructions().subscribe({
      next: (data) => {
        if (data.name) {
          this.adminBankName = data.name;
          this.adminBankAccount = data.account;
          this.adminBankAccountName = data.account_name;
        }
      },
      error: (err) => console.error('Failed to load bank details', err)
    });
  }

  closePaymentModal() {
    this.isPaymentModalOpen = false;
    this.selectedPaymentOrderId = null;
    this.selectedTrx = null;
    this.paymentProofFile = null;
    this.paymentProofUrl = null;
  }

  onPaymentFileSelected(event: any) {
    const file = event.target.files[0];
    if (file) {
      this.paymentProofFile = file;
      const reader = new FileReader();
      reader.onload = (e: any) => { this.paymentProofUrl = e.target.result; };
      reader.readAsDataURL(file);
    }
  }

  submitPaymentProof() {
    if (!this.selectedPaymentOrderId || !this.paymentProofFile) return;

    this.orderService.uploadPaymentProof(this.selectedPaymentOrderId, this.paymentProofFile).subscribe({
      next: async (res: any) => {
        this.closePaymentModal();
        const toast = await this.toastCtrl.create({
          message: res.message || 'Bukti pembayaran berhasil diunggah.',
          duration: 3000,
          color: 'success'
        });
        toast.present();
        this.loadTransactions();
      },
      error: async (err: any) => {
        const toast = await this.toastCtrl.create({
          message: err.error?.message || 'Gagal mengunggah bukti pembayaran.',
          duration: 2000,
          color: 'danger'
        });
        toast.present();
      }
    });
  }

  // --- Refund Modal ---
  isRefundModalOpen = false;
  isRefundProofModalOpen = false;
  refundData = {
    bankName: '',
    bankAccount: '',
    accountName: ''
  };

  openRefundModal(trx: any) {
    this.selectedTrx = trx;
    this.isRefundModalOpen = true;
    this.refundData = {
      bankName: '',
      bankAccount: '',
      accountName: ''
    };
  }

  closeRefundModal() {
    this.isRefundModalOpen = false;
    this.selectedTrx = null;
  }

  submitRefundInfo() {
    if (!this.selectedTrx) return;
    this.orderService.submitRefundInfo(this.selectedTrx.id, {
      refund_bank_name: this.refundData.bankName,
      refund_bank_account: this.refundData.bankAccount,
      refund_bank_account_name: this.refundData.accountName
    }).subscribe({
      next: async (res: any) => {
        this.closeRefundModal();
        const toast = await this.toastCtrl.create({
          message: res.message || 'Data rekening berhasil dikirim',
          duration: 3000,
          color: 'success'
        });
        toast.present();
        this.loadTransactions();
      },
      error: async (err: any) => {
        const toast = await this.toastCtrl.create({
          message: err.error?.message || 'Gagal mengirim data rekening',
          duration: 2000,
          color: 'danger'
        });
        toast.present();
      }
    });
  }

  openRefundProofModal(trx: any) {
    this.selectedTrx = trx;
    this.isRefundProofModalOpen = true;
  }

  closeRefundProofModal() {
    this.isRefundProofModalOpen = false;
    this.selectedTrx = null;
  }
}
