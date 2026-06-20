import { NgModule } from '@angular/core';
import { BrowserModule } from '@angular/platform-browser';
import { BrowserAnimationsModule } from '@angular/platform-browser/animations';
import { RouteReuseStrategy } from '@angular/router';
import { HttpClientModule, HTTP_INTERCEPTORS } from '@angular/common/http';
import { CommonModule } from '@angular/common';

// ── Interceptors ───────────────────────────────────────────────────────────────
import { ApiInterceptor } from './interceptors/api.interceptor';

import { IonicModule, IonicRouteStrategy } from '@ionic/angular';

import { AppComponent } from './app.component';
import { AppRoutingModule } from './app-routing.module';

// ── Components ────────────────────────────────────────────────────────────────
import { OfflineModalComponent } from './components/offline-modal/offline-modal.component';

// ── Services ──────────────────────────────────────────────────────────────────
// NetworkService menggunakan `providedIn: 'root'` → otomatis terdaftar,
// tidak perlu ditambahkan ke providers array secara manual.

@NgModule({
  declarations: [
    AppComponent,
    OfflineModalComponent,   // Modal offline harus dideclare agar bisa dipakai ModalController
  ],
  imports: [
    BrowserModule,
    BrowserAnimationsModule,  // Diperlukan untuk Angular animations (splash transition)
    CommonModule,
    HttpClientModule,
    IonicModule.forRoot({
      mode: 'md',             // Material Design mode — konsisten di Android & iOS
    }),
    AppRoutingModule,
  ],
  providers: [
    { provide: RouteReuseStrategy, useClass: IonicRouteStrategy },
    // Interceptor: otomatis sisipkan Bearer token ke setiap HTTP request
    { provide: HTTP_INTERCEPTORS, useClass: ApiInterceptor, multi: true },
  ],
  bootstrap: [AppComponent],
})
export class AppModule {}
