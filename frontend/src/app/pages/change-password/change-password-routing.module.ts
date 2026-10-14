// Define la ruta de la pantalla de cambio de contraseña.

import { NgModule } from '@angular/core';
import { Routes, RouterModule } from '@angular/router';

import { ChangePasswordPage } from './change-password.page';

// Rutas de navegación
const routes: Routes = [
  {
    path: '',
    component: ChangePasswordPage,
  },
];

// Dependencias del módulo
@NgModule({
  imports: [RouterModule.forChild(routes)],
  exports: [RouterModule],
})
export class ChangePasswordPageRoutingModule {}
