import { Component, OnInit } from '@angular/core';
import { ActivatedRoute, Router } from '@angular/router';
import { ProductService, Product } from '../services/product.service';
import { CartService } from '../services/cart.service';
import { ToastController } from '@ionic/angular';
import { Location } from '@angular/common';
import { environment } from '../../environments/environment';

@Component({
  selector: 'app-product-detail',
  templateUrl: './product-detail.page.html',
  styleUrls: ['./product-detail.page.scss'],
  standalone: false
})
export class ProductDetailPage implements OnInit {

  productId: number | null = null;
  product: any = null;
  isLoading = true;
  cartCount = 0;

  constructor(
    private route: ActivatedRoute,
    private router: Router,
    private productService: ProductService,
    private cartService: CartService,
    private toastCtrl: ToastController,
    private location: Location
  ) { }

  ngOnInit() {
    this.cartService.cartItems$.subscribe(() => {
      this.cartCount = this.cartService.getTotalItems();
    });
    
    this.route.paramMap.subscribe(params => {
      const id = params.get('id');
      if (id) {
        this.productId = Number(id);
        this.loadProduct();
      }
    });
  }

  loadProduct() {
    this.isLoading = true;
    this.productService.getProduct(this.productId!).subscribe({
      next: (res: any) => {
        this.product = res.data || res;
        this.isLoading = false;
      },
      error: (err) => {
        console.error('Failed to load product', err);
        this.isLoading = false;
        this.showToast('Gagal memuat detail produk');
        this.goBack();
      }
    });
  }

  goBack() {
    this.location.back();
  }

  goToCart() {
    this.router.navigate(['/cart']);
  }

  visitStore() {
    if (!this.product?.seller_id) return;
    this.router.navigate(['/seller-store', this.product.seller_id]);
  }

  async addToCart() {
    if (!this.product) return;
    
    this.cartService.addToCart({
      product_id: this.product.id,
      product_name: this.product.name,
      product_image: this.getProductImage(this.product),
      seller_id: this.product.seller_id,
      seller_name: this.product.seller?.name || this.product.seller?.username || 'Seller',
      unit_price: this.product.price,
      quantity: 1,
      stock: this.product.stock
    });
    
    const toast = await this.toastCtrl.create({
      message: `${this.product.name} dimasukkan ke keranjang!`,
      duration: 2000,
      color: 'success',
      position: 'top'
    });
    toast.present();
  }

  buyNow() {
    if (!this.product) return;
    
    const checkoutItem = {
      product_id: this.product.id,
      product_name: this.product.name,
      product_image: this.getProductImage(this.product),
      seller_id: this.product.seller_id,
      seller_name: this.product.seller?.name || this.product.seller?.username || 'Seller',
      unit_price: this.product.price,
      quantity: 1,
      stock: this.product.stock
    };

    this.router.navigate(['/checkout'], {
      state: { product: checkoutItem }
    });
  }

  async showToast(msg: string) {
    const toast = await this.toastCtrl.create({
      message: msg,
      duration: 2000,
      position: 'bottom'
    });
    toast.present();
  }

  getProductImage(prod: any): string {
    if (!prod) return 'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?w=500&auto=format&fit=crop';
    
    if (prod.image_url) {
      if (prod.image_url.startsWith('http')) {
          if (prod.image_url.includes('via.placeholder.com')) {
              return 'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?w=500&auto=format&fit=crop';
          }
          return prod.image_url;
      }
      return prod.image_url.startsWith('/') ? environment.webUrl + prod.image_url : environment.webUrl + '/storage/' + prod.image_url;
    }
    
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

  getAllProductImages(prod: any): string[] {
    if (!prod) return ['https://images.unsplash.com/photo-1505740420928-5e560c06d30e?w=500&auto=format&fit=crop'];
    
    let result: string[] = [];
    
    if (prod.image_url) {
      let url = prod.image_url;
      if (url.startsWith('http')) {
        if (url.includes('via.placeholder.com')) url = 'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?w=500&auto=format&fit=crop';
      } else {
        url = url.startsWith('/') ? environment.webUrl + url : environment.webUrl + '/storage/' + url;
      }
      result.push(url);
    }
    
    let imagesArr = prod.images;
    if (typeof imagesArr === 'string') { try { imagesArr = JSON.parse(imagesArr); } catch (e) { imagesArr = null; } }
    
    if (imagesArr && Array.isArray(imagesArr)) {
      imagesArr.forEach((img: string) => {
        let url = img;
        if (url.startsWith('http')) {
          if (url.includes('via.placeholder.com')) url = 'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?w=500&auto=format&fit=crop';
        } else {
          url = url.startsWith('/') ? environment.webUrl + url : environment.webUrl + '/storage/' + url;
        }
        if (!result.includes(url)) {
          result.push(url);
        }
      });
    }
    
    if (result.length === 0) {
      result.push('https://images.unsplash.com/photo-1505740420928-5e560c06d30e?w=500&auto=format&fit=crop');
    }
    
    return result;
  }
}
