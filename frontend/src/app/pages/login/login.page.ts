// Controla el inicio de sesión y la configuración del servidor.

import { Component, OnInit, ChangeDetectorRef } from '@angular/core';
import { Router } from '@angular/router';
import { Auth } from '../../services/auth';
import { ApiConfigService } from '../../services/api-config';
import { ApiDiagnosticsService } from '../../services/api-diagnostics';

@Component({
  selector: 'app-login',
  templateUrl: './login.page.html',
  styleUrls: ['./login.page.scss'],
  standalone: false,
})
export class LoginPage implements OnInit {

  // Datos y estado
  username: string = '';
  password: string = '';
  showServerSettings = false;
  serverIp = '';
  serverMessage = '';
  serverError = '';

  debugText = 'Sin peticiones todavía';
  showConnectionDetails = false;
  connectionChecked = false;
  loading: boolean = false;
  errorMessage: string = '';

  // Dependencias e inicio
  constructor(
    private auth: Auth,
    private diagnostics: ApiDiagnosticsService,
    private apiConfig: ApiConfigService,
    private router: Router,
    private changeDetector: ChangeDetectorRef
  ) {}

  // Inicialización
  ngOnInit() {
    this.serverIp = this.apiConfig.getServerIp();
  }

  // Mostrar configuración del servidor
  toggleServerSettings() {
    this.showServerSettings = !this.showServerSettings;
    this.serverIp = this.apiConfig.getServerIp();
    this.serverMessage = '';
    this.serverError = '';
  }

  // Guardar IP del servidor
  saveServerIp() {
    this.serverMessage = '';
    this.serverError = '';

    try {
      this.apiConfig.setServerIp(this.serverIp);
      this.serverMessage = 'IP guardada correctamente';
      this.showServerSettings = false;
    } catch (error: any) {
      this.serverError = error.message || 'IP inválida';
    }
  }

  // Iniciar sesión
  async login() {
    if (this.loading) return;

    this.errorMessage = '';
    this.connectionChecked = false;
    this.diagnostics.last = null;
    this.debugText = 'Preparando solicitud a la API...';

    if (!this.username.trim() || !this.password) {
      this.errorMessage = 'Usuario y contraseña son obligatorios';
      this.diagnostics.reportLocalError(this.errorMessage);
      this.debugText = this.diagnostics.asText();
      this.changeDetector.markForCheck();
      return;
    }

    try {
      this.apiConfig.getApiUrl();
    } catch (error: any) {
      this.errorMessage = error.message || 'Configura la IP del servidor';
      this.diagnostics.reportLocalError(this.errorMessage);
      this.debugText = this.diagnostics.asText();
      this.changeDetector.markForCheck();
      return;
    }

    this.loading = true;
    this.changeDetector.markForCheck();

    try {
      const response = await this.auth.login(this.username, this.password);

      this.debugText = this.diagnostics.asText();
      this.connectionChecked = true;

      if (response.success) {
        if (response.user.must_change_password) {
          await this.router.navigate(['/change-password']);
        } else {
          await this.router.navigate(['/home']);
        }
      }
    } catch (error: any) {
      this.connectionChecked = true;
      this.debugText = this.diagnostics.asText();

      if (error.response?.data?.message) {
        this.errorMessage = error.response.data.message;
      } else {
        this.errorMessage = `Error: ${error?.message || 'No se pudo conectar'}`;
      }
    } finally {
      this.debugText = this.diagnostics.asText();
      this.loading = false;
      // Axios puede completar fuera del ciclo de actualización de Angular.
      this.changeDetector.markForCheck();
    }
  }
}
