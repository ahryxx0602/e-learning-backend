<?php

namespace Modules\Students\Repositories;

use App\Repositories\BaseRepository;
use Modules\Students\Models\Student;

/**
 * Class StudentsRepository
 *
 * Eloquent implementation cho StudentsRepositoryInterface.
 * Extends BaseRepository (đã có sẵn 9 methods chuẩn + clamp perPage, soft-delete support).
 * Thêm các method riêng cho Students tại đây.
 */
class StudentsRepository extends BaseRepository implements StudentsRepositoryInterface
{
    public function __construct(Student $model)
    {
        parent::__construct($model);
    }
}
