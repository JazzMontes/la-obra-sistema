// Gestiona pedidos, productos, pagos y el historial de ventas.

import { Component, ChangeDetectorRef } from '@angular/core';
import { Orders } from '../../services/orders';
import { Sales } from '../../services/sales';
import { PaymentMethods } from '../../services/payment-methods';
import { Products } from '../../services/products';
import { Auth } from '../../services/auth';

@Component({
  selector: 'app-sales',
  templateUrl: 'sales.page.html',
  styleUrls: ['sales.page.scss'],
  standalone: false,
})
export class SalesPage {

  // Datos y estado
  activeOrders: any[] = [];
  history: any[] = [];
  products: any[] = [];
  methods: any[] = [];
  current: any = null;
  user: any = null;
  historyDate = '';
  loading = false;
  saving = false;
  message = '';
  modalOpen = false;
  payOpen = false;
  selectedProductId: number | null = null;
  quantity = 1;
  paymentMethodId: number | null = null;
  cashReceived: number | null = null;

  // Dependencias e inicio
  constructor(
    private orders: Orders,
    private sales: Sales,
    private paymentMethods: PaymentMethods,
    private productsService: Products,
    private auth: Auth,
    private cdr: ChangeDetectorRef
  ) {
    this.user = this.auth.getStoredUser();
  }

  // Comprobar rol de administrador
  get isAdmin() {
    return this.user?.role === 'admin';
  }

  // Entrada a la pantalla
  async ionViewWillEnter() {
    this.user = this.auth.getStoredUser();
    await this.load();
  }

  // Obtener mensaje de error
  error(e: any, fallback: string) {
    return e.response?.data?.message || fallback;
  }

  // Cargar datos
  async load() {
    this.loading = true;
    this.message = '';

    try {
      const filters: any = {};

      if (this.isAdmin && this.historyDate) filters.date = this.historyDate;

      const [o, h, p, m] = await Promise.all([
        this.orders.getActive(),
        this.sales.getAll(filters),
        this.productsService.getAll(),
        this.paymentMethods.getAll(),
      ]);
      this.activeOrders = o.orders || [];
      this.history = h.sales || [];
      this.products = (p.products || p.data || []).filter(
        (x: any) =>
          x.status === 'active' && Boolean(Number(x.is_available ?? 1))
      );
      this.methods = (m.payment_methods || m.methods || m.data || []).filter(
        (x: any) => x.status === 'active'
      );
    } catch (e: any) {
      this.message = this.error(e, 'No se pudo cargar el punto de venta');
    } finally {
      this.loading = false;
      this.cdr.detectChanges();
    }
  }

  // Filtrar historial
  async filterHistory() {
    await this.load();
  }

  // Limpiar fecha del historial
  clearHistoryDate() {
    this.historyDate = '';
    this.load();
  }

  // Crear pedido
  async newOrder() {
    if (this.saving) return;

    this.saving = true;

    try {
      const r = await this.orders.create();
      await this.openOrder(r.order.id);
      this.message = 'Pedido creado correctamente';
    } catch (e: any) {
      this.message = this.error(e, 'No se pudo crear el pedido');
    } finally {
      this.saving = false;
      this.cdr.detectChanges();
    }
  }

  // Abrir pedido
  async openOrder(id: number) {
    try {
      const r = await this.orders.getById(Number(id));
      this.current = r.order;
      this.modalOpen = true;
      this.selectedProductId = null;
      this.quantity = 1;
    } catch (e: any) {
      this.message = this.error(e, 'No se pudo abrir el pedido');
    }
  }

  // Abrir venta del historial
  async openHistory(id: number) {
    try {
      const r = await this.sales.getById(Number(id));
      this.current = r.sale;
      this.modalOpen = true;
      this.selectedProductId = null;
      this.quantity = 1;
    } catch (e: any) {
      this.message = this.error(e, 'No se pudo abrir el historial del pedido');
    }
  }

  // Cerrar pedido
  closeOrder() {
    this.modalOpen = false;
    this.current = null;
    this.selectedProductId = null;
    this.quantity = 1;
  }

  // Actualizar pedido actual
  async refreshCurrent() {
    if (!this.current) return;

    const r = await this.orders.getById(Number(this.current.id));
    this.current = r.order;
    await this.load();
  }

