// Reúne los módulos y componentes principales de la aplicación.

import { NgModule } from '@angular/core';
import { BrowserModule } from '@angular/platform-browser';
import { RouteReuseStrategy } from '@angular/router';

import {
  IonicRouteStrategy,
  provideIonicAngular,
  IonApp,
  IonRouterOutlet,
  IonModal,
  IonHeader,
  IonToolbar,
  IonTitle,
  IonButtons,
  IonButton,
  IonContent,
} from '@ionic/angular';

import { AppComponent } from './app.component';
import { AppRoutingModule } from './app-routing.module';

// Dependencias del módulo
@NgModule({
  declarations: [AppComponent],
  imports: [
    BrowserModule,
    IonApp,
    IonRouterOutlet,
    IonModal,
    IonHeader,
    IonToolbar,
    IonTitle,
    IonButtons,
    IonButton,
    IonContent,
    AppRoutingModule,
  ],
  providers: [
    provideIonicAngular(),
    { provide: RouteReuseStrategy, useClass: IonicRouteStrategy },
  ],
  bootstrap: [AppComponent],
})
export class AppModule {}
