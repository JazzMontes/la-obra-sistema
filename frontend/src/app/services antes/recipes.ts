import { Injectable } from '@angular/core';
import axios from 'axios';

export interface RecipeInput {
  input_id: number;
  quantity: number;
}

@Injectable({ providedIn: 'root' })
export class Recipes {
  private apiUrl = 'http://localhost/la-obra-sistema/backend';

  private getHeaders() {
    const token = localStorage.getItem('token');
    return { 'Content-Type': 'application/json', Authorization: `Bearer ${token}` };
  }

  async getByProduct(productId: number) {
    const response = await axios.get(`${this.apiUrl}/recipes/show.php`, {
      params: { product_id: productId }, headers: this.getHeaders()
    });
    return response.data;
  }

  async create(productId: number, inputs: RecipeInput[]) {
    const response = await axios.post(
      `${this.apiUrl}/recipes/create.php`,
      { product_id: productId, inputs },
      { headers: this.getHeaders() }
    );
    return response.data;
  }

  async update(productId: number, inputs: RecipeInput[]) {
    const response = await axios.patch(
      `${this.apiUrl}/recipes/update.php`,
      { product_id: productId, inputs },
      { headers: this.getHeaders() }
    );
    return response.data;
  }
}
