// Envía las solicitudes de insumos a la API.

import { Injectable } from '@angular/core';
import axios from 'axios';
import { ApiConfigService } from './api-config';

@Injectable({ providedIn: 'root' })
export class Inputs {

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
    const response = await axios.get(`${this.apiUrl}/inputs/index.php`, {
      headers: this.getHeaders(),
    });
    return response.data;
  }

  // Consultar registro por ID
  async getById(id: number) {
    const response = await axios.get(`${this.apiUrl}/inputs/show.php`, {
      params: { id },
      headers: this.getHeaders(),
    });
    return response.data;
  }

  // Registrar datos
  async create(name: string, unit: string) {
    const response = await axios.post(
      `${this.apiUrl}/inputs/create.php`,
      { name: name.trim(), unit: unit.trim() },
      { headers: this.getHeaders() }
    );
    return response.data;
  }

  // Actualizar datos
  async update(id: number, changes: { name?: string; unit?: string }) {
    const response = await axios.patch(
      `${this.apiUrl}/inputs/update.php`,
      { id, ...changes },
      { headers: this.getHeaders() }
    );
    return response.data;
  }

  // Desactivar registro
  async deactivate(id: number) {
    const response = await axios.patch(
      `${this.apiUrl}/inputs/deactivate.php`,
      { id },
      { headers: this.getHeaders() }
    );
    return response.data;
  }
}
