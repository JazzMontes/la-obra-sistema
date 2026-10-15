// Comprueba el funcionamiento de cambio de contraseña.

import { ComponentFixture, TestBed } from '@angular/core/testing';
import { ChangePasswordPage } from './change-password.page';

// Pruebas
describe('ChangePasswordPage', () => {
  let component: ChangePasswordPage;
  let fixture: ComponentFixture<ChangePasswordPage>;

  // Preparar prueba
  beforeEach(() => {
    fixture = TestBed.createComponent(ChangePasswordPage);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  // Comprobar resultado
  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
