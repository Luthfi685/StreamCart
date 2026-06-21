import { NgModule } from '@angular/core';
import { PreloadAllModules, RouterModule, Routes } from '@angular/router';
import { AuthGuard } from './guards/auth.guard';
import { RoleGuard } from './guards/role.guard';

export const routes: Routes = [
  // ── ENTRY POINT ──────────────────────────────────────────────────────────────
  // Default redirect ke splash/welcome logic yang ditangani AppComponent
  {
    path: '',
    redirectTo: 'login',
    pathMatch: 'full'
  },

  // ── ONBOARDING ───────────────────────────────────────────────────────────────
  {
    path: 'welcome',
    loadChildren: () => import('./welcome/welcome.module').then(m => m.WelcomePageModule)
  },
  {
    path: 'privacy-policy',
    loadChildren: () => import('./privacy-policy/privacy-policy.module').then(m => m.PrivacyPolicyPageModule)
  },

  {
    path: 'login',
    loadChildren: () => import('./login/login.module').then(m => m.LoginPageModule)
  },
  {
    path: 'register',
    loadChildren: () => import('./register/register.module').then(m => m.RegisterPageModule)
  },
  {
    path: 'forgot-password',
    loadChildren: () => import('./forgot-password/forgot-password.module').then(m => m.ForgotPasswordPageModule)
  },

  // ── BUYER ONLY ───────────────────────────────────────────────────────────
  {
    path: 'home',
    canActivate: [AuthGuard, RoleGuard],
    data: { roles: ['buyer', 'seller'] },
    loadChildren: () => import('./home/home.module').then(m => m.HomePageModule)
  },
  {
    path: 'checkout',
    canActivate: [AuthGuard, RoleGuard],
    data: { roles: ['buyer', 'seller'] },
    loadChildren: () => import('./checkout/checkout.module').then(m => m.CheckoutPageModule)
  },

  // ── SELLER ONLY ──────────────────────────────────────────────────────────
  // (Fitur Seller telah dipindahkan ke Web Dashboard Laravel)

  // ── ADMIN ONLY ───────────────────────────────────────────────────────────
  // (Fitur Admin telah dipindahkan ke Web Dashboard Laravel)

  // ── SHARED (buyer + seller + admin) ──────────────────────────────────────
  {
    path: 'profile',
    canActivate: [AuthGuard],
    loadChildren: () => import('./profile/profile.module').then(m => m.ProfilePageModule)
  },
  {
    path: 'transactions',
    canActivate: [AuthGuard],
    loadChildren: () => import('./transactions/transactions.module').then(m => m.TransactionsPageModule)
  },
  {
    path: 'live-room',
    canActivate: [AuthGuard],
    loadChildren: () => import('./live-room/live-room.module').then(m => m.LiveRoomPageModule)
  },
  {
    path: 'settings',
    canActivate: [AuthGuard],
    loadChildren: () => import('./settings/settings.module').then(m => m.SettingsPageModule)
  },
  {
    path: 'add-address',
    canActivate: [AuthGuard],
    loadChildren: () => import('./add-address/add-address.module').then(m => m.AddAddressPageModule)
  },
  {
    path: 'wallet',
    loadChildren: () => import('./wallet/wallet.module').then( m => m.WalletPageModule)
  },
  {
    path: 'create-live',
    loadChildren: () => import('./create-live/create-live.module').then( m => m.CreateLivePageModule)
  },
  {
    path: 'help-center',
    loadChildren: () => import('./help-center/help-center.module').then( m => m.HelpCenterPageModule)
  },
  {
    path: 'cart',
    loadChildren: () => import('./cart/cart.module').then( m => m.CartPageModule)
  },
  {
    path: 'notifications',
    loadChildren: () => import('./notifications/notifications.module').then( m => m.NotificationsPageModule)
  },
  {
    path: 'product-detail/:id',
    loadChildren: () => import('./product-detail/product-detail.module').then( m => m.ProductDetailPageModule)
  },
  {
    path: 'seller-store/:sellerId',
    canActivate: [AuthGuard],
    loadChildren: () => import('./seller-store/seller-store.module').then( m => m.SellerStorePageModule)
  }
];

@NgModule({
  imports: [
    RouterModule.forRoot(routes, { preloadingStrategy: PreloadAllModules })
  ],
  exports: [RouterModule]
})
export class AppRoutingModule { }