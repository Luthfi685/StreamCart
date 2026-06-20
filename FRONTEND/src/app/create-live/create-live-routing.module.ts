import { NgModule } from '@angular/core';
import { Routes, RouterModule } from '@angular/router';

import { CreateLivePage } from './create-live.page';

const routes: Routes = [
  {
    path: '',
    component: CreateLivePage
  }
];

@NgModule({
  imports: [RouterModule.forChild(routes)],
  exports: [RouterModule],
})
export class CreateLivePageRoutingModule {}
