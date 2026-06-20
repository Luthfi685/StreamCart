import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';

import { IonicModule } from '@ionic/angular';

import { CreateLivePageRoutingModule } from './create-live-routing.module';

import { CreateLivePage } from './create-live.page';

@NgModule({
  imports: [
    CommonModule,
    FormsModule,
    IonicModule,
    CreateLivePageRoutingModule
  ],
  declarations: [CreateLivePage]
})
export class CreateLivePageModule {}
