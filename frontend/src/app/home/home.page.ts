// Recupera el usuario y controla el acceso a la pantalla de inicio.

import { Component } from '@angular/core';
import { Router } from '@angular/router';
import { Auth } from '../services/auth';

@Component({
  selector: 'app-home',
  templateUrl: 'home.page.html',
  styleUrls: ['home.page.scss'],
  standalone: false,
})
export class HomePage {

  // Datos y estado
  user: any;

  // Dependencias e inicio
  constructor(
    private auth: Auth,
    private router: Router
  ) {
    this.user = this.auth.getStoredUser();

    console.log('Usuario recuperado en Home:', this.user);

    if (!this.user) {
      this.router.navigate(['/login']);
    }
  }
}
