// Envía las solicitudes de ventas y pedidos a la API.

import { Injectable } from '@angular/core';
import axios from 'axios';
import { ApiConfigService } from './api-config';

@Injectable({ providedIn: 'root' })
export class Sales {

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

  // Registrar pago
  async pay(orderId: number, paymentMethodId: number, cashReceived?: number) {
    const body: any = { order_id: orderId, payment_method_id: paymentMethodId };

    if (cashReceived !== undefined) body.cash_received = cashReceived;

    return (
      await axios.post(`${this.apiUrl}/sales/payment.php`, body, {
        headers: this.getHeaders(),
      })
    ).data;
  }

  // Consultar registros
  async getAll(filters: any = {}) {
    return (
      await axios.get(`${this.apiUrl}/sales/index.php`, {
        params: filters,
        headers: this.getHeaders(),
      })
    ).data;
  }

  // Consultar registro por ID
  async getById(id: number) {
    return (
      await axios.get(`${this.apiUrl}/sales/show.php`, {
        params: { id },
        headers: this.getHeaders(),
      })
    ).data;
  }

  // Cancelar
  async cancel(orderId: number) {
    return (
      await axios.patch(
        `${this.apiUrl}/sales/cancel.php`,
        { order_id: orderId },
        { headers: this.getHeaders() }
      )
    ).data;
  }
}
