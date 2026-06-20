import { Injectable } from '@angular/core';
import { CanActivate, Router, UrlTree } from '@angular/router';

/**
 * AuthGuard — blocks unauthenticated users from accessing protected routes.
 * Redirects to /login if no token is found.
 */
@Injectable({ providedIn: 'root' })
export class AuthGuard implements CanActivate {
  constructor(private router: Router) {}

  canActivate(): boolean | UrlTree {
    const token = localStorage.getItem('sc_token');
    if (!token) {
      return this.router.createUrlTree(['/login']);
    }
    return true;
  }
}
