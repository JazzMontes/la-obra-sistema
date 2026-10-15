// Consulta los costos estimados de los productos.

import { Injectable } from '@angular/core';
import axios from 'axios';
import { ApiConfigService } from './api-config';

@Injectable({ providedIn: 'root' })
export class Costs {

  // Dependencias e inicio
  constructor(private apiConfig: ApiConfigService) {}

  // Obtener dirección de la API
  private get apiUrl(): string {
    return this.apiConfig.getApiUrl();
  }

  // Preparar encabezados
  private getHeaders() {
    const token = localStorage.getItem('token');
    return { Authorization: `Bearer ${token}` };
  }

  // Estos métodos quedan conectados a los endpoints definidos.
  // El backend todavía necesita Compras para poder calcular costos reales.

  // Consultar costo del producto
  async getProductCost(productId: number) {
    const response = await axios.get(`${this.apiUrl}/costs/product.php`, {
      params: { product_id: productId },
      headers: this.getHeaders(),
    });
    return response.data;
  }

  // Consultar detalle del costo
  async getProductCostDetail(productId: number) {
    const response = await axios.get(`${this.apiUrl}/costs/detail.php`, {
      params: { product_id: productId },
      headers: this.getHeaders(),
    });
    return response.data;
  }
}
