// Reúne las dependencias de la pantalla de recetas.

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
  IonTitle,
  IonToolbar,
} from '@ionic/angular';
import { RecipesPageRoutingModule } from './recipes-routing.module';
import { RecipesPage } from './recipes.page';

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
    IonTitle,
    IonToolbar,
    RecipesPageRoutingModule,
  ],
  declarations: [RecipesPage],
})
export class RecipesPageModule {}
