import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';
import { environment } from '../../environments/environment';

export interface Order {
  id: number;
  buyer_id: number;
  seller_id: number;
  items?: {
    product_id: number;
    quantity: number;
    unit_price: number;
    subtotal: number;
    product?: {
      id: number;
      name: string;
      image?: string;
    };
  }[];
  live_session_id?: number;
  total_price: number;
  status: 'pending_payment' | 'checking_admin' | 'success' | 'fail' | 'processed' | 'completed' | 'cancelled' | 'pending';
  payment_proof?: string;
  shipping_address?: string;
  created_at: string;
  buyer?: {
    id: number;
    name: string;
    email: string;
  };
  seller?: {
    id: number;
    name: string;
  };
}

export interface CreateOrderPayload {
  items: { product_id: number; quantity: number }[];
  live_session_id?: number;
  shipping_address: string;
  shipping_fee?: number;
}

/**
 * OrderService
 * ─────────────────────────────────────────────────────────────────────────────
 * Buyer: buat pesanan, upload bukti bayar, konfirmasi selesai
 * Seller: list & update status pesanan
 * Shared: riwayat transaksi
 */
@Injectable({ providedIn: 'root' })
export class OrderService {

  private readonly base = environment.apiUrl;

  constructor(private http: HttpClient) {}

  // ── Buyer ─────────────────────────────────────────────────────────────────────

  createOrder(payload: CreateOrderPayload): Observable<{ message: string; data: Order }> {
    return this.http.post<{ message: string; data: Order }>(`${this.base}/v1/buyer/orders`, payload);
  }

  getPaymentInstructions(): Observable<any> {
    return this.http.get(`${this.base}/payment-instructions`);
  }

  getOrders(): Observable<{ data: Order[] }> {
    return this.http.get<{ data: Order[] }>(`${this.base}/orders`);
  }

  getTransactionHistory(): Observable<{ data: Order[] }> {
    return this.http.get<{ data: Order[] }>(`${this.base}/v1/buyer/transactions/history`);
  }

  uploadPaymentProof(orderId: number, file: File): Observable<any> {
    const form = new FormData();
    form.append('payment_proof', file);
    return this.http.post(`${this.base}/v1/buyer/orders/${orderId}/payment-proof`, form);
  }

  confirmComplete(orderId: number): Observable<any> {
    return this.http.post(`${this.base}/v1/buyer/orders/${orderId}/confirm`, {});
  }

  requestCancelOrder(orderId: number, payload: { reason_category: string, reason_detail?: string }): Observable<any> {
    return this.http.post(`${this.base}/v1/buyer/orders/${orderId}/request-cancel`, payload);
  }

  submitReviews(orderId: number, reviews: any[]): Observable<any> {
    return this.http.post(`${this.base}/v1/buyer/orders/${orderId}/reviews`, { reviews });
  }

  submitRefundInfo(orderId: number, refundData: any): Observable<any> {
    return this.http.post(`${this.base}/v1/buyer/orders/${orderId}/refund-info`, refundData);
  }

  // ── Seller ────────────────────────────────────────────────────────────────────

  /** Seller mengubah status pesanan (contoh: 'shipped') */
  updateOrderStatus(orderId: number, status: string): Observable<any> {
    return this.http.put(`${this.base}/orders/${orderId}/status`, { status });
  }
}
