import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';

import { IonicModule } from '@ionic/angular';

import { LiveRoomPageRoutingModule } from './live-room-routing.module';

import { LiveRoomPage } from './live-room.page';

@NgModule({
  imports: [
    CommonModule,
    FormsModule,
    IonicModule,
    LiveRoomPageRoutingModule
  ],
  declarations: [LiveRoomPage]
})
export class LiveRoomPageModule {}
