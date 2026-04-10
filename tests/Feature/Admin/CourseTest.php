<?php

namespace Tests\Feature\Admin;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Course\Models\Course;
use Modules\Categories\Models\Category;
use Modules\Teachers\Models\Teachers;
use Modules\Users\Models\User;
use Tests\TestCase;

class CourseTest extends TestCase
{
    use RefreshDatabase;

    private string $baseUrl = '/api/v1/admin/courses';

    protected function setupAdmin()
    {
        $admin = User::forceCreate([
            'name' => 'Admin Test',
            'email' => 'admin_course_test@test.com',
            'password' => 'password123',
        ]);
        
        $this->actingAs($admin, 'admin');
        return $admin;
    }

    private function createDependencies()
    {
        $teacher = Teachers::create([
            'name' => 'Teacher Test',
            'slug' => 'teacher-test',
        ]);

        $category = Category::create([
            'name' => 'Category Test',
            'slug' => 'category-test',
        ]);

        return [$teacher, $category];
    }

    public function test_courses_index_returns_success()
    {
        $this->setupAdmin();

        $response = $this->getJson($this->baseUrl);

        $response->assertStatus(200);
    }

    public function test_create_course_fails_without_required_fields()
    {
        $this->setupAdmin();

        $response = $this->postJson($this->baseUrl, []);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['name', 'slug', 'teacher_id']);
    }

    public function test_create_course_success()
    {
        $this->setupAdmin();
        [$teacher, $category] = $this->createDependencies();

        $response = $this->postJson($this->baseUrl, [
            'name' => 'Khóa học Laravel',
            'slug' => 'khoa-hoc-laravel',
            'teacher_id' => $teacher->id,
            'category_id' => $category->id,
            'level' => 'beginner',
            'price' => 199000,
            'status' => 1
        ]);

        $response->assertStatus(201)
                 ->assertJsonPath('success', true);

        $this->assertDatabaseHas('courses', [
            'slug' => 'khoa-hoc-laravel',
            'teacher_id' => $teacher->id
        ]);
    }

    public function test_create_course_fails_with_duplicate_slug()
    {
        $this->setupAdmin();
        [$teacher, $category] = $this->createDependencies();

        Course::create([
            'name' => 'Course 1',
            'slug' => 'course-1',
            'teacher_id' => $teacher->id,
        ]);

        $response = $this->postJson($this->baseUrl, [
            'name' => 'Course 2',
            'slug' => 'course-1',
            'teacher_id' => $teacher->id,
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['slug']);
    }

    public function test_update_course_success()
    {
        $this->setupAdmin();
        [$teacher, $category] = $this->createDependencies();

        $course = Course::create([
            'name' => 'Course 1',
            'slug' => 'course-1',
            'teacher_id' => $teacher->id,
        ]);

        $response = $this->putJson($this->baseUrl . '/' . $course->id, [
            'name' => 'Course Updated',
            'slug' => 'course-updated',
            'teacher_id' => $teacher->id,
            'price' => 299000
        ]);

        $response->assertStatus(200)
                 ->assertJsonPath('success', true);

        $this->assertDatabaseHas('courses', [
            'id' => $course->id,
            'slug' => 'course-updated'
        ]);
    }

    public function test_delete_course_success()
    {
        $this->setupAdmin();
        [$teacher, $category] = $this->createDependencies();

        $course = Course::create([
            'name' => 'Course 1',
            'slug' => 'course-1',
            'teacher_id' => $teacher->id,
        ]);

        $response = $this->deleteJson($this->baseUrl . '/' . $course->id);

        $response->assertStatus(200);
        $this->assertSoftDeleted('courses', [
            'id' => $course->id
        ]);
    }

    public function test_toggle_course_status()
    {
        $this->setupAdmin();
        [$teacher, $category] = $this->createDependencies();

        $course = Course::create([
            'name' => 'Course 1',
            'slug' => 'course-1',
            'teacher_id' => $teacher->id,
            'status' => 0
        ]);

        $response = $this->patchJson($this->baseUrl . '/' . $course->id . '/toggle-status');

        $response->assertStatus(200);
        $this->assertDatabaseHas('courses', [
            'id' => $course->id,
            'status' => 1
        ]);
    }

    public function test_courses_trashed_returns_success()
    {
        $this->setupAdmin();

        $response = $this->getJson($this->baseUrl . '/trashed');

        $response->assertStatus(200);
    }

    public function test_create_course_fails_if_sale_price_greater_than_price()
    {
        $this->setupAdmin();
        [$teacher, $category] = $this->createDependencies();

        $response = $this->postJson($this->baseUrl, [
            'name'       => 'Course Sale Test',
            'slug'       => 'course-sale-test',
            'teacher_id' => $teacher->id,
            'price'      => 100000,
            'sale_price' => 200000, // Invalid: larger than price
            'level'      => 'beginner',
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['sale_price']);
    }

    public function test_restore_course_success()
    {
        $this->setupAdmin();
        [$teacher, $category] = $this->createDependencies();

        $course = Course::create([
            'name'       => 'Course to Restore',
            'slug'       => 'course-to-restore',
            'teacher_id' => $teacher->id,
        ]);
        $course->delete(); // Soft delete

        $response = $this->postJson($this->baseUrl . '/' . $course->id . '/restore');

        $response->assertStatus(200);
        $this->assertDatabaseHas('courses', [
            'id'         => $course->id,
            'deleted_at' => null
        ]);
    }

    public function test_force_delete_course_success()
    {
        $this->setupAdmin();
        [$teacher, $category] = $this->createDependencies();

        $course = Course::create([
            'name'       => 'Course to Force Delete',
            'slug'       => 'course-to-force-delete',
            'teacher_id' => $teacher->id,
        ]);
        $course->delete();

        $response = $this->deleteJson($this->baseUrl . '/' . $course->id . '/force-delete');

        $response->assertStatus(200);
        $this->assertDatabaseMissing('courses', [
            'id' => $course->id
        ]);
    }

    public function test_bulk_status_courses_success()
    {
        $this->setupAdmin();
        [$teacher, $category] = $this->createDependencies();

        $course1 = Course::create(['name' => 'C1', 'slug' => 'c1', 'teacher_id' => $teacher->id, 'status' => 0]);
        $course2 = Course::create(['name' => 'C2', 'slug' => 'c2', 'teacher_id' => $teacher->id, 'status' => 0]);

        $response = $this->patchJson($this->baseUrl . '/bulk-status', [
            'ids'    => [$course1->id, $course2->id],
            'status' => 1
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('courses', ['id' => $course1->id, 'status' => 1]);
        $this->assertDatabaseHas('courses', ['id' => $course2->id, 'status' => 1]);
    }

    public function test_bulk_delete_courses_success()
    {
        $this->setupAdmin();
        [$teacher, $category] = $this->createDependencies();

        $course1 = Course::create(['name' => 'C1', 'slug' => 'c1', 'teacher_id' => $teacher->id]);
        $course2 = Course::create(['name' => 'C2', 'slug' => 'c2', 'teacher_id' => $teacher->id]);

        $response = $this->deleteJson($this->baseUrl . '/bulk-delete', [
            'ids' => [$course1->id, $course2->id]
        ]);

        $response->assertStatus(200);
        $this->assertSoftDeleted('courses', ['id' => $course1->id]);
        $this->assertSoftDeleted('courses', ['id' => $course2->id]);
    }

    public function test_courses_pagination()
    {
        $this->setupAdmin();
        [$teacher, $category] = $this->createDependencies();
        
        for ($i = 1; $i <= 20; $i++) {
            Course::create([
                'name'       => "Course $i",
                'slug'       => "course-$i",
                'teacher_id' => $teacher->id,
            ]);
        }

        $response = $this->getJson($this->baseUrl . '?per_page=10');

        $response->assertStatus(200)
                 ->assertJsonPath('pagination.per_page', 10)
                 ->assertJsonPath('pagination.total', 20);
    }

    public function test_courses_search_and_filter()
    {
        $this->setupAdmin();
        [$teacher, $category] = $this->createDependencies();
        
        // Tạo các khóa học mẫu
        Course::create(['name' => 'Laravel Basic', 'slug' => 'laravel-basic', 'teacher_id' => $teacher->id, 'status' => 1]);
        Course::create(['name' => 'React Advance', 'slug' => 'react-advance', 'teacher_id' => $teacher->id, 'status' => 0]);
        
        // 1. Test Search
        $response = $this->getJson($this->baseUrl . '?search=Laravel');
        $response->assertStatus(200)
                 ->assertJsonFragment(['name' => 'Laravel Basic'])
                 ->assertJsonMissing(['name' => 'React Advance']);

        // 2. Test Filter status
        $response = $this->getJson($this->baseUrl . '?status=0');
        $response->assertStatus(200)
                 ->assertJsonFragment(['name' => 'React Advance'])
                 ->assertJsonMissing(['name' => 'Laravel Basic']);
    }

    public function test_bulk_restore_courses_success()
    {
        $this->setupAdmin();
        [$teacher, $category] = $this->createDependencies();

        $c1 = Course::create(['name' => 'C1', 'slug' => 'c1', 'teacher_id' => $teacher->id]);
        $c2 = Course::create(['name' => 'C2', 'slug' => 'c2', 'teacher_id' => $teacher->id]);
        $c1->delete();
        $c2->delete();

        $response = $this->postJson($this->baseUrl . '/bulk-restore', [
            'ids' => [$c1->id, $c2->id]
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('courses', ['id' => $c1->id, 'deleted_at' => null]);
        $this->assertDatabaseHas('courses', ['id' => $c2->id, 'deleted_at' => null]);
    }

    public function test_bulk_force_delete_courses_success()
    {
        $this->setupAdmin();
        [$teacher, $category] = $this->createDependencies();

        $c1 = Course::create(['name' => 'C1', 'slug' => 'c1', 'teacher_id' => $teacher->id]);
        $c1->delete();

        $response = $this->deleteJson($this->baseUrl . '/bulk-force-delete', [
            'ids' => [$c1->id]
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseMissing('courses', ['id' => $c1->id]);
    }
}
