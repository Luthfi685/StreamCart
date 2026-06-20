import { Injectable } from '@angular/core';
import { HttpClient, HttpParams } from '@angular/common/http';
import { Observable } from 'rxjs';
import { environment } from '../../environments/environment';

export interface Product {
  id: number;
  seller_id: number;
  name: string;
  description: string;
  price: number;
  stock: number;
  image?: string;
  category?: string;
  created_at?: string;
  updated_at?: string;
}

export interface ProductListResponse {
  data: Product[];
  current_page: number;
  last_page: number;
  total: number;
}

/**
 * ProductService
 * ─────────────────────────────────────────────────────────────────────────────
 * Public: list & detail produk
 * Seller (butuh token): create, update, delete
 */
@Injectable({ providedIn: 'root' })
export class ProductService {

  private readonly base = environment.apiUrl;

  constructor(private http: HttpClient) {}

  // ── Public ────────────────────────────────────────────────────────────────────

  getProducts(filters?: { search?: string; category?: string; page?: number }): Observable<ProductListResponse> {
    let params = new HttpParams();
    if (filters?.search)   params = params.set('search', filters.search);
    if (filters?.category) params = params.set('category', filters.category);
    if (filters?.page)     params = params.set('page', filters.page.toString());
    return this.http.get<ProductListResponse>(`${this.base}/products`, { params });
  }

  getProduct(id: number): Observable<{ data: Product }> {
    return this.http.get<{ data: Product }>(`${this.base}/products/${id}`);
  }

  getRecommendations(): Observable<any> {
    return this.http.get(`${this.base}/v1/buyer/recommendations`);
  }

  // ── Seller (requires auth) ────────────────────────────────────────────────────

  createProduct(data: Partial<Product> & { image?: File }): Observable<any> {
    const form = new FormData();
    Object.entries(data).forEach(([key, val]) => {
      if (val !== undefined && val !== null) form.append(key, val as any);
    });
    return this.http.post(`${this.base}/products`, form);
  }

  updateProduct(id: number, data: Partial<Product> & { image?: File }): Observable<any> {
    const form = new FormData();
    Object.entries(data).forEach(([key, val]) => {
      if (val !== undefined && val !== null) form.append(key, val as any);
    });
    // Laravel tidak menerima PUT multipart, workaround menggunakan POST + _method
    form.append('_method', 'PUT');
    return this.http.post(`${this.base}/products/${id}`, form);
  }

  deleteProduct(id: number): Observable<any> {
    return this.http.delete(`${this.base}/products/${id}`);
  }
}
