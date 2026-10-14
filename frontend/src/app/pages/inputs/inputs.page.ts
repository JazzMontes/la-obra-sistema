// Controla la consulta y gestión de insumos.

import { Component, ChangeDetectorRef } from '@angular/core';
import { Inputs } from '../../services/inputs';

@Component({
  selector: 'app-inputs',
  templateUrl: 'inputs.page.html',
  styleUrls: ['inputs.page.scss'],
  standalone: false,
})
export class InputsPage {

  // Datos y estado
  inputs: any[] = [];
  form: any = { name: '', unit: '' };
  editingId: number | null = null;
  message = '';
  modalOpen = false;
  saving = false;
  loading = false;

  // Abrir formulario de registro
  openCreate() {
    this.cancel();
    this.message = '';
    this.modalOpen = true;
  }

  // Dependencias e inicio
  constructor(
    private cdr: ChangeDetectorRef,
    private service: Inputs
  ) {}

  // Entrada a la pantalla
  async ionViewWillEnter() {
    await this.load();
  }

  // Cargar datos
  async load() {
    this.loading = true;
    this.message = '';

    try {
      const r = await this.service.getAll();
      this.inputs = r.inputs || r.data || [];
    } catch (e: any) {
      this.message =
        e.response?.data?.message || 'No se pudieron cargar los insumos';
    } finally {
      this.loading = false;
      this.cdr.detectChanges();
    }
  }

  // Preparar edición
  edit(i: any) {
    this.modalOpen = true;
    this.editingId = Number(i.id);
    this.form = { name: i.name, unit: i.unit };
  }

  // Cancelar
  cancel() {
    this.modalOpen = false;
    this.editingId = null;
    this.form = { name: '', unit: '' };
  }

  // Guardar datos
  async save() {
    if (this.saving) return;

    if (!this.form.name.trim() || !this.form.unit.trim()) {
      this.message = 'Completa los datos requeridos con valores válidos';
      return;
    }

    this.saving = true;

    try {
      if (this.editingId) await this.service.update(this.editingId, this.form);
      else await this.service.create(this.form.name, this.form.unit);

      this.cancel();
      await this.load();
    } catch (e: any) {
      this.message = e.response?.data?.message || 'No se pudo guardar';
    } finally {
      this.saving = false;
      this.cdr.detectChanges();
    }
  }

  // Desactivar registro
  async deactivate(id: number) {
    try {
      await this.service.deactivate(Number(id));
      await this.load();
    } catch (e: any) {
      this.message = e.response?.data?.message || 'No se pudo desactivar';
    }
  }
}
