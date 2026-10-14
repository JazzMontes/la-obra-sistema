// Conecta la aplicación con los diagnósticos de la API.

import { Component } from '@angular/core';
import { ApiDiagnosticsService } from './services/api-diagnostics';

@Component({
  selector: 'app-root',
  templateUrl: 'app.component.html',
  styleUrls: ['app.component.scss'],
  standalone: false,
})
export class AppComponent {

  // Dependencias e inicio
  constructor(public diagnostics: ApiDiagnosticsService) {}
}
