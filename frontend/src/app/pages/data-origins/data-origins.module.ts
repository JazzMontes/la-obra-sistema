// Reúne las dependencias de la pantalla de orígenes de datos.

import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import {
  IonBackButton,
  IonButtons,
  IonContent,
  IonHeader,
  IonItem,
  IonLabel,
  IonList,
  IonTitle,
  IonToolbar,
} from '@ionic/angular';
import { RouterModule } from '@angular/router';

import { DataOriginsPage } from './data-origins.page';

// Dependencias del módulo
@NgModule({
  declarations: [DataOriginsPage],
  imports: [
    CommonModule,
    IonBackButton,
    IonButtons,
    IonContent,
    IonHeader,
    IonItem,
    IonLabel,
    IonList,
    IonTitle,
    IonToolbar,
    RouterModule.forChild([
      {
        path: '',
        component: DataOriginsPage,
      },
    ]),
  ],
})
export class DataOriginsPageModule {}
