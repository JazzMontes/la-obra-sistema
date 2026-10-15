// Controla la consulta y gestión de recetas.

import { Component, ChangeDetectorRef } from '@angular/core';
import { Recipes } from '../../services/recipes';
import { Products } from '../../services/products';
import { Inputs } from '../../services/inputs';

@Component({
  selector: 'app-recipes',
  templateUrl: 'recipes.page.html',
  styleUrls: ['recipes.page.scss'],
  standalone: false,
})
export class RecipesPage {

  // Datos y estado
  products: any[] = [];
  inputs: any[] = [];
  productId: number | null = null;
  recipeInputs: any[] = [];
  entries: any[] = [];
  message = '';
  loading = false;
  saving = false;
  modalOpen = false;
  editing = false;
  recipeLoading = false;

  // Dependencias e inicio
  constructor(
    private recipes: Recipes,
    private productsService: Products,
    private inputsService: Inputs,
    private cdr: ChangeDetectorRef
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
      const [p, i] = await Promise.allSettled([
        this.productsService.getAll(),
        this.inputsService.getAll(),
      ]);

      if (p.status === 'rejected') throw p.reason;

      this.products = (p.value.products || p.value.data || []).filter(
        (x: any) => x.type === 'prepared'
      );

      if (i.status === 'fulfilled')
        this.inputs = i.value.inputs || i.value.data || [];
      else this.message = 'No se pudieron cargar los insumos';

      this.entries = await Promise.all(
        this.products.map(async (product) => {
          try {
            const r = await this.recipes.getByProduct(Number(product.id));
            return {
              product,
              inputs: r.recipe?.inputs || [],
              exists: true,
              error: '',
            };
          } catch (e: any) {
            return {
              product,
              inputs: [],
              exists: false,
              error:
                e.response?.status === 404
                  ? 'Sin receta registrada'
                  : e.response?.data?.message ||
                    'No se pudo consultar la receta',
            };
          }
        })
      );
    } catch (e: any) {
      this.message =
        e.response?.data?.message || 'No se pudieron cargar los productos';
    } finally {
      this.loading = false;
      this.cdr.detectChanges();
    }
  }

  // Abrir formulario de registro
  openCreate() {
    this.productId = null;
    this.recipeInputs = [];
    this.editing = false;
    this.message = '';
    this.modalOpen = true;
  }

  // Preparar edición
  edit(entry: any) {
    this.productId = Number(entry.product.id);
    this.recipeInputs = entry.inputs.map((x: any) => ({
      input_id: Number(x.input_id ?? x.id),
      quantity: Number(x.quantity),
    }));
    this.editing = entry.exists;
    this.message = '';
    this.modalOpen = true;
  }

  // Cancelar
  cancel() {
    this.modalOpen = false;
    this.productId = null;
    this.recipeInputs = [];
  }

  // Agregar insumo
  add() {
    this.recipeInputs.push({ input_id: null, quantity: null });
  }

  // Quitar insumo
  remove(i: number) {
    this.recipeInputs.splice(i, 1);
  }

  // Consultar receta
  async loadRecipe() {
    if (!this.productId) return;

    const selected = Number(this.productId);
    this.recipeLoading = true;
    this.message = '';
    this.recipeInputs = [];

    try {
      const r = await this.recipes.getByProduct(selected);

      if (Number(this.productId) !== selected) return;

      this.recipeInputs = (r.recipe?.inputs || []).map((x: any) => ({
        input_id: Number(x.input_id ?? x.id),
        quantity: Number(x.quantity),
      }));
      this.editing = true;
    } catch (e: any) {
      if (Number(this.productId) !== selected) return;

      this.editing = false;
      this.message =
        e.response?.status === 404
          ? 'Este producto todavía no tiene receta'
          : e.response?.data?.message || 'No se pudo consultar la receta';
    } finally {
      this.recipeLoading = false;
      this.cdr.detectChanges();
    }
  }

  // Guardar datos
  async save() {
    if (this.saving || this.recipeLoading) return;

    if (
      !this.productId ||
      !this.recipeInputs.length ||
      this.recipeInputs.some(
        (x) =>
          !x.input_id ||
          !Number.isFinite(Number(x.quantity)) ||
          Number(x.quantity) <= 0
      )
    ) {
      this.message =
        'Selecciona un producto y agrega insumos con cantidades mayores a cero';
      return;
    }

    this.saving = true;
    const rows = this.recipeInputs.map((x) => ({
      input_id: Number(x.input_id),
      quantity: Number(x.quantity),
    }));

    try {
      // Consultar de nuevo evita crear una receta duplicada.
      try {
        await this.recipes.getByProduct(Number(this.productId));
        this.editing = true;
      } catch (e: any) {
        if (e.response?.status === 404) this.editing = false;
        else throw e;
      }

      if (this.editing) await this.recipes.update(Number(this.productId), rows);
      else await this.recipes.create(Number(this.productId), rows);

      this.cancel();
      await this.load();
    } catch (e: any) {
      this.message =
        e.response?.data?.message || 'No se pudo guardar la receta';
    } finally {
      this.saving = false;
      this.cdr.detectChanges();
    }
  }
}
