<?php

declare(strict_types=1);

namespace App\Domain\Auth\DTOs;

use App\Domain\Auth\Enums\DocumentType;

class RegisterData
{
    public function __construct(
        public readonly string $firstName,
        public readonly string $lastName,
        public readonly string $email,
        public readonly string $password,
        public readonly DocumentType $documentType,
        public readonly string $documentNumber,
        public readonly ?string $phone,
        public readonly string $role,
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
            firstName: $data['first_name'],
            lastName: $data['last_name'],
            email: $data['email'],
            password: $data['password'],
            documentType: DocumentType::from($data['document_type']),
            documentNumber: $data['document_number'],
            phone: $data['phone'] ?? null,
            role: $data['role'],
        );
    }
}
