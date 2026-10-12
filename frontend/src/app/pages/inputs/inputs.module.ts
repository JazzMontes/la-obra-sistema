import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { IonBackButton, IonButton, IonButtons, IonCard, IonCardContent, IonContent, IonFab, IonFabButton, IonFooter, IonHeader, IonInput, IonItem, IonLabel, IonList, IonModal, IonTitle, IonToolbar } from '@ionic/angular';
import { InputsPageRoutingModule } from './inputs-routing.module';
import { InputsPage } from './inputs.page';
@NgModule({imports:[CommonModule,FormsModule,IonBackButton, IonButton, IonButtons, IonCard, IonCardContent, IonContent, IonFab, IonFabButton, IonFooter, IonHeader, IonInput, IonItem, IonLabel, IonList, IonModal, IonTitle, IonToolbar,InputsPageRoutingModule],declarations:[InputsPage]})
export class InputsPageModule {}
