import { CommonModule } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { IonButton, IonContent, IonIcon } from '@ionic/angular/standalone';
import { addIcons } from 'ionicons';
import { eyeOutline } from 'ionicons/icons';
import { Sale, SaleLine } from '../../domain/sale.model';
import { SaleService } from '../../infrastructure/sale.service';
import { AuthService } from '../../../identity/infrastructure/auth.service';
import { UserService } from '../../../identity/infrastructure/user.service';
import {
  OrderLine,
  OrderLineService,
} from '../../../orders/infrastructure/order-line.service';
import { ProductService } from '../../../catalog/infrastructure/product.service';

interface UserOption {
  id: string;
  name: string;
}
interface ProductOption {
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
  orderLines: OrderLine[] = [];
  products: ProductOption[] = [];

  selectedSale: Sale | null = null;
  showSaleDetailModal = false;

  selectedSaleLines: SaleLine[] = [];
  isLoadingSaleLines = false;

  constructor(
    private saleService: SaleService,
    private authService: AuthService,
    private userService: UserService,
    private orderLineService: OrderLineService,
    private productService: ProductService,
  ) {
    addIcons({ 'eye-outline': eyeOutline });
  }

  ngOnInit(): void {
    this.loadUsers();
    this.loadOrderLines();
    this.loadProducts();
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

  loadSaleLines(saleId: string): void {
    this.isLoadingSaleLines = true;

    this.saleService.getSaleLines(saleId).subscribe({
      next: (response: any) => {
        console.log('SALE LINES RESPONSE', response);
        this.selectedSaleLines = response.sale_lines ?? response.data ?? [];
        this.isLoadingSaleLines = false;
      },
      error: (error: unknown) => {
        console.log('ERROR loading sale lines', error);
        this.selectedSaleLines = [];
        this.isLoadingSaleLines = false;
      },
    });
  }

  loadOrderLines(): void {
    this.orderLineService.getOrderLines().subscribe({
      next: (response: any) => {
        this.orderLines = response.order_lines ?? response.data ?? response;
      },
      error: (error: unknown) => {
        console.log('ERROR loading order lines', error);
      },
    });
  }

  loadProducts(): void {
    this.productService.getProducts().subscribe({
      next: (response: any) => {
        this.products = response.products ?? response.data ?? response;
      },
      error: (error: unknown) => {
        console.log('ERROR loading products', error);
      },
    });
  }

  getProductName(orderLineId: string): string {
    const orderLine = this.orderLines.find((item: OrderLine) => {
      return String(item.id) === String(orderLineId);
    });

    if (!orderLine) {
      return 'Producto';
    }

    const product = this.products.find((item: ProductOption) => {
      return String(item.id) === String(orderLine.product_id);
    });

    return product?.name ?? 'Producto';
  }

  closeSaleDetail(): void {
    this.showSaleDetailModal = false;
    this.selectedSale = null;
    this.selectedSaleLines = [];
  }

  formatMoney(cents: number): string {
    return `${(cents / 100).toFixed(2)} €`;
  }

  getLineSubtotal(line: SaleLine): number {
    return line.price * line.quantity;
  }

  openSaleDetail(sale: Sale): void {
    this.selectedSale = sale;
    this.selectedSaleLines = [];
    this.showSaleDetailModal = true;

    console.log('SALE SELECTED', sale);

    this.loadSaleLines(sale.id);
  }

  closeSaleDetailFromBackdrop(event: MouseEvent): void {
  if (event.target !== event.currentTarget) {
    return;
  }

  this.closeSaleDetail();
}
}
