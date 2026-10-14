// Define la ruta de la pantalla de mermas y pérdidas.

import { NgModule } from '@angular/core';
import { RouterModule, Routes } from '@angular/router';
import { WastePage } from './waste.page';
// Rutas de navegación
const routes: Routes = [{ path: '', component: WastePage }];

// Dependencias del módulo
@NgModule({ imports: [RouterModule.forChild(routes)], exports: [RouterModule] })
export class WastePageRoutingModule {}
