import { Injectable } from '@angular/core';
import axios from 'axios';

@Injectable({ providedIn: 'root' })
export class Categories {
  private apiUrl = 'http://localhost/la-obra-sistema/backend';

  private getHeaders() {
    const token = localStorage.getItem('token');
    return { 'Content-Type': 'application/json', Authorization: `Bearer ${token}` };
  }

  async getAll() {
    const response = await axios.get(`${this.apiUrl}/categories/index.php`, { headers: this.getHeaders() });
    return response.data;
  }

  async create(name: string) {
    const response = await axios.post(
      `${this.apiUrl}/categories/create.php`,
      { name: name.trim() },
      { headers: this.getHeaders() }
    );
    return response.data;
  }

  async update(id: number, name: string) {
    const response = await axios.patch(
      `${this.apiUrl}/categories/update.php`,
      { id, name: name.trim() },
      { headers: this.getHeaders() }
    );
    return response.data;
  }

  async deactivate(id: number) {
    const response = await axios.patch(
      `${this.apiUrl}/categories/deactivate.php`,
      { id },
      { headers: this.getHeaders() }
    );
    return response.data;
  }
}
