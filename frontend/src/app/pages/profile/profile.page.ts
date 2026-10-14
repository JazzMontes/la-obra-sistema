// Consulta el perfil y lee los datos de una imagen seleccionada.

import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { Auth } from '../../services/auth';
import * as exifr from 'exifr';
import { addIcons } from 'ionicons';
import {
  personOutline,
  cameraOutline,
  atOutline,
  mailOutline,
  shieldCheckmarkOutline,
  documentTextOutline,
  imageOutline,
  bluetoothOutline,
} from 'ionicons/icons';

@Component({
  selector: 'app-profile',
  templateUrl: './profile.page.html',
  styleUrls: ['./profile.page.scss'],
  standalone: false,
})
export class ProfilePage implements OnInit {

  // Datos y estado
  user: any = null;
  imageUrl: string | null = null;
  imageName = '';
  metadata: Record<string, unknown> | null = null;
  error = '';
  loading = false;

  // Dependencias e inicio
  constructor(
    private auth: Auth,
    private router: Router
  ) {
    addIcons({
      personOutline,
      cameraOutline,
      atOutline,
      mailOutline,
      shieldCheckmarkOutline,
      documentTextOutline,
      imageOutline,
      bluetoothOutline,
    });
  }

  // Inicialización
  async ngOnInit() {
    if (!this.auth.isLoggedIn()) {
      await this.router.navigate(['/login']);
      return;
    }

    this.user = this.auth.getStoredUser();

    try {
      const result = await this.auth.getMe();

      if (result.success) this.user = result.user;
    } catch {
      /* Mostrar información de sesión guardada si la API no responde. */
    }
  }

  // Leer imagen seleccionada
  async selectImage(event: Event) {
    const input = event.target as HTMLInputElement;
    const file = input.files?.[0];

    if (!file) return;

    this.error = '';

    if (!file.type.startsWith('image/')) {
      this.error = 'Selecciona un archivo de imagen.';
      return;
    }

    this.loading = true;

    try {
      if (this.imageUrl) URL.revokeObjectURL(this.imageUrl);

      this.imageUrl = URL.createObjectURL(file);
      this.imageName = file.name;
      const exif = await exifr.parse(file, { gps: false }).catch(() => null);
      this.metadata = {
        nombre: file.name,
        tipo: file.type,
        tamanoBytes: file.size,
        ultimaModificacion: new Date(file.lastModified).toISOString(),
        exif: exif || {},
      };
    } catch {
      this.error = 'No se pudieron leer los metadatos.';
      this.metadata = null;
    } finally {
      this.loading = false;
      input.value = '';
    }
  }

  // Mostrar metadatos
  get metadataText(): string {
    return JSON.stringify(this.metadata, null, 2);
  }
}
