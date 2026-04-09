<?php

namespace Modules\Lessons\Repositories;

use App\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Modules\Lessons\Models\Section;

class SectionRepository extends BaseRepository implements SectionRepositoryInterface
{
    public function __construct(Section $model)
    {
        parent::__construct($model);
    }

    public function getByCourse(int $courseId, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $perPage = max(1, min($perPage, static::MAX_PER_PAGE));

        $query = $this->model->newQuery()
            ->where('course_id', $courseId)
            ->ordered();

        if (isset($filters['status']) && $filters['status'] !== '') {
            $query->where('status', (int) $filters['status']);
        }

        return $query->paginate($perPage);
    }

    public function toggleStatus(int $id): Model
    {
        $section = $this->model->newQuery()->findOrFail($id);
        $section->update(['status' => $section->status === 1 ? 0 : 1]);
        $section->refresh();

        return $section;
    }

    public function reorder(array $orders): void
    {
        DB::transaction(function () use ($orders) {
            foreach ($orders as $item) {
                Section::where('id', $item['id'])->update(['order' => $item['order']]);
            }
        });
    }
}
