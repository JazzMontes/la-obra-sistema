import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterModule } from '@angular/router';
import { IonHeader, IonToolbar, IonButtons, IonBackButton, IonTitle, IonContent, IonButton, IonIcon } from '@ionic/angular';
import { ProfilePage } from './profile.page';
@NgModule({ declarations: [ProfilePage], imports: [CommonModule, IonHeader, IonToolbar, IonButtons, IonBackButton, IonTitle, IonContent, IonButton, IonIcon, RouterModule.forChild([{ path: '', component: ProfilePage }])] })
export class ProfilePageModule {}
