<?php

namespace App\Family\Infrastructure\Entrypoint\Http;

use App\Family\Application\GetFamily\GetFamily;
use App\Family\Domain\Exception\FamilyNotFoundException;
use Illuminate\Http\JsonResponse;

final class GetController
{
    public function __construct(
        private GetFamily $getFamily,
    ) {}

    public function __invoke(string $id): JsonResponse
    {
        try {
            $response = ($this->getFamily)($id);

            return new JsonResponse($response->toArray(), 200);
        } catch (FamilyNotFoundException $exception) {
            return new JsonResponse(['message' => $exception->getMessage()], 404);
        }
    }
}
