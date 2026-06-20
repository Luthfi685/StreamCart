import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { AlertController, LoadingController } from '@ionic/angular';
import { AuthService } from '../services/auth.service';
import { OrderService, CreateOrderPayload } from '../services/order.service';
import { CartService } from '../services/cart.service';
import { environment } from '../../environments/environment';
import { AddressService } from '../services/address.service';
@Component({
  selector: 'app-checkout',
  templateUrl: './checkout.page.html',
  styleUrls: ['./checkout.page.scss'],
  standalone: false
})
export class CheckoutPage implements OnInit {
  items: any[] = [];
  sessionId: string | null = null;

  shippingFee = 0;
  paymentProofFile: File | null = null;
  paymentProofUrl: string | null = null;
  shippingAddress  = '';
  buyerName        = 'Pembeli';
  buyerPhone       = '';

  // Info rekening Admin (dari response API order)
  adminBankName        = 'Memuat...';
  adminBankAccount     = '—';
  adminBankAccountName = '—';

  // ID order yang sudah dibuat (untuk upload bukti)
  createdOrderId: number | null = null;
  constructor(
    private router: Router,
    private alertController: AlertController,
    private loadingController: LoadingController,
    private auth: AuthService,
    private orderService: OrderService,
    private cartService: CartService,
    private addressService: AddressService
  ) {}

  ionViewWillEnter() {
    let fallbackUserAddress = '';
    this.auth.user$.subscribe(user => {
      if (user) {
        this.buyerName = user.name || 'Pembeli';
        this.buyerPhone = user.phone || '';
        fallbackUserAddress = user.address || '';
      }
    });

    this.addressService.getAddresses().subscribe({
      next: (res) => {
        if (res.data && res.data.length > 0) {
          const addr = res.data[0];
          this.shippingAddress = `${addr.location_name}, ${addr.region}`;
          if (addr.address_detail) this.shippingAddress += ` (${addr.address_detail})`;
          this.buyerPhone = addr.phone;
          this.buyerName = addr.receiver_name;
        } else {
          this.shippingAddress = fallbackUserAddress;
        }
        this.calculateShipping();
      },
      error: (err) => console.error('Failed to load address', err)
    });

    // Fetch Admin Bank Details directly via OrderService
    this.orderService.getPaymentInstructions().subscribe({
      next: (data) => {
        if (data.name) {
          this.adminBankName = data.name;
          this.adminBankAccount = data.account;
          this.adminBankAccountName = data.account_name;
        }
      },
      error: (err) => {
        console.error('Failed to load bank details', err);
        this.adminBankName = 'Gagal memuat';
      }
    });
  }

  calculateShipping() {
    if (this.shippingAddress && this.items.length > 0) {
      // Dummy logic: Base Rp 10.000 + Rp 2.000 per extra item quantity
      let totalQty = this.items.reduce((sum, item) => sum + item.quantity, 0);
      this.shippingFee = 10000 + (totalQty > 1 ? (totalQty - 1) * 2000 : 0);
    } else {
      this.shippingFee = 0;
    }
  }

  getImage(url: string): string {
    if (!url) return 'https://via.placeholder.com/150';
    if (url.startsWith('http')) return url;
    if (url.startsWith('/')) return environment.webUrl + url;
    return environment.webUrl + '/' + url;
  }

  ngOnInit() {
    const state = history.state;
    if (state?.items) {
      this.items = state.items;
      this.sessionId = state.sessionId || null;
    } else if (state?.product) {
      // Compatibility for direct buy
      const p = state.product;
      this.items = [{
        product_id: p.product_id || p.id,
        product_name: p.product_name || p.name,
        product_image: p.product_image || p.image_url || p.image || '',
        unit_price: p.unit_price || p.price,
        seller_id: p.seller_id,
        seller_name: p.seller_name,
        quantity: p.quantity || 1,
        stock: p.stock || 999
      }];
      this.sessionId = state.sessionId || null;
    }
    this.calculateShipping();
  }

  get total() {
    const sum = this.items.reduce((acc, curr) => acc + (curr.unit_price * curr.quantity), 0);
    return sum + this.shippingFee;
  }

  increaseQuantity(item: any) { 
    if (item.quantity < item.stock) {
      item.quantity++; 
      this.calculateShipping();
    }
  }
  decreaseQuantity(item: any) { 
    if (item.quantity > 1) {
      item.quantity--; 
      this.calculateShipping();
    }
  }

  onFileSelected(event: any) {
    const file = event.target.files[0];
    if (file) {
      this.paymentProofFile = file;
      const reader = new FileReader();
      reader.onload = (e: any) => { this.paymentProofUrl = e.target.result; };
      reader.readAsDataURL(file);
    }
  }



  /**
   * STEP 1: Buat order → dapat info rekening Admin
   * STEP 2: Upload bukti transfer → kirim ke backend → notif email ke Admin
   */
  async processPayment() {
    if (!this.paymentProofFile) {
      const alert = await this.alertController.create({
        header: 'Bukti Transfer Kosong',
        message: 'Mohon unggah foto bukti transfer terlebih dahulu.',
        buttons: ['OK']
      });
      await alert.present();
      return;
    }

    const loading = await this.loadingController.create({
      message: 'Memproses pesanan...',
      spinner: 'circles'
    });
    await loading.present();

    // ── STEP 1: Buat order ──────────────────────────────
    const payload: CreateOrderPayload = {
      items: this.items.map(i => ({ product_id: i.product_id, quantity: i.quantity })),
      shipping_address: this.shippingAddress,
      shipping_fee: this.shippingFee,
      live_session_id: this.sessionId ? Number(this.sessionId) : undefined
    };

    this.orderService.createOrder(payload).subscribe({
      next: async (res: any) => {
        // Backend returns data -> order
        const orderId = res.data?.id || res.order?.id;
        if (!orderId) { 
          await loading.dismiss(); 
          this.showError('ERROR: orderId tidak ditemukan dalam response: ' + JSON.stringify(res)); 
          return; 
        }

        // Tampung info rekening Admin dari response
        const pi = res.payment_instruction || res.data?.payment_instruction;
        this.adminBankName        = pi?.bank_name    || '—';
        this.adminBankAccount     = pi?.bank_account || '—';
        this.adminBankAccountName = pi?.account_name || '—';

        // ── STEP 2: Upload bukti transfer ──────
        loading.message = 'Mengirim bukti transfer...';

        this.orderService.uploadPaymentProof(orderId, this.paymentProofFile!).subscribe({
          next: async () => {
            await loading.dismiss();
            const alert = await this.alertController.create({
              header: '✅ Pembayaran Terkirim!',
              message: 'Bukti transfer sedang diverifikasi Admin. Kamu bisa pantau statusnya di halaman Pesanan.',
              buttons: [{
                text: 'Lihat Pesanan',
                handler: () => {
                  if (history.state?.isCart && history.state?.seller_id) {
                    this.cartService.clearCartForSeller(history.state.seller_id);
                  }
                  this.router.navigate(['/transactions']);
                }
              }]
            });
            await alert.present();
          },
          error: async (err) => {
            await loading.dismiss();
            this.showError(err.error?.message || 'Gagal upload bukti transfer.');
          }
        });
      },
      error: async (err) => {
        await loading.dismiss();
        const errDetails = err.error?.message || err.message || JSON.stringify(err);
        this.showError('ERROR: ' + errDetails);
      }
    });
  }

  private async showError(msg: string) {
    const alert = await this.alertController.create({ header: 'Gagal', message: msg, buttons: ['OK'] });
    await alert.present();
  }
}
