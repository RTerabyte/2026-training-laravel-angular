<?php

namespace App\Family\Infrastructure\Entrypoint\Http;

use App\Family\Application\CreateFamily\CreateFamily;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

final class PostController
{
    public function __construct(
        private CreateFamily $createFamily,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'restaurant_id' => ['required', 'integer'],
            'name' => ['required', 'string', 'max:255'], 
        ]);

        $response = ($this->createFamily)(
            (string) $validated['restaurant_id'],
            $validated['name'],
        );

        return new JsonResponse($response->toArray(), 201);
    }
}