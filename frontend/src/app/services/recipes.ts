// Envía las solicitudes de recetas a la API.

import { Injectable } from '@angular/core';
import axios from 'axios';
import { ApiConfigService } from './api-config';

export interface RecipeInput {
  input_id: number;
  quantity: number;
}

@Injectable({ providedIn: 'root' })
export class Recipes {

  // Dependencias e inicio
  constructor(private apiConfig: ApiConfigService) {}

  // Obtener dirección de la API
  private get apiUrl(): string {
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

  // Consultar receta del producto
  async getByProduct(productId: number) {
    const response = await axios.get(`${this.apiUrl}/recipes/show.php`, {
      params: { product_id: productId },
      headers: this.getHeaders(),
    });
    return response.data;
  }

  // Registrar datos
  async create(productId: number, inputs: RecipeInput[]) {
    const response = await axios.post(
      `${this.apiUrl}/recipes/create.php`,
      { product_id: productId, inputs },
      { headers: this.getHeaders() }
    );
    return response.data;
  }

  // Actualizar datos
  async update(productId: number, inputs: RecipeInput[]) {
    const response = await axios.patch(
      `${this.apiUrl}/recipes/update.php`,
      { product_id: productId, inputs },
      { headers: this.getHeaders() }
    );
    return response.data;
  }
}
