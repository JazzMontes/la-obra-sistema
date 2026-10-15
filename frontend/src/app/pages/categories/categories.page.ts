// Controla la consulta y gestión de categorías.

import { Component, ChangeDetectorRef } from '@angular/core';
import { Categories } from '../../services/categories';

@Component({
  selector: 'app-categories',
  templateUrl: 'categories.page.html',
  styleUrls: ['categories.page.scss'],
  standalone: false,
})
export class CategoriesPage {

  // Datos y estado
  categories: any[] = [];
  name = '';
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
    private service: Categories
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
      this.categories = r.categories || r.data || [];
    } catch (e: any) {
      this.message =
        e.response?.data?.message || 'No se pudieron cargar las categorías';
    } finally {
      this.loading = false;
      this.cdr.detectChanges();
    }
  }

  // Preparar edición
  edit(c: any) {
    this.modalOpen = true;
    this.editingId = Number(c.id);
    this.name = c.name;
  }

  // Cancelar
  cancel() {
    this.modalOpen = false;
    this.editingId = null;
    this.name = '';
  }

  // Guardar datos
  async save() {
    if (this.saving) return;

    if (!this.name.trim()) {
      this.message = 'Completa los datos requeridos con valores válidos';
      return;
    }

    this.saving = true;

    try {
      if (this.editingId) await this.service.update(this.editingId, this.name);
      else await this.service.create(this.name);

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
