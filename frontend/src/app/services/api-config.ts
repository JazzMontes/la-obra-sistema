// Guarda la configuración del servidor y construye la dirección de la API.

import { Injectable } from '@angular/core';

export interface DataOrigin {
  ip: string;
  protocol: 'http' | 'https';
  apachePort: number;
  mysqlPort: number;
  basePath: string;
}

@Injectable({ providedIn: 'root' })
export class ApiConfigService {

  // Datos y estado
  private readonly KEY = 'la_obra_data_origin_json';
  private readonly OLD_KEY = 'la_obra_server_ip';
  private readonly defaults: DataOrigin = {
    ip: '',
    protocol: 'http',
    apachePort: 80,
    mysqlPort: 3306,
    basePath: '/la-obra-sistema/backend',
  };

  // Leer configuración guardada
  getOrigin(): DataOrigin {
    try {
      const saved = JSON.parse(localStorage.getItem(this.KEY) || '{}');
      const ip = saved.ip || localStorage.getItem(this.OLD_KEY) || '';
      return { ...this.defaults, ...saved, ip };
    } catch {
      return { ...this.defaults, ip: localStorage.getItem(this.OLD_KEY) || '' };
    }
  }

  // Obtener IP del servidor
  getServerIp(): string {
    return this.getOrigin().ip;
  }

  // Guardar IP del servidor
  setServerIp(ip: string): void {
    const normalized = ip.trim();

    if (!this.isValidServerIp(normalized))
      throw new Error('Ingresa una dirección IPv4 válida');

    const origin = { ...this.getOrigin(), ip: normalized };
    localStorage.setItem(this.KEY, JSON.stringify(origin));
    localStorage.removeItem(this.OLD_KEY);
  }

  // Validar IP del servidor
  isValidServerIp(ip: string): boolean {
    const parts = ip.split('.');
    return (
      parts.length === 4 &&
      parts.every(
        (p) => /^\d{1,3}$/.test(p) && Number(p) >= 0 && Number(p) <= 255
      )
    );
  }

  // Construir dirección de la API
  getApiUrl(): string {
    const o = this.getOrigin();

    if (!o.ip) throw new Error('Configura la IP del servidor desde el login');

    return `${o.protocol}://${o.ip}:${o.apachePort}${o.basePath}`;
  }

  // Mostrar configuración en JSON
  getJson(): string {
    return JSON.stringify(this.getOrigin(), null, 2);
  }
}
