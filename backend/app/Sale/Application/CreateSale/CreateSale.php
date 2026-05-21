<?php

namespace App\Sale\Application\CreateSale;

use App\Sale\Domain\Entity\Sale;
use App\Sale\Domain\Interfaces\SaleRepositoryInterface;
use App\Sale\Domain\ValueObject\SaleTotal;
use App\Shared\Domain\ValueObject\DomainDateTime;
use App\Shared\Domain\ValueObject\OrderId;
use App\Shared\Domain\ValueObject\RestaurantId;
use App\Shared\Domain\ValueObject\UserId;
use App\Sale\Domain\ValueObject\SaleTicketNumber;

final class CreateSale
{
    public function __construct(
        private SaleRepositoryInterface $saleRepository,
    ) {}

    public function __invoke(
        string $restaurantId,
        string $orderId,
        string $userId,
        int $total,
    ): CreateSaleResponse {
        $restaurantIdVO = RestaurantId::create($restaurantId);
        $orderIdVO = OrderId::create($orderId);
        $userIdVO = UserId::create($userId);
        $totalVO = SaleTotal::create($total);
        $ticketNumberVO = SaleTicketNumber::create($this->saleRepository->nextTicketNumberByRestaurant($restaurantId),);
        $sale = Sale::dddCreate(
            $restaurantIdVO,
            $orderIdVO,
            $userIdVO,
            $ticketNumberVO,
            DomainDateTime::now(),
            $totalVO,
        );

        $this->saleRepository->save($sale);

        return CreateSaleResponse::create($sale);
    }
}
