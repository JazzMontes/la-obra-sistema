// Controla la consulta y gestión de métodos de pago.

import { Component, ChangeDetectorRef } from '@angular/core';
import { PaymentMethods } from '../../services/payment-methods';

@Component({
  selector: 'app-payment-methods',
  templateUrl: 'payment-methods.page.html',
  styleUrls: ['payment-methods.page.scss'],
  standalone: false,
})
export class PaymentMethodsPage {

  // Datos y estado
  methods: any[] = [];
  name = '';
  message = '';
  modalOpen = false;
  saving = false;

  // Dependencias e inicio
  constructor(
    private service: PaymentMethods,
    private cdr: ChangeDetectorRef
  ) {}

  // Entrada a la pantalla
  async ionViewWillEnter() {
    await this.load();
  }

  // Cargar datos
  async load() {
    try {
      const r = await this.service.getAll();
      this.methods = r.payment_methods || r.methods || r.data || [];
    } catch (e: any) {
      this.message =
        e.response?.data?.message ||
        'No se pudieron cargar los métodos de pago';
    } finally {
      this.cdr.detectChanges();
    }
  }

  // Registrar datos
  async create() {
    if (!this.name.trim()) return;

    this.saving = true;

    try {
      await this.service.create(this.name);
      this.name = '';
      this.modalOpen = false;
      this.message = 'Método agregado correctamente';
      await this.load();
    } catch (e: any) {
      this.message = e.response?.data?.message || 'No se pudo agregar';
    } finally {
      this.saving = false;
    }
  }

  // Cambiar estado
  async toggle(m: any) {
    try {
      await this.service.setStatus(
        Number(m.id),
        m.status === 'active' ? 'inactive' : 'active'
      );
      await this.load();
    } catch (e: any) {
      this.message =
        e.response?.data?.message || 'No se pudo cambiar el estado';
    }
  }
}
