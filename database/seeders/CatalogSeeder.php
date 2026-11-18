<?php

namespace Database\Seeders;

use App\Enums\UnitType;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;

/**
 * Demo catalog of a neighbourhood grocery shop (local development only).
 */
class CatalogSeeder extends Seeder
{
    private const CATALOG = [
        ['Fruta y verdura', 'Producto fresco de temporada y de proximidad.', [
            ['Tomate de ensalada', 'FRU-001', 2.40, UnitType::Weight, true, 'Expositor fruta'],
            ['Manzana golden', 'FRU-002', 1.95, UnitType::Weight, true, 'Expositor fruta'],
            ['Plátano de Canarias', 'FRU-003', 2.25, UnitType::Weight, true, 'Expositor fruta'],
            ['Lechuga romana', 'FRU-004', 0.99, UnitType::Unit, true, 'Expositor verdura'],
            ['Patata para freír (saco 2 kg)', 'FRU-005', 2.80, UnitType::Unit, false, 'Almacén A'],
        ]],
        ['Carnicería', 'Carne fresca cortada al momento.', [
            ['Pechuga de pollo', 'CAR-001', 8.90, UnitType::Weight, true, 'Cámara 1'],
            ['Muslos de pollo', 'CAR-002', 4.95, UnitType::Weight, true, 'Cámara 1'],
            ['Carne picada mixta', 'CAR-003', 9.50, UnitType::Weight, true, 'Cámara 1'],
        ]],
        ['Lácteos y huevos', 'Leche, yogures, quesos frescos y huevos.', [
            ['Huevos camperos (docena)', 'LAC-001', 3.40, UnitType::Unit, true, 'Nevera 2'],
            ['Huevos camperos (media docena)', 'LAC-002', 1.85, UnitType::Unit, true, 'Nevera 2'],
            ['Leche entera 1 L', 'LAC-003', 1.05, UnitType::Unit, true, 'Nevera 2'],
            ['Yogur natural (pack 4)', 'LAC-004', 1.60, UnitType::Unit, true, 'Nevera 2'],
        ]],
        ['Panadería', 'Pan del día y bollería.', [
            ['Barra de pan', 'PAN-001', 0.95, UnitType::Unit, true, 'Mostrador'],
            ['Pan de payés', 'PAN-002', 3.20, UnitType::Unit, true, 'Mostrador'],
        ]],
        ['Despensa', 'Arroz, pasta, legumbres, aceite y conservas.', [
            ['Arroz bomba 1 kg', 'DES-001', 3.75, UnitType::Unit, false, 'Pasillo 3'],
            ['Aceite de oliva virgen extra 1 L', 'DES-002', 8.95, UnitType::Unit, false, 'Pasillo 3'],
            ['Garbanzos cocidos 400 g', 'DES-003', 1.10, UnitType::Unit, false, 'Pasillo 4'],
            ['Espaguetis 500 g', 'DES-004', 0.89, UnitType::Unit, false, 'Pasillo 4'],
        ]],
        ['Bebidas', 'Agua, zumos y refrescos.', [
            ['Agua mineral 1,5 L', 'BEB-001', 0.55, UnitType::Unit, false, 'Pasillo 6'],
            ['Zumo de naranja 1 L', 'BEB-002', 2.15, UnitType::Unit, true, 'Nevera 3'],
        ]],
    ];

    /**
     * Opening stock: ~25 % of each product goes to the online channel, as suggested
     * in the proposal. A couple of products start low/out of stock to show the alerts.
     */
    private function seedStock(Product $product): void
    {
        $stock = $product->stock()->firstOrCreate();

        if ($stock->on_hand > 0) {
            return; // never overwrite real counts
        }

        [$onHand, $minAlert] = match (true) {
            $product->sku === 'CAR-001' => [2, 3],         // below minimum
            $product->sku === 'PAN-002' => [0, 2],         // sold out
            $product->unit_type === UnitType::Weight => [20, 3],
            default => [40, 8],
        };

        $online = $product->unit_type === UnitType::Weight ? round($onHand * 0.3, 2) : floor($onHand * 0.25);

        $stock->update([
            'on_hand' => $onHand,
            'online_qty' => $online,
            'store_qty' => $onHand - $online,
            'min_alert' => $minAlert,
        ]);
    }

    public function run(): void
    {
        foreach (self::CATALOG as $order => [$name, $description, $products]) {
            $category = Category::updateOrCreate(['name' => $name], ['description' => $description, 'sort_order' => $order + 1]);

            foreach ($products as [$product, $sku, $price, $unit, $perishable, $location]) {
                $model = Product::updateOrCreate(['sku' => $sku], [
                    'category_id' => $category->id,
                    'name' => $product,
                    'price' => $price,
                    'unit_type' => $unit,
                    'is_perishable' => $perishable,
                    'location' => $location,
                ]);

                $this->seedStock($model);
            }
        }
    }
}
