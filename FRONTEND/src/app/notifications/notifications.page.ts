import { Component, OnInit } from '@angular/core';
import { NotificationService } from '../services/notification.service';

@Component({
  selector: 'app-notifications',
  templateUrl: './notifications.page.html',
  styleUrls: ['./notifications.page.scss'],
  standalone: false
})
export class NotificationsPage implements OnInit {
  notifications: any[] = [];
  isLoading = true;

  constructor(private notificationService: NotificationService) { }

  ngOnInit() {
    this.loadNotifications();
  }

  loadNotifications() {
    this.isLoading = true;
    this.notificationService.getNotifications(1).subscribe({
      next: (res) => {
        this.notifications = res.data;
        this.isLoading = false;
        // Mark all as read when opening page
        this.notificationService.markAllAsRead().subscribe();
      },
      error: () => {
        this.isLoading = false;
      }
    });
  }

  doRefresh(event: any) {
    this.notificationService.getNotifications(1).subscribe({
      next: (res) => {
        this.notifications = res.data;
        event.target.complete();
      },
      error: () => event.target.complete()
    });
  }
}
