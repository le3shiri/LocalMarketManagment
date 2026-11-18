<?php

namespace Tests\Feature\Admin;

use App\Enums\UnitType;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProductEditTest extends TestCase
{
    use RefreshDatabase;

    private function payload(Product $product, array $overrides = []): array
    {
        return array_merge([
            'category_id' => $product->category_id,
            'name' => $product->name,
            'sku' => $product->sku,
            'price' => '3.10',
            'unit_type' => UnitType::Unit->value,
        ], $overrides);
    }

    public function test_edit_form_is_prefilled(): void
    {
        $product = Product::factory()->create(['name' => 'Leche entera']);

        $this->actingAs(User::factory()->admin()->create())
            ->get("/admin/products/{$product->slug}/edit")
            ->assertOk()
            ->assertSee('Leche entera');
    }

    public function test_admin_can_update_price_and_information(): void
    {
        $product = Product::factory()->create(['price' => 2.00]);

        $this->actingAs(User::factory()->admin()->create())
            ->put("/admin/products/{$product->slug}", $this->payload($product, ['name' => 'Leche semidesnatada', 'price' => '1,15']))
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('admin.products.index'));

        $product->refresh();
        $this->assertSame('1.15', $product->price);
        $this->assertSame('leche-semidesnatada', $product->slug);
    }

    public function test_keeping_the_same_sku_is_allowed(): void
    {
        $product = Product::factory()->create(['sku' => 'LAC-010']);

        $this->actingAs(User::factory()->admin()->create())
            ->put("/admin/products/{$product->slug}", $this->payload($product))
            ->assertSessionHasNoErrors();
    }

    public function test_replacing_the_image_deletes_the_previous_file(): void
    {
        Storage::fake('public');
        $old = UploadedFile::fake()->image('old.jpg')->store('products', 'public');
        $product = Product::factory()->create(['image_path' => $old]);

        $this->actingAs(User::factory()->admin()->create())
            ->put("/admin/products/{$product->slug}", $this->payload($product, ['image' => UploadedFile::fake()->image('new.png')]));

        Storage::disk('public')->assertMissing($old);
        Storage::disk('public')->assertExists($product->refresh()->image_path);
    }

    public function test_customers_can_not_edit_products(): void
    {
        $product = Product::factory()->create();

        $this->actingAs(User::factory()->create())
            ->put("/admin/products/{$product->slug}", $this->payload($product))
            ->assertForbidden();
    }
}
