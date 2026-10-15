// Envía las solicitudes de mermas y pérdidas a la API.

import { Injectable } from '@angular/core';
import axios from 'axios';
import { ApiConfigService } from './api-config';

@Injectable({ providedIn: 'root' })
export class Waste {

  // Dependencias e inicio
  constructor(private config: ApiConfigService) {}

  // Obtener dirección de la API
  private get url() {
    return this.config.getApiUrl() + '/waste';
  }

  // Preparar encabezados
  private get headers() {
    return {
      'Content-Type': 'application/json',
      Authorization: `Bearer ${localStorage.getItem('token')}`,
    };
  }

  // Consultar registros
  async list(params: any = {}) {
    return (
      await axios.get(this.url + '/index.php', {
        params,
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

  // Comparar periodos
  async compare(params: any) {
    return (
      await axios.get(this.url + '/compare.php', {
        params,
        headers: this.headers,
      })
    ).data;
  }

  // Consultar indicadores
  async indicators(params: any) {
    return (
      await axios.get(this.url + '/indicators.php', {
        params,
        headers: this.headers,
      })
    ).data;
  }

  // Consultar pérdidas por elemento
  async ranking(params: any) {
    return (
      await axios.get(this.url + '/ranking.php', {
        params,
        headers: this.headers,
      })
    ).data;
  }
}
