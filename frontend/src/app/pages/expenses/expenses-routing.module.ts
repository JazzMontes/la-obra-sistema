// Define la ruta de la pantalla de gastos.

import { NgModule } from '@angular/core';
import { RouterModule, Routes } from '@angular/router';
import { ExpensesPage } from './expenses.page';
// Rutas de navegación
const routes: Routes = [{ path: '', component: ExpensesPage }];

// Dependencias del módulo
@NgModule({ imports: [RouterModule.forChild(routes)], exports: [RouterModule] })
export class ExpensesPageRoutingModule {}
