import { Injectable } from '@angular/core';
import axios from 'axios';
import { ApiConfigService } from './api-config';

@Injectable({ providedIn: 'root' })
export class Costs {
  constructor(private apiConfig: ApiConfigService) {}

  private get apiUrl(): string {
    return this.apiConfig.getApiUrl();
  }

  private getHeaders() {
    const token = localStorage.getItem('token');
    return { Authorization: `Bearer ${token}` };
  }

  // Estos métodos quedan conectados a los endpoints definidos.
  // El backend todavía necesita Compras para poder calcular costos reales.
  async getProductCost(productId: number) {
    const response = await axios.get(`${this.apiUrl}/costs/product.php`, {
      params: { product_id: productId }, headers: this.getHeaders()
    });
    return response.data;
  }

  async getProductCostDetail(productId: number) {
    const response = await axios.get(`${this.apiUrl}/costs/detail.php`, {
      params: { product_id: productId }, headers: this.getHeaders()
    });
    return response.data;
  }
}
