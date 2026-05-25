import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ActivatedRoute, Router } from '@angular/router';
import { IonContent } from '@ionic/angular/standalone';
import { Product } from '../../../../catalog/domain/product.model';
import { OrderProductsComponent } from '../components/order-products/order-products.component';
import { AuthService } from '../../../../identity/infrastructure/auth.service';
import {
  CurrentOrderLine,
  OrderSummaryComponent,
} from '../components/order-summary/order-summary.component';
import { Order, OrderService } from '../../../infrastructure/order.service';
import { CurrentOrderFacade } from '../../../application/current-order.facade';
import { Family } from '../../../../catalog/domain/family.model';
import {
  PaymentLine,
  PaymentModalComponent,
  PaymentResult,
} from '../components/payment-modal/payment-modal.component';
import { PrebillModalComponent } from '../components/prebill-modal/prebill-modal.component';
import { TableService } from '../../../../floor/infrastructure/table.service';
import { FinalTicketModalComponent } from '../components/final-ticket-modal/final-ticket-modal.component';

@Component({
  selector: 'app-orders',
  standalone: true,
  templateUrl: './orders.component.html',
  styleUrls: ['./orders.component.scss'],
  imports: [
    CommonModule,
    IonContent,
    OrderSummaryComponent,
    OrderProductsComponent,
    PaymentModalComponent,
    PrebillModalComponent,
    FinalTicketModalComponent,
  ],
})
export class OrdersComponent implements OnInit {
  orderId: string | null = null;
  currentOrder: Order | null = null;

  families: Family[] = [];

  products: Product[] = [];
  orderLines: CurrentOrderLine[] = [];

  user: any = null;
  isLoading = false;

  isSentToKitchen = false;
  sentLineIds: string[] = [];

  showPaymentModal = false;

  showPrebillModal = false;

  tableName = '';

  showFinalTicketModal = false;
  finalTicketPayments: PaymentLine[] = [];

  showCancelOrderModal = false;

  constructor(
    private route: ActivatedRoute,
    private router: Router,
    private authService: AuthService,
    private currentOrderFacade: CurrentOrderFacade,
    private tableService: TableService,
    private orderService: OrderService,
  ) {}

  ngOnInit(): void {
    this.orderId = this.route.snapshot.paramMap.get('id');
    this.user = this.authService.getUser();

    this.loadPageData();
  }

  loadPageData(): void {
    if (!this.orderId) {
      return;
    }

    this.isLoading = true;

    this.currentOrderFacade.loadCurrentOrderPage(this.orderId).subscribe({
      next: ({ currentOrder, products, orderLines, families }) => {
        this.currentOrder = currentOrder;
        this.products = products;
        this.orderLines = orderLines;
        this.families = families;
        this.restoreSentLines();
        this.loadTableName();
        this.isLoading = false;
      },
      error: (error: unknown) => {
        console.log('ERROR loading order page', error);
        this.isLoading = false;
      },
    });
  }

  loadTableName(): void {
    if (!this.currentOrder) {
      return;
    }

    const currentOrder = this.currentOrder;

    this.tableService.getTables().subscribe({
      next: (response: any) => {
        const tables = response.tables ?? response.data ?? response;

        const table = tables.find((item: any) => {
          return String(item.id) === String(currentOrder.table_id);
        });

        this.tableName = table?.name ?? '';
      },
      error: (error: unknown) => {
        console.log('ERROR loading table name', error);
      },
    });
  }

  addProduct(product: Product): void {
    if (!this.currentOrder || !this.user) {
      return;
    }

    this.currentOrderFacade
      .addProductToOrder(
        product,
        this.currentOrder.id,
        this.user,
        this.getEditableOrderLines(),
      )
      .subscribe({
        next: (line) => {
          if (!line) {
            return;
          }

          const existingLine = this.orderLines.find(
            (item) => String(item.id) === String(line.id),
          );

          if (existingLine) {
            existingLine.quantity = line.quantity;
            return;
          }

          this.orderLines.push(line);
        },
        error: (error: unknown) => {
          console.log('ERROR adding product to order', error);
        },
      });
  }
  getEditableOrderLines(): CurrentOrderLine[] {
    return this.orderLines.filter((line) => {
      return this.canEditLine(line);
    });
  }

  increaseLine(line: CurrentOrderLine): void {
    if (!this.canEditLine(line)) {
      return;
    }

    this.changeLineQuantity(line, line.quantity + 1);
  }

  decreaseLine(line: CurrentOrderLine): void {
    if (!this.canEditLine(line)) {
      return;
    }

    if (line.quantity <= 1) {
      this.deleteLine(line);
      return;
    }

    this.changeLineQuantity(line, line.quantity - 1);
  }

