// Consulta las ventas y genera sus gráficas.

import {
  Component,
  ElementRef,
  ViewChild,
  ChangeDetectorRef,
  OnDestroy,
} from '@angular/core';
import { Router } from '@angular/router';
import { Auth } from '../../services/auth';
import {
  ReportsService,
  ProductReport,
  DailyReport,
  MethodReport,
  CategoryReport,
} from '../../services/reports';

@Component({
  selector: 'app-reports',
  templateUrl: './reports.page.html',
  styleUrls: ['./reports.page.scss'],
  standalone: false,
})
export class ReportsPage implements OnDestroy {

  // Datos y estado
  @ViewChild('productsChart', { static: true })
  productsChart!: ElementRef<HTMLElement>;
  @ViewChild('dailyChart', { static: true })
  dailyChart!: ElementRef<HTMLElement>;
  @ViewChild('methodsChart', { static: true })
  methodsChart!: ElementRef<HTMLElement>;
  @ViewChild('categoriesChart', { static: true })
  categoriesChart!: ElementRef<HTMLElement>;
  from = this.localDate(
    new Date(new Date().getFullYear(), new Date().getMonth(), 1)
  );
  to = this.localDate(new Date());
  loading = false;
  error = '';
  updatedAt = '';
  total = 0;
  orderCount = 0;
  units = 0;
  private sequence = 0;
  private readonly colors = [
    '#ffc526',
    '#61c4a7',
    '#729fe7',
    '#ef9273',
    '#b596e4',
    '#d3d775',
    '#6ac9d7',
    '#df86b1',
  ];

  // Dependencias e inicio
  constructor(
    private reports: ReportsService,
    private auth: Auth,
    private router: Router,
    private cdr: ChangeDetectorRef
  ) {}

  // Entrada a la pantalla
  ionViewWillEnter() {
    if (!this.auth.isLoggedIn()) {
      void this.router.navigate(['/login']);
      return;
    }

    if (this.auth.getStoredUser()?.role !== 'admin') {
      void this.router.navigate(['/home']);
      return;
    }

    void this.load();
  }

  // Salida de la pantalla
  ionViewWillLeave() {
    this.sequence++;
    this.loading = false;
  }

  // Cierre del componente
  ngOnDestroy() {
    this.sequence++;
  }

  // Dar formato a la fecha
  private localDate(date: Date): string {
    return `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`;
  }

  // Obtener días del periodo
  private dates(from: string, to: string): string[] {
    const days: string[] = [];
    const start = new Date(from + 'T00:00:00Z'),
      end = new Date(to + 'T00:00:00Z');
    for (let day = start; day <= end; day.setUTCDate(day.getUTCDate() + 1))
      days.push(day.toISOString().slice(0, 10));
    return days;
  }

  // Validar fecha
  private validDate(value: string): boolean {
    return (
      /^\d{4}-\d{2}-\d{2}$/.test(value) &&
      !isNaN(Date.parse(value + 'T00:00:00Z')) &&
      new Date(value + 'T00:00:00Z').toISOString().slice(0, 10) === value
    );
  }

  // Cargar datos
  async load() {
    if (this.loading) return;

    this.error = '';
    this.updatedAt = '';
    const from = this.from,
      to = this.to;

    if (
      !this.validDate(from) ||
      !this.validDate(to) ||
      from > to ||
      (Date.parse(to) - Date.parse(from)) / 86400000 > 365
    ) {
      this.error =
        'Selecciona fechas válidas en orden y un periodo de hasta 366 días.';
      this.clear('Selecciona un periodo válido.');
      this.cdr.markForCheck();
      return;
    }

    const d3 = (window as any).d3;

    if (!d3) {
      this.error =
        'No se pudo cargar D3. Revisa que se haya copiado la carpeta assets/vendor.';
      this.cdr.markForCheck();
      return;
    }

    this.loading = true;
    this.clear('Cargando información…');
    this.cdr.markForCheck();
    const current = ++this.sequence;

    try {
      const [products, daily, methods, categories] = await Promise.all([
        this.reports.get<ProductReport>('products', from, to),
        this.reports.get<DailyReport>('daily', from, to),
        this.reports.get<MethodReport>('payment-methods', from, to),
        this.reports.get<CategoryReport>('categories', from, to),
      ]);

      if (current !== this.sequence) return;

      this.total = daily.reduce((sum, row) => sum + row.amount, 0);
      this.orderCount = daily.reduce((sum, row) => sum + row.orders, 0);
      this.units = products.reduce((sum, row) => sum + row.quantity, 0);
      const days = this.dates(from, to);
      this.drawBars(d3, products);
      this.drawDaily(d3, daily, days);
      this.drawMethods(d3, methods);
      this.drawCategories(d3, categories, days);
      this.updatedAt = new Date().toLocaleString('es-MX');
    } catch (e: any) {
      if (current !== this.sequence) return;

      this.error =
        e.response?.data?.message ||
        e.message ||
        'No fue posible cargar los reportes';
      this.clear('No fue posible obtener los datos.');
    } finally {
      if (current === this.sequence) {
        this.loading = false;
        this.cdr.markForCheck();
      }
    }
  }

