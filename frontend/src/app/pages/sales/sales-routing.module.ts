// Define la ruta de la pantalla de ventas y pedidos.

import { NgModule } from '@angular/core';
import { Routes, RouterModule } from '@angular/router';
import { SalesPage } from './sales.page';
// Rutas de navegación
const routes: Routes = [{ path: '', component: SalesPage }];

// Dependencias del módulo
@NgModule({ imports: [RouterModule.forChild(routes)], exports: [RouterModule] })
export class SalesPageRoutingModule {}