  changeLineQuantity(line: CurrentOrderLine, quantity: number): void {
    this.currentOrderFacade.updateLineQuantity(line, quantity).subscribe({
      next: (updatedLine) => {
        const existingLine = this.orderLines.find(
          (item) => item.id === updatedLine?.id,
        );

        if (existingLine && updatedLine) {
          existingLine.quantity = updatedLine.quantity;
        }
      },
      error: (error: unknown) => {
        console.log('ERROR updating order line', error);
      },
    });
  }

  deleteLine(line: CurrentOrderLine): void {
    if (!this.canEditLine(line)) {
      return;
    }

    this.currentOrderFacade.deleteLine(line.id).subscribe({
      next: () => {
        this.orderLines = this.orderLines.filter((item) => item.id !== line.id);
      },
      error: (error: unknown) => {
        console.log('ERROR deleting order line', error);
      },
    });
  }

  checkout(): void {
    if (!this.currentOrder || !this.user || this.orderLines.length === 0) {
      return;
    }

    this.showPaymentModal = true;
  }

  confirmPayment(payment: PaymentResult): void {
    console.log('PAYMENT', payment);

    if (!this.currentOrder || !this.user) {
      return;
    }

    this.currentOrderFacade
      .checkoutOrder(this.currentOrder.id, this.user)
      .subscribe({
        next: () => {
          this.finalTicketPayments = payment.payments;
          this.showPaymentModal = false;
          this.showFinalTicketModal = true;
        },
        error: (error: unknown) => {
          console.log('ERROR checkout', error);
        },
      });
  }

  getOrderTotal(): number {
    return this.orderLines.reduce((total, line) => {
      return total + line.price * line.quantity;
    }, 0);
  }

  sendToKitchen(): void {
    if (this.orderLines.length === 0) {
      return;
    }

    const newSentLineIds = this.orderLines.map((line) => line.id);

    this.sentLineIds = Array.from(
      new Set([...this.sentLineIds, ...newSentLineIds]),
    );

    this.isSentToKitchen = true;
    this.persistSentLines();
  }
  openPrebill(): void {
    if (this.orderLines.length === 0) {
      return;
    }

    this.showPrebillModal = true;
  }
  goBackToTables(): void {
    this.showFinalTicketModal = false;
    this.router.navigate(['/tpv/tables']);
  }

  openCancelOrderModal(): void {
    if (this.orderLines.length > 0) {
      return;
    }

    this.showCancelOrderModal = true;
  }

  closeCancelOrderModal(): void {
    this.showCancelOrderModal = false;
  }

  closeCancelOrderModalFromBackdrop(event: MouseEvent): void {
    if (event.target !== event.currentTarget) {
      return;
    }

    this.closeCancelOrderModal();
  }

  confirmCancelOrder(): void {
    if (!this.currentOrder || !this.user || this.orderLines.length > 0) {
      return;
    }

    const payload = {
      status: 'cancelled',
      closed_by_user_id: String(this.user.id),
      diners: Number(this.currentOrder.diners),
      closed_at: new Date().toISOString(),
    };

    this.orderService.updateOrder(this.currentOrder.id, payload).subscribe({
      next: () => {
        localStorage.removeItem(this.getSentLinesStorageKey());

        this.showCancelOrderModal = false;
        this.router.navigate(['/tpv/tables']);
      },
      error: (error: unknown) => {
        console.log('ERROR liberando mesa', error);
      },
    });
  }

  private getSentLinesStorageKey(): string {
    return `order_sent_lines_${this.orderId}`;
  }

  private restoreSentLines(): void {
    if (!this.orderId) {
      return;
    }

    const storedValue = localStorage.getItem(this.getSentLinesStorageKey());

    if (!storedValue) {
      this.sentLineIds = [];
      this.isSentToKitchen = false;
      return;
    }

    this.sentLineIds = JSON.parse(storedValue);
    this.isSentToKitchen = this.sentLineIds.length > 0;
  }

  private persistSentLines(): void {
    if (!this.orderId) {
      return;
    }

    localStorage.setItem(
      this.getSentLinesStorageKey(),
      JSON.stringify(this.sentLineIds),
    );
  }

  private isLineSent(line: CurrentOrderLine): boolean {
    return this.sentLineIds.includes(line.id);
  }

  private isLineFromCurrentUser(line: CurrentOrderLine): boolean {
    return String(line.user_id) === String(this.user?.id);
  }

  private canEditLine(line: CurrentOrderLine): boolean {
    return this.isLineFromCurrentUser(line) && !this.isLineSent(line);
  }
}