  // Limpiar gráficas
  private clear(message: string) {
    for (const ref of [
      this.productsChart,
      this.dailyChart,
      this.methodsChart,
      this.categoriesChart,
    ])
      if (ref) ref.nativeElement.textContent = message;
  }

  // Preparar gráfica
  private svg(d3: any, host: HTMLElement, height: number, label: string) {
    host.textContent = '';
    const svg = d3
      .select(host)
      .append('svg')
      .attr('viewBox', `0 0 640 ${height}`)
      .attr('role', 'img')
      .attr('aria-label', label)
      .style('width', '100%')
      .style('min-width', '480px')
      .style('display', 'block')
      .style('color', '#9ca3aa');
    svg.append('title').text(label);
    return svg;
  }

  // Dar formato al importe
  private money(value: number): string {
    return new Intl.NumberFormat('es-MX', {
      style: 'currency',
      currency: 'MXN',
    }).format(value);
  }

  // Mostrar tabla del reporte
  private table(
    d3: any,
    host: HTMLElement,
    headers: string[],
    rows: (string | number)[][]
  ) {
    const details = d3
      .select(host)
      .append('details')
      .style('margin-top', '16px');
    details
      .append('summary')
      .text('Ver datos del reporte')
      .style('cursor', 'pointer')
      .style('color', '#ffc526');
    const table = details
      .append('table')
      .style('width', '100%')
      .style('font-size', '12px')
      .style('margin-top', '12px');
    table
      .append('thead')
      .append('tr')
      .selectAll('th')
      .data(headers)
      .join('th')
      .text((v: string) => v)
      .style('text-align', 'left');
    table
      .append('tbody')
      .selectAll('tr')
      .data(rows)
      .join('tr')
      .selectAll('td')
      .data((row: any) => row)
      .join('td')
      .text((v: any) => v)
      .style('padding', '6px 4px')
      .style('border-bottom', '1px solid #292e33');
  }

  // Mostrar resultado vacío
  private empty(host: HTMLElement) {
    host.textContent = 'Sin ventas pagadas en este periodo.';
  }

  // Dibujar ventas por producto
  private drawBars(d3: any, rows: ProductReport[]) {
    const host = this.productsChart.nativeElement;

    if (!rows.length) {
      this.empty(host);
      return;
    }

    const height = Math.max(300, rows.length * 34 + 50),
      svg = this.svg(d3, host, height, 'Unidades vendidas por producto');
    const x = d3
      .scaleLinear()
      .domain([0, d3.max(rows, (r: ProductReport) => r.quantity) || 1])
      .nice()
      .range([180, 600]);
    const y = d3
      .scaleBand()
      .domain(rows.map((r) => String(r.product_id)))
      .range([16, height - 35])
      .padding(0.25);
    const names = new Map(rows.map((r) => [String(r.product_id), r.name]));
    svg
      .append('g')
      .attr('transform', 'translate(180,0)')
      .call(
        d3
          .axisLeft(y)
          .tickSize(0)
          .tickFormat((id: string) => {
            const name = names.get(id) || '';
            return name.length > 23 ? name.slice(0, 22) + '…' : name;
          })
      );
    svg
      .append('g')
      .attr('transform', `translate(0,${height - 35})`)
      .call(d3.axisBottom(x).ticks(5).tickFormat(d3.format('d')));
    svg
      .selectAll('rect')
      .data(rows)
      .join('rect')
      .attr('x', 180)
      .attr('y', (r: ProductReport) => y(String(r.product_id)))
      .attr('width', (r: ProductReport) => x(r.quantity) - 180)
      .attr('height', y.bandwidth())
      .attr('rx', 4)
      .attr('fill', '#ffc526')
      .append('title')
      .text(
        (r: ProductReport) =>
          `${r.name}: ${r.quantity} unidades · ${this.money(r.amount)}`
      );
    this.table(
      d3,
      host,
      ['Producto', 'Unidades', 'Importe'],
      rows.map((r) => [r.name, r.quantity, this.money(r.amount)])
    );
  }

