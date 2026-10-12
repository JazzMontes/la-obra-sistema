import { Component } from '@angular/core';
import { ApiDiagnosticsService } from './services/api-diagnostics';

@Component({
  selector: 'app-root',
  templateUrl: 'app.component.html',
  styleUrls: ['app.component.scss'],
  standalone: false,
})
export class AppComponent {
  constructor(public diagnostics: ApiDiagnosticsService) {}
}
