// Define los datos de los reportes y los consulta en la API.

import { Injectable } from '@angular/core';
import axios from 'axios';
import { ApiConfigService } from './api-config';

export interface ProductReport {
  product_id: number;
  name: string;
  quantity: number;
  amount: number;
}

export interface DailyReport {
  date: string;
  amount: number;
  orders: number;
}

export interface MethodReport {
  method_id: number;
  name: string;
  amount: number;
  orders: number;
}

export interface CategoryReport {
  date: string;
  category_id: number;
  category: string;
  amount: number;
}

@Injectable({ providedIn: 'root' })
export class ReportsService {

  // Dependencias e inicio
  constructor(private config: ApiConfigService) {}

  // Consultar reporte
  async get<T>(report: string, from: string, to: string): Promise<T[]> {
    const response = await axios.get(
      `${this.config.getApiUrl()}/reports/${report}.php`,
      {
        params: { from, to },
        headers: { Authorization: `Bearer ${localStorage.getItem('token')}` },
        timeout: 15000,
      }
    );
    return response.data.data;
  }
}