  // Dibujar ejes
  private axes(d3: any, svg: any, x: any, y: any, days: string[]) {
    const ticks = days.filter(
      (_: string, i: number) =>
        i % Math.max(1, Math.ceil(days.length / 6)) === 0
    );
    svg
      .append('g')
      .attr('transform', 'translate(0,265)')
      .call(
        d3
          .axisBottom(x)
          .tickValues(ticks)
          .tickFormat((v: string) => v.slice(5))
      );
    svg
      .append('g')
      .attr('transform', 'translate(75,0)')
      .call(
        d3
          .axisLeft(y)
          .ticks(5)
          .tickFormat((n: number) => '$' + d3.format('~s')(n))
      );
  }

  // Dibujar ventas por día
  private drawDaily(d3: any, rows: DailyReport[], days: string[]) {
    const host = this.dailyChart.nativeElement;

    if (!rows.length) {
      this.empty(host);
      return;
    }

    const values = new Map(rows.map((r) => [r.date, r]));
    const data = days.map(
      (date) => values.get(date) || { date, amount: 0, orders: 0 }
    );
    const svg = this.svg(
      d3,
      host,
      310,
      'Importe cobrado por día en pesos mexicanos'
    );
    const x = d3.scalePoint().domain(days).range([75, 615]).padding(0.25);
    const y = d3
      .scaleLinear()
      .domain([0, d3.max(data, (r: DailyReport) => r.amount) || 1])
      .nice()
      .range([265, 20]);
    this.axes(d3, svg, x, y, days);
    svg
      .append('path')
      .datum(data)
      .attr('fill', 'none')
      .attr('stroke', '#ffc526')
      .attr('stroke-width', 3)
      .attr(
        'd',
        d3
          .line()
          .x((r: DailyReport) => x(r.date))
          .y((r: DailyReport) => y(r.amount))
      );
    svg
      .selectAll('circle')
      .data(data)
      .join('circle')
      .attr('cx', (r: DailyReport) => x(r.date))
      .attr('cy', (r: DailyReport) => y(r.amount))
      .attr('r', 3)
      .attr('fill', '#ffc526')
      .append('title')
      .text(
        (r: DailyReport) =>
          `${r.date}: ${this.money(r.amount)} · ${r.orders} pedidos`
      );
    this.table(
      d3,
      host,
      ['Fecha', 'Importe', 'Pedidos'],
      data.map((r) => [r.date, this.money(r.amount), r.orders])
    );
  }

