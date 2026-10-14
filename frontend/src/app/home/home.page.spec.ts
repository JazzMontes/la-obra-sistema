// Comprueba el funcionamiento de inicio.

import { ComponentFixture, TestBed } from '@angular/core/testing';
import { provideIonicAngular } from '@ionic/angular';
import { HomePageModule } from './home.module';

import { HomePage } from './home.page';

// Pruebas
describe('HomePage', () => {
  let component: HomePage;
  let fixture: ComponentFixture<HomePage>;

  // Preparar prueba
  beforeEach(async () => {
    await TestBed.configureTestingModule({
      providers: [provideIonicAngular()],
      imports: [HomePageModule],
    }).compileComponents();

    fixture = TestBed.createComponent(HomePage);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  // Comprobar resultado
  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
