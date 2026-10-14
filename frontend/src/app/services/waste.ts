import {Injectable} from '@angular/core';
import axios from 'axios';
import {ApiConfigService} from './api-config';
@Injectable({providedIn:'root'}) export class Waste {
 constructor(private config:ApiConfigService){}
 private get url(){return this.config.getApiUrl()+'/waste';}
 private get headers(){return {'Content-Type':'application/json',Authorization:`Bearer ${localStorage.getItem('token')}`};}
 async list(params:any={}){return (await axios.get(this.url+'/index.php',{params,headers:this.headers})).data;}
 async show(id:number){return (await axios.get(this.url+'/show.php',{params:{id},headers:this.headers})).data;}
 async create(data:any){return (await axios.post(this.url+'/create.php',data,{headers:this.headers})).data;}
 async compare(params:any){return (await axios.get(this.url+'/compare.php',{params,headers:this.headers})).data;}
 async indicators(params:any){return (await axios.get(this.url+'/indicators.php',{params,headers:this.headers})).data;}
 async ranking(params:any){return (await axios.get(this.url+'/ranking.php',{params,headers:this.headers})).data;}
}
