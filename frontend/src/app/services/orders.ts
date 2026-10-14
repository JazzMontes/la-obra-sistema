// Envía las solicitudes para gestionar pedidos y sus productos.

import { Injectable } from '@angular/core';
import axios from 'axios';
import { ApiConfigService } from './api-config';

@Injectable({ providedIn: 'root' })
export class Orders {

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

  // Registrar datos
  async create() {
    return (
      await axios.post(
        `${this.apiUrl}/orders/create.php`,
        {},
        { headers: this.getHeaders() }
      )
    ).data;
  }

  // Consultar pedidos activos
  async getActive() {
    return (
      await axios.get(`${this.apiUrl}/orders/active.php`, {
        headers: this.getHeaders(),
      })
    ).data;
  }

  // Consultar registro por ID
  async getById(id: number) {
    return (
      await axios.get(`${this.apiUrl}/orders/show.php`, {
        params: { id },
        headers: this.getHeaders(),
      })
    ).data;
  }

  // Agregar producto
  async addItem(orderId: number, productId: number, quantity: number) {
    return (
      await axios.post(
        `${this.apiUrl}/orders/add-item.php`,
        { order_id: orderId, product_id: productId, quantity },
        { headers: this.getHeaders() }
      )
    ).data;
  }

  // Actualizar producto del pedido
  async updateItem(orderId: number, productId: number, quantity: number) {
    return (
      await axios.patch(
        `${this.apiUrl}/orders/update-item.php`,
        { order_id: orderId, product_id: productId, quantity },
        { headers: this.getHeaders() }
      )
    ).data;
  }

  // Quitar producto
  async removeItem(orderId: number, productId: number) {
    return (
      await axios.delete(`${this.apiUrl}/orders/remove-item.php`, {
        headers: this.getHeaders(),
        data: { order_id: orderId, product_id: productId },
      })
    ).data;
  }

  // Confirmar pedido
  async confirm(orderId: number) {
    return (
      await axios.patch(
        `${this.apiUrl}/orders/confirm.php`,
        { order_id: orderId },
        { headers: this.getHeaders() }
      )
    ).data;
  }

  // Marcar pedido entregado
  async deliver(orderId: number) {
    return (
      await axios.patch(
        `${this.apiUrl}/orders/deliver.php`,
        { order_id: orderId },
        { headers: this.getHeaders() }
      )
    ).data;
  }

  // Cancelar
  async cancel(orderId: number) {
    return (
      await axios.patch(
        `${this.apiUrl}/orders/cancel.php`,
        { order_id: orderId },
        { headers: this.getHeaders() }
      )
    ).data;
  }
}
