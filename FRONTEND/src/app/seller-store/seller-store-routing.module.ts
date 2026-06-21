import { NgModule } from '@angular/core';
import { Routes, RouterModule } from '@angular/router';

import { SellerStorePage } from './seller-store.page';

const routes: Routes = [
  {
    path: '',
    component: SellerStorePage
  }
];

@NgModule({
  imports: [RouterModule.forChild(routes)],
  exports: [RouterModule],
})
export class SellerStorePageRoutingModule {}
