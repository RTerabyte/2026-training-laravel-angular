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

  searchTerm = '';
  startDate = '';
  endDate = '';

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
          this.sales = (response.sales ?? response.data ?? response).sort(
            (a: Sale, b: Sale) => {
              return (
                new Date(b.value_date).getTime() -
                new Date(a.value_date).getTime()
              );
            },
          );
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

  getUserName(userId: string | number): string {
    const user = this.users.find((item: UserOption) => {
      return String(item.id) === String(userId);
    });

    return user?.name ?? String(userId);
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

  getProductName(orderLineId: string | number): string {
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

  setSearchTerm(event: Event): void {
    const input = event.target as HTMLInputElement;

    this.searchTerm = input.value;
  }

  setStartDate(event: Event): void {
    const input = event.target as HTMLInputElement;

    this.startDate = input.value;
  }

  setEndDate(event: Event): void {
    const input = event.target as HTMLInputElement;

    this.endDate = input.value;
  }

  clearFilters(): void {
    this.searchTerm = '';
    this.startDate = '';
    this.endDate = '';
  }

  setTodayFilter(): void {
    const today = this.formatDateForInput(new Date());

    this.startDate = today;
    this.endDate = today;
  }

  getFilteredSales(): Sale[] {
    const term = this.searchTerm.trim().toLowerCase();

    return this.sales.filter((sale) => {
      const ticket = String(sale.ticket_number ?? '').toLowerCase();
      const userName = this.getUserName(sale.user_id).toLowerCase();
      const total = this.formatMoney(sale.total).toLowerCase();

      const matchesSearch =
        !term ||
        ticket.includes(term) ||
        userName.includes(term) ||
        total.includes(term);

      const saleDate = new Date(sale.value_date);

      const matchesStart =
        !this.startDate || saleDate >= this.getStartOfDay(this.startDate);

      const matchesEnd =
        !this.endDate || saleDate <= this.getEndOfDay(this.endDate);

      return matchesSearch && matchesStart && matchesEnd;
    });
  }

  getTotalFilteredSales(): number {
    return this.getFilteredSales().reduce((total, sale) => {
      return total + sale.total;
    }, 0);
  }

  getAverageFilteredTicket(): number {
    const filteredSales = this.getFilteredSales();

    if (filteredSales.length === 0) {
      return 0;
    }

    return Math.round(this.getTotalFilteredSales() / filteredSales.length);
  }

  getBestTicketTotal(): number {
    const filteredSales = this.getFilteredSales();

    if (filteredSales.length === 0) {
      return 0;
    }

    return Math.max(...filteredSales.map((sale) => sale.total));
  }

  private getStartOfDay(value: string): Date {
    const [year, month, day] = value.split('-').map(Number);

    return new Date(year, month - 1, day, 0, 0, 0);
  }

  private getEndOfDay(value: string): Date {
    const [year, month, day] = value.split('-').map(Number);

    return new Date(year, month - 1, day, 23, 59, 59);
  }

  private formatDateForInput(date: Date): string {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');

    return `${year}-${month}-${day}`;
  }

  closeSaleDetailFromBackdrop(event: MouseEvent): void {
    if (event.target !== event.currentTarget) {
      return;
    }

    this.closeSaleDetail();
  }
}
