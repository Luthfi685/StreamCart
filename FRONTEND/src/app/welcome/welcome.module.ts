import { NgModule, CUSTOM_ELEMENTS_SCHEMA } from '@angular/core';
import { CommonModule } from '@angular/common';
import { IonicModule } from '@ionic/angular';
import { WelcomePageRoutingModule } from './welcome-routing.module';
import { WelcomePage } from './welcome.page';

@NgModule({
  imports: [
    CommonModule,
    IonicModule,
    WelcomePageRoutingModule
  ],
  declarations: [WelcomePage],
  // PENTING: CUSTOM_ELEMENTS_SCHEMA diperlukan agar Angular mengenali
  // <swiper-container> dan <swiper-slide> sebagai Web Components (Ionic 8 / Swiper 11)
  schemas: [CUSTOM_ELEMENTS_SCHEMA],
})
export class WelcomePageModule {}
