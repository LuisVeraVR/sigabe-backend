<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\Equipment\Models\Equipment;
use App\Domain\Equipment\Models\EquipmentBrand;
use App\Domain\Equipment\Models\EquipmentType;
use Illuminate\Database\Seeder;

class EquipmentSeeder extends Seeder
{
    public function run(): void
    {
        $this->createEquipmentTypes();
        $this->createEquipmentBrands();
        $this->createEquipment();
    }

    private function createEquipmentTypes(): void
    {
        $types = [
            [
                'name' => 'Computador Portátil',
                'slug' => 'computador-portatil',
                'description' => 'Laptops y notebooks para uso general y especializado',
                'icon' => 'laptop',
                'requires_training' => false,
                'average_loan_duration_hours' => 48,
            ],
            [
                'name' => 'Computador de Escritorio',
                'slug' => 'computador-escritorio',
                'description' => 'PCs de escritorio para laboratorios',
                'icon' => 'desktop',
                'requires_training' => false,
                'average_loan_duration_hours' => 24,
            ],
            [
                'name' => 'Proyector',
                'slug' => 'proyector',
                'description' => 'Proyectores multimedia para presentaciones',
                'icon' => 'projector',
                'requires_training' => true,
                'average_loan_duration_hours' => 8,
            ],
            [
                'name' => 'Tablet',
                'slug' => 'tablet',
                'description' => 'Tablets Android e iPad',
                'icon' => 'tablet',
                'requires_training' => false,
                'average_loan_duration_hours' => 24,
            ],
            [
                'name' => 'Cámara Digital',
                'slug' => 'camara-digital',
                'description' => 'Cámaras fotográficas y de video profesionales',
                'icon' => 'camera',
                'requires_training' => true,
                'average_loan_duration_hours' => 8,
            ],
            [
                'name' => 'Micrófono',
                'slug' => 'microfono',
                'description' => 'Micrófonos de diferentes tipos',
                'icon' => 'microphone',
                'requires_training' => false,
                'average_loan_duration_hours' => 8,
            ],
            [
                'name' => 'Parlantes',
                'slug' => 'parlantes',
                'description' => 'Sistemas de audio y parlantes',
                'icon' => 'speaker',
                'requires_training' => false,
                'average_loan_duration_hours' => 8,
            ],
            [
                'name' => 'Impresora',
                'slug' => 'impresora',
                'description' => 'Impresoras láser e inkjet',
                'icon' => 'printer',
                'requires_training' => true,
                'average_loan_duration_hours' => 4,
            ],
            [
                'name' => 'Router WiFi',
                'slug' => 'router-wifi',
                'description' => 'Routers y puntos de acceso inalámbricos',
                'icon' => 'wifi',
                'requires_training' => true,
                'average_loan_duration_hours' => 12,
            ],
            [
                'name' => 'Disco Duro Externo',
                'slug' => 'disco-duro-externo',
                'description' => 'Unidades de almacenamiento externas',
                'icon' => 'hard-drive',
                'requires_training' => false,
                'average_loan_duration_hours' => 24,
            ],
        ];

        foreach ($types as $type) {
            // Usamos slug como clave de búsqueda para evitar duplicados
            EquipmentType::updateOrCreate(
                ['slug' => $type['slug']], // criterio único
                $type                       // datos a actualizar/crear
            );
        }

        $this->command?->info('✓ Tipos de equipos creados/actualizados');
    }

    private function createEquipmentBrands(): void
    {
        $brands = [
            [
                'name' => 'HP',
                'slug' => 'hp',
                'country' => 'Estados Unidos',
                'website' => 'https://www.hp.com',
            ],
            [
                'name' => 'Dell',
                'slug' => 'dell',
                'country' => 'Estados Unidos',
                'website' => 'https://www.dell.com',
            ],
            [
                'name' => 'Lenovo',
                'slug' => 'lenovo',
                'country' => 'China',
                'website' => 'https://www.lenovo.com',
            ],
            [
                'name' => 'Asus',
                'slug' => 'asus',
                'country' => 'Taiwán',
                'website' => 'https://www.asus.com',
            ],
            [
                'name' => 'Acer',
                'slug' => 'acer',
                'country' => 'Taiwán',
                'website' => 'https://www.acer.com',
            ],
            [
                'name' => 'Apple',
                'slug' => 'apple',
                'country' => 'Estados Unidos',
                'website' => 'https://www.apple.com',
            ],
            [
                'name' => 'Samsung',
                'slug' => 'samsung',
                'country' => 'Corea del Sur',
                'website' => 'https://www.samsung.com',
            ],
            [
                'name' => 'Epson',
                'slug' => 'epson',
                'country' => 'Japón',
                'website' => 'https://www.epson.com',
            ],
            [
                'name' => 'Canon',
                'slug' => 'canon',
                'country' => 'Japón',
                'website' => 'https://www.canon.com',
            ],
            [
                'name' => 'Sony',
                'slug' => 'sony',
                'country' => 'Japón',
                'website' => 'https://www.sony.com',
            ],
            [
                'name' => 'LG',
                'slug' => 'lg',
                'country' => 'Corea del Sur',
                'website' => 'https://www.lg.com',
            ],
            [
                'name' => 'TP-Link',
                'slug' => 'tp-link',
                'country' => 'China',
                'website' => 'https://www.tp-link.com',
            ],
        ];

        foreach ($brands as $brand) {
            EquipmentBrand::updateOrCreate(
                ['slug' => $brand['slug']],
                $brand
            );
        }

        $this->command?->info('✓ Marcas de equipos creadas/actualizadas');
    }

