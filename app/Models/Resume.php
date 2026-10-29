<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

/**
 * The JSON stored in resumes.data, after withDefaults() has filled any missing keys.
 *
 * @phpstan-type ResumeData array{
 *     personal: array<string, string>,
 *     experience: list<array<string, mixed>>,
 *     education: list<array<string, mixed>>,
 *     skills: list<array<string, mixed>>,
 *     tools: list<array<string, mixed>>,
 *     projects: list<array<string, mixed>>,
 *     languages: list<array<string, mixed>>,
 *     certifications: list<array<string, mixed>>,
 *     hobbies: list<string>,
 *     references: list<array<string, mixed>>,
 *     settings: array{show_levels: array<string, bool>, section_order: list<string>}
 * }
 *
 * @property int $id
 * @property int $user_id
 * @property string $title
 * @property string $template
 * @property string $color
 * @property ResumeData $data
 * @property string|null $photo_path
 * @property string|null $photo_original_path
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User $user
 */
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

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'data' => 'array',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
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

    /**
     * @return ResumeData
     */
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
     *
     * @return ResumeData
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
