import { Injectable } from '@angular/core';
import axios from 'axios';
import { ApiConfigService } from './api-config';

@Injectable({ providedIn: 'root' })
export class Inputs {
  constructor(private apiConfig: ApiConfigService) {}

  private get apiUrl(): string {
    return this.apiConfig.getApiUrl();
  }

  private getHeaders() {
    const token = localStorage.getItem('token');
    return { 'Content-Type': 'application/json', Authorization: `Bearer ${token}` };
  }

  async getAll() {
    const response = await axios.get(`${this.apiUrl}/inputs/index.php`, { headers: this.getHeaders() });
    return response.data;
  }

  async getById(id: number) {
    const response = await axios.get(`${this.apiUrl}/inputs/show.php`, {
      params: { id }, headers: this.getHeaders()
    });
    return response.data;
  }

  async create(name: string, unit: string) {
    const response = await axios.post(
      `${this.apiUrl}/inputs/create.php`,
      { name: name.trim(), unit: unit.trim() },
      { headers: this.getHeaders() }
    );
    return response.data;
  }

  async update(id: number, changes: { name?: string; unit?: string }) {
    const response = await axios.patch(
      `${this.apiUrl}/inputs/update.php`,
      { id, ...changes },
      { headers: this.getHeaders() }
    );
    return response.data;
  }

  async deactivate(id: number) {
    const response = await axios.patch(
      `${this.apiUrl}/inputs/deactivate.php`,
      { id },
      { headers: this.getHeaders() }
    );
    return response.data;
  }
}
