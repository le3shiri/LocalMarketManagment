<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_a_category_and_slug_is_generated(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->post('/admin/categories', ['name' => 'Fruta y verdura', 'sort_order' => 1])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('categories', ['name' => 'Fruta y verdura', 'slug' => 'fruta-y-verdura']);
    }

    public function test_category_names_are_unique(): void
    {
        Category::factory()->create(['name' => 'Bebidas']);

        $this->actingAs(User::factory()->admin()->create())
            ->post('/admin/categories', ['name' => 'Bebidas'])
            ->assertSessionHasErrors('name');
    }

    public function test_categories_are_listed_in_their_sort_order(): void
    {
        Category::factory()->create(['name' => 'Segunda', 'sort_order' => 2]);
        Category::factory()->create(['name' => 'Primera', 'sort_order' => 1]);

        $this->actingAs(User::factory()->admin()->create())
            ->get('/admin/categories')
            ->assertSeeInOrder(['Primera', 'Segunda']);
    }

    public function test_admin_can_rename_a_category(): void
    {
        $category = Category::factory()->create(['name' => 'Lacteos']);

        $this->actingAs(User::factory()->admin()->create())
            ->put("/admin/categories/{$category->slug}", ['name' => 'Lácteos y huevos'])
            ->assertRedirect(route('admin.categories.index'));

        $this->assertSame('lacteos-y-huevos', $category->refresh()->slug);
    }

    public function test_admin_can_delete_an_empty_category(): void
    {
        $category = Category::factory()->create();

        $this->actingAs(User::factory()->admin()->create())
            ->delete("/admin/categories/{$category->slug}");

        $this->assertModelMissing($category);
    }

    public function test_staff_without_permission_can_not_manage_categories(): void
    {
        $this->actingAs(User::factory()->withRole(Role::CASHIER)->create())
            ->get('/admin/categories')
            ->assertForbidden();
    }
}
