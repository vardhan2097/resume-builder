<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Resume extends Model
{
    protected $fillable = [
        'title',
        'template',
        'color',
        'data',
        'photo_path',
        'photo_original_path',
    ];

    protected function casts(): array
    {
        return [
            'data' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    protected static function booted(): void
    {
        static::creating(function (Resume $resume) {
            if (empty($resume->data)) {
                $resume->data = self::defaultData();
            }
        });

        static::deleted(function (Resume $resume) {
            Storage::disk('local')->delete(array_filter([$resume->photo_path, $resume->photo_original_path]));
        });
    }

    public static function defaultData(): array
    {
        return [
            'personal' =>
            [
                'full_name' => '',
                'job_title' => '',
                'email' => '',
                'phone' => '',
                'location' => '',
                'website' => '',
                'linkedin' => '',
                'summary' => '',
            ],
            'experience' => [],
            'education' => [],
            'skills' => [],
            'tools' => [],
            'projects' => [],
            'languages' => [],
            'certifications' => [],
            'hobbies' => [],
            'references' => [],
        ];
    }
}
