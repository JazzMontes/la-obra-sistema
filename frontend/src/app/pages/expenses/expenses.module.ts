// Reúne las dependencias de la pantalla de gastos.

import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import {
  IonBackButton,
  IonButton,
  IonButtons,
  IonCard,
  IonCardContent,
  IonContent,
  IonFab,
  IonFabButton,
  IonFooter,
  IonHeader,
  IonInput,
  IonItem,
  IonLabel,
  IonList,
  IonModal,
  IonSelect,
  IonSelectOption,
  IonTextarea,
  IonTitle,
  IonToolbar,
} from '@ionic/angular';
import { ExpensesPage } from './expenses.page';
import { ExpensesPageRoutingModule } from './expenses-routing.module';

// Dependencias del módulo
@NgModule({
  imports: [
    CommonModule,
    FormsModule,
    IonBackButton,
    IonButton,
    IonButtons,
    IonCard,
    IonCardContent,
    IonContent,
    IonFab,
    IonFabButton,
    IonFooter,
    IonHeader,
    IonInput,
    IonItem,
    IonLabel,
    IonList,
    IonModal,
    IonSelect,
    IonSelectOption,
    IonTextarea,
    IonTitle,
    IonToolbar,
    ExpensesPageRoutingModule,
  ],
  declarations: [ExpensesPage],
})
export class ExpensesPageModule {}
