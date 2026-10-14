// Controla la consulta y gestión de compras.

import { Component, ChangeDetectorRef } from '@angular/core';
import { Purchases } from '../../services/purchases';
import { Inputs } from '../../services/inputs';
import { PaymentMethods } from '../../services/payment-methods';

@Component({
  selector: 'app-purchases',
  templateUrl: 'purchases.page.html',
  styleUrls: ['purchases.page.scss'],
  standalone: false,
})
export class PurchasesPage {

  // Datos y estado
  records: any[] = [];
  inputs: any[] = [];
  methods: any[] = [];
  prices: any[] = [];
  detail: any = null;
  loading = false;
  saving = false;
  modalOpen = false;
  detailOpen = false;
  pricesOpen = false;
  message = '';
  from = '';
  to = '';
  selectedInput: any = null;
  editingId: number | null = null;
  form: any = this.blank();

  // Dependencias e inicio
  constructor(
    private api: Purchases,
    private inputApi: Inputs,
    private methodApi: PaymentMethods,
    private cdr: ChangeDetectorRef
  ) {}

  // Preparar formulario vacío
  blank() {
    return {
      purchase_date: new Date().toLocaleDateString('en-CA'),
      supplier: '',
      payment_method_id: null,
      items: [{ input_id: null, quantity: 1, unit_cost: 0 }],
      change_reason: '',
    };
  }

  // Entrada a la pantalla
  async ionViewWillEnter() {
    await this.load();

    try {
      const [i, m] = await Promise.all([
        this.inputApi.getAll(),
        this.methodApi.getAll(),
      ]);
      this.inputs = i.inputs || i.data || [];
      this.methods = m.payment_methods || m.methods || m.data || [];
    } catch (e: any) {
      this.message =
        e.response?.data?.message || 'No se cargaron los catálogos';
    }

    this.cdr.detectChanges();
  }

  // Cargar datos
  async load() {
    this.loading = true;

    try {
      const r = await this.api.list({
        date_from: this.from || undefined,
        date_to: this.to || undefined,
      });
      this.records = r.purchases || [];
      this.message = '';
    } catch (e: any) {
      this.message =
        e.response?.data?.message || 'No se pudieron consultar las compras';
    } finally {
      this.loading = false;
      this.cdr.detectChanges();
    }
  }

  // Abrir formulario de registro
  openCreate() {
    this.editingId = null;
    this.form = this.blank();
    this.message = '';
    this.modalOpen = true;
  }

  // Preparar edición
  async edit(row: any) {
    try {
      const r = await this.api.show(+row.id);
      const p = r.purchase;
      this.editingId = +p.id;
      this.form = {
        purchase_date: p.purchase_date,
        supplier: p.supplier || '',
        payment_method_id: +p.payment_method_id,
        items: p.items.map((x: any) => ({
          input_id: +x.input_id,
          quantity: +x.quantity,
          unit_cost: +x.unit_cost,
        })),
        change_reason: '',
      };
      this.message = '';
      this.modalOpen = true;
    } catch (e: any) {
      this.message =
        e.response?.data?.message || 'No se pudo consultar la compra';
    }
  }

  // Consultar detalle
  async show(row: any) {
    try {
      this.detail = (await this.api.show(+row.id)).purchase;
      this.detailOpen = true;
    } catch (e: any) {
      this.message = e.response?.data?.message || 'No se pudo abrir el detalle';
    }
  }

  // Consultar historial de precios
  async priceHistory(input: any) {
    try {
      this.selectedInput = input;
      this.prices = (await this.api.prices(+input.id)).prices || [];
      this.pricesOpen = true;
    } catch (e: any) {
      this.message =
        e.response?.data?.message || 'No se pudo consultar el historial';
    }
  }

  // Agregar insumo
  addItem() {
    this.form.items.push({ input_id: null, quantity: 1, unit_cost: 0 });
  }

  // Quitar insumo
  removeItem(i: number) {
    if (this.form.items.length > 1) this.form.items.splice(i, 1);
  }

  // Calcular total
  get total() {
    return this.form.items.reduce(
      (s: number, x: any) =>
        s + (Number(x.quantity) || 0) * (Number(x.unit_cost) || 0),
      0
    );
  }

  // Guardar datos
  async save() {
    if (this.saving) return;

    const f = this.form;

    if (
      !f.purchase_date ||
      !f.payment_method_id ||
      !f.items.length ||
      f.items.some(
        (x: any) =>
          !x.input_id ||
          !(Number(x.quantity) > 0) ||
          Number(x.unit_cost) < 0 ||
          x.unit_cost === ''
      )
    ) {
      this.message =
        'Completa la fecha, método de pago e insumos con valores válidos';
      return;
    }

    this.saving = true;

    try {
      const d = {
        ...f,
        payment_method_id: +f.payment_method_id,
        items: f.items.map((x: any) => ({
          input_id: +x.input_id,
          quantity: +x.quantity,
          unit_cost: +x.unit_cost,
        })),
      };

      if (this.editingId) await this.api.update({ id: this.editingId, ...d });
      else await this.api.create(d);

      this.modalOpen = false;
      await this.load();
    } catch (e: any) {
      this.message =
        e.response?.data?.message || 'No se pudo guardar la compra';
    } finally {
      this.saving = false;
      this.cdr.detectChanges();
    }
  }
}
