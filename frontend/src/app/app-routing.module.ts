import { NgModule } from '@angular/core';
import { PreloadAllModules, RouterModule, Routes } from '@angular/router';

const routes: Routes = [
  { path: 'data-origins', loadChildren: () => import('./pages/data-origins/data-origins.module').then(m => m.DataOriginsPageModule) },
  {
    path: 'home',
    loadChildren: () =>
      import('./home/home.module').then(m => m.HomePageModule)
  },
  {
    path: 'login',
    loadChildren: () =>
      import('./pages/login/login.module').then(m => m.LoginPageModule)
  },
  {
    path: 'change-password',
    loadChildren: () =>
      import('./pages/change-password/change-password.module').then(m => m.ChangePasswordPageModule)
  },
  {
    path: 'products',
    loadChildren: () =>
      import('./pages/products/products.module').then(m => m.ProductsPageModule)
  },
  {
    path: 'categories',
    loadChildren: () =>
      import('./pages/categories/categories.module').then(m => m.CategoriesPageModule)
  },
  {
    path: 'inputs',
    loadChildren: () =>
      import('./pages/inputs/inputs.module').then(m => m.InputsPageModule)
  },
  {
    path: 'recipes',
    loadChildren: () =>
      import('./pages/recipes/recipes.module').then(m => m.RecipesPageModule)
  },
  {
    path: 'sales',
    loadChildren: () =>
      import('./pages/sales/sales.module').then(m => m.SalesPageModule)
  },
  {
    path: 'payment-methods',
    loadChildren: () =>
      import('./pages/payment-methods/payment-methods.module').then(m => m.PaymentMethodsPageModule)
  },
  {
    path: '',
    redirectTo: 'login',
    pathMatch: 'full'
  },
  {
    path: '**',
    redirectTo: 'login'
  }
];

@NgModule({
  imports: [
    RouterModule.forRoot(routes, {
      preloadingStrategy: PreloadAllModules
    })
  ],
  exports: [RouterModule]
})
export class AppRoutingModule { }
