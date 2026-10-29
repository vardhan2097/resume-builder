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

    /**
     * Sections the user can reorder, in their default order. Personal details
     * and the summary always come first.
     */
    public const SECTIONS = [
        'experience', 'education', 'skills', 'tools', 'projects',
        'certifications', 'languages', 'hobbies', 'references',
    ];

    public static function defaultData(): array
    {
        return [
            'personal' => [
                'full_name' => '',
                'job_title' => '',
                'email' => '',
                'phone' => '',
                'location' => '',
                'website' => '',
                'linkedin' => '',
                'github' => '',
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
            'settings' => [
                // Show the 1–5 rating next to each item, per rated section.
                'show_levels' => [
                    'skills' => true,
                    'tools' => true,
                    'languages' => true,
                ],
                'section_order' => self::SECTIONS,
            ],
        ];
    }

    /**
     * Saved (or half-typed) data with any missing keys filled from defaultData(),
     * so resumes saved before a field existed still render.
     */
    public static function withDefaults(mixed $data): array
    {
        $defaults = self::defaultData();
        $data = array_replace($defaults, (array) $data);

        $data['personal'] = array_replace($defaults['personal'], (array) $data['personal']);
        // Not array_replace_recursive: that would merge section_order by index.
        $settings = array_replace($defaults['settings'], (array) $data['settings']);
        $settings['show_levels'] = array_replace($defaults['settings']['show_levels'], (array) $settings['show_levels']);
        $data['settings'] = $settings;

        return $data;
    }
}
