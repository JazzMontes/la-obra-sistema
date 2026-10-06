import { Component } from '@angular/core';
import { Router } from '@angular/router';
import { Auth } from '../services/auth';

@Component({
  selector: 'app-home',
  templateUrl: 'home.page.html',
  styleUrls: ['home.page.scss'],
  standalone: false,
})
export class HomePage {

  user: any;

  constructor(
    private auth: Auth,
    private router: Router
  ) {
    this.user = this.auth.getStoredUser();

    console.log('Usuario recuperado en Home:', this.user);

    if (!this.user) {
      this.router.navigate(['/login']);
    }
  }

}