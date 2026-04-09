<?php

namespace Modules\Lessons\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Course\Models\Course;
use Modules\Lessons\Models\Lesson;
use Modules\Lessons\Models\LessonProgress;
use Modules\Lessons\Models\Section;
use Illuminate\Support\Str;
use Modules\Lessons\Http\Requests\StoreLessonRequest;
use Modules\Lessons\Http\Requests\UpdateLessonRequest;
use Modules\Lessons\Repositories\LessonRepositoryInterface;

class LessonController extends Controller
{
    use ApiResponse;

    protected LessonRepositoryInterface $repository;

    public function __construct(LessonRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    // ── Admin CRUD ──

    /**
     * Danh sách bài giảng của một khóa học (có phân trang + filter).
     */
    public function index(Request $request, int $course_id): JsonResponse
    {
        $request->validate([
            'status'   => 'nullable|in:0,1',
            'type'     => 'nullable|in:video,document,text',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        // Kiểm tra course tồn tại — trả 404 rõ ràng thay vì empty list
        Course::findOrFail($course_id);

        $perPage = (int) $request->query('per_page', 15);
        $filters = $request->only(['status', 'type']);

        $data = $this->repository->getByCourse($course_id, $filters, $perPage);

        return $this->paginated($data, 'Lấy danh sách bài giảng thành công.');
    }

    /**
     * Tạo mới bài giảng cho khóa học.
     */
    public function store(StoreLessonRequest $request, int $course_id): JsonResponse
    {
        // Kiểm tra course tồn tại — tự throw 404 nếu không có
        Course::findOrFail($course_id);

        $validated = $request->validated();
        $validated['course_id'] = $course_id;
        $validated['slug'] = Str::slug($validated['title']) . '-' . uniqid();

        // Validate section thuộc đúng course này (nếu có truyền section_id)
        if (!empty($validated['section_id'])) {
            $sectionExists = Section::where('id', $validated['section_id'])
                ->where('course_id', $course_id)
                ->exists();
            if (!$sectionExists) {
                return $this->error('Chương không thuộc khóa học này.', 422);
            }
        }

        // Nếu không truyền order → tự đặt = số lessons hiện tại của section (hoặc course nếu không có section)
        if (!isset($validated['order'])) {
            $orderQuery = Lesson::where('course_id', $course_id);
            if (!empty($validated['section_id'])) {
                $orderQuery->where('section_id', $validated['section_id']);
            }
            $validated['order'] = $orderQuery->count();
        }

        $lesson = $this->repository->create($validated);

        // Cập nhật counter total_lessons trên Course (cross-module)
        Course::where('id', $course_id)->increment('total_lessons');

        return $this->success($lesson, 'Tạo bài giảng thành công.', 201);
    }

    public function show(int $id): JsonResponse
    {
        $lesson = $this->repository->findOrFail($id, ['*'], ['video', 'document']);

        return $this->success($lesson, 'Lấy chi tiết bài giảng thành công.');
    }

    /**
     * Cập nhật bài giảng.
     */
    public function update(UpdateLessonRequest $request, int $id): JsonResponse
    {
        $lesson = $this->repository->update($id, $request->validated());

        return $this->success($lesson, 'Cập nhật bài giảng thành công.');
    }

    /**
     * Xóa bài giảng (soft delete).
     */
    public function destroy(int $id): JsonResponse
    {
        $lesson = $this->repository->findOrFail($id);
        $this->repository->delete($id);

        // Giảm counter total_lessons trên Course (cross-module)
        Course::where('id', $lesson->course_id)->decrement('total_lessons');

        return $this->success(null, 'Xóa bài giảng thành công.');
    }

    /**
     * Toggle trạng thái draft/published (0 ↔ 1).
     */
    public function toggleStatus(int $id): JsonResponse
    {
        $lesson = $this->repository->toggleStatus($id);

        return $this->success(
            ['id' => $lesson->id, 'status' => $lesson->status],
            'Cập nhật trạng thái thành công.'
        );
    }

    // ── Soft Delete Operations ──

    /**
     * Danh sách bài giảng đã bị soft-delete (thùng rác).
     */
    public function trashed(Request $request): JsonResponse
    {
        $request->validate([
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $perPage = (int) $request->query('per_page', 15);
        $data = $this->repository->paginateTrashed($perPage);

        return $this->paginated($data, 'Lấy danh sách bài giảng đã xóa thành công.');
    }

    /**
     * Khôi phục bài giảng đã soft-delete.
     */
    public function restore(int $id): JsonResponse
    {
        // Dùng withTrashed để tìm lesson đã bị xóa mềm
        $lesson = Lesson::withTrashed()->findOrFail($id);
        $this->repository->restore($id);

        // Tăng lại counter total_lessons trên Course (cross-module)
        Course::where('id', $lesson->course_id)->increment('total_lessons');

        return $this->success(null, 'Khôi phục bài giảng thành công.');
    }

    /**
     * Xóa vĩnh viễn bài giảng.
     */
    public function forceDelete(int $id): JsonResponse
    {
        $this->repository->forceDeleteById($id);

        return $this->success(null, 'Xóa vĩnh viễn bài giảng thành công.');
    }

    // ── Reorder ──

    /**
     * Cập nhật thứ tự bài giảng hàng loạt.
     */
    public function reorder(Request $request): JsonResponse
    {
        $request->validate([
            'orders'          => 'required|array',
            'orders.*.id'     => 'required|integer|exists:lessons,id',
            'orders.*.order'  => 'required|integer|min:0',
        ]);

        // Đảm bảo tất cả lesson trong array thuộc cùng 1 course
        $ids = collect($request->orders)->pluck('id')->toArray();
        $courseIds = Lesson::whereIn('id', $ids)->distinct()->pluck('course_id');
        if ($courseIds->count() > 1) {
            return $this->error('Không thể sắp xếp bài giảng của nhiều khóa học cùng lúc.', 422);
        }

        $this->repository->reorder($request->orders);

        return $this->success(null, 'Sắp xếp bài giảng thành công.');
    }

    // ── Bulk Actions ──

    /**
     * Hành động hàng loạt: cập nhật trạng thái hoặc phân chương (assign-section).
     */
    public function bulkAction(Request $request): JsonResponse
    {
        $request->validate([
            'ids'        => 'required|array|min:1',
            'ids.*'      => 'integer|exists:lessons,id',
            'action'     => 'required|string|in:publish,unpublish,activate,deactivate,assign-section',
            'section_id' => 'nullable|integer|exists:sections,id',
        ]);

        // ── Phân chương hàng loạt ──
        if ($request->action === 'assign-section') {
            $sectionId = $request->section_id; // null = bỏ phân chương

            // Validate: tất cả lessons phải thuộc cùng 1 course
            $courseIds = Lesson::whereIn('id', $request->ids)->distinct()->pluck('course_id');
            if ($courseIds->count() > 1) {
                return $this->error('Các bài giảng phải thuộc cùng một khóa học.', 422);
            }

            // Validate: section phải thuộc đúng course đó (nếu section_id != null)
            if ($sectionId) {
                $courseId = $courseIds->first();
                $sectionBelongsToCourse = Section::where('id', $sectionId)
                    ->where('course_id', $courseId)
                    ->exists();
                if (!$sectionBelongsToCourse) {
                    return $this->error('Chương không thuộc khóa học này.', 422);
                }
            }

            $count = Lesson::whereIn('id', $request->ids)
                ->update(['section_id' => $sectionId]);

            $message = $sectionId
                ? "Đã gán {$count} bài giảng vào chương thành công."
                : "Đã bỏ phân chương {$count} bài giảng thành công.";

            return $this->success(null, $message);
        }

        // ── Các action trạng thái (activate/deactivate/publish/unpublish) ──
        $count = $this->repository->actionMany($request->ids, $request->action);

        return $this->success(null, "Cập nhật trạng thái hàng loạt {$count} bài giảng thành công.");
    }

    /**
     * Soft delete hàng loạt bài giảng.
     */
    public function bulkDelete(Request $request): JsonResponse
    {
        $request->validate([
            'ids'   => 'required|array|min:1',
            'ids.*' => 'integer|exists:lessons,id',
        ]);

        $lessons = Lesson::whereIn('id', $request->ids)->get();
        $count = $this->repository->deleteMany($request->ids);

        // Giảm counter total_lessons trên Course (cross-module)
        foreach ($lessons->groupBy('course_id') as $courseId => $group) {
            Course::where('id', $courseId)->decrement('total_lessons', $group->count());
        }

        return $this->success(null, "Xóa hàng loạt {$count} bài giảng thành công.");
    }

    /**
     * Khôi phục hàng loạt bài giảng đã xóa.
     */
    public function bulkRestore(Request $request): JsonResponse
    {
        $request->validate([
            'ids'   => 'required|array|min:1',
            'ids.*' => 'integer',
        ]);

        $lessons = Lesson::withTrashed()->whereIn('id', $request->ids)->get();
        $count = $this->repository->restoreMany($request->ids);

        // Tăng lại counter total_lessons trên Course (cross-module)
        foreach ($lessons->groupBy('course_id') as $courseId => $group) {
            Course::where('id', $courseId)->increment('total_lessons', $group->count());
        }

        return $this->success(null, "Khôi phục hàng loạt {$count} bài giảng thành công.");
    }

    /**
     * Xóa vĩnh viễn hàng loạt bài giảng.
     */
    public function bulkForceDelete(Request $request): JsonResponse
    {
        $request->validate([
            'ids'   => 'required|array|min:1',
            'ids.*' => 'integer',
        ]);

        $count = $this->repository->forceDeleteMany($request->ids);

        return $this->success(null, "Xóa vĩnh viễn hàng loạt {$count} bài giảng thành công.");
    }

    // ── Client API ──

    /**
     * Client: Danh sách bài giảng của khóa đã mua (kèm progress).
     */
    public function myLessons(Request $request, string $slug): JsonResponse
    {
        $course = Course::where('slug', $slug)->where('status', 1)->first();

        if (!$course) {
            return $this->error('Khóa học không tồn tại.', 404);
        }

        // Kiểm tra student đã enroll
        $enrolled = $course->students()->where('student_id', auth('api')->id())->exists();

        if (!$enrolled) {
            return $this->error('Bạn chưa mua khóa học này.', 403);
        }

        $studentId = auth('api')->id();

        // Load tất cả progress 1 lần — tránh N+1
        $progressMap = LessonProgress::where('student_id', $studentId)
            ->where('course_id', $course->id)
            ->get()
            ->keyBy('lesson_id');

        // Lấy sections published kèm lessons published, group theo chương
        $sections = Section::where('course_id', $course->id)
            ->where('status', 1)
            ->ordered()
            ->with([
                'lessons' => fn($q) => $q->where('status', 1)->ordered(),
            ])
            ->get()
            ->map(function ($section) use ($progressMap) {
                return [
                    'id'      => $section->id,
                    'title'   => $section->title,
                    'order'   => $section->order,
                    'lessons' => $section->lessons->map(fn($lesson) => $this->formatLesson($lesson, $progressMap)),
                ];
            });

        // Lessons không có section (section_id = null)
        $orphanLessons = Lesson::where('course_id', $course->id)
            ->whereNull('section_id')
            ->where('status', 1)
            ->ordered()
            ->get()
            ->map(fn($lesson) => $this->formatLesson($lesson, $progressMap));

        $result = [
            'sections'       => $sections,
            'orphan_lessons' => $orphanLessons, // bài học chưa gán chương
        ];

        return $this->success($result, 'Lấy danh sách bài giảng thành công.');
    }

    /**
     * Client: Lấy video/chi tiết bài học đã mua
     */
    public function myLessonDetail(Request $request, string $slug, string $lessonSlug): JsonResponse
    {
        $course = Course::where('slug', $slug)->where('status', 1)->first();

        if (!$course) {
            return $this->error('Khóa học không tồn tại.', 404);
        }

        // Kiểm tra student đã enroll
        $enrolled = $course->students()->where('student_id', auth('api')->id())->exists();

        if (!$enrolled) {
            return $this->error('Bạn chưa mua khóa học này.', 403);
        }

        $lesson = Lesson::where('course_id', $course->id)
            ->where('slug', $lessonSlug)
            ->where('status', 1)
            ->with(['video', 'document'])
            ->first();

        if (!$lesson) {
            return $this->error('Bài học không tồn tại.', 404);
        }

        return $this->success([
            'id'           => $lesson->id,
            'title'        => $lesson->title,
            'type'         => $lesson->type,
            'video_url'    => $lesson->video ? $lesson->video->url : null,
            'document_url' => $lesson->document ? $lesson->document->url : null,
            'content'      => $lesson->content,
            'course_name'  => $course->name,
        ], 'Lấy chi tiết bài học thành công.');
    }

    /**
     * Client: Cập nhật tiến độ học bài giảng.
     */
    public function updateProgress(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'watched_seconds' => 'required|integer|min:0',
            'is_completed'    => 'nullable|boolean',
        ]);

        $lesson = Lesson::find($id);

        if (!$lesson) {
            return $this->error('Bài giảng không tồn tại.', 404);
        }

        // Kiểm tra student đã enroll course chứa lesson
        $enrolled = Course::where('id', $lesson->course_id)
            ->whereHas('students', fn($q) => $q->where('student_id', auth('api')->id()))
            ->exists();

        if (!$enrolled) {
            return $this->error('Bạn chưa mua khóa học này.', 403);
        }

        $studentId = auth('api')->id();

        // Lấy progress hiện tại (nếu có) để xử lý completed_at
        $existingProgress = LessonProgress::where('student_id', $studentId)
            ->where('lesson_id', $id)
            ->first();

        $isCompleted = $request->boolean('is_completed', false);

        // completed_at chỉ set lần đầu khi is_completed=true, không ghi đè nếu đã có
        $completedAt = $isCompleted && !$existingProgress?->completed_at
            ? now()
            : ($existingProgress?->completed_at ?? null);

        $progress = LessonProgress::updateOrCreate(
            ['student_id' => $studentId, 'lesson_id' => $id],
            [
                'course_id'       => $lesson->course_id,
                'watched_seconds' => $request->watched_seconds,
                'is_completed'    => $isCompleted,
                'completed_at'    => $completedAt,
            ]
        );

        return $this->success([
            'lesson_id'       => $id,
            'course_id'       => $lesson->course_id,
            'is_completed'    => (bool) $progress->is_completed,
            'watched_seconds' => $progress->watched_seconds,
            'completed_at'    => $progress->completed_at,
        ], 'Cập nhật tiến độ thành công.');
    }

    // ── Helpers ──

    private function formatLesson($lesson, $progressMap): array
    {
        $progress = $progressMap->get($lesson->id);

        return [
            'id'         => $lesson->id,
            'title'      => $lesson->title,
            'slug'       => $lesson->slug,
            'type'       => $lesson->type,
            'order'      => $lesson->order,
            'is_preview' => $lesson->is_preview,
            'duration'   => $lesson->duration,
            'progress'   => $progress ? [
                'is_completed'    => (bool) $progress->is_completed,
                'watched_seconds' => $progress->watched_seconds,
                'completed_at'    => $progress->completed_at,
            ] : null,
        ];
    }

    /**
     * Client: Tổng quan tiến độ học của khóa học.
     */
    public function courseProgress(Request $request, string $slug): JsonResponse
    {
        $course = Course::where('slug', $slug)->where('status', 1)->first();

        if (!$course) {
            return $this->error('Khóa học không tồn tại.', 404);
        }

        // Kiểm tra student đã enroll
        $enrolled = $course->students()->where('student_id', auth('api')->id())->exists();

        if (!$enrolled) {
            return $this->error('Bạn chưa mua khóa học này.', 403);
        }

        $studentId = auth('api')->id();

        // Lấy tất cả lessons status=1 của course
        $lessons = Lesson::where('course_id', $course->id)
            ->where('status', 1)
            ->orderBy('order')
            ->get();

        // Lấy tất cả progress của student cho course này, keyed by lesson_id
        $progressMap = LessonProgress::where('student_id', $studentId)
            ->where('course_id', $course->id)
            ->get()
            ->keyBy('lesson_id');

        // Tính toán tiến độ tổng
        $totalLessons     = $lessons->count();
        $completedLessons = $progressMap->where('is_completed', 1)->count();
        $percent          = $totalLessons > 0 ? round($completedLessons / $totalLessons * 100) : 0;

        // Group lessons theo section
        $sections = Section::where('course_id', $course->id)
            ->where('status', 1)
            ->ordered()
            ->get()
            ->map(function ($section) use ($lessons, $progressMap) {
                $sectionLessons = $lessons->where('section_id', $section->id)->values();

                return [
                    'id'              => $section->id,
                    'title'           => $section->title,
                    'order'           => $section->order,
                    'total'           => $sectionLessons->count(),
                    'completed'       => $sectionLessons->filter(fn($l) => $progressMap->has($l->id) && $progressMap[$l->id]->is_completed)->count(),
                    'lessons'         => $sectionLessons->map(fn($lesson) => [
                        'id'              => $lesson->id,
                        'title'           => $lesson->title,
                        'is_completed'    => $progressMap->has($lesson->id) && (bool) $progressMap[$lesson->id]->is_completed,
                        'watched_seconds' => $progressMap[$lesson->id]->watched_seconds ?? 0,
                    ]),
                ];
            });

        return $this->success([
            'course_id'         => $course->id,
            'total_lessons'     => $totalLessons,
            'completed_lessons' => $completedLessons,
            'percent'           => $percent,
            'sections'          => $sections,
        ], 'Lấy tiến độ học thành công.');
    }
}
