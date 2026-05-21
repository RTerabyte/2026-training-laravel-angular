import { CommonModule } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { IonButton, IonContent, IonIcon } from '@ionic/angular/standalone';
import { addIcons } from 'ionicons';
import { eyeOutline } from 'ionicons/icons';
import { Sale } from '../../domain/sale.model';
import { SaleService } from '../../infrastructure/sale.service';
import { AuthService } from '../../../identity/infrastructure/auth.service';
import { UserService } from '../../../identity/infrastructure/user.service';

interface UserOption {
  id: string;
  name: string;
}

@Component({
  selector: 'app-sales',
  standalone: true,
  templateUrl: './sales.component.html',
  styleUrls: ['./sales.component.scss'],
  imports: [CommonModule, IonContent, IonButton, IonIcon],
})
export class SalesComponent implements OnInit {
  sales: Sale[] = [];
  isLoading = false;

  users: UserOption[] = [];
  
  selectedSale: Sale | null = null;
  showSaleDetailModal = false;

  constructor(
    private saleService: SaleService,
    private authService: AuthService,
    private userService: UserService,
  ) {
    addIcons({ 'eye-outline': eyeOutline });
  }

  ngOnInit(): void {
    this.loadUsers();
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

  loadUsers(): void {
    this.userService.getUsers().subscribe({
      next: (response: any) => {
        this.users = response.users ?? response.data ?? response;
      },
      error: (error: unknown) => {
        console.log('ERROR loading users', error);
      },
    });
  }

  formatDate(date: string): string {
    return new Date(date).toLocaleString('es-ES');
  }

  getUserName(userId: string): string {
    const user = this.users.find((item: UserOption) => {
      return String(item.id) === String(userId);
    });

    return user?.name ?? userId;
  }
  

  openSaleDetail(sale: Sale): void {
    this.selectedSale = sale;
    this.showSaleDetailModal = true;

    console.log('SALE SELECTED', sale);
  }
}
