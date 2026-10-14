// Define la ruta de la pantalla de inicio.

import { NgModule } from '@angular/core';
import { RouterModule, Routes } from '@angular/router';
import { HomePage } from './home.page';

// Rutas de navegación
const routes: Routes = [
  {
    path: '',
    component: HomePage,
  },
];

// Dependencias del módulo
@NgModule({
  imports: [RouterModule.forChild(routes)],
  exports: [RouterModule],
})
export class HomePageRoutingModule {}
