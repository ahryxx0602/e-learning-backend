<?php

namespace Tests\Feature\Admin;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Categories\Models\Category;
use Modules\Users\Models\User;
use Tests\TestCase;

class CategoryTest extends TestCase
{
    use RefreshDatabase;

    private string $baseUrl = '/api/v1/admin/categories';

    protected function setupAdmin()
    {
        $admin = User::forceCreate([
            'name' => 'Admin Test',
            'email' => 'admin_cat_test@test.com',
            'password' => 'password123',
        ]);

        $this->actingAs($admin, 'admin');

        return $admin;
    }

    public function test_categories_index_returns_success()
    {
        $this->setupAdmin();

        $response = $this->getJson($this->baseUrl);

        $response->assertStatus(200);
    }

    public function test_create_category_fails_without_required_fields()
    {
        $this->setupAdmin();

        $response = $this->postJson($this->baseUrl, []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    public function test_create_category_success_root()
    {
        $this->setupAdmin();

        $response = $this->postJson($this->baseUrl, [
            'name' => 'Kỹ năng mềm',
            'slug' => 'ky-nang-mem',
            'parent_id' => null,
            'status' => 1,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('categories', [
            'slug' => 'ky-nang-mem',
            'name' => 'Kỹ năng mềm',
        ]);
    }

    public function test_create_category_success_child()
    {
        $this->setupAdmin();

        $parent = Category::create([
            'name' => 'Kỹ năng mềm',
            'slug' => 'ky-nang-mem',
        ]);

        $response = $this->postJson($this->baseUrl, [
            'name' => 'Thuyết trình',
            'slug' => 'thuyet-trinh',
            'parent_id' => $parent->id,
            'status' => 1,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('categories', [
            'slug' => 'thuyet-trinh',
            'parent_id' => $parent->id,
        ]);
    }

    public function test_create_category_fails_with_duplicate_slug()
    {
        $this->setupAdmin();

        Category::create([
            'name' => 'Kỹ năng mềm',
            'slug' => 'ky-nang-mem',
        ]);

        $response = $this->postJson($this->baseUrl, [
            'name' => 'Kỹ năng mềm 2',
            'slug' => 'ky-nang-mem',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['slug']);
    }

    public function test_update_category_success()
    {
        $this->setupAdmin();

        $category = Category::create([
            'name' => 'Kỹ năng mềm',
            'slug' => 'ky-nang-mem',
        ]);

        $response = $this->putJson($this->baseUrl.'/'.$category->id, [
            'name' => 'Kỹ Năng Mềm (Updated)',
            'slug' => 'ky-nang-mem-updated',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'slug' => 'ky-nang-mem-updated',
            'name' => 'Kỹ Năng Mềm (Updated)',
        ]);
    }

    public function test_update_category_duplicate_slug()
    {
        $this->setupAdmin();

        Category::create([
            'name' => 'Cat 1',
            'slug' => 'cat-1',
        ]);

        $categoryToUpdate = Category::create([
            'name' => 'Cat 2',
            'slug' => 'cat-2',
        ]);

        $response = $this->putJson($this->baseUrl.'/'.$categoryToUpdate->id, [
            'name' => 'Cat 2 Updated',
            'slug' => 'cat-1',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['slug']);
    }

    public function test_delete_category_success()
    {
        $this->setupAdmin();

        $category = Category::create([
            'name' => 'Kỹ năng mềm',
            'slug' => 'ky-nang-mem',
        ]);

        $response = $this->deleteJson($this->baseUrl.'/'.$category->id);

        $response->assertStatus(200);
        $this->assertSoftDeleted('categories', [
            'id' => $category->id,
        ]);
    }

    public function test_toggle_category_status()
    {
        $this->setupAdmin();

        $category = Category::create([
            'name' => 'Kỹ năng mềm',
            'slug' => 'ky-nang-mem',
            'status' => 1,
        ]);

        $response = $this->patchJson($this->baseUrl.'/'.$category->id.'/toggle-status');

        $response->assertStatus(200);

        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'status' => 0,
        ]);
    }

    public function test_cannot_delete_category_with_children()
    {
        $this->setupAdmin();

        $parent = Category::create(['name' => 'Cha', 'slug' => 'cha']);
        Category::create(['name' => 'Con', 'slug' => 'con', 'parent_id' => $parent->id]);

        $response = $this->deleteJson($this->baseUrl.'/'.$parent->id);

        // Expect: chặn xóa nếu có con
        $response->assertStatus(400)
            ->assertJsonFragment(['success' => false]);

        $this->assertDatabaseHas('categories', ['id' => $parent->id, 'deleted_at' => null]);
    }

    public function test_categories_pagination()
    {
        $this->setupAdmin();

        // Tạo 20 categories
        for ($i = 1; $i <= 20; $i++) {
            Category::create([
                'name' => "Category $i",
                'slug' => "category-$i",
            ]);
        }

        $response = $this->getJson($this->baseUrl.'?per_page=10');

        $response->assertStatus(200)
            ->assertJsonPath('pagination.per_page', 10)
            ->assertJsonPath('pagination.total', 20);
    }

    public function test_categories_search()
    {
        $this->setupAdmin();

        Category::create(['name' => 'Lập trình PHP', 'slug' => 'lap-trinh-php']);
        Category::create(['name' => 'Thiết kế Web', 'slug' => 'thiet-ke-web']);
        Category::create(['name' => 'Ngoại ngữ', 'slug' => 'ngoai-ngu']);

        $response = $this->getJson($this->baseUrl.'?search=Lập trình');

        $response->assertStatus(200)
            ->assertJsonFragment(['name' => 'Lập trình PHP'])
            ->assertJsonMissing(['name' => 'Thiết kế Web']);
    }

    public function test_restore_category_fails_if_parent_is_soft_deleted()
    {
        $this->setupAdmin();

        $parent = Category::create(['name' => 'Parent', 'slug' => 'parent']);
        $child = Category::create(['name' => 'Child', 'slug' => 'child', 'parent_id' => $parent->id]);

        $parent->delete();
        $child->delete();

        $response = $this->postJson($this->baseUrl.'/'.$child->id.'/restore');

        $response->assertStatus(400)
            ->assertJsonFragment(['message' => 'Vui lòng khôi phục danh mục cha trước.']);
    }

    public function test_restore_category_success_if_parent_is_active()
    {
        $this->setupAdmin();

        $parent = Category::create(['name' => 'Parent', 'slug' => 'parent']);
        $child = Category::create(['name' => 'Child', 'slug' => 'child', 'parent_id' => $parent->id]);

        $child->delete();

        $response = $this->postJson($this->baseUrl.'/'.$child->id.'/restore');

        $response->assertStatus(200);
        $this->assertDatabaseHas('categories', ['id' => $child->id, 'deleted_at' => null]);
    }

    public function test_bulk_restore_skips_invalid_items()
    {
        $this->setupAdmin();

        $parentA = Category::create(['name' => 'Parent A', 'slug' => 'parent-a']);
        $childB = Category::create(['name' => 'Child B', 'slug' => 'child-b', 'parent_id' => $parentA->id]);
        $parentC = Category::create(['name' => 'Parent C', 'slug' => 'parent-c']);

        $parentA->delete();
        $childB->delete();
        $parentC->delete();

        // Bulk restore B and C. B should fail (as A is still trashed), C should succeed.
        $response = $this->postJson($this->baseUrl.'/bulk-restore', [
            'ids' => [$childB->id, $parentC->id],
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.restored_count', 1);

        $this->assertDatabaseHas('categories', ['id' => $parentC->id, 'deleted_at' => null]);
        $this->assertSoftDeleted('categories', ['id' => $childB->id]);
    }
}
