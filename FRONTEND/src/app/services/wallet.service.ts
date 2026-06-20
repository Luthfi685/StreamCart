import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';
import { environment } from '../../environments/environment';

export interface Wallet {
  id: number;
  seller_id: number;
  balance: number;
  on_hold: number; // Dana dalam escrow
}

export interface WalletTransaction {
  id: number;
  wallet_id: number;
  type: 'credit' | 'debit';
  amount: number;
  description: string;
  created_at: string;
}

export interface WithdrawalRequest {
  id: number;
  wallet_id: number;
  amount: number;
  bank_name: string;
  account_number: string;
  account_name: string;
  status: 'pending' | 'approved' | 'rejected';
  created_at: string;
}

/**
 * WalletService
 * ─────────────────────────────────────────────────────────────────────────────
 * Khusus Seller: lihat saldo, riwayat mutasi, dan manajemen penarikan
 */
@Injectable({ providedIn: 'root' })
export class WalletService {

  private readonly base = environment.apiUrl;

  constructor(private http: HttpClient) {}

  getWallet(): Observable<{ data: Wallet }> {
    return this.http.get<{ data: Wallet }>(`${this.base}/wallet`);
  }

  getTransactions(): Observable<{ data: WalletTransaction[] }> {
    return this.http.get<{ data: WalletTransaction[] }>(`${this.base}/wallet/transactions`);
  }

  getWithdrawalHistory(): Observable<{ data: WithdrawalRequest[] }> {
    return this.http.get<{ data: WithdrawalRequest[] }>(`${this.base}/wallet/withdrawals`);
  }

  requestWithdrawal(payload: {
    amount: number;
    bank_name: string;
    account_number: string;
    account_name: string;
  }): Observable<any> {
    return this.http.post(`${this.base}/wallet/withdraw`, payload);
  }
}
