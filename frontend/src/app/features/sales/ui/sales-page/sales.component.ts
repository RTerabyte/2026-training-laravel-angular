import { CommonModule } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { IonButton, IonContent } from '@ionic/angular/standalone';
import { Sale } from '../../domain/sale.model';
import { SaleService } from '../../infrastructure/sale.service';
import { AuthService } from '../../../identity/infrastructure/auth.service';

@Component({
  selector: 'app-sales',
  standalone: true,
  templateUrl: './sales.component.html',
  styleUrls: ['./sales.component.scss'],
  imports: [CommonModule, IonContent, IonButton],
})
export class SalesComponent implements OnInit {
  sales: Sale[] = [];
  isLoading = false;

  constructor(
    private saleService: SaleService,
    private authService: AuthService,
  ) {}

  ngOnInit(): void {
    this.loadSales();
  }

  loadSales(): void {
    const user = this.authService.getUser();

    if (!user?.restaurant_id) {
      console.log('No restaurant_id found in user');
      return;
    }

    this.isLoading = true;

    this.saleService
      .getSales({
        restaurant_id: user.restaurant_id,
      })
      .subscribe({
        next: (response: any) => {
          console.log('SALES RESPONSE', response);
          this.sales = response.sales ?? response.data ?? response;
          this.isLoading = false;
        },
        error: (error: unknown) => {
          console.log('ERROR loading sales', error);
          this.isLoading = false;
        },
      });
  }

  formatDate(date: string): string {
    return new Date(date).toLocaleString('es-ES');
  }
}
