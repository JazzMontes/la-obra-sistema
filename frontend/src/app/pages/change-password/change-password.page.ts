// Valida y envía el cambio de contraseña.

import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { Auth } from '../../services/auth';

@Component({
  selector: 'app-change-password',
  templateUrl: './change-password.page.html',
  styleUrls: ['./change-password.page.scss'],
  standalone: false,
})
export class ChangePasswordPage implements OnInit {

  // Datos y estado
  currentPassword: string = '';
  newPassword: string = '';
  confirmPassword: string = '';

  loading: boolean = false;
  errorMessage: string = '';

  // Dependencias e inicio
  constructor(
    private auth: Auth,
    private router: Router
  ) {}

  // Inicialización
  ngOnInit() {}

  // Cambiar contraseña
  async changePassword() {
    this.errorMessage = '';

    // Validar campos vacíos
    if (!this.currentPassword || !this.newPassword || !this.confirmPassword) {
      this.errorMessage = 'Todos los campos son obligatorios';
      return;
    }

    // Validar que las nuevas contraseñas coincidan
    if (this.newPassword !== this.confirmPassword) {
      this.errorMessage = 'Las contraseñas nuevas no coinciden';
      return;
    }

    this.loading = true;

    try {
      const response = await this.auth.changePassword(
        this.currentPassword,
        this.newPassword
      );

      if (response.success) {
        await this.auth.getMe();
        await this.router.navigate(['/home']);
      }
    } catch (error: any) {
      console.error('Error al cambiar contraseña:', error);

      if (error.response?.data?.message) {
        this.errorMessage = error.response.data.message;
      } else {
        this.errorMessage = 'No se pudo cambiar la contraseña';
      }
    } finally {
      this.loading = false;
    }
  }
}
