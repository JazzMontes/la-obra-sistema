import { Injectable } from '@angular/core';
import axios from 'axios';
import { ApiConfigService } from './api-config';
@Injectable({providedIn:'root'})
export class Purchases {
 constructor(private config:ApiConfigService){}
 private get url(){return this.config.getApiUrl()+'/purchases';}
 private get headers(){return {'Content-Type':'application/json',Authorization:`Bearer ${localStorage.getItem('token')}`};}
 async list(filters:any={}){return (await axios.get(this.url+'/index.php',{params:filters,headers:this.headers})).data;}
 async show(id:number){return (await axios.get(this.url+'/show.php',{params:{id},headers:this.headers})).data;}
 async create(data:any){return (await axios.post(this.url+'/create.php',data,{headers:this.headers})).data;}
 async update(data:any){return (await axios.patch(this.url+'/update.php',data,{headers:this.headers})).data;}
 async prices(input_id:number){return (await axios.get(this.url+'/prices.php',{params:{input_id},headers:this.headers})).data;}
}
