// Envía las solicitudes de categorías a la API.

import { Injectable } from '@angular/core';
import axios from 'axios';
import { ApiConfigService } from './api-config';

@Injectable({ providedIn: 'root' })
export class Categories {

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

  // Consultar registros
  async getAll() {
    const response = await axios.get(`${this.apiUrl}/categories/index.php`, {
      headers: this.getHeaders(),
    });
    return response.data;
  }

  // Registrar datos
  async create(name: string) {
    const response = await axios.post(
      `${this.apiUrl}/categories/create.php`,
      { name: name.trim() },
      { headers: this.getHeaders() }
    );
    return response.data;
  }

  // Actualizar datos
  async update(id: number, name: string) {
    const response = await axios.patch(
      `${this.apiUrl}/categories/update.php`,
      { id, name: name.trim() },
      { headers: this.getHeaders() }
    );
    return response.data;
  }

  // Desactivar registro
  async deactivate(id: number) {
    const response = await axios.patch(
      `${this.apiUrl}/categories/deactivate.php`,
      { id },
      { headers: this.getHeaders() }
    );
    return response.data;
  }
}
