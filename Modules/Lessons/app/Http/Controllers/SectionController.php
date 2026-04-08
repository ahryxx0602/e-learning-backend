<?php

namespace Modules\Lessons\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Course\Models\Course;
use Modules\Lessons\Models\Section;
use Modules\Lessons\Http\Requests\StoreSectionRequest;
use Modules\Lessons\Http\Requests\UpdateSectionRequest;
use Modules\Lessons\Repositories\SectionRepositoryInterface;

class SectionController extends Controller
{
    use ApiResponse;

    protected SectionRepositoryInterface $repository;

    public function __construct(SectionRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    // ── Admin CRUD ──

    /**
     * Danh sách chương của một khóa học.
     */
    public function index(Request $request, int $course_id): JsonResponse
    {
        $request->validate([
            'status'   => 'nullable|in:0,1',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        Course::findOrFail($course_id);

        $perPage = (int) $request->query('per_page', 15);
        $filters = $request->only(['status']);

        $data = $this->repository->getByCourse($course_id, $filters, $perPage);

        return $this->paginated($data, 'Lấy danh sách chương thành công.');
    }

    /**
     * Tạo mới chương cho khóa học.
     */
    public function store(StoreSectionRequest $request, int $course_id): JsonResponse
    {
        Course::findOrFail($course_id);

        $validated = $request->validated();
        $validated['course_id'] = $course_id;

        // Nếu không truyền order → tự đặt = số sections hiện tại
        if (!isset($validated['order'])) {
            $validated['order'] = Section::where('course_id', $course_id)->count();
        }

        $section = $this->repository->create($validated);

        return $this->success($section, 'Tạo chương thành công.', 201);
    }

    /**
     * Chi tiết chương kèm danh sách bài giảng.
     */
    public function show(int $id): JsonResponse
    {
        $section = $this->repository->findOrFail($id);
        $section->load('lessons');

        return $this->success($section, 'Lấy chi tiết chương thành công.');
    }

    /**
     * Cập nhật chương.
     */
    public function update(UpdateSectionRequest $request, int $id): JsonResponse
    {
        $section = $this->repository->update($id, $request->validated());

        return $this->success($section, 'Cập nhật chương thành công.');
    }

    /**
     * Xóa chương (soft delete). Lessons trong chương sẽ có section_id = null.
     */
    public function destroy(int $id): JsonResponse
    {
        $this->repository->findOrFail($id);
        $this->repository->delete($id);

        return $this->success(null, 'Xóa chương thành công.');
    }

    /**
     * Toggle trạng thái draft/published (0 ↔ 1).
     */
    public function toggleStatus(int $id): JsonResponse
    {
        $section = $this->repository->toggleStatus($id);

        return $this->success(
            ['id' => $section->id, 'status' => $section->status],
            'Cập nhật trạng thái chương thành công.'
        );
    }

    // ── Soft Delete Operations ──

    /**
     * Danh sách chương đã bị soft-delete (thùng rác).
     */
    public function trashed(Request $request): JsonResponse
    {
        $request->validate([
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $perPage = (int) $request->query('per_page', 15);
        $data = $this->repository->paginateTrashed($perPage);

        return $this->paginated($data, 'Lấy danh sách chương đã xóa thành công.');
    }

    /**
     * Khôi phục chương đã soft-delete.
     */
    public function restore(int $id): JsonResponse
    {
        Section::withTrashed()->findOrFail($id);
        $this->repository->restore($id);

        return $this->success(null, 'Khôi phục chương thành công.');
    }

    /**
     * Xóa vĩnh viễn chương.
     */
    public function forceDelete(int $id): JsonResponse
    {
        $this->repository->forceDeleteById($id);

        return $this->success(null, 'Xóa vĩnh viễn chương thành công.');
    }

    // ── Reorder ──

    /**
     * Cập nhật thứ tự chương hàng loạt.
     */
    public function reorder(Request $request): JsonResponse
    {
        $request->validate([
            'orders'         => 'required|array',
            'orders.*.id'    => 'required|integer|exists:sections,id',
            'orders.*.order' => 'required|integer|min:0',
        ]);

        // Đảm bảo tất cả sections thuộc cùng 1 course
        $ids = collect($request->orders)->pluck('id')->toArray();
        $courseIds = Section::whereIn('id', $ids)->distinct()->pluck('course_id');
        if ($courseIds->count() > 1) {
            return $this->error('Không thể sắp xếp chương của nhiều khóa học cùng lúc.', 422);
        }

        $this->repository->reorder($request->orders);

        return $this->success(null, 'Sắp xếp chương thành công.');
    }

    // ── Bulk Actions ──

    /**
     * Cập nhật trạng thái chương hàng loạt.
     */
    public function bulkAction(Request $request): JsonResponse
    {
        $request->validate([
            'ids'    => 'required|array|min:1',
            'ids.*'  => 'integer|exists:sections,id',
            'action' => 'required|string|in:publish,unpublish,activate,deactivate',
        ]);

        $count = $this->repository->actionMany($request->ids, $request->action);

        return $this->success(null, "Cập nhật trạng thái hàng loạt {$count} chương thành công.");
    }

    /**
     * Xóa hàng loạt chương.
     */
    public function bulkDelete(Request $request): JsonResponse
    {
        $request->validate([
            'ids'   => 'required|array|min:1',
            'ids.*' => 'integer|exists:sections,id',
        ]);

        $count = $this->repository->deleteMany($request->ids);

        return $this->success(null, "Xóa hàng loạt {$count} chương thành công.");
    }

    /**
     * Khôi phục hàng loạt chương đã xóa.
     */
    public function bulkRestore(Request $request): JsonResponse
    {
        $request->validate([
            'ids'   => 'required|array|min:1',
            'ids.*' => 'integer',
        ]);

        $count = $this->repository->restoreMany($request->ids);

        return $this->success(null, "Khôi phục hàng loạt {$count} chương thành công.");
    }

    /**
     * Xóa vĩnh viễn hàng loạt chương.
     */
    public function bulkForceDelete(Request $request): JsonResponse
    {
        $request->validate([
            'ids'   => 'required|array|min:1',
            'ids.*' => 'integer',
        ]);

        $count = $this->repository->forceDeleteMany($request->ids);

        return $this->success(null, "Xóa vĩnh viễn hàng loạt {$count} chương thành công.");
    }

    // ── Public ──

    /**
     * Public: Lấy toàn bộ curriculum (sections + lessons published) của 1 khóa học theo slug.
     */
    public function curriculum(string $slug): JsonResponse
    {
        $course = Course::where('slug', $slug)->where('status', 1)->first();

        if (!$course) {
            return $this->error('Khóa học không tồn tại.', 404);
        }

        $sections = Section::where('course_id', $course->id)
            ->where('status', 1)
            ->ordered()
            ->with([
                'lessons' => function ($q) {
                    $q->where('status', 1)
                      ->ordered()
                      ->select('id', 'section_id', 'title', 'slug', 'type', 'order', 'is_preview', 'duration');
                },
            ])
            ->get()
            ->map(fn($section) => [
                'id'          => $section->id,
                'title'       => $section->title,
                'description' => $section->description,
                'order'       => $section->order,
                'lessons'     => $section->lessons->values(),
            ]);

        return $this->success([
            'course_id' => $course->id,
            'sections'  => $sections,
        ], 'Lấy curriculum thành công.');
    }
}
