import { Injectable } from '@angular/core';

@Injectable({
  providedIn: 'root'
})
export class ApiConfigService {

  private readonly SERVER_IP = '192.168.1.122';
  private readonly APACHE_PORT = '80';

  constructor() {}

  getApiUrl(): string {
    return `http://${this.SERVER_IP}:${this.APACHE_PORT}/la-obra-sistema/backend`;
  }
}
