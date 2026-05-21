<?php

namespace App\Sale\Application\CheckoutOrder;

final readonly class CheckoutOrderResponse
{
    public function __construct(
        public string $saleId,
        public string $restaurantId,
        public string $orderId,
        public string $userId,
        public ?int $ticketNumber,
        public int $total,

    ) {}

    public static function create(
        string $saleId,
        string $restaurantId,
        string $orderId,
        string $userId,
        ?int $ticketNumber,
        int $total,
    ): self {
        return new self(
            $saleId,
            $restaurantId,
            $orderId,
            $userId,
            $ticketNumber,
            $total,
        );
    }

    public function toArray(): array
    {
        return [
            'sale_id' => $this->saleId,
            'restaurant_id' => $this->restaurantId,
            'order_id' => $this->orderId,
            'user_id' => $this->userId,
            'ticket_number' => $this->ticketNumber,
            'total' => $this->total,
        ];
    }
}
