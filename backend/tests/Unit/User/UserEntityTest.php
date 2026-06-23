<?php

namespace Tests\Unit\User;

use App\Shared\Domain\ValueObject\DomainDateTime;
use App\Shared\Domain\ValueObject\Email;
use App\Shared\Domain\ValueObject\RestaurantId;
use App\User\Domain\Entity\User;
use App\User\Domain\ValueObject\PasswordHash;
use App\User\Domain\ValueObject\UserImageSrc;
use App\User\Domain\ValueObject\UserName;
use App\User\Domain\ValueObject\UserPin;
use App\User\Domain\ValueObject\UserRole;
use PHPUnit\Framework\TestCase;

class UserEntityTest extends TestCase
{
    public function test_ddd_create_builds_entity_with_attributes_and_vos(): void
    {
        $restaurantId = RestaurantId::create('1');
        $role = UserRole::create('admin');
        $imageSrc = UserImageSrc::create(null);
        $name = UserName::create('Test User');
        $email = Email::create('user@example.com');
        $passwordHash = PasswordHash::create(
            '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
        );
        $pin = UserPin::create('1234');

        $user = User::dddCreate(
            $restaurantId,
            $role,
            $imageSrc,
            $name,
            $email,
            $passwordHash,
            $pin,
        );

        $this->assertInstanceOf(User::class, $user);
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',
            $user->id()->value(),
        );
        $this->assertSame('Test User', $user->name()->value());
        $this->assertSame('user@example.com', $user->email()->value());
        $this->assertSame(
            '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
            $user->passwordHash()->value(),
        );

        $this->assertInstanceOf(DomainDateTime::class, $user->createdAt());
        $this->assertInstanceOf(DomainDateTime::class, $user->updatedAt());
        $this->assertEqualsWithDelta(
            $user->createdAt()->value()->getTimestamp(),
            $user->updatedAt()->value()->getTimestamp(),
            1,
        );
    }
}
