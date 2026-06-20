import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { environment } from '../../environments/environment';
import { BehaviorSubject, Observable } from 'rxjs';
import { tap } from 'rxjs/operators';
import { AuthService } from './auth.service';

@Injectable({
  providedIn: 'root'
})
export class NotificationService {
  private apiUrl = `${environment.apiUrl}/user/notifications`;
  
  public unreadCount = new BehaviorSubject<number>(0);
  private pollingInterval: any;
  
  constructor(private http: HttpClient, private auth: AuthService) {
    this.auth.user$.subscribe(user => {
      if (user && this.auth.isLoggedIn) {
        this.startPolling();
      } else {
        this.stopPolling();
        this.unreadCount.next(0);
      }
    });
  }

  private startPolling() {
    this.fetchUnreadCount();
    if (!this.pollingInterval) {
      this.pollingInterval = setInterval(() => {
        this.fetchUnreadCount();
      }, 15000); // 15 seconds
    }
  }

  private stopPolling() {
    if (this.pollingInterval) {
      clearInterval(this.pollingInterval);
      this.pollingInterval = null;
    }
  }

  getNotifications(page = 1): Observable<any> {
    return this.http.get<any>(`${this.apiUrl}?page=${page}`).pipe(
      tap(res => {
        if (res && res.unread_count !== undefined) {
          this.unreadCount.next(res.unread_count);
        }
      })
    );
  }

  markAsRead(id: string): Observable<any> {
    return this.http.patch(`${this.apiUrl}/${id}/read`, {}).pipe(
      tap(() => this.fetchUnreadCount())
    );
  }

  markAllAsRead(): Observable<any> {
    return this.http.patch(`${this.apiUrl}/read-all`, {}).pipe(
      tap(() => this.unreadCount.next(0))
    );
  }
  
  fetchUnreadCount() {
    if (!this.auth.isLoggedIn) return;
    this.http.get<any>(this.apiUrl).subscribe({
      next: (res) => {
        if (res && res.unread_count !== undefined) {
          this.unreadCount.next(res.unread_count);
        }
      },
      error: () => {}
    });
  }
}
