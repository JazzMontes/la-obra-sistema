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

    // Limpiamos cualquier mensaje anterior
    this.errorMessage = '';

    // Validamos que haya escrito ambos campos
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

      console.error('Error al iniciar sesión:', error);

      if (error.response?.data?.message) {
        this.errorMessage = error.response.data.message;
      } else {
        this.errorMessage = 'No se pudo conectar con el servidor';
      }

    } finally {

      this.loading = false;

    }
  }

}