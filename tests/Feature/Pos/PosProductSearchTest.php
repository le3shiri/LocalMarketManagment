<?php

namespace Tests\Feature\Pos;

use App\Livewire\Pos\Terminal;
use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use App\Rules\Ean13;
use Database\Seeders\CatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PosProductSearchTest extends TestCase
{
    use RefreshDatabase;

    private User $cashier;

    private const MILK_EAN = '8410000000016'; // valid check digit

    protected function setUp(): void
    {
        parent::setUp();
        $this->cashier = User::factory()->withRole(Role::CASHIER)->create();
    }

    private function terminal()
    {
        return Livewire::actingAs($this->cashier)->test(Terminal::class);
    }

    public function test_ean13_check_digit_is_validated(): void
    {
        $this->assertTrue(Ean13::isValid(self::MILK_EAN));
        $this->assertTrue(Ean13::isValid('4006381333931'));
        $this->assertFalse(Ean13::isValid('4006381333932'));
        $this->assertFalse(Ean13::isValid('12345'));
    }

    public function test_admin_can_store_a_barcode_and_invalid_ones_are_rejected(): void
    {
        $product = Product::factory()->create();
        $admin = User::factory()->admin()->create();
        $payload = ['category_id' => $product->category_id, 'name' => $product->name, 'sku' => $product->sku, 'price' => '1.05', 'unit_type' => 'unit'];

        $this->actingAs($admin)->put("/admin/products/{$product->slug}", $payload + ['barcode' => '841 0000 000 016'])->assertSessionHasNoErrors();
        $this->assertSame(self::MILK_EAN, $product->refresh()->barcode);

        $this->actingAs($admin)->put("/admin/products/{$product->slug}", $payload + ['barcode' => '4006381333932'])->assertSessionHasErrors('barcode');
    }

    public function test_cashier_opens_the_terminal(): void
    {
        $this->actingAs($this->cashier)->get('/caja')->assertOk()->assertSee('Escanea el código de barras');
    }

    public function test_scanning_a_barcode_adds_the_product_directly(): void
    {
        $milk = Product::factory()->create(['name' => 'Leche entera 1 L', 'barcode' => self::MILK_EAN]);

        $this->terminal()
            ->set('search', self::MILK_EAN)
            ->call('scan')
            ->set('search', self::MILK_EAN)
            ->call('scan')
            ->assertSet('lines', [$milk->id => 2.0])
            ->assertSet('search', '')
            ->assertSee('Leche entera 1 L');
    }

    public function test_searching_by_name_lists_matches_to_click(): void
    {
        $bread = Product::factory()->create(['name' => 'Pan de payés']);
        Product::factory()->create(['name' => 'Arroz bomba']);

        $this->terminal()
            ->set('search', 'pan')
            ->assertSee('Pan de payés')
            ->assertDontSee('Arroz bomba')
            ->call('add', $bread->id)
            ->assertSet('lines', [$bread->id => 1.0]);
    }

    public function test_sku_and_single_name_match_also_work_with_enter(): void
    {
        $rice = Product::factory()->create(['name' => 'Arroz bomba', 'sku' => 'DES-001']);

        $this->terminal()->set('search', 'des-001')->call('scan')->assertSet('lines', [$rice->id => 1.0]);
        $this->terminal()->set('search', 'arroz')->call('scan')->assertSet('lines', [$rice->id => 1.0]);
    }

    public function test_unknown_codes_and_withdrawn_products_show_a_message(): void
    {
        Product::factory()->inactive()->create(['barcode' => self::MILK_EAN]);

        $this->terminal()
            ->set('search', self::MILK_EAN)
            ->call('scan')
            ->assertSet('lines', [])
            ->assertSee('No se ha encontrado ningún producto');
    }

    public function test_demo_catalog_barcodes_are_valid_and_unique(): void
    {
        $this->seed(CatalogSeeder::class);
        $codes = Product::whereNotNull('barcode')->pluck('barcode');

        $this->assertGreaterThan(10, $codes->count());
        $this->assertSame($codes->count(), $codes->unique()->count());
        $codes->each(fn ($code) => $this->assertTrue(Ean13::isValid($code), $code));
    }

    public function test_customers_and_warehouse_staff_can_not_use_the_till(): void
    {
        $this->actingAs(User::factory()->create())->get('/caja')->assertForbidden();
        $this->actingAs(User::factory()->withRole(Role::WAREHOUSE)->create())->get('/caja')->assertForbidden();
    }
}
