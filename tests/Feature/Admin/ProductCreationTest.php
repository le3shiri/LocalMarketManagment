<?php

namespace Tests\Feature\Admin;

use App\Enums\UnitType;
use App\Models\Category;
use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProductCreationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->admin()->create();
        $this->category = Category::factory()->create(['name' => 'Fruta y verdura']);
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'category_id' => $this->category->id,
            'name' => 'Tomate de ensalada',
            'sku' => 'fru-001',
            'description' => 'Tomate de proximidad.',
            'price' => '2,40',
            'unit_type' => UnitType::Weight->value,
            'is_perishable' => '1',
            'location' => 'Pasillo 1',
        ], $overrides);
    }

    public function test_admin_can_create_a_weighted_perishable_product_with_image(): void
    {
        Storage::fake('public');

        $this->actingAs($this->admin)
            ->post('/admin/products', $this->payload(['image' => UploadedFile::fake()->image('tomate.jpg')]))
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('admin.products.index'));

        $product = Product::firstWhere('sku', 'FRU-001');
        $this->assertSame('2.40', $product->price, 'Decimal commas are accepted');
        $this->assertSame(UnitType::Weight, $product->unit_type);
        $this->assertTrue($product->is_perishable);
        $this->assertSame('tomate-de-ensalada', $product->slug);
        Storage::disk('public')->assertExists($product->image_path);
    }

    public function test_sku_must_be_unique(): void
    {
        Product::factory()->create(['sku' => 'FRU-001']);

        $this->actingAs($this->admin)->post('/admin/products', $this->payload())->assertSessionHasErrors('sku');
    }

    public function test_price_must_be_positive_with_two_decimals(): void
    {
        $this->actingAs($this->admin)->post('/admin/products', $this->payload(['price' => '0']))->assertSessionHasErrors('price');
        $this->actingAs($this->admin)->post('/admin/products', $this->payload(['price' => '1.999']))->assertSessionHasErrors('price');
    }

    public function test_products_with_the_same_name_get_distinct_slugs(): void
    {
        $a = Product::factory()->create(['name' => 'Pan de payés']);
        $b = Product::factory()->create(['name' => 'Pan de payés']);

        $this->assertNotSame($a->slug, $b->slug);
    }

    public function test_product_list_shows_price_per_unit_type(): void
    {
        Product::factory()->byWeight()->create(['name' => 'Manzana golden', 'price' => 1.95]);

        $this->actingAs($this->admin)->get('/admin/products')
            ->assertOk()
            ->assertSee('Manzana golden')
            ->assertSee('/ kg');
    }

    public function test_a_category_with_products_can_not_be_deleted(): void
    {
        Product::factory()->for($this->category)->create();

        $this->actingAs($this->admin)
            ->delete("/admin/categories/{$this->category->slug}")
            ->assertSessionHas('error');

        $this->assertModelExists($this->category);
    }

    public function test_warehouse_staff_can_not_create_products(): void
    {
        $this->actingAs(User::factory()->withRole(Role::WAREHOUSE)->create())
            ->post('/admin/products', $this->payload())
            ->assertForbidden();
    }
}
