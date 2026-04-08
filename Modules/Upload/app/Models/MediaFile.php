<?php

namespace Modules\Upload\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MediaFile extends Model
{
    use HasFactory;

    protected $table = 'media_files';

    protected $fillable = [
        'disk',
        'type',
        'original_name',
        'path',
        'url',
        'mime_type',
        'size',
        'status',
        'reference_count',
        'duration',
        'width',
        'height',
        'bitrate',
        'codec',
        'uploaded_by',
    ];

    protected $casts = [
        'size'            => 'integer',
        'reference_count' => 'integer',
        'duration'        => 'integer',
        'width'           => 'integer',
        'height'          => 'integer',
        'bitrate'         => 'integer',
    ];
}
