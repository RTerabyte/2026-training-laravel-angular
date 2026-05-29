import { CommonModule } from '@angular/common';
import { Component, EventEmitter, Input, OnInit, Output } from '@angular/core';
import {
  IonButton,
  IonCard,
  IonCardContent,
  IonInput,
} from '@ionic/angular/standalone';
import { FormsModule } from '@angular/forms';

export type PaymentMethod = 'cash' | 'card';

export interface PaymentLine {
  method: PaymentMethod;
  amount: number;
  receivedAmount?: number;
  changeAmount?: number;
}

export interface PaymentResult {
  payments: PaymentLine[];
  totalPaid: number;
  changeAmount: number;
}

@Component({
  selector: 'app-payment-modal',
  standalone: true,
  templateUrl: './payment-modal.component.html',
  styleUrls: ['./payment-modal.component.scss'],
  imports: [
    CommonModule,
    FormsModule,
    IonCard,
    IonCardContent,
    IonButton,
    IonInput,
  ],
})
export class PaymentModalComponent implements OnInit {
  @Input() total = 0;
  @Input() diners = 1;

  @Output() paymentConfirmed = new EventEmitter<PaymentResult>();
  @Output() closed = new EventEmitter<void>();

  selectedMethod: PaymentMethod = 'cash';

  cashReceived = 0;
  cardAmount = 0;

  activeAmountText = '0.00';
  isTypingAmount = false;

  payments: PaymentLine[] = [];

  quickCashAmounts = [5, 10, 20, 50, 100];

  splitPeople = 1;

  showSplitOptions = false;

  keypadKeys = ['1', '2', '3', '4', '5', '6', '7', '8', '9', ',', '0', '←'];

  ngOnInit(): void {
    this.splitPeople = Math.max(Number(this.diners), 1);
    this.setCurrentAmountFromCents(this.getPendingAmount());
  }

  selectMethod(method: PaymentMethod): void {
    this.selectedMethod = method;
    this.setCurrentAmountFromCents(this.getPendingAmount());
  }

  pressKey(key: string): void {
    if (key === '←') {
      this.removeLastDigit();
      return;
    }

    if (key === ',') {
      this.addDecimalSeparator();
      return;
    }

    this.addDigit(key);
  }

  clearAmount(): void {
    this.activeAmountText = '0';
    this.isTypingAmount = true;
    this.syncActiveAmount();
  }

  setQuickCashAmount(amount: number): void {
    this.setActiveAmount(amount);
  }

  setExactAmount(): void {
    this.setCurrentAmountFromCents(this.getPendingAmount());
  }

  setSplitAmount(): void {
    this.setCurrentAmountFromCents(this.getSplitAmount());
  }

  increaseSplitPeople(): void {
    this.splitPeople++;
  }

  decreaseSplitPeople(): void {
    if (this.splitPeople <= 1) {
      return;
    }

    this.splitPeople--;
  }

  useDinersAsSplitPeople(): void {
    this.splitPeople = Math.max(Number(this.diners), 1);
  }

  addPayment(): void {
    if (this.selectedMethod === 'cash') {
      this.addCashPayment();
      return;
    }

    this.addCardPayment();
  }

  addCashPayment(): void {
    const receivedAmount = Math.round(Number(this.cashReceived) * 100);

    if (receivedAmount <= 0) {
      return;
    }

    const pendingAmount = this.getPendingAmount();

    if (pendingAmount <= 0) {
      return;
    }

    const paymentAmount = Math.min(receivedAmount, pendingAmount);
    const changeAmount = Math.max(receivedAmount - pendingAmount, 0);

    this.payments.push({
      method: 'cash',
      amount: paymentAmount,
      receivedAmount,
      changeAmount,
    });

    this.setCurrentAmountFromCents(this.getPendingAmount());
  }

