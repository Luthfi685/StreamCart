import { platformBrowserDynamic } from '@angular/platform-browser-dynamic';
import { defineCustomElements } from '@ionic/pwa-elements/loader';
import { AppModule } from './app/app.module';

// ── Register Swiper Web Components ────────────────────────────────────────────
// Diperlukan karena IonSlides telah dihapus di Ionic 7+.
// Menggunakan Swiper sebagai Web Component (swiper/element/bundle).
import { register } from 'swiper/element/bundle';
register();

defineCustomElements(window);

platformBrowserDynamic().bootstrapModule(AppModule)
  .catch(err => console.log(err));