import { Component, OnInit, OnDestroy } from '@angular/core';
import { Router } from '@angular/router';
import { NavController, ToastController } from '@ionic/angular';
import { Subscription } from 'rxjs';
import { CartService, CartItem } from '../services/cart.service';

import { environment } from '../../environments/environment';

@Component({
  selector: 'app-cart',
  templateUrl: './cart.page.html',
  styleUrls: ['./cart.page.scss'],
  standalone: false
})
export class CartPage implements OnInit, OnDestroy {
  groupedCart: any[] = [];
  private cartSub!: Subscription;

  getImage(url: string): string {
    if (!url) return 'https://via.placeholder.com/150';
    if (url.startsWith('http')) return url;
    if (url.startsWith('/')) return environment.webUrl + url;
    return environment.webUrl + '/' + url;
  }

  constructor(
    private cartService: CartService,
    private navCtrl: NavController,
    private router: Router,
    private toastCtrl: ToastController
  ) { }

  ngOnInit() {
    this.cartSub = this.cartService.cartItems$.subscribe(items => {
      this.groupedCart = this.cartService.getItemsBySeller();
    });
  }

  ngOnDestroy() {
    if (this.cartSub) this.cartSub.unsubscribe();
  }

  goBack() {
    this.navCtrl.back();
  }

  increaseQty(item: CartItem) {
    this.cartService.updateQuantity(item.product_id, item.quantity + 1);
  }

  decreaseQty(item: CartItem) {
    this.cartService.updateQuantity(item.product_id, item.quantity - 1);
  }

  removeItem(item: CartItem) {
    this.cartService.removeFromCart(item.product_id);
  }

  checkoutSeller(group: any) {
    // Array of items
    const items = group.items;
    this.router.navigate(['/checkout'], {
      state: { items: items, seller_id: group.seller_id, isCart: true }
    });
  }

  async clearCart() {
    this.cartService.clearCart();
    const toast = await this.toastCtrl.create({
      message: 'Keranjang berhasil dikosongkan.',
      duration: 2000,
      color: 'success'
    });
    toast.present();
  }
}
