import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';

import { IonicModule } from '@ionic/angular';

import { SellerStorePageRoutingModule } from './seller-store-routing.module';

import { SellerStorePage } from './seller-store.page';

@NgModule({
  imports: [
    CommonModule,
    FormsModule,
    IonicModule,
    SellerStorePageRoutingModule
  ],
  declarations: [SellerStorePage]
})
export class SellerStorePageModule {}
