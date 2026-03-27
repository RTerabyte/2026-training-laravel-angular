<?php

namespace App\Family\Infrastructure\Persistence\Repositories;

use App\Family\Domain\Entity\Family;
use App\Family\Domain\Interfaces\FamilyRepositoryInterface;
use App\Family\Infrastructure\Persistence\Models\EloquentFamily;

class EloquentFamilyRepository implements FamilyRepositoryInterface
{
    public function __construct(
        private EloquentFamily $model,
    ) {}

    public function save(Family $family): void
    {
        $this->model->newQuery()->updateOrCreate(
            ['uuid' => $family->id()->value()],
            [
                'name' => $family->name(),
                'restaurant_id' => $family->restaurantId(),
                'active' => $family->active(),
                'created_at' => $family->createdAt()->value(),
                'updated_at' => $family->updatedAt()->value(),
            ]
        );
    }

    public function findById(string $id): ?Family
    {
        $model = $this->model->newQuery()->where('uuid', $id)->first();

        if ($model === null) {
            return null;
        }

        return Family::fromPersistence(
            $model->uuid,
            $model->name,
            $model->restaurant_id,
            $model->active,
            $model->created_at->toDateTimeImmutable(),
            $model->updated_at->toDateTimeImmutable(),
        );
    }

   
    public function findAll(): array
    {
        $models = $this->model->newQuery()->get();

        return $models->map(function (EloquentFamily $model) {
            return Family::fromPersistence(
                $model->uuid,
                $model->name,
                $model->restaurant_id,
                $model->active,
                $model->created_at->toDateTimeImmutable(),
                $model->updated_at->toDateTimeImmutable(),
            );
        })->all();
    }

    public function delete(Family $family): void
    {
        $this->model->newQuery()->where('uuid', $family->id()->value())->delete();
    }
}