// Define la ruta de la pantalla de insumos.

import { NgModule } from '@angular/core';
import { Routes, RouterModule } from '@angular/router';
import { InputsPage } from './inputs.page';
// Rutas de navegación
const routes: Routes = [{ path: '', component: InputsPage }];

// Dependencias del módulo
@NgModule({ imports: [RouterModule.forChild(routes)], exports: [RouterModule] })
export class InputsPageRoutingModule {}
