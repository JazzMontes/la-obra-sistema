// Define la ruta de la pantalla de categorías.

import { NgModule } from '@angular/core';
import { Routes, RouterModule } from '@angular/router';
import { CategoriesPage } from './categories.page';
// Rutas de navegación
const routes: Routes = [{ path: '', component: CategoriesPage }];

// Dependencias del módulo
@NgModule({ imports: [RouterModule.forChild(routes)], exports: [RouterModule] })
export class CategoriesPageRoutingModule {}
