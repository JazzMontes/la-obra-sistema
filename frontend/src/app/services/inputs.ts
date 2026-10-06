import { Injectable } from '@angular/core';
import axios from 'axios';

@Injectable({ providedIn: 'root' })
export class Inputs {
  private apiUrl = 'http://localhost/la-obra-sistema/backend';

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
