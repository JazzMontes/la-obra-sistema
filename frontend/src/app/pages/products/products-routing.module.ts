// Define la ruta de la pantalla de productos.

import { NgModule } from '@angular/core';
import { Routes, RouterModule } from '@angular/router';
import { ProductsPage } from './products.page';
// Rutas de navegación
const routes: Routes = [{ path: '', component: ProductsPage }];

// Dependencias del módulo
@NgModule({ imports: [RouterModule.forChild(routes)], exports: [RouterModule] })
export class ProductsPageRoutingModule {}
