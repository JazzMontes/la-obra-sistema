// Reúne las dependencias de la pantalla de métodos de pago.

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
  IonHeader,
  IonInput,
  IonItem,
  IonLabel,
  IonList,
  IonModal,
  IonText,
  IonTitle,
  IonToolbar,
} from '@ionic/angular';
import { PaymentMethodsPageRoutingModule } from './payment-methods-routing.module';
import { PaymentMethodsPage } from './payment-methods.page';

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
    IonHeader,
    IonInput,
    IonItem,
    IonLabel,
    IonList,
    IonModal,
    IonText,
    IonTitle,
    IonToolbar,
    PaymentMethodsPageRoutingModule,
  ],
  declarations: [PaymentMethodsPage],
})
export class PaymentMethodsPageModule {}
