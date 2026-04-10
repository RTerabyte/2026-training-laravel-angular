<?php

namespace App\Restaurant\Application\UpdateRestaurant;

use App\Restaurant\Domain\Exception\RestaurantNotFoundException;
use App\Restaurant\Domain\Interfaces\RestaurantRepositoryInterface;
use App\Restaurant\Domain\ValueObject\RestaurantLegalName;
use App\Restaurant\Domain\ValueObject\RestaurantName;
use App\Shared\Domain\ValueObject\Email;
use App\Shared\Domain\ValueObject\TaxId;

final class UpdateRestaurant
{
    public function __construct(
        private RestaurantRepositoryInterface $restaurantRepository,
    ) {}

    public function __invoke(
        string $id,
        ?string $name = null,
        ?string $legalName = null,
        ?string $taxId = null,
        ?string $email = null,
    ): UpdateRestaurantResponse {
        $restaurant = $this->restaurantRepository->findById($id);

        if ($restaurant === null) {
            throw RestaurantNotFoundException::withId($id);
        }

        $updatedRestaurant = $restaurant->update(
            $name !== null ? RestaurantName::create($name) : $restaurant->name(),
            $legalName !== null ? RestaurantLegalName::create($legalName) : $restaurant->legalName(),
            $taxId !== null ? TaxId::create($taxId) : $restaurant->taxId(),
            $email !== null ? Email::create($email) : $restaurant->email(),
        );

        $this->restaurantRepository->save($updatedRestaurant);

        return UpdateRestaurantResponse::create($updatedRestaurant);
    }
}
