// Envía las solicitudes de compras a la API.

import { Injectable } from '@angular/core';
import axios from 'axios';
import { ApiConfigService } from './api-config';

@Injectable({ providedIn: 'root' })
export class Purchases {

  // Dependencias e inicio
  constructor(private config: ApiConfigService) {}

  // Obtener dirección de la API
  private get url() {
    return this.config.getApiUrl() + '/purchases';
  }

  // Preparar encabezados
  private get headers() {
    return {
      'Content-Type': 'application/json',
      Authorization: `Bearer ${localStorage.getItem('token')}`,
    };
  }

  // Consultar registros
  async list(filters: any = {}) {
    return (
      await axios.get(this.url + '/index.php', {
        params: filters,
        headers: this.headers,
      })
    ).data;
  }

  // Consultar detalle
  async show(id: number) {
    return (
      await axios.get(this.url + '/show.php', {
        params: { id },
        headers: this.headers,
      })
    ).data;
  }

  // Registrar datos
  async create(data: any) {
    return (
      await axios.post(this.url + '/create.php', data, {
        headers: this.headers,
      })
    ).data;
  }

  // Actualizar datos
  async update(data: any) {
    return (
      await axios.patch(this.url + '/update.php', data, {
        headers: this.headers,
      })
    ).data;
  }

  // Consultar precios
  async prices(input_id: number) {
    return (
      await axios.get(this.url + '/prices.php', {
        params: { input_id },
        headers: this.headers,
      })
    ).data;
  }
}
