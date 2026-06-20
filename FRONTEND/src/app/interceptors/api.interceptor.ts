import { Injectable } from '@angular/core';
import {
  HttpInterceptor,
  HttpRequest,
  HttpHandler,
  HttpEvent,
  HttpErrorResponse,
} from '@angular/common/http';
import { Observable, throwError } from 'rxjs';
import { catchError } from 'rxjs/operators';
import { Router } from '@angular/router';

/**
 * ApiInterceptor
 * ─────────────────────────────────────────────────────────────────────────────
 * Secara otomatis:
 * 1. Menyisipkan Bearer token ke setiap HTTP request yang keluar
 * 2. Menangani error 401 (Unauthorized) → redirect ke /login
 */
@Injectable()
export class ApiInterceptor implements HttpInterceptor {

  constructor(private router: Router) {}

  intercept(req: HttpRequest<any>, next: HttpHandler): Observable<HttpEvent<any>> {
    const token = localStorage.getItem('sc_token');

    // Clone request dan tambahkan header Authorization jika token tersedia
    let authReq = req;
    if (token) {
      authReq = req.clone({
        setHeaders: {
          Authorization: `Bearer ${token}`,
          Accept: 'application/json',
        },
      });
    } else {
      authReq = req.clone({
        setHeaders: { Accept: 'application/json' },
      });
    }

    return next.handle(authReq).pipe(
      catchError((error: HttpErrorResponse) => {
        // Jika 401 Unauthorized → token expired atau tidak valid → logout
        if (error.status === 401) {
          localStorage.removeItem('sc_token');
          localStorage.removeItem('sc_user');
          this.router.navigate(['/login'], { replaceUrl: true });
        }
        return throwError(() => error);
      })
    );
  }
}
