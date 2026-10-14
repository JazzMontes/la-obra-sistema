// Consulta la configuración guardada del servidor.

import { Component } from '@angular/core';
import { ApiConfigService } from '../../services/api-config';

@Component({
  selector: 'app-data-origins',
  templateUrl: './data-origins.page.html',
  styleUrls: ['./data-origins.page.scss'],
  standalone: false,
})
export class DataOriginsPage {

  // Dependencias e inicio
  constructor(public config: ApiConfigService) {}

  // Obtener configuración del servidor
  get origin() {
    return this.config.getOrigin();
  }

  // Mostrar configuración en JSON
  get json() {
    return this.config.getJson();
  }
}
