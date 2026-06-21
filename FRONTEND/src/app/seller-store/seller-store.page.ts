import { Component, OnInit } from '@angular/core';
import { ActivatedRoute, Router } from '@angular/router';
import { Location } from '@angular/common';
import { ToastController } from '@ionic/angular';
import { ProductService } from '../services/product.service';
import { CartService } from '../services/cart.service';
import { environment } from '../../environments/environment';

@Component({
  selector: 'app-seller-store',
  templateUrl: './seller-store.page.html',
  styleUrls: ['./seller-store.page.scss'],
  standalone: false
})
export class SellerStorePage implements OnInit {
  sellerId: number | null = null;
  seller: any = null;
  products: any[] = [];
  isLoading = true;
  cartCount = 0;

  constructor(
    private route: ActivatedRoute,
    private router: Router,
    private location: Location,
    private productService: ProductService,
    private cartService: CartService,
    private toastCtrl: ToastController
  ) {}

  ngOnInit() {
    this.cartService.cartItems$.subscribe(() => {
      this.cartCount = this.cartService.getTotalItems();
    });

    this.route.paramMap.subscribe(params => {
      const id = params.get('sellerId');
      if (id) {
        this.sellerId = Number(id);
        this.loadSellerData();
      }
    });
  }

  loadSellerData() {
    this.isLoading = true;
    this.productService.getProducts().subscribe({
      next: (res: any) => {
        const allProducts = res.data || res;
        // Filter produk milik seller ini
        const sellerProducts = allProducts.filter((p: any) => p.seller_id === this.sellerId);

        if (sellerProducts.length > 0) {
          // Ambil info seller dari produk pertama
          this.seller = sellerProducts[0].seller;
        }
        this.products = sellerProducts;
        this.isLoading = false;
      },
      error: () => {
        this.isLoading = false;
        this.showToast('Gagal memuat data toko');
      }
    });
  }

  goBack() {
    this.location.back();
  }

  goToCart() {
    this.router.navigate(['/cart']);
  }

  viewProduct(product: any) {
    this.router.navigate(['/product-detail', product.id]);
  }

  async addToCart(product: any) {
    this.cartService.addToCart({
      product_id: product.id,
      product_name: product.name,
      product_image: this.getProductImage(product),
      seller_id: product.seller_id,
      seller_name: this.seller?.store_name || this.seller?.name || 'Seller',
      unit_price: product.price,
      quantity: 1,
      stock: product.stock
    });
    this.showToast(`${product.name} ditambahkan ke keranjang`);
  }

  getProductImage(prod: any): string {
    if (!prod) return 'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?w=500&auto=format&fit=crop';
    if (prod.image_url) {
      if (prod.image_url.startsWith('http')) return prod.image_url.includes('via.placeholder.com') ? 'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?w=500&auto=format&fit=crop' : prod.image_url;
      return prod.image_url.startsWith('/') ? environment.webUrl + prod.image_url : environment.webUrl + '/storage/' + prod.image_url;
    }
    let imagesArr = prod.images;
    if (typeof imagesArr === 'string') { try { imagesArr = JSON.parse(imagesArr); } catch { imagesArr = null; } }
    if (imagesArr && Array.isArray(imagesArr) && imagesArr.length > 0) {
      const firstImg = imagesArr[0];
      if (firstImg.startsWith('http')) return firstImg.includes('via.placeholder.com') ? 'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?w=500&auto=format&fit=crop' : firstImg;
      return firstImg.startsWith('/') ? environment.webUrl + firstImg : environment.webUrl + '/storage/' + firstImg;
    }
    return 'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?w=500&auto=format&fit=crop';
  }

  getSellerInitial(): string {
    const name = this.seller?.store_name || this.seller?.name || 'S';
    return name[0].toUpperCase();
  }

  async showToast(msg: string) {
    const toast = await this.toastCtrl.create({
      message: msg,
      duration: 2000,
      color: 'success',
      position: 'bottom'
    });
    toast.present();
  }
}
