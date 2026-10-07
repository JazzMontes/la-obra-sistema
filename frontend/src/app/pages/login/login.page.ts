import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { Auth } from '../../services/auth';

@Component({
  selector: 'app-login',
  templateUrl: './login.page.html',
  styleUrls: ['./login.page.scss'],
  standalone: false,
})
export class LoginPage implements OnInit {

  username: string = '';
  password: string = '';

  loading: boolean = false;
  errorMessage: string = '';

  constructor(
    private auth: Auth,
    private router: Router
  ) { }

  ngOnInit() {
  }

  async login() {

    this.errorMessage = '';

    if (!this.username.trim() || !this.password) {
      this.errorMessage = 'Usuario y contraseña son obligatorios';
      return;
    }

    this.loading = true;

    try {

      const response = await this.auth.login(
        this.username,
        this.password
      );

      if (response.success) {

        console.log('Login correcto:', response);
        console.log('Usuario:', response.user);

        if (response.user.must_change_password) {
          await this.router.navigate(['/change-password']);
        } else {
          await this.router.navigate(['/home']);
        }

      }

    } catch (error: any) {

      console.error('===== ERROR LOGIN =====');
      console.error('Error completo:', error);
      console.error('Mensaje:', error?.message);
      console.error('Código:', error?.code);
      console.error('Respuesta:', error?.response);
      console.error('Datos respuesta:', error?.response?.data);
      console.error('=======================');

      if (error.response?.data?.message) {
        this.errorMessage = error.response.data.message;
      } else {
        this.errorMessage =
          `Error: ${error?.message || 'No se pudo conectar'}`;
      }

    } finally {

      this.loading = false;

    }
  }
}