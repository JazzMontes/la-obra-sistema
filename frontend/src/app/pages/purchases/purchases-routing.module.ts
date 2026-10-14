// Define la ruta de la pantalla de compras.

import { NgModule } from '@angular/core';
import { RouterModule, Routes } from '@angular/router';
import { PurchasesPage } from './purchases.page';
// Rutas de navegación
const routes: Routes = [{ path: '', component: PurchasesPage }];

// Dependencias del módulo
@NgModule({ imports: [RouterModule.forChild(routes)], exports: [RouterModule] })
export class PurchasesPageRoutingModule {}
