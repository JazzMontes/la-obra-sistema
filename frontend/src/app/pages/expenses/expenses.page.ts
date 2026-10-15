// Controla la consulta y gestión de gastos.

import { Component, ChangeDetectorRef } from '@angular/core';
import { Expenses } from '../../services/expenses';
import { Auth } from '../../services/auth';

@Component({
  selector: 'app-expenses',
  templateUrl: 'expenses.page.html',
  styleUrls: ['expenses.page.scss'],
  standalone: false,
})
export class ExpensesPage {

  // Datos y estado
  records: any[] = [];
  detail: any = null;
  history: any[] = [];
  loading = false;
  saving = false;
  modalOpen = false;
  detailOpen = false;
  historyOpen = false;
  message = '';
  cancelOpen = false;
  cancelTarget: any = null;
  cancelReason = '';
  cancelError = '';
  cancelling = false;
  editingId: number | null = null;
  form: any = { amount: null, reason: '', change_reason: '' };
  status = '';
  from = '';
  to = '';
  userId: any = null;
  isAdmin = false;

  // Dependencias e inicio
  constructor(
    private api: Expenses,
    private auth: Auth,
    private cdr: ChangeDetectorRef
  ) {
    this.isAdmin = this.auth.getStoredUser()?.role === 'admin';
  }

  // Entrada a la pantalla
  async ionViewWillEnter() {
    await this.load();
  }

  // Cargar datos
  async load() {
    this.loading = true;

    try {
      const r = await this.api.list({
        status: this.status || undefined,
        date_from: this.from || undefined,
        date_to: this.to || undefined,
        user_id: this.isAdmin && this.userId ? this.userId : undefined,
      });
      this.records = r.expenses || [];
      this.message = '';
    } catch (e: any) {
      this.message =
        e.response?.data?.message || 'No se pudieron consultar los gastos';
    } finally {
      this.loading = false;
      this.cdr.detectChanges();
    }
  }

  // Abrir formulario de registro
  openCreate() {
    this.editingId = null;
    this.form = { amount: null, reason: '', change_reason: '' };
    this.message = '';
    this.modalOpen = true;
  }

  // Preparar edición
  edit(e: any) {
    if (e.status !== 'active') return;

    this.editingId = +e.id;
    this.form = { amount: +e.amount, reason: e.reason, change_reason: '' };
    this.message = '';
    this.modalOpen = true;
  }

  // Consultar detalle
  async show(e: any) {
    try {
      this.detail = (await this.api.show(+e.id)).expense;
      this.detailOpen = true;
    } catch (x: any) {
      this.message =
        x.response?.data?.message || 'No se pudo consultar el gasto';
    }
  }

  // Consultar historial
  async viewHistory(e: any) {
    try {
      this.history = (await this.api.history(+e.id)).history || [];
      this.historyOpen = true;
    } catch (x: any) {
      this.message =
        x.response?.data?.message || 'No se pudo consultar el historial';
    }
  }

  // Guardar datos
  async save() {
    if (this.saving) return;

    if (
      !(Number(this.form.amount) > 0) ||
      !String(this.form.reason || '').trim() ||
      (this.editingId && !String(this.form.change_reason || '').trim())
    ) {
      this.message =
        'Completa monto, motivo y, si corriges, la razón del cambio';
      return;
    }

    this.saving = true;

    try {
      if (this.editingId)
        await this.api.update({
          id: this.editingId,
          amount: +this.form.amount,
          reason: this.form.reason.trim(),
          change_reason: this.form.change_reason.trim(),
        });
      else
        await this.api.create({
          amount: +this.form.amount,
          reason: this.form.reason.trim(),
        });

      this.modalOpen = false;
      await this.load();
    } catch (x: any) {
      this.message = x.response?.data?.message || 'No se pudo guardar';
    } finally {
      this.saving = false;
      this.cdr.detectChanges();
    }
  }

  // Abrir cancelación
  openCancel(e: any) {
    if (e.status !== 'active') return;

    this.cancelTarget = e;
    this.cancelReason = '';
    this.cancelError = '';
    this.cancelOpen = true;
  }

  // Cerrar cancelación
  closeCancel() {
    if (!this.cancelling) this.cancelOpen = false;
  }

  // Confirmar cancelación
  async confirmCancel() {
    if (this.cancelling || !this.cancelTarget) return;

    const reason = this.cancelReason.trim();

    if (!reason) {
      this.cancelError = 'Escribe el motivo de la cancelación.';
      return;
    }

    this.cancelling = true;
    this.cancelError = '';

    try {
      await this.api.cancel(+this.cancelTarget.id, reason);
      this.cancelOpen = false;
      await this.load();
    } catch (x: any) {
      this.cancelError =
        x.response?.data?.message || 'No se pudo cancelar el gasto.';
    } finally {
      this.cancelling = false;
      this.cdr.detectChanges();
    }
  }

  // Leer datos del historial
  parseHistoryData(data: any): any {
    if (data === null || data === undefined) return {};

    if (typeof data === 'object') return data;

    try {
      const parsed = JSON.parse(data);
      return parsed && typeof parsed === 'object' ? parsed : {};
    } catch {
      return {};
    }
  }

  // Obtener valor del historial
  historyValue(h: any, field: string, previous = false): any {
    const data = this.parseHistoryData(previous ? h.previous_data : h.new_data);
    return data[field];
  }

  // Mostrar monto del historial
  historyAmount(h: any, previous = false): string {
    const amount = this.historyValue(h, 'amount', previous);
    return amount === null || amount === undefined
      ? '—'
      : new Intl.NumberFormat('es-MX', {
          style: 'currency',
          currency: 'MXN',
        }).format(Number(amount));
  }

  // Mostrar estado del historial
  historyStatus(h: any, previous = false): string {
    const status = this.historyValue(h, 'status', previous);
    return status === 'cancelled'
      ? 'Cancelado'
      : status === 'active'
        ? 'Activo'
        : '—';
  }
}
