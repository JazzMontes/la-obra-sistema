// Comprueba el funcionamiento de inicio de sesión.

import { ComponentFixture, TestBed } from '@angular/core/testing';
import { LoginPage } from './login.page';

// Pruebas
describe('LoginPage', () => {
  let component: LoginPage;
  let fixture: ComponentFixture<LoginPage>;

  // Preparar prueba
  beforeEach(() => {
    fixture = TestBed.createComponent(LoginPage);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  // Comprobar resultado
  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
