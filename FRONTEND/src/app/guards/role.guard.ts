import { Injectable } from '@angular/core';
import { CanActivate, ActivatedRouteSnapshot, Router, UrlTree } from '@angular/router';

/**
 * RoleGuard — ensures only users with the allowed role can access a route.
 * 
 * Usage in routes:
 *   canActivate: [RoleGuard],
 *   data: { roles: ['seller'] }
 *
 * Redirect logic:
 *   - No token         → /login
 *   - role = seller    → /seller-dashboard
 *   - role = admin     → /admin-dashboard
 *   - role = buyer     → /home
 */
@Injectable({ providedIn: 'root' })
export class RoleGuard implements CanActivate {
  constructor(private router: Router) {}

  canActivate(route: ActivatedRouteSnapshot): boolean | UrlTree {
    const token = localStorage.getItem('sc_token');
    if (!token) {
      return this.router.createUrlTree(['/login']);
    }

    const userStr = localStorage.getItem('sc_user');
    if (!userStr) {
      return this.router.createUrlTree(['/login']);
    }

    let user;
    try {
      user = JSON.parse(userStr);
      if (!user) throw new Error();
    } catch {
      localStorage.removeItem('sc_token');
      localStorage.removeItem('sc_user');
      return this.router.createUrlTree(['/login']);
    }
    const role: string = user.role || 'buyer';
    const allowedRoles: string[] = route.data?.['roles'] || [];

    // If no roles restriction → allow
    if (allowedRoles.length === 0) return true;

    // Role matches → allow
    if (allowedRoles.includes(role)) return true;

    // Role does NOT match → redirect to the correct home for that role
    return this.redirectByRole(role);
  }

  private redirectByRole(role: string): UrlTree {
    if (role === 'admin')   return this.router.createUrlTree(['/admin-dashboard']);
    return this.router.createUrlTree(['/home']);
  }
}
