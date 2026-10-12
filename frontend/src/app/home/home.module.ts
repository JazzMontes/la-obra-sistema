import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { IonButton, IonContent, IonHeader, IonTitle, IonToolbar, IonRouterLink } from '@ionic/angular';
import { FormsModule } from '@angular/forms';
import { HomePage } from './home.page';

import { HomePageRoutingModule } from './home-routing.module';


@NgModule({
  imports: [
    CommonModule,
    FormsModule,
    IonButton, IonContent, IonHeader, IonTitle, IonToolbar, IonRouterLink,
    HomePageRoutingModule
  ],
  declarations: [HomePage]
})
export class HomePageModule {}
