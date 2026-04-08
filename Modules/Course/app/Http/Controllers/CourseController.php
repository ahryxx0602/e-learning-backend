<?php

namespace Modules\Course\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Modules\Course\Http\Requests\StoreCourseRequest;
use Modules\Course\Http\Requests\UpdateCourseRequest;
use Modules\Course\Http\Requests\BulkDeleteCourseRequest;
use Modules\Course\Http\Requests\BulkRestoreCourseRequest;
use Modules\Course\Http\Requests\BulkForceDeleteCourseRequest;
use Modules\Course\Http\Resources\CourseResource;
use Modules\Course\Repositories\CourseRepositoryInterface;

class CourseController extends Controller
{
    use ApiResponse;

    protected CourseRepositoryInterface $repository;

    public function __construct(CourseRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    // ── Admin CRUD ──

    /**
     * Danh sách Courses (có phân trang + filter).
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'search'      => 'nullable|string|max:100',
            'status'      => 'nullable|integer|in:0,1',
            'teacher_id'  => 'nullable|integer|exists:teachers,id',
            'category_id' => 'nullable|integer|exists:categories,id',
            'level'       => 'nullable|string|in:beginner,intermediate,advanced',
            'per_page'    => 'nullable|integer|min:1|max:100',
        ]);

        $perPage = (int) $request->query('per_page', 15);
        $filters = $request->only(['search', 'status', 'teacher_id', 'category_id', 'level']);

        $data = $this->repository->getFiltered($filters, $perPage);
        $data->setCollection(CourseResource::collection($data->getCollection())->collection);

        return $this->paginated($data);
    }

    /**
     * Tạo mới Course.
     */
    public function store(StoreCourseRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $categoryIds = $validated['category_ids'] ?? [];
        unset($validated['category_ids']);

        $course = DB::transaction(function () use ($validated, $categoryIds) {
            $course = $this->repository->create($validated);

            // Sync categories nếu có
            if (!empty($categoryIds)) {
                $this->repository->syncCategories($course->id, $categoryIds);
            }

            return $course;
        });

        $course->refresh();
        $course->load(['teacher', 'categories']);

        return $this->success(new CourseResource($course), 'Khóa học đã được tạo thành công.', 201);
    }

    /**
     * Chi tiết Course.
     */
    public function show(int $id): JsonResponse
    {
        $course = $this->repository->findOrFail($id, ['*'], ['teacher', 'categories']);

        return $this->success(new CourseResource($course));
    }

    /**
     * Cập nhật Course.
     */
    public function update(UpdateCourseRequest $request, int $id): JsonResponse
    {
        $validated = $request->validated();
        $categoryIds = $validated['category_ids'] ?? null;
        unset($validated['category_ids']);

        $course = DB::transaction(function () use ($id, $validated, $categoryIds) {
            $course = $this->repository->update($id, $validated);

            // Sync categories nếu được gửi lên
            if ($categoryIds !== null) {
                $this->repository->syncCategories($course->id, $categoryIds);
            }

            return $course;
        });

        $course->load(['teacher', 'categories']);

        return $this->success(new CourseResource($course), 'Khóa học đã được cập nhật thành công.');
    }

    /**
     * Xoá Course (soft delete).
     */
    public function destroy(int $id): JsonResponse
    {
        $this->repository->delete($id);

        return $this->success(null, 'Khóa học đã được xoá thành công.');
    }

    /**
     * Toggle trạng thái draft/published.
     */
    public function toggleStatus(int $id): JsonResponse
    {
        $course = $this->repository->toggleStatus($id);

        $statusText = $course->status === 1 ? 'xuất bản' : 'chuyển về nháp';

        return $this->success(new CourseResource($course), "Khóa học đã được {$statusText}.");
    }

    // ── Soft Delete Operations ──

    /**
     * Danh sách Courses đã bị soft-delete (thùng rác).
     */
    public function trashed(Request $request): JsonResponse
    {
        $request->validate([
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $perPage = (int) $request->query('per_page', 15);
        $data = $this->repository->paginateTrashed($perPage);
        $data->setCollection(CourseResource::collection($data->getCollection())->collection);

        return $this->paginated($data);
    }

    /**
     * Khôi phục một Course đã soft-delete.
     */
    public function restore(int $id): JsonResponse
    {
        $this->repository->restore($id);

        return $this->success(null, 'Khóa học đã được khôi phục thành công.');
    }

    /**
     * Xoá vĩnh viễn một Course.
     */
    public function forceDelete(int $id): JsonResponse
    {
        $course = $this->repository->findTrashed($id);

        // Xóa thumbnail trên storage nếu có
        $this->deleteThumbnailFile($course->thumbnail ?? null);

        // Detach categories pivot
        $course->categories()->detach();

        $course->forceDelete();

        return $this->success(null, 'Khóa học đã bị xoá vĩnh viễn.');
    }

    // ── Bulk Operations ──

    /**
     * Xoá nhiều Courses (soft delete).
     */
    public function bulkDelete(BulkDeleteCourseRequest $request): JsonResponse
    {
        $deleted = DB::transaction(function () use ($request) {
            return $this->repository->deleteMany($request->ids);
        });

        return $this->success(
            ['deleted_count' => $deleted, 'deleted_ids' => $request->ids],
            "Đã xoá {$deleted} khóa học thành công."
        );
    }

    /**
     * Khôi phục nhiều Courses đã soft-delete.
     */
    public function bulkRestore(BulkRestoreCourseRequest $request): JsonResponse
    {
        $restored = DB::transaction(function () use ($request) {
            return $this->repository->restoreMany($request->ids);
        });

        return $this->success(
            ['restored_count' => $restored, 'restored_ids' => $request->ids],
            "Đã khôi phục {$restored} khóa học thành công."
        );
    }

    /**
     * Xoá vĩnh viễn nhiều Courses.
     */
    public function bulkForceDelete(BulkForceDeleteCourseRequest $request): JsonResponse
    {
        $deleted = DB::transaction(function () use ($request) {
            $courses = $this->repository->findManyTrashed($request->ids);

            foreach ($courses as $course) {
                $this->deleteThumbnailFile($course->thumbnail ?? null);
                $course->categories()->detach();
                $course->forceDelete();
            }

            return $courses->count();
        });

        return $this->success(
            ['deleted_count' => $deleted, 'deleted_ids' => $request->ids],
            "Đã xoá vĩnh viễn {$deleted} khóa học."
        );
    }

    // ── Public API ──

    /**
     * Public: Danh sách khóa học đã published.
     */
    public function publicIndex(Request $request): JsonResponse
    {
        $request->validate([
            'search'      => 'nullable|string|max:100',
            'category_id' => 'nullable|integer|exists:categories,id',
            'level'       => 'nullable|string|in:beginner,intermediate,advanced',
            'per_page'    => 'nullable|integer|min:1|max:100',
        ]);

        $perPage = (int) $request->query('per_page', 15);
        $filters = $request->only(['search', 'category_id', 'level']);

        $data = $this->repository->getPublished($filters, $perPage);
        $data->setCollection(CourseResource::collection($data->getCollection())->collection);

        return $this->paginated($data);
    }

    /**
     * Public: Chi tiết khóa học theo slug.
     */
    public function publicShow(string $slug): JsonResponse
    {
        if (!preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug)) {
            return $this->error('Khóa học không tồn tại.', 404);
        }

        $course = $this->repository->findBySlug($slug, true);

        if (!$course) {
            return $this->error('Khóa học không tồn tại.', 404);
        }

        return $this->success(new CourseResource($course));
    }

    /**
     * Public: Danh sách bài giảng của khóa học (lock nếu chưa mua).
     */
    public function publicLessons(string $slug): JsonResponse
    {
        if (!preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug)) {
            return $this->error('Khóa học không tồn tại.', 404);
        }

        $course = $this->repository->findBySlug($slug, true);

        if (!$course) {
            return $this->error('Khóa học không tồn tại.', 404);
        }

        // Kiểm tra học viên đã mua chưa
        $isPurchased = false;
        if (auth('api')->check()) {
            $isPurchased = $course->students()->where('student_id', auth('api')->id())->exists();
        }

        // Load lessons theo trạng thái mua
        $lessonsQuery = $course->lessons()->where('status', 1);

        if (!$isPurchased) {
            $lessonsQuery->where('is_preview', true);
        }

        $lessons = $lessonsQuery->orderBy('order', 'asc')->get()->map(fn ($lesson) => [
            'id'         => $lesson->id,
            'title'      => $lesson->title,
            'slug'       => $lesson->slug,
            'type'       => $lesson->type,
            'order'      => $lesson->order,
            'is_preview' => $lesson->is_preview,
            'duration'   => $lesson->duration,
        ])->values();

        return $this->success([
            'is_purchased' => $isPurchased,
            'lessons'      => $lessons,
        ], 'Lấy danh sách bài giảng thành công.');
    }

    /**
     * Client: Danh sách khóa học đã mua (auth:api).
     */
    public function myCourses(Request $request): JsonResponse
    {
        $request->validate([
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $perPage = (int) $request->query('per_page', 15);
        $studentId = auth('api')->id();

        $courses = $this->repository->getByStudent($studentId, $perPage);

        $courses->setCollection(CourseResource::collection($courses->getCollection())->collection);

        return $this->paginated($courses);
    }

    // ── Private Helpers ──

    /**
     * Xóa file thumbnail khỏi storage.
     * Thumbnail URL dạng /storage/thumbnails/uuid.jpg → path = thumbnails/uuid.jpg
     */
    private function deleteThumbnailFile(?string $thumbnail): void
    {
        if (empty($thumbnail)) {
            return;
        }

        // Chuyển URL /storage/thumbnails/xxx.jpg → thumbnails/xxx.jpg
        $path = preg_replace('#^/storage/#', '', $thumbnail);

        if ($path && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }
}
