import { Component } from '@angular/core';
import { Products } from '../../services/products';
import { Categories } from '../../services/categories';
@Component({selector:'app-products',templateUrl:'products.page.html',styleUrls:['products.page.scss'],standalone:false})
export class ProductsPage {
products:any[]=[]; categories:any[]=[]; form:any={name:'',description:'',category_id:null,type:'prepared',price:null}; editingId:number|null=null; message='';
constructor(private productsService: Products, private categoriesService: Categories){}
async ionViewWillEnter(){ await this.load(); }
async load(){ try{ const [p,c]=await Promise.all([this.productsService.getAll(),this.categoriesService.getAll()]); this.products=p.products||p.data||[]; this.categories=c.categories||c.data||[]; }catch(e:any){this.message=e.response?.data?.message||'No se pudieron cargar los productos';}}
edit(p:any){this.editingId=Number(p.id);this.form={name:p.name,description:p.description,category_id:Number(p.category_id??p.category?.id),type:p.type,price:Number(p.price),is_available:Boolean(Number(p.is_available??1))};}
cancel(){this.editingId=null;this.form={name:'',description:'',category_id:null,type:'prepared',price:null};}
async save(){try{if(this.editingId){await this.productsService.update(this.editingId,this.form);}else{await this.productsService.create(this.form.name,this.form.description,Number(this.form.category_id),this.form.type,Number(this.form.price));}this.message='Producto guardado correctamente';this.cancel();await this.load();}catch(e:any){this.message=e.response?.data?.message||'No se pudo guardar el producto';}}
async deactivate(id:number){try{await this.productsService.deactivate(Number(id));await this.load();}catch(e:any){this.message=e.response?.data?.message||'No se pudo desactivar';}}
}