  addCardPayment(): void {
    const amount = Math.round(Number(this.cardAmount) * 100);
    const pendingAmount = this.getPendingAmount();

    if (amount <= 0 || amount > pendingAmount) {
      return;
    }

    this.payments.push({
      method: 'card',
      amount,
    });

    this.setCurrentAmountFromCents(this.getPendingAmount());
  }

  removePayment(index: number): void {
    this.payments.splice(index, 1);
    this.setCurrentAmountFromCents(this.getPendingAmount());
  }

  confirmPayment(): void {
    if (!this.isPaymentComplete()) {
      return;
    }

    this.paymentConfirmed.emit({
      payments: this.payments,
      totalPaid: this.getPaidAmount(),
      changeAmount: this.getChangeAmount(),
    });
  }

  getTotalInEuros(): number {
    return this.total / 100;
  }

  getPaidAmount(): number {
    return this.payments.reduce((total, payment) => {
      return total + payment.amount;
    }, 0);
  }

  getPendingAmount(): number {
    return Math.max(this.total - this.getPaidAmount(), 0);
  }

  getChangeAmount(): number {
    return this.payments.reduce((total, payment) => {
      return total + (payment.changeAmount ?? 0);
    }, 0);
  }

  getSplitAmount(): number {
    if (this.splitPeople <= 1) {
      return this.getPendingAmount();
    }

    return Math.min(
      Math.ceil(this.total / this.splitPeople),
      this.getPendingAmount(),
    );
  }

  getActiveAmountDisplay(): string {
    return `${this.activeAmountText.replace('.', ',')} €`;
  }

  isPaymentComplete(): boolean {
    return this.total > 0 && this.getPendingAmount() === 0;
  }

  fixNegativeAmounts(): void {
    if (this.cashReceived < 0) {
      this.cashReceived = 0;
    }

    if (this.cardAmount < 0) {
      this.cardAmount = 0;
    }
  }

  toggleSplitOptions(): void {
    this.showSplitOptions = !this.showSplitOptions;
  }

  private addDigit(key: string): void {
    if (!this.isTypingAmount) {
      this.activeAmountText = key;
      this.isTypingAmount = true;
      this.syncActiveAmount();
      return;
    }

    if (this.activeAmountText === '0') {
      this.activeAmountText = key;
    } else {
      this.activeAmountText += key;
    }

    const decimalPart = this.activeAmountText.split('.')[1];

    if (decimalPart && decimalPart.length > 2) {
      this.activeAmountText = this.activeAmountText.slice(0, -1);
      return;
    }

    this.syncActiveAmount();
  }

  private addDecimalSeparator(): void {
    if (!this.isTypingAmount) {
      this.activeAmountText = '0.';
      this.isTypingAmount = true;
      this.syncActiveAmount();
      return;
    }

    if (this.activeAmountText.includes('.')) {
      return;
    }

    this.activeAmountText += '.';
    this.syncActiveAmount();
  }

  private removeLastDigit(): void {
    if (!this.isTypingAmount) {
      this.activeAmountText = '0';
      this.isTypingAmount = true;
      this.syncActiveAmount();
      return;
    }

    this.activeAmountText =
      this.activeAmountText.length > 1
        ? this.activeAmountText.slice(0, -1)
        : '0';

    this.syncActiveAmount();
  }

  private setCurrentAmountFromCents(amount: number): void {
    this.setActiveAmount(amount / 100);
  }

  private setActiveAmount(amount: number): void {
    const safeAmount = Math.max(Number(amount), 0);

    this.activeAmountText = safeAmount.toFixed(2);
    this.isTypingAmount = false;

    if (this.selectedMethod === 'cash') {
      this.cashReceived = safeAmount;
      return;
    }

    this.cardAmount = safeAmount;
  }

  private syncActiveAmount(): void {
    const amount = Number(this.activeAmountText.replace(',', '.')) || 0;

    if (this.selectedMethod === 'cash') {
      this.cashReceived = amount;
      return;
    }

    this.cardAmount = amount;
  }
}
