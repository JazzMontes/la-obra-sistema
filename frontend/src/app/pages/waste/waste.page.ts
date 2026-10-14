import {Component,ChangeDetectorRef} from '@angular/core';
import {Waste} from '../../services/waste';
import {Inputs} from '../../services/inputs';
import {Products} from '../../services/products';
import {Auth} from '../../services/auth';
@Component({selector:'app-waste',templateUrl:'waste.page.html',styleUrls:['waste.page.scss'],standalone:false})
export class WastePage {
 isAdmin=false; loading=false;saving=false;analyticsLoading=false;modalOpen=false;detailOpen=false;
 records:any[]=[];detail:any=null;inputs:any[]=[];products:any[]=[];catalogMessage='';message='';formError='';
 from='';to='';filterType='';classification='';tab='records';
 form:any={affected_type:'product',affected_id:null,quantity:null,is_approximate:false,waste_date:'',reason:'',classification:''};
 analysisFrom='';analysisTo='';compareFrom='';compareTo='';indicators:any=null;ranking:any[]=[];comparison:any=null;
 constructor(private api:Waste,private inputsApi:Inputs,private productsApi:Products,private auth:Auth,private cdr:ChangeDetectorRef){this.isAdmin=this.auth.getStoredUser()?.role==='admin';const d=this.today();this.form.waste_date=d;this.analysisTo=d;this.analysisFrom=this.daysAgo(30);this.compareTo=d;this.compareFrom=this.daysAgo(7);}
 private today(){const d=new Date();return [d.getFullYear(),String(d.getMonth()+1).padStart(2,'0'),String(d.getDate()).padStart(2,'0')].join('-');}
 private daysAgo(n:number){const d=new Date();d.setDate(d.getDate()-n);return [d.getFullYear(),String(d.getMonth()+1).padStart(2,'0'),String(d.getDate()).padStart(2,'0')].join('-');}
 async ionViewWillEnter(){await this.load();}
 private error(e:any,fallback:string){return e?.response?.data?.message||e?.message||fallback;}
 async load(){this.loading=true;this.message='';try{const r=await this.api.list({date_from:this.isAdmin&&this.from||undefined,date_to:this.isAdmin&&this.to||undefined,affected_type:this.filterType||undefined,classification:this.classification.trim()||undefined});this.records=r.waste_records||[];}catch(e:any){this.message=this.error(e,'No se pudieron consultar las mermas');}finally{this.loading=false;this.cdr.detectChanges();}}
 async openCreate(){this.form={affected_type:'product',affected_id:null,quantity:null,is_approximate:false,waste_date:this.today(),reason:'',classification:''};this.formError='';this.catalogMessage='';this.inputs=[];this.products=[];this.modalOpen=true;await this.loadCatalog();}
 async loadCatalog(){this.form.affected_id=null;this.catalogMessage='';if(this.form.affected_type==='preparation')return;
 try{if(this.form.affected_type==='input'){
 if(!this.isAdmin){this.catalogMessage='El catálogo de insumos requiere permiso de administrador en la API actual.';return;}
 const r=await this.inputsApi.getAll();this.inputs=(r.inputs||[]).filter((x:any)=>x.status==='active');
 }else{const r=await this.productsApi.getAll();this.products=(r.products||[]).filter((x:any)=>x.status==='active');}
 }catch(e:any){this.catalogMessage=this.error(e,'No se pudo cargar el catálogo');}finally{this.cdr.detectChanges();}}
 async save(){if(this.saving)return;this.formError='';const f=this.form;const q=Number(f.quantity);
 if(!['input','product'].includes(f.affected_type)){this.formError='El registro de preparaciones todavía no está disponible.';return;}
 if(!Number.isInteger(Number(f.affected_id))||Number(f.affected_id)<=0||!Number.isFinite(q)||q<=0||Math.round(q*1000)!==q*1000||!f.waste_date||!String(f.reason||'').trim()||!String(f.classification||'').trim()){
 this.formError='Selecciona el elemento, indica una cantidad válida (hasta 3 decimales), fecha, motivo y clasificación.';return;}
 this.saving=true;try{await this.api.create({affected_type:f.affected_type,affected_id:Number(f.affected_id),quantity:q,is_approximate:!!f.is_approximate,waste_date:f.waste_date,reason:f.reason.trim(),classification:f.classification.trim()});this.modalOpen=false;await this.load();}catch(e:any){this.formError=this.error(e,'No se pudo registrar la merma');}finally{this.saving=false;this.cdr.detectChanges();}}
 async show(r:any){this.message='';try{this.detail=(await this.api.show(+r.id)).waste;this.detailOpen=true;}catch(e:any){this.message=this.error(e,'No se pudo consultar el detalle');}finally{this.cdr.detectChanges();}}
 async loadAnalysis(){if(!this.isAdmin)return;if(!this.analysisFrom||!this.analysisTo||this.analysisFrom>this.analysisTo){this.message='Selecciona un periodo válido';return;}this.analyticsLoading=true;this.message='';try{const params={date_from:this.analysisFrom,date_to:this.analysisTo};const [i,r]=await Promise.all([this.api.indicators(params),this.api.ranking(params)]);this.indicators=i;this.ranking=r.ranking||[];}catch(e:any){this.message=this.error(e,'No se pudieron consultar los indicadores');}finally{this.analyticsLoading=false;this.cdr.detectChanges();}}
 async loadComparison(){if(!this.isAdmin)return;if(!this.compareFrom||!this.compareTo||this.compareFrom>this.compareTo||!this.analysisFrom||!this.analysisTo||this.analysisFrom>this.analysisTo){this.message='Selecciona periodos válidos';return;}this.analyticsLoading=true;this.message='';try{this.comparison=await this.api.compare({period1_start:this.compareFrom,period1_end:this.compareTo,period2_start:this.analysisFrom,period2_end:this.analysisTo});}catch(e:any){this.message=this.error(e,'No se pudo comparar');}finally{this.analyticsLoading=false;this.cdr.detectChanges();}}
 typeName(t:string){return t==='input'?'Insumo':t==='product'?'Producto':'Preparación';}
 costLabel(r:any){return r?.cost_status==='calculated'?'Costo estimado':'Costo no disponible';}
}
