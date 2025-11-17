<?php

declare(strict_types=1);

namespace App\Domain\Equipment\DTOs;

use App\Domain\Equipment\Enums\EquipmentCondition;
use App\Domain\Equipment\Enums\EquipmentStatus;

class CreateEquipmentData
{
    public function __construct(
        public readonly int $equipmentTypeId,
        public readonly ?int $equipmentBrandId,
        public readonly string $name,
        public readonly ?string $model,
        public readonly string $serialNumber,
        public readonly ?string $assetCode,
        public readonly ?string $purchaseDate,
        public readonly ?float $purchaseCost,
        public readonly ?string $supplier,
        public readonly ?string $warrantyExpirationDate,
        public readonly ?array $specifications,
        public readonly EquipmentCondition $condition,
        public readonly EquipmentStatus $status,
        public readonly ?int $currentSpaceId,
        public readonly ?array $requiresAccessories,
        public readonly ?string $notes,
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
            equipmentTypeId: $data['equipment_type_id'],
            equipmentBrandId: $data['equipment_brand_id'] ?? null,
            name: $data['name'],
            model: $data['model'] ?? null,
            serialNumber: $data['serial_number'],
            assetCode: $data['asset_code'] ?? null,
            purchaseDate: $data['purchase_date'] ?? null,
            purchaseCost: isset($data['purchase_cost']) ? (float) $data['purchase_cost'] : null,
            supplier: $data['supplier'] ?? null,
            warrantyExpirationDate: $data['warranty_expiration_date'] ?? null,
            specifications: $data['specifications'] ?? null,
            condition: EquipmentCondition::from($data['condition'] ?? 'good'),
            status: EquipmentStatus::from($data['status'] ?? 'available'),
            currentSpaceId: $data['current_space_id'] ?? null,
            requiresAccessories: $data['requires_accessories'] ?? null,
            notes: $data['notes'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'equipment_type_id' => $this->equipmentTypeId,
            'equipment_brand_id' => $this->equipmentBrandId,
            'name' => $this->name,
            'model' => $this->model,
            'serial_number' => $this->serialNumber,
            'asset_code' => $this->assetCode,
            'purchase_date' => $this->purchaseDate,
            'purchase_cost' => $this->purchaseCost,
            'supplier' => $this->supplier,
            'warranty_expiration_date' => $this->warrantyExpirationDate,
            'specifications' => $this->specifications,
            'condition' => $this->condition,
            'status' => $this->status,
            'current_space_id' => $this->currentSpaceId,
            'requires_accessories' => $this->requiresAccessories,
            'notes' => $this->notes,
        ];
    }
}
