<?php

declare(strict_types=1);

namespace App\Domain\Auth\DTOs;

class LoginCredentials
{
    public function __construct(
        public readonly string $email,
        public readonly string $password,
        public readonly string $deviceName,
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
            email: $data['email'],
            password: $data['password'],
            deviceName: $data['device_name'] ?? 'unknown',
        );
    }
}
