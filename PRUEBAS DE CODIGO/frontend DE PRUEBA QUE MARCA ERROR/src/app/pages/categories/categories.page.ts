import { Component } from '@angular/core';
import { Categories } from '../../services/categories';
@Component({selector:'app-categories',templateUrl:'categories.page.html',styleUrls:['categories.page.scss'],standalone:false})
export class CategoriesPage {
categories:any[]=[]; name=''; editingId:number|null=null; message=''; constructor(private service: Categories){} async ionViewWillEnter(){await this.load();} async load(){try{const r=await this.service.getAll();this.categories=r.categories||r.data||[];}catch(e:any){this.message=e.response?.data?.message||'No se pudieron cargar las categorías';}} edit(c:any){this.editingId=Number(c.id);this.name=c.name;} cancel(){this.editingId=null;this.name='';} async save(){try{if(this.editingId)await this.service.update(this.editingId,this.name);else await this.service.create(this.name);this.cancel();await this.load();}catch(e:any){this.message=e.response?.data?.message||'No se pudo guardar';}} async deactivate(id:number){try{await this.service.deactivate(Number(id));await this.load();}catch(e:any){this.message=e.response?.data?.message||'No se pudo desactivar';}}
}
