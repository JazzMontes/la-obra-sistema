// Servidor: a qué computadora se conecta la app (arquitectura cliente-servidor).
import { ChangeDetectionStrategy, Component } from '@angular/core';
import axios from 'axios';
import { ApiConfigService, DataOrigin } from '../../services/api-config';
import { UiService } from '../../services/ui';
import { Auth } from '../../services/auth';
import { errorMessage } from '../../services/api';
import { autoRefresh } from '../../services/render';

@Component({
  changeDetection: ChangeDetectionStrategy.Default,
  selector: 'app-data-origins',
  templateUrl: 'data-origins.page.html',
  styleUrls: ['data-origins.page.scss'],
  standalone: false,
})
export class DataOriginsPage {
  // Actualiza la vista al terminar peticiones y al entrar a la pantalla (Angular sin zone.js)
  private readonly refresher = autoRefresh();

  form: DataOrigin;
  testing = false;
  result: { ok: boolean; text: string; ms?: number; time?: string; missing?: string[]; migrations?: string[] } | null = null;

  constructor(private config: ApiConfigService, private ui: UiService, private auth: Auth) {
    this.form = this.config.getOrigin();
  }

  get loggedIn(): boolean {
    return this.auth.isLoggedIn();
  }

  get preview(): string {
    return `${this.form.protocol}://${this.form.ip || '<IP>'}:${this.form.apachePort}${this.form.basePath}`;
  }

  get json(): string {
    return this.config.getJson();
  }

  ionViewWillEnter() {
    this.form = this.config.getOrigin();
  }

  save() {
    try {
      this.config.setOrigin(this.form);
      this.form = this.config.getOrigin();
      this.ui.success('Configuración guardada');
      this.test();
    } catch (e: any) {
      this.ui.error(e.message);
    }
  }

  async test() {
    this.testing = true;
    const start = performance.now();
    try {
      const r = await axios.get(`${this.config.getApiUrl()}/health.php`, { timeout: 6000 });
      this.result = {
        ok: !!r.data?.success,
        text: r.data?.message ?? 'Respuesta recibida',
        ms: Math.round(performance.now() - start),
        time: r.data?.server_time,
        missing: r.data?.missing ?? [],
        migrations: r.data?.run_migrations ?? [],
      };
    } catch (e) {
      this.result = { ok: false, text: errorMessage(e, 'No se pudo conectar') };
    } finally {
      this.testing = false;
    }
  }
}
