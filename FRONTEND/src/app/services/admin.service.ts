import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';
import { environment } from '../../environments/environment';

export interface AdminStats {
  total_users: number;
  total_sellers: number;
  total_orders: number;
  total_revenue: number;
  active_live_sessions: number;
}

export interface AdminUser {
  id: number;
  name: string;
  email: string;
  role: string;
  is_verified: boolean;
  created_at: string;
}

/**
 * AdminService
 * ─────────────────────────────────────────────────────────────────────────────
 * Endpoint khusus admin: statistik, users, transaksi, log, verifikasi pembayaran, WD
 */
@Injectable({ providedIn: 'root' })
export class AdminService {

  private readonly base = `${environment.apiUrl}/admin`;

  constructor(private http: HttpClient) {}

  getStats(): Observable<{ data: AdminStats }> {
    return this.http.get<{ data: AdminStats }>(`${this.base}/stats`);
  }

  getUsers(): Observable<{ data: AdminUser[] }> {
    return this.http.get<{ data: AdminUser[] }>(`${this.base}/users`);
  }

  createUser(data: any): Observable<any> {
    return this.http.post(`${this.base}/users`, data);
  }

  updateUser(id: number, data: any): Observable<any> {
    return this.http.put(`${this.base}/users/${id}`, data);
  }

  deleteUser(id: number): Observable<any> {
    return this.http.delete(`${this.base}/users/${id}`);
  }

  getTransactions(): Observable<{ data: any[] }> {
    return this.http.get<{ data: any[] }>(`${this.base}/transactions`);
  }

  getActivityLogs(): Observable<{ data: any[] }> {
    return this.http.get<{ data: any[] }>(`${this.base}/activity-logs`);
  }

  // ── Payment Verification ──────────────────────────────────────────────────────

  getPendingPayments(): Observable<{ data: any[] }> {
    return this.http.get<{ data: any[] }>(`${this.base}/orders/pending-payments`);
  }

  verifyPayment(orderId: number, action: 'approve' | 'reject'): Observable<any> {
    return this.http.put(`${this.base}/orders/${orderId}/verify-payment`, { action });
  }

  // ── Withdrawal Management ─────────────────────────────────────────────────────

  getWithdrawals(): Observable<{ data: any[] }> {
    return this.http.get<{ data: any[] }>(`${this.base}/withdrawals`);
  }

  processWithdrawal(withdrawalId: number, action: 'approve' | 'reject'): Observable<any> {
    return this.http.put(`${this.base}/withdrawals/${withdrawalId}`, { action });
  }
}
