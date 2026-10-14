// Define la ruta de la pantalla de métodos de pago.

import { NgModule } from '@angular/core';
import { Routes, RouterModule } from '@angular/router';
import { PaymentMethodsPage } from './payment-methods.page';
// Rutas de navegación
const routes: Routes = [{ path: '', component: PaymentMethodsPage }];

// Dependencias del módulo
@NgModule({ imports: [RouterModule.forChild(routes)], exports: [RouterModule] })
export class PaymentMethodsPageRoutingModule {}
