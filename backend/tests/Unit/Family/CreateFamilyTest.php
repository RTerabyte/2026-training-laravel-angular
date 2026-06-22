<?php

namespace Tests\Unit\Family;

use App\Family\Application\CreateFamily\CreateFamily;
use App\Family\Application\CreateFamily\CreateFamilyResponse;
use App\Family\Domain\Entity\Family;
use App\Family\Domain\Interfaces\FamilyRepositoryInterface;
use Mockery;
use PHPUnit\Framework\TestCase;

class CreateFamilyTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_invoke_creates_family_saves_via_repository_and_returns_response(): void
    {
        $repository = Mockery::mock(FamilyRepositoryInterface::class);

        $repository->shouldReceive('save')
            ->once()
            ->with(Mockery::on(function (Family $family) {
                return $family->restaurantId()->value() === '1'
                    && $family->name()->value() === 'Entrantes'
                    && $family->active() === true;
            }));

        $createFamily = new CreateFamily($repository);
        $response = $createFamily('1', 'Entrantes');

        $this->assertInstanceOf(CreateFamilyResponse::class, $response);
        $this->assertSame('1', $response->restaurantId);
        $this->assertSame('Entrantes', $response->name);
        $this->assertTrue($response->active);
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',
            $response->id,
        );

        $array = $response->toArray();
        $this->assertArrayHasKey('created_at', $array);
        $this->assertArrayHasKey('updated_at', $array);
    }
}
