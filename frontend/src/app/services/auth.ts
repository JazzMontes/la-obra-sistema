import { Injectable } from '@angular/core';
import axios from 'axios';
import { ApiConfigService } from './api-config';

@Injectable({
  providedIn: 'root'
})
export class Auth {

  constructor(private apiConfig: ApiConfigService) {}

  private get apiUrl(): string {
    return this.apiConfig.getApiUrl();
  }

  // =========================
  // INICIAR SESIÓN
  // =========================
  async login(username: string, password: string) {

    // TEMPORAL: para revisar la conexión desde Android
    console.log('===== DATOS DE CONEXIÓN =====');
    console.log('API URL:', `${this.apiUrl}/auth/login.php`);
    console.log('ORIGIN:', window.location.origin);
    console.log('=============================');

    const response = await axios.post(
      `${this.apiUrl}/auth/login.php`,
      {
        username: username.trim(),
        password: password
      },
      {
        headers: {
          'Content-Type': 'application/json'
        }
      }
    );

    const data = response.data;

    if (data.success && data.token) {
      localStorage.setItem('token', data.token);
      localStorage.setItem('user', JSON.stringify(data.user));
    }

    return data;
  }

  // =========================
  // REGISTRARSE
  // =========================
  async register(
    name: string,
    username: string,
    email: string,
    password: string
  ) {

    const response = await axios.post(
      `${this.apiUrl}/auth/register.php`,
      {
        name: name.trim(),
        username: username.trim(),
        email: email.trim(),
        password: password
      },
      {
        headers: {
          'Content-Type': 'application/json'
        }
      }
    );

    return response.data;
  }

  // =========================
  // OBTENER TOKEN GUARDADO
  // =========================
  getToken(): string | null {
    return localStorage.getItem('token');
  }

  // =========================
  // OBTENER USUARIO GUARDADO
  // =========================
  getStoredUser() {

    const user = localStorage.getItem('user');

    if (!user) {
      return null;
    }

    return JSON.parse(user);
  }

  // =========================
  // CONSULTAR MI PERFIL
  // =========================
  async getMe() {

    const token = this.getToken();

    const response = await axios.get(
      `${this.apiUrl}/auth/me.php`,
      {
        headers: {
          Authorization: `Bearer ${token}`
        }
      }
    );

    const data = response.data;

    if (data.success && data.user) {
      localStorage.setItem(
        'user',
        JSON.stringify(data.user)
      );
    }

    return data;
  }

  // =========================
  // MODIFICAR MI PERFIL
  // =========================
  async updateProfile(
    name: string,
    username: string,
    email: string
  ) {

    const token = this.getToken();

    const response = await axios.put(
      `${this.apiUrl}/auth/me.php`,
      {
        name: name.trim(),
        username: username.trim(),
        email: email.trim()
      },
      {
        headers: {
          'Content-Type': 'application/json',
          Authorization: `Bearer ${token}`
        }
      }
    );

    const data = response.data;

    if (data.success && data.user) {
      localStorage.setItem(
        'user',
        JSON.stringify(data.user)
      );
    }

    return data;
  }

  // =========================
  // CAMBIAR MI CONTRASEÑA
  // =========================
  async changePassword(
    currentPassword: string,
    newPassword: string
  ) {

    const token = this.getToken();

    const response = await axios.patch(
      `${this.apiUrl}/auth/password.php`,
      {
        current_password: currentPassword,
        new_password: newPassword
      },
      {
        headers: {
          'Content-Type': 'application/json',
          Authorization: `Bearer ${token}`
        }
      }
    );

    return response.data;
  }

  // =========================
  // CERRAR SESIÓN
  // =========================
  async logout() {

    const token = this.getToken();

    try {

      const response = await axios.post(
        `${this.apiUrl}/auth/logout.php`,
        {},
        {
          headers: {
            Authorization: `Bearer ${token}`
          }
        }
      );

      return response.data;

    } finally {

      localStorage.removeItem('token');
      localStorage.removeItem('user');

    }
  }

  // =========================
  // SABER SI HAY SESIÓN LOCAL
  // =========================
  isLoggedIn(): boolean {
    return this.getToken() !== null;
  }

}