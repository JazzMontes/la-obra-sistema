import { Injectable } from '@angular/core';
import axios from 'axios';
import { ApiConfigService } from './api-config';

@Injectable({ providedIn: 'root' })
export class Orders {
  constructor(private apiConfig: ApiConfigService) {}
  private get apiUrl(){ return this.apiConfig.getApiUrl(); }
  private getHeaders(){ const token=localStorage.getItem('token'); return {'Content-Type':'application/json',Authorization:`Bearer ${token}`}; }
  async create(){ return (await axios.post(`${this.apiUrl}/orders/create.php`,{}, {headers:this.getHeaders()})).data; }
  async getActive(){ return (await axios.get(`${this.apiUrl}/orders/active.php`,{headers:this.getHeaders()})).data; }
  async getById(id:number){ return (await axios.get(`${this.apiUrl}/orders/show.php`,{params:{id},headers:this.getHeaders()})).data; }
  async addItem(orderId:number,productId:number,quantity:number){ return (await axios.post(`${this.apiUrl}/orders/add-item.php`,{order_id:orderId,product_id:productId,quantity},{headers:this.getHeaders()})).data; }
  async updateItem(orderId:number,productId:number,quantity:number){ return (await axios.patch(`${this.apiUrl}/orders/update-item.php`,{order_id:orderId,product_id:productId,quantity},{headers:this.getHeaders()})).data; }
  async removeItem(orderId:number,productId:number){ return (await axios.delete(`${this.apiUrl}/orders/remove-item.php`,{headers:this.getHeaders(),data:{order_id:orderId,product_id:productId}})).data; }
  async confirm(orderId:number){ return (await axios.patch(`${this.apiUrl}/orders/confirm.php`,{order_id:orderId},{headers:this.getHeaders()})).data; }
  async deliver(orderId:number){ return (await axios.patch(`${this.apiUrl}/orders/deliver.php`,{order_id:orderId},{headers:this.getHeaders()})).data; }
  async cancel(orderId:number){ return (await axios.patch(`${this.apiUrl}/orders/cancel.php`,{order_id:orderId},{headers:this.getHeaders()})).data; }
}
