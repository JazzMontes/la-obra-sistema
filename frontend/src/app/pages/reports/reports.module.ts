// Reúne las dependencias de la pantalla de reportes.

import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { RouterModule } from '@angular/router';
import {
  IonHeader,
  IonToolbar,
  IonTitle,
  IonButtons,
  IonBackButton,
  IonContent,
  IonItem,
  IonInput,
  IonButton,
} from '@ionic/angular';
import { ReportsPage } from './reports.page';

// Dependencias del módulo
@NgModule({
  declarations: [ReportsPage],
  imports: [
    CommonModule,
    FormsModule,
    IonHeader,
    IonToolbar,
    IonTitle,
    IonButtons,
    IonBackButton,
    IonContent,
    IonItem,
    IonInput,
    IonButton,
    RouterModule.forChild([{ path: '', component: ReportsPage }]),
  ],
})
export class ReportsPageModule {}