  // Dibujar métodos de pago
  private drawMethods(d3: any, rows: MethodReport[]) {
    const host = this.methodsChart.nativeElement;
    const total = rows.reduce((sum, r) => sum + r.amount, 0);

    if (!rows.length || total <= 0) {
      this.empty(host);
      return;
    }

    const svg = this.svg(
      d3,
      host,
      Math.max(310, rows.length * 26 + 50),
      'Distribución de cobros por método de pago'
    );
    const color = d3
      .scaleOrdinal()
      .domain(rows.map((r) => String(r.method_id)))
      .range(this.colors);
    const pie = d3
      .pie()
      .value((r: MethodReport) => r.amount)
      .sort(null)(rows);
    const arc = d3.arc().innerRadius(74).outerRadius(120);
    svg
      .append('g')
      .attr('transform', 'translate(165,155)')
      .selectAll('path')
      .data(pie)
      .join('path')
      .attr('d', arc)
      .attr('fill', (r: any) => color(String(r.data.method_id)))
      .attr('stroke', '#14171a')
      .attr('stroke-width', 3)
      .append('title')
      .text(
        (r: any) =>
          `${r.data.name}: ${this.money(r.data.amount)} (${((r.data.amount / total) * 100).toFixed(1)}%)`
      );
    svg
      .append('text')
      .attr('x', 165)
      .attr('y', 155)
      .attr('text-anchor', 'middle')
      .attr('fill', '#f7f7f5')
      .attr('font-size', 18)
      .text(this.money(total));
    rows.forEach((r, i) => {
      svg
        .append('rect')
        .attr('x', 320)
        .attr('y', 40 + i * 26)
        .attr('width', 10)
        .attr('height', 10)
        .attr('fill', color(String(r.method_id)));
      svg
        .append('text')
        .attr('x', 340)
        .attr('y', 50 + i * 26)
        .attr('fill', '#f7f7f5')
        .attr('font-size', 12)
        .text(`${r.name}: ${((r.amount / total) * 100).toFixed(1)}%`);
    });
    this.table(
      d3,
      host,
      ['Método', 'Importe', 'Porcentaje'],
      rows.map((r) => [
        r.name,
        this.money(r.amount),
        ((r.amount / total) * 100).toFixed(1) + '%',
      ])
    );
  }

  // Dibujar ventas por categoría
  private drawCategories(d3: any, rows: CategoryReport[], days: string[]) {
    const host = this.categoriesChart.nativeElement;

    if (!rows.length) {
      this.empty(host);
      return;
    }

    const ids = Array.from(new Set(rows.map((r) => String(r.category_id))));
    const names = new Map(rows.map((r) => [String(r.category_id), r.category]));
    const lookup = new Map(
      rows.map((r) => [r.date + '|' + r.category_id, r.amount])
    );
    const data = days.map((date) => {
      const day: any = { date };
      ids.forEach((id) => (day[id] = lookup.get(date + '|' + id) || 0));
      return day;
    });
    const series = d3.stack().keys(ids)(data);
    const svg = this.svg(
      d3,
      host,
      340 + Math.ceil(ids.length / 2) * 24,
      'Ventas por categoría y día en pesos mexicanos'
    );
    // Padding creates a visible area even when the selected period has one day.
    const x = d3.scalePoint().domain(days).range([75, 615]).padding(0);
    const y = d3
      .scaleLinear()
      .domain([
        0,
        d3.max(data, (r: any) => ids.reduce((sum, id) => sum + r[id], 0)) || 1,
      ])
      .nice()
      .range([265, 20]);
    const color = d3.scaleOrdinal().domain(ids).range(this.colors);
    this.axes(d3, svg, x, y, days);

    if (days.length === 1) {
      svg
        .selectAll('rect')
        .data(series)
        .join('rect')
        .attr('x', 300)
        .attr('width', 90)
        .attr('y', (r: any) => y(r[0][1]))
        .attr('height', (r: any) => y(r[0][0]) - y(r[0][1]))
        .attr('fill', (r: any) => color(r.key));
    } else {
      svg
        .selectAll('path.area')
        .data(series)
        .join('path')
        .attr('class', 'area')
        .attr('fill', (r: any) => color(r.key))
        .attr('opacity', 0.85)
        .attr(
          'd',
          d3
            .area()
            .x((r: any) => x(r.data.date))
            .y0((r: any) => y(r[0]))
            .y1((r: any) => y(r[1]))
        )
        .append('title')
        .text((r: any) => names.get(r.key) || '');
    }

    ids.forEach((id, i) => {
      const xx = 75 + (i % 2) * 270,
        yy = 310 + Math.floor(i / 2) * 24;
      svg
        .append('rect')
        .attr('x', xx)
        .attr('y', yy - 10)
        .attr('width', 10)
        .attr('height', 10)
        .attr('fill', color(id));
      svg
        .append('text')
        .attr('x', xx + 16)
        .attr('y', yy)
        .attr('fill', '#f7f7f5')
        .attr('font-size', 12)
        .text(names.get(id));
    });
    this.table(
      d3,
      host,
      ['Fecha', 'Categoría', 'Importe'],
      rows.map((r) => [r.date, r.category, this.money(r.amount)])
    );
  }
}
