// Define la ruta de la pantalla de inicio de sesión.

import { NgModule } from '@angular/core';
import { Routes, RouterModule } from '@angular/router';

import { LoginPage } from './login.page';

// Rutas de navegación
const routes: Routes = [
  {
    path: '',
    component: LoginPage,
  },
];

// Dependencias del módulo
@NgModule({
  imports: [RouterModule.forChild(routes)],
  exports: [RouterModule],
})
export class LoginPageRoutingModule {}