  // Agregar producto
  async addItem() {
    if (!this.current || !this.selectedProductId || Number(this.quantity) <= 0)
      return;

    this.saving = true;

    try {
      await this.orders.addItem(
        this.current.id,
        Number(this.selectedProductId),
        Number(this.quantity)
      );
      await this.refreshCurrent();
      this.selectedProductId = null;
      this.quantity = 1;
    } catch (e: any) {
      this.message = this.error(e, 'No se pudo agregar el producto');
    } finally {
      this.saving = false;
      this.cdr.detectChanges();
    }
  }

  // Cambiar cantidad
  async changeQty(item: any, delta: number) {
    const q = Number(item.quantity) + delta;

    if (q <= 0) {
      await this.removeItem(item);
      return;
    }

    try {
      await this.orders.updateItem(this.current.id, Number(item.product_id), q);
      await this.refreshCurrent();
    } catch (e: any) {
      this.message = this.error(e, 'No se pudo modificar la cantidad');
    }
  }

  // Quitar producto
  async removeItem(item: any) {
    try {
      await this.orders.removeItem(this.current.id, Number(item.product_id));
      await this.refreshCurrent();
    } catch (e: any) {
      this.message = this.error(e, 'No se pudo eliminar el producto');
    }
  }

  // Confirmar pedido
  async confirmOrder() {
    try {
      await this.orders.confirm(this.current.id);
      await this.refreshCurrent();
      this.message = 'Pedido confirmado';
    } catch (e: any) {
      this.message = this.error(e, 'No se pudo confirmar el pedido');
    }
  }

  // Preparar cobro
  startPay(order: any) {
    this.current = order;
    this.paymentMethodId = null;
    this.cashReceived = null;
    this.payOpen = true;
  }

  // Obtener método de pago
  selectedMethod() {
    return this.methods.find(
      (m: any) => Number(m.id) === Number(this.paymentMethodId)
    );
  }

  // Comprobar pago en efectivo
  isCash() {
    return (
      (this.selectedMethod()?.name || '').trim().toLowerCase() === 'efectivo'
    );
  }

  // Obtener efectivo recibido
  cashValue() {
    const value = Number(this.cashReceived);
    return Number.isFinite(value) ? value : 0;
  }

  // Obtener total del pedido
  orderTotal() {
    const value = Number(this.current?.total);
    return Number.isFinite(value) ? value : 0;
  }

  // Calcular cambio
  changeAmount() {
    return Math.max(0, this.cashValue() - this.orderTotal());
  }

  // Comprobar efectivo suficiente
  cashInsufficient() {
    return (
      this.isCash() &&
      (this.cashReceived === null || this.cashValue() < this.orderTotal())
    );
  }

  // Registrar pago
  async pay() {
    if (!this.current || !this.paymentMethodId) return;

    this.saving = true;

    try {
      const cash = this.isCash() ? Number(this.cashReceived) : undefined;
      const r = await this.sales.pay(
        Number(this.current.id),
        Number(this.paymentMethodId),
        cash
      );
      this.payOpen = false;
      this.message =
        r.payment?.change_amount != null
          ? `Pago registrado. Cambio: $${Number(r.payment.change_amount).toFixed(2)}`
          : 'Pago registrado correctamente';
      await this.load();

      if (this.modalOpen) await this.openOrder(Number(this.current.id));
    } catch (e: any) {
      this.message = this.error(e, 'No se pudo registrar el pago');
    } finally {
      this.saving = false;
      this.cdr.detectChanges();
    }
  }

  // Marcar pedido entregado
  async deliver(order: any) {
    try {
      await this.orders.deliver(Number(order.id));
      this.message = 'Pedido marcado como entregado';
      await this.load();

      if (this.modalOpen && this.current?.id === order.id)
        await this.openOrder(Number(order.id));
    } catch (e: any) {
      this.message = this.error(e, 'No se pudo marcar como entregado');
    }
  }

  // Cancelar pedido
  async cancelOrder() {
    if (!this.current) return;

    try {
      await this.orders.cancel(Number(this.current.id));
      this.closeOrder();
      this.message = 'Pedido cancelado';
      await this.load();
    } catch (e: any) {
      this.message = this.error(e, 'No se pudo cancelar el pedido');
    }
  }
}
