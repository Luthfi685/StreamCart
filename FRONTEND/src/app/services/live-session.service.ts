import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';
import { environment } from '../../environments/environment';
import { Product } from './product.service';

export interface LiveSession {
  id: number;
  seller_id: number;
  title: string;
  description?: string;
  thumbnail?: string;
  status: 'scheduled' | 'live' | 'ended';
  viewer_count?: number;
  likes_count?: number;
  started_at?: string;
  ended_at?: string;
  seller?: {
    id: number;
    name: string;
    avatar?: string;
  };
  products?: Product[];
  pinned_products?: Product[];
}

export interface ChatMessage {
  id: number;
  user_id: number;
  live_session_id: number;
  message: string;
  created_at: string;
  user?: {
    id: number;
    name: string;
    avatar?: string;
  };
}

export interface LiveStatus {
  status: 'scheduled' | 'live' | 'ended';
  viewer_count: number;
  likes_count: number;
  pinned_products?: Product[];
}

export interface CreateSessionPayload {
  title: string;
  description?: string;
  thumbnail?: string | File;
}

/**
 * LiveSessionService
 * ─────────────────────────────────────────────────────────────────────────────
 * Public: list sesi live & detail
 * Seller: create, update status, bind products, pin produk
 * User: kirim chat, like
 */
@Injectable({ providedIn: 'root' })
export class LiveSessionService {

  private readonly base = environment.apiUrl;

  constructor(private http: HttpClient) {}

  // ── Public ────────────────────────────────────────────────────────────────────

  getSessions(status?: 'live' | 'scheduled' | 'ended'): Observable<{ data: LiveSession[] }> {
    const params: any = status ? { status } : {};
    return this.http.get<{ data: LiveSession[] }>(`${this.base}/live-sessions`, { params });
  }

  getSession(id: number): Observable<{ data: LiveSession }> {
    return this.http.get<{ data: LiveSession }>(`${this.base}/live-sessions/${id}`);
  }

  /** Polling endpoint — dipanggil periodik untuk cek status live terbaru */
  getLiveStatus(id: number): Observable<LiveStatus> {
    return this.http.get<LiveStatus>(`${this.base}/live-sessions/${id}/live-status`);
  }

  getChat(sessionId: number): Observable<{ data: ChatMessage[] }> {
    return this.http.get<{ data: ChatMessage[] }>(`${this.base}/live-sessions/${sessionId}/chat`);
  }

  // ── User Actions (requires auth) ──────────────────────────────────────────────

  sendChat(sessionId: number, message: string): Observable<any> {
    return this.http.post(`${this.base}/live-sessions/${sessionId}/chat`, { message });
  }

  likeSession(sessionId: number): Observable<any> {
    return this.http.put(`${this.base}/live-sessions/${sessionId}/like`, {});
  }

  // ── Seller Actions (requires auth) ────────────────────────────────────────────

  createSession(data: CreateSessionPayload): Observable<any> {
    const form = new FormData();
    Object.entries(data).forEach(([key, val]) => {
      if (val !== undefined && val !== null) form.append(key, val as any);
    });
    return this.http.post(`${this.base}/live-sessions`, form);
  }

  updateSessionStatus(id: number, status: 'live' | 'ended'): Observable<any> {
    return this.http.put(`${this.base}/live-sessions/${id}/status`, { status });
  }

  endSession(id: number): Observable<any> {
    return this.updateSessionStatus(id, 'ended');
  }

  bindProducts(sessionId: number, productIds: number[]): Observable<any> {
    return this.http.post(`${this.base}/live-sessions/${sessionId}/products`, { product_ids: productIds });
  }

  pinProduct(sessionId: number, productId: number | null): Observable<any> {
    return this.http.put(`${this.base}/live-sessions/${sessionId}/pin-product`, { product_id: productId });
  }

  getSessionProducts(sessionId: number): Observable<{ data: Product[] }> {
    return this.http.get<{ data: Product[] }>(`${this.base}/live-sessions/${sessionId}/products`);
  }
}
