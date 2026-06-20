import { Injectable } from '@angular/core';
import { BehaviorSubject } from 'rxjs';

export interface CartItem {
  product_id: number;
  product_name: string;
  product_image: string;
  seller_id: number;
  seller_name: string;
  unit_price: number;
  quantity: number;
  stock: number;
}

import { AuthService } from './auth.service';

@Injectable({
  providedIn: 'root'
})
export class CartService {
  private cartItems: CartItem[] = [];
  private cartItemsSubject = new BehaviorSubject<CartItem[]>([]);
  public cartItems$ = this.cartItemsSubject.asObservable();
  private cartKey = 'streamcart_cart_guest';

  constructor(private authService: AuthService) {
    this.authService.user$.subscribe(user => {
      if (user) {
        this.cartKey = `streamcart_cart_${user.id}`;
      } else {
        this.cartKey = 'streamcart_cart_guest';
      }
      this.loadCart();
    });
  }

  private loadCart() {
    const saved = localStorage.getItem(this.cartKey);
    if (saved) {
      try {
        this.cartItems = JSON.parse(saved);
        this.cartItemsSubject.next(this.cartItems);
      } catch (e) {
        this.cartItems = [];
        this.cartItemsSubject.next(this.cartItems);
      }
    } else {
      this.cartItems = [];
      this.cartItemsSubject.next(this.cartItems);
    }
  }

  private saveCart() {
    localStorage.setItem(this.cartKey, JSON.stringify(this.cartItems));
    this.cartItemsSubject.next(this.cartItems);
  }

  public addToCart(item: CartItem) {
    const index = this.cartItems.findIndex(i => i.product_id === item.product_id);
    if (index > -1) {
      // Check stock
      if (this.cartItems[index].quantity + item.quantity <= item.stock) {
        this.cartItems[index].quantity += item.quantity;
      } else {
        this.cartItems[index].quantity = item.stock;
      }
    } else {
      this.cartItems.push(item);
    }
    this.saveCart();
  }

  public updateQuantity(productId: number, quantity: number) {
    const index = this.cartItems.findIndex(i => i.product_id === productId);
    if (index > -1) {
      if (quantity <= 0) {
        this.removeFromCart(productId);
      } else {
        this.cartItems[index].quantity = quantity;
        this.saveCart();
      }
    }
  }

  public removeFromCart(productId: number) {
    this.cartItems = this.cartItems.filter(i => i.product_id !== productId);
    this.saveCart();
  }

  public clearCart() {
    this.cartItems = [];
    this.saveCart();
  }
  
  public clearCartForSeller(sellerId: number) {
    this.cartItems = this.cartItems.filter(i => i.seller_id !== sellerId);
    this.saveCart();
  }

  public getTotalItems(): number {
    return this.cartItems.reduce((total, item) => total + item.quantity, 0);
  }
  
  public getItemsBySeller() {
    // Group by seller
    const grouped = new Map<number, { seller_name: string; items: CartItem[] }>();
    this.cartItems.forEach(item => {
      if (!grouped.has(item.seller_id)) {
        grouped.set(item.seller_id, { seller_name: item.seller_name, items: [] });
      }
      grouped.get(item.seller_id)!.items.push(item);
    });
    return Array.from(grouped.entries()).map(([seller_id, data]) => ({
      seller_id,
      seller_name: data.seller_name,
      items: data.items,
      total_price: data.items.reduce((sum, i) => sum + (i.unit_price * i.quantity), 0)
    }));
  }
}
