import { Injectable } from '@angular/core';
import axios from 'axios';
import { ApiConfigService } from './api-config';
@Injectable({providedIn:'root'})
export class Expenses {
 constructor(private config:ApiConfigService){}
 private get url(){return this.config.getApiUrl()+'/expenses';}
 private get headers(){return {'Content-Type':'application/json',Authorization:`Bearer ${localStorage.getItem('token')}`};}
 async list(filters:any={}){return (await axios.get(this.url+'/index.php',{params:filters,headers:this.headers})).data;}
 async show(id:number){return (await axios.get(this.url+'/show.php',{params:{id},headers:this.headers})).data;}
 async create(data:any){return (await axios.post(this.url+'/create.php',data,{headers:this.headers})).data;}
 async update(data:any){return (await axios.patch(this.url+'/update.php',data,{headers:this.headers})).data;}
 async cancel(id:number,cancellation_reason:string){return (await axios.patch(this.url+'/cancel.php',{id,cancellation_reason},{headers:this.headers})).data;}
 async history(id:number){return (await axios.get(this.url+'/history.php',{params:{id},headers:this.headers})).data;}
}
