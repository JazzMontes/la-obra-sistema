import {NgModule} from '@angular/core';
import {RouterModule,Routes} from '@angular/router';
import {PurchasesPage} from './purchases.page';
const routes:Routes=[{path:'',component:PurchasesPage}];
@NgModule({imports:[RouterModule.forChild(routes)],exports:[RouterModule]})
export class PurchasesPageRoutingModule{}
