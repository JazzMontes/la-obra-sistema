import { Component, ChangeDetectorRef } from '@angular/core';
import { Products } from '../../services/products';
import { Categories } from '../../services/categories';
@Component({selector:'app-products',templateUrl:'products.page.html',styleUrls:['products.page.scss'],standalone:false})
export class ProductsPage {
products:any[]=[]; categories:any[]=[]; form:any={name:'',description:'',category_id:null,type:'prepared',price:null}; editingId:number|null=null; message='';
modalOpen=false; saving=false; loading=false;
openCreate(){this.cancel();this.message="";this.modalOpen=true;}
constructor(private cdr: ChangeDetectorRef, private productsService: Products, private categoriesService: Categories){}
async ionViewWillEnter(){ await this.load(); }
async load(){this.loading=true;this.message=""; try{ const results=await Promise.allSettled([this.productsService.getAll(),this.categoriesService.getAll()]); const [p,c]=results; if(p.status==='fulfilled')this.products=p.value.products||p.value.data||[]; if(c.status==='fulfilled')this.categories=c.value.categories||c.value.data||[]; if(p.status==='rejected')throw p.reason; if(c.status==='rejected')this.message="Los productos se cargaron, pero no se pudieron cargar las categorías"; }catch(e:any){this.message=e.response?.data?.message||'No se pudieron cargar los productos';}finally{this.loading=false;this.cdr.detectChanges();}}
edit(p:any){this.modalOpen=true;this.editingId=Number(p.id);this.form={name:p.name,description:p.description,category_id:Number(p.category_id??p.category?.id),type:p.type,price:Number(p.price),is_available:Boolean(Number(p.is_available??1))};}
cancel(){this.modalOpen=false;this.editingId=null;this.form={name:'',description:'',category_id:null,type:'prepared',price:null};}
async save(){if(this.saving)return;if(!this.form.name.trim() || !this.form.category_id || this.form.price===null || this.form.price==='' || !Number.isFinite(Number(this.form.price)) || Number(this.form.price)<0){this.message="Completa los datos requeridos con valores válidos";return;}this.saving=true;try{if(this.editingId){await this.productsService.update(this.editingId,this.form);}else{await this.productsService.create(this.form.name,this.form.description,Number(this.form.category_id),this.form.type,Number(this.form.price));}this.message='Producto guardado correctamente';this.cancel();await this.load();}catch(e:any){this.message=e.response?.data?.message||'No se pudo guardar el producto';}finally{this.saving=false;this.cdr.detectChanges();}}
async deactivate(id:number){try{await this.productsService.deactivate(Number(id));await this.load();}catch(e:any){this.message=e.response?.data?.message||'No se pudo desactivar';}}
async activate(id:number){try{await this.productsService.activate(Number(id));await this.load();}catch(e:any){this.message=e.response?.data?.message||'No se pudo activar';}}
}