    private function createEquipment(): void
    {
        // Obtener tipos y marcas
        $laptopType    = EquipmentType::where('slug', 'computador-portatil')->first();
        $projectorType = EquipmentType::where('slug', 'proyector')->first();
        $tabletType    = EquipmentType::where('slug', 'tablet')->first();
        $cameraType    = EquipmentType::where('slug', 'camara-digital')->first();

        $hp      = EquipmentBrand::where('slug', 'hp')->first();
        $dell    = EquipmentBrand::where('slug', 'dell')->first();
        $lenovo  = EquipmentBrand::where('slug', 'lenovo')->first();
        $epson   = EquipmentBrand::where('slug', 'epson')->first();
        $samsung = EquipmentBrand::where('slug', 'samsung')->first();
        $canon   = EquipmentBrand::where('slug', 'canon')->first();

        $equipmentData = [
            // Laptops HP
            [
                'equipment_type_id' => $laptopType?->id,
                'equipment_brand_id' => $hp?->id,
                'name' => 'HP Pavilion 15',
                'model' => 'Pavilion 15-EG2000',
                'serial_number' => 'SN-HP-2023-001',
                'asset_code' => 'EQ-2023-0001',
                'purchase_date' => '2023-01-15',
                'purchase_cost' => 2800000,
                'supplier' => 'HP Colombia',
                'warranty_expiration_date' => '2026-01-15',
                'specifications' => [
                    'processor' => 'Intel Core i7-1255U',
                    'ram' => '16GB DDR4',
                    'storage' => '512GB SSD',
                    'screen' => '15.6" FHD',
                    'graphics' => 'Intel Iris Xe',
                ],
                'condition' => 'excellent',
                'status' => 'available',
                'requires_accessories' => ['Cargador', 'Mouse inalámbrico', 'Maletín'],
            ],
            [
                'equipment_type_id' => $laptopType?->id,
                'equipment_brand_id' => $hp?->id,
                'name' => 'HP ProBook 450',
                'model' => 'ProBook 450 G9',
                'serial_number' => 'SN-HP-2023-002',
                'asset_code' => 'EQ-2023-0002',
                'purchase_date' => '2023-02-20',
                'purchase_cost' => 3200000,
                'supplier' => 'HP Colombia',
                'warranty_expiration_date' => '2026-02-20',
                'specifications' => [
                    'processor' => 'Intel Core i7-1260P',
                    'ram' => '16GB DDR4',
                    'storage' => '1TB SSD',
                    'screen' => '15.6" FHD',
                ],
                'condition' => 'excellent',
                'status' => 'available',
                'requires_accessories' => ['Cargador', 'Docking station'],
            ],

            // Laptops Dell
            [
                'equipment_type_id' => $laptopType?->id,
                'equipment_brand_id' => $dell?->id,
                'name' => 'Dell Inspiron 15',
                'model' => 'Inspiron 15-3520',
                'serial_number' => 'SN-DELL-2023-001',
                'asset_code' => 'EQ-2023-0003',
                'purchase_date' => '2023-03-10',
                'purchase_cost' => 2500000,
                'supplier' => 'Dell Colombia',
                'warranty_expiration_date' => '2026-03-10',
                'specifications' => [
                    'processor' => 'Intel Core i5-1235U',
                    'ram' => '8GB DDR4',
                    'storage' => '512GB SSD',
                    'screen' => '15.6" FHD',
                ],
                'condition' => 'good',
                'status' => 'on_loan',
                'requires_accessories' => ['Cargador', 'Mouse'],
            ],

            // Laptops Lenovo
            [
                'equipment_type_id' => $laptopType?->id,
                'equipment_brand_id' => $lenovo?->id,
                'name' => 'Lenovo ThinkPad E14',
                'model' => 'ThinkPad E14 Gen 4',
                'serial_number' => 'SN-LENOVO-2023-001',
                'asset_code' => 'EQ-2023-0004',
                'purchase_date' => '2023-04-05',
                'purchase_cost' => 3000000,
                'supplier' => 'Lenovo Colombia',
                'warranty_expiration_date' => '2026-04-05',
                'specifications' => [
                    'processor' => 'Intel Core i7-1255U',
                    'ram' => '16GB DDR4',
                    'storage' => '512GB SSD',
                    'screen' => '14" FHD',
                ],
                'condition' => 'excellent',
                'status' => 'available',
                'requires_accessories' => ['Cargador', 'Mouse', 'Maletín'],
            ],

            // Proyectores Epson
            [
                'equipment_type_id' => $projectorType?->id,
                'equipment_brand_id' => $epson?->id,
                'name' => 'Epson PowerLite',
                'model' => 'PowerLite 2250U',
                'serial_number' => 'SN-EPSON-2023-001',
                'asset_code' => 'EQ-2023-0005',
                'purchase_date' => '2023-05-20',
                'purchase_cost' => 4500000,
                'supplier' => 'Epson Colombia',
                'warranty_expiration_date' => '2026-05-20',
                'specifications' => [
                    'resolution' => 'WUXGA (1920x1200)',
                    'brightness' => '5000 lumens',
                    'contrast' => '15000:1',
                    'connectivity' => 'HDMI, VGA, USB, WiFi',
                ],
                'condition' => 'excellent',
                'status' => 'available',
                'requires_accessories' => ['Cable HDMI', 'Control remoto', 'Cable de poder', 'Maletín'],
            ],
            [
                'equipment_type_id' => $projectorType?->id,
                'equipment_brand_id' => $epson?->id,
                'name' => 'Epson BrightLink',
                'model' => 'BrightLink 710Ui',
                'serial_number' => 'SN-EPSON-2023-002',
                'asset_code' => 'EQ-2023-0006',
                'purchase_date' => '2023-06-15',
                'purchase_cost' => 5200000,
                'supplier' => 'Epson Colombia',
                'warranty_expiration_date' => '2026-06-15',
                'specifications' => [
                    'resolution' => 'WUXGA (1920x1200)',
                    'brightness' => '4000 lumens',
                    'interactive' => 'Sí',
                    'connectivity' => 'HDMI, VGA, USB, WiFi',
                ],
                'condition' => 'excellent',
                'status' => 'maintenance',
                'requires_accessories' => ['Cable HDMI', 'Control remoto', 'Lápiz interactivo', 'Cable de poder'],
            ],

            // Tablets Samsung
            [
                'equipment_type_id' => $tabletType?->id,
                'equipment_brand_id' => $samsung?->id,
                'name' => 'Samsung Galaxy Tab S8',
                'model' => 'SM-X706B',
                'serial_number' => 'SN-SAMSUNG-2023-001',
                'asset_code' => 'EQ-2023-0007',
                'purchase_date' => '2023-07-10',
                'purchase_cost' => 2200000,
                'supplier' => 'Samsung Colombia',
                'warranty_expiration_date' => '2025-07-10',
                'specifications' => [
                    'screen' => '11" LTPS TFT',
                    'processor' => 'Snapdragon 8 Gen 1',
                    'ram' => '8GB',
                    'storage' => '128GB',
                    'os' => 'Android 12',
                ],
                'condition' => 'good',
                'status' => 'available',
                'requires_accessories' => ['Cargador', 'S Pen', 'Estuche protector'],
            ],

            // Cámaras Canon
            [
                'equipment_type_id' => $cameraType?->id,
                'equipment_brand_id' => $canon?->id,
                'name' => 'Canon EOS Rebel T7',
                'model' => 'EOS 2000D',
                'serial_number' => 'SN-CANON-2023-001',
                'asset_code' => 'EQ-2023-0008',
                'purchase_date' => '2023-08-05',
                'purchase_cost' => 1800000,
                'supplier' => 'Canon Colombia',
                'warranty_expiration_date' => '2025-08-05',
                'specifications' => [
                    'sensor' => 'APS-C CMOS 24.1MP',
                    'video' => 'Full HD 1080p',
                    'iso' => '100-6400',
                    'connectivity' => 'WiFi, NFC',
                ],
                'condition' => 'excellent',
                'status' => 'available',
                'requires_accessories' => ['Lente 18-55mm', 'Batería extra', 'Cargador', 'Tarjeta SD 64GB', 'Estuche'],
            ],
        ];

        foreach ($equipmentData as $data) {
            if (empty($data['asset_code'])) {
                continue;
            }

            Equipment::updateOrCreate(
                ['asset_code' => $data['asset_code']], // clave única
                $data
            );
        }

        $this->command?->info('✓ Equipos creados/actualizados: ' . count($equipmentData) . ' items');
    }
}
