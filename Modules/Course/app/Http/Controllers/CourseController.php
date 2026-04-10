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
use Modules\Course\Http\Requests\BulkStatusCourseRequest;
use Modules\Course\Http\Resources\CourseResource;
use Modules\Course\Models\Course;
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

        // Fetch old course to retrieve old thumbnail for cleanup
        $oldCourse = $this->repository->findOrFail($id);
        $oldThumbnail = $oldCourse->thumbnail;

        $course = DB::transaction(function () use ($id, $validated, $categoryIds) {
            $course = $this->repository->update($id, $validated);

            // Sync categories nếu được gửi lên
            if ($categoryIds !== null) {
                $this->repository->syncCategories($course->id, $categoryIds);
            }

            return $course;
        });

        // Cleanup old thumbnail file if it was explicitly modified or removed
        if (array_key_exists('thumbnail', $validated) && $validated['thumbnail'] !== $oldThumbnail) {
            $this->deleteThumbnailFile($oldThumbnail);
        }

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

    /**
     * Cập nhật trạng thái cho nhiều Courses.
     */
    public function bulkStatus(BulkStatusCourseRequest $request): JsonResponse
    {
        $count = $this->repository->actionMany($request->ids, $request->status === 1 ? 'publish' : 'unpublish');

        return $this->success(
            ['updated_count' => $count, 'updated_ids' => $request->ids],
            "Đã cập nhật {$count} khóa học thành công."
        );
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

        // Load sections theo trạng thái mua
        $sections = $course->sections()->where('status', 1)->with(['lessons' => function($q) {
            $q->where('status', 1);
        }])->get()->map(fn ($section) => [
            'id'      => $section->id,
            'title'   => $section->title,
            'order'   => $section->order,
            'lessons' => $section->lessons->map(fn ($lesson) => [
                'id'         => $lesson->id,
                'title'      => $lesson->title,
                'slug'       => $lesson->slug,
                'type'       => $lesson->type,
                'order'      => $lesson->order,
                'is_preview' => $lesson->is_preview,
                'duration'   => $lesson->duration,
            ])->values(),
        ])->values();

        return $this->success([
            'is_purchased' => $isPurchased,
            'sections'     => $sections,
        ], 'Lấy danh sách bài giảng thành công.');
    }

    /**
     * Client: Đăng ký khóa học miễn phí.
     */
    public function enrollFree(string $slug): JsonResponse
    {
        $course = $this->repository->findBySlug($slug, true);
        if (!$course) {
            return $this->error('Khóa học không tồn tại.', 404);
        }

        if ($course->price > 0) {
            return $this->error('Khóa học này không miễn phí.', 400);
        }

        $student = auth('api')->user();

        if (!$course->students()->where('student_id', $student->id)->exists()) {
            $course->students()->attach($student->id, ['enrolled_at' => now()]);
            
            // Cập nhật số lượng học viên (+1)
            $this->repository->incrementStudentCount($course->id);
        }

        return $this->success(null, 'Đăng ký thành công! Bạn đã có thể vào học.');
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

    /**
     * Public: Xem chi tiết bài học nếu là bài học thử (is_preview = 1).
     */
    public function publicPreviewLesson(string $courseSlug, string $lessonSlug): JsonResponse
    {
        $course = $this->repository->findBySlug($courseSlug, true);
        if (!$course) {
            return $this->error('Khóa học không tồn tại.', 404);
        }

        $lesson = \Modules\Lessons\Models\Lesson::where('course_id', $course->id)
            ->where('slug', $lessonSlug)
            ->where('status', 1)
            ->with(['video', 'document'])
            ->first();

        if (!$lesson) {
            return $this->error('Bài học không tồn tại.', 404);
        }

        if (!$lesson->is_preview) {
            return $this->error('Đây không phải bài học thử.', 403);
        }

        return $this->success([
            'id'           => $lesson->id,
            'title'        => $lesson->title,
            'type'         => $lesson->type,
            'video_url'    => $lesson->video ? $lesson->video->url : null,
            'document_url' => $lesson->document ? $lesson->document->url : null,
            'content'      => $lesson->content,
            'is_preview'   => $lesson->is_preview,
        ], 'Lấy bài học thử thành công.');
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
