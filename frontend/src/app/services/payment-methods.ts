// Envía las solicitudes de métodos de pago a la API.

import { Injectable } from '@angular/core';
import axios from 'axios';
import { ApiConfigService } from './api-config';

@Injectable({ providedIn: 'root' })
export class PaymentMethods {

  // Dependencias e inicio
  constructor(private apiConfig: ApiConfigService) {}

  // Obtener dirección de la API
  private get apiUrl() {
    return this.apiConfig.getApiUrl();
  }

  // Preparar encabezados
  private getHeaders() {
    const token = localStorage.getItem('token');
    return {
      'Content-Type': 'application/json',
      Authorization: `Bearer ${token}`,
    };
  }

  // Consultar registros
  async getAll() {
    return (
      await axios.get(`${this.apiUrl}/payment-methods/index.php`, {
        headers: this.getHeaders(),
      })
    ).data;
  }

  // Registrar datos
  async create(name: string) {
    return (
      await axios.post(
        `${this.apiUrl}/payment-methods/create.php`,
        { name: name.trim() },
        { headers: this.getHeaders() }
      )
    ).data;
  }

  // Cambiar estado
  async setStatus(id: number, status: 'active' | 'inactive') {
    return (
      await axios.patch(
        `${this.apiUrl}/payment-methods/status.php`,
        { id, status },
        { headers: this.getHeaders() }
      )
    ).data;
  }
}
