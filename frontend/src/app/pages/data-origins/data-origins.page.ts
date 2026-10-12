import { Component } from '@angular/core';
import { ApiConfigService } from '../../services/api-config';
@Component({selector:'app-data-origins',templateUrl:'./data-origins.page.html',styleUrls:['./data-origins.page.scss'],standalone:false})
export class DataOriginsPage {
  constructor(public config: ApiConfigService) {}
  get origin() { return this.config.getOrigin(); }
  get json() { return this.config.getJson(); }
}
