import { Component } from '@angular/core';
import { Inputs } from '../../services/inputs';
@Component({selector:'app-inputs',templateUrl:'inputs.page.html',styleUrls:['inputs.page.scss'],standalone:false})
export class InputsPage {
inputs:any[]=[]; form:any={name:'',unit:''}; editingId:number|null=null; message=''; constructor(private service: Inputs){} async ionViewWillEnter(){await this.load();} async load(){try{const r=await this.service.getAll();this.inputs=r.inputs||r.data||[];}catch(e:any){this.message=e.response?.data?.message||'No se pudieron cargar los insumos';}} edit(i:any){this.editingId=Number(i.id);this.form={name:i.name,unit:i.unit};} cancel(){this.editingId=null;this.form={name:'',unit:''};} async save(){try{if(this.editingId)await this.service.update(this.editingId,this.form);else await this.service.create(this.form.name,this.form.unit);this.cancel();await this.load();}catch(e:any){this.message=e.response?.data?.message||'No se pudo guardar';}} async deactivate(id:number){try{await this.service.deactivate(Number(id));await this.load();}catch(e:any){this.message=e.response?.data?.message||'No se pudo desactivar';}}
}
