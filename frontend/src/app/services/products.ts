import { Injectable } from '@angular/core';
import axios from 'axios';
import { ApiConfigService } from './api-config';

@Injectable({ providedIn: 'root' })
export class Products {
  constructor(private apiConfig: ApiConfigService) {}

  private get apiUrl(): string {
    return this.apiConfig.getApiUrl();
  }

  private getHeaders() {
    const token = localStorage.getItem('token');
    return {
      'Content-Type': 'application/json',
      Authorization: `Bearer ${token}`
    };
  }

  async getAll() {
    const response = await axios.get(`${this.apiUrl}/products/index.php`, {
      headers: this.getHeaders()
    });
    return response.data;
  }

  async getById(id: number) {
    const response = await axios.get(`${this.apiUrl}/products/show.php`, {
      params: { id },
      headers: this.getHeaders()
    });
    return response.data;
  }

  async create(name: string, description: string, categoryId: number, type: 'prepared' | 'resale', price: number) {
    const response = await axios.post(
      `${this.apiUrl}/products/create.php`,
      { name: name.trim(), description: description.trim(), category_id: categoryId, type, price },
      { headers: this.getHeaders() }
    );
    return response.data;
  }

  async update(id: number, changes: {
    name?: string;
    description?: string;
    category_id?: number;
    type?: 'prepared' | 'resale';
    price?: number;
    is_available?: boolean;
  }) {
    const response = await axios.patch(
      `${this.apiUrl}/products/update.php`,
      { id, ...changes },
      { headers: this.getHeaders() }
    );
    return response.data;
  }

  async deactivate(id: number) {
    const response = await axios.patch(
      `${this.apiUrl}/products/deactivate.php`,
      { id },
      { headers: this.getHeaders() }
    );
    return response.data;
  }

  async activate(id: number) {
    const response = await axios.patch(
      `${this.apiUrl}/products/activate.php`,
      { id },
      { headers: this.getHeaders() }
    );
    return response.data;
  }
}
