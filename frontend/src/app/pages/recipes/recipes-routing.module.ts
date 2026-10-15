// Define la ruta de la pantalla de recetas.

import { NgModule } from '@angular/core';
import { Routes, RouterModule } from '@angular/router';
import { RecipesPage } from './recipes.page';
// Rutas de navegación
const routes: Routes = [{ path: '', component: RecipesPage }];

// Dependencias del módulo
@NgModule({ imports: [RouterModule.forChild(routes)], exports: [RouterModule] })
export class RecipesPageRoutingModule {}
