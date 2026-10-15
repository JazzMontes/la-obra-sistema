// Reúne las dependencias de la pantalla de mermas y pérdidas.

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
  IonCheckbox,
  IonSegment,
  IonSegmentButton,
  IonSpinner,
  IonBadge,
} from '@ionic/angular';
import { WastePage } from './waste.page';
import { WastePageRoutingModule } from './waste-routing.module';

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
    IonCheckbox,
    IonSegment,
    IonSegmentButton,
    IonSpinner,
    IonBadge,
    WastePageRoutingModule,
  ],
  declarations: [WastePage],
})
export class WastePageModule {}
