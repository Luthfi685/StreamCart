import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable, BehaviorSubject, tap } from 'rxjs';
import { environment } from '../../environments/environment';

/** Model user yang disimpan secara lokal */
export interface User {
  id: number;
  name: string;
  email: string;
  phone?: string;
  address?: string;
  role: 'buyer' | 'seller' | 'admin';
  avatar?: string;
  is_verified: boolean;
}

export interface LoginPayload {
  email: string;
  password: string;
}

export interface RegisterPayload {
  name: string;
  email: string;
  password: string;
  password_confirmation: string;
  phone?: string;
  role?: 'buyer' | 'seller';
}

export interface AuthResponse {
  message: string;
  user: User;
  token: string;
}

/**
 * AuthService
 * ─────────────────────────────────────────────────────────────────────────────
 * Menangani semua operasi autentikasi:
 * - Login, Register, Logout
 * - Verifikasi OTP
 * - Lupa & Reset Password
 * - Profil pengguna
 * - Reactive state user via BehaviorSubject
 */
@Injectable({ providedIn: 'root' })
export class AuthService {

  private readonly base = environment.apiUrl;

  /** State reaktif pengguna yang sedang login */
  private _user$ = new BehaviorSubject<User | null>(this.loadUserFromStorage());
  readonly user$ = this._user$.asObservable();

  constructor(private http: HttpClient) {}

  // ── Getters ──────────────────────────────────────────────────────────────────

  get currentUser(): User | null {
    return this._user$.value;
  }

  get isLoggedIn(): boolean {
    return !!localStorage.getItem('sc_token');
  }

  get token(): string | null {
    return localStorage.getItem('sc_token');
  }

  // ── Auth Actions ─────────────────────────────────────────────────────────────

  login(payload: LoginPayload): Observable<AuthResponse> {
    return this.http.post<AuthResponse>(`${this.base}/login`, payload).pipe(
      tap(res => this.saveSession(res))
    );
  }

  register(payload: RegisterPayload): Observable<any> {
    return this.http.post(`${this.base}/register`, payload);
  }

  verifyOtp(email: string, otp: string): Observable<any> {
    return this.http.post(`${this.base}/verify-otp`, { email, otp_code: otp }).pipe(
      tap((res: any) => {
        if (res.token && res.user) {
          this.saveSession(res);
        }
      })
    );
  }

  resendOtp(email: string): Observable<any> {
    return this.http.post(`${this.base}/resend-otp`, { email });
  }

  forgotPassword(email: string): Observable<any> {
    return this.http.post(`${this.base}/forgot-password`, { email });
  }

  resetPassword(payload: { email: string; token: string; password: string; password_confirmation: string }): Observable<any> {
    return this.http.post(`${this.base}/reset-password`, {
      email: payload.email,
      otp_code: payload.token,
      password: payload.password
    });
  }

  logout(): Observable<any> {
    return this.http.post(`${this.base}/logout`, {}).pipe(
      tap(() => this.clearSession())
    );
  }

  // ── Profile ───────────────────────────────────────────────────────────────────

  getProfile(): Observable<any> {
    return this.http.get<any>(`${this.base}/profile`).pipe(
      tap(res => {
        // Backend might return flat user object or wrapped in { user: ... }
        const userData = res.user ? res.user : res;
        this._user$.next(userData);
        localStorage.setItem('sc_user', JSON.stringify(userData));
      })
    );
  }

  updateProfile(data: any): Observable<any> {
    const form = new FormData();
    Object.entries(data).forEach(([key, val]) => {
      if (val !== undefined && val !== null) form.append(key, val as any);
    });
    form.append('_method', 'PUT');
    return this.http.post(`${this.base}/profile`, form).pipe(
      tap((res: any) => {
        if (res.user) {
          this._user$.next(res.user);
          localStorage.setItem('sc_user', JSON.stringify(res.user));
        }
      })
    );
  }

  updatePassword(payload: { current_password: string; password: string; password_confirmation: string }): Observable<any> {
    return this.http.put(`${this.base}/password`, {
      old_password: payload.current_password,
      new_password: payload.password,
      new_password_confirmation: payload.password_confirmation
    });
  }

  registerSeller(data: any): Observable<any> {
    return this.http.post(`${this.base}/register-seller`, data);
  }

  // ── Session Helpers ───────────────────────────────────────────────────────────

  private saveSession(res: AuthResponse) {
    localStorage.setItem('sc_token', res.token);
    localStorage.setItem('sc_user', JSON.stringify(res.user));
    this._user$.next(res.user);
  }

  private clearSession() {
    localStorage.removeItem('sc_token');
    localStorage.removeItem('sc_user');
    this._user$.next(null);
  }

  private loadUserFromStorage(): User | null {
    try {
      const raw = localStorage.getItem('sc_user');
      return raw ? JSON.parse(raw) : null;
    } catch {
      return null;
    }
  }
}
