@use('App\Support\ResumeFormat', 'F')
@php
    $s = F::prepare($data);
    extract($s);
    $dark = F::shade($color, 0.25);
    $card = F::tint($color, 0.92);
    $line = F::tint($color, 0.7);

    // One column: every section follows the user's order, so DomPDF can break anywhere.
    $sections = F::sectionOrder($data);

    $titles = ['skills' => 'Key Skills', 'tools' => 'Software'] + F::TITLES;
    $icons = ['experience' => 'briefcase', 'education' => 'education', 'projects' => 'project', 'skills' => 'star', 'tools' => 'tools',
        'languages' => 'language', 'certifications' => 'award', 'hobbies' => 'heart', 'references' => 'people'];
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $p['full_name'] ?: 'Resume' }}</title>
    <style>
        {!! F::fontFaces(['Montserrat', 'Montserrat SemiBold', 'Lato'], $pdf) !!}

        @page { margin: {{ $pdf ? '30px 34px' : '0' }}; }
        body { margin: 0; padding: {{ $pdf ? '0' : '30px 34px' }}; font-family: 'Lato', 'DejaVu Sans', Arial, sans-serif; font-size: 9.5px; line-height: 1.35; color: #374151; background: #ffffff; }
        table { border-collapse: collapse; width: 100%; }
        td { vertical-align: top; padding: 0; }
        p { margin: 0 0 6px; }
        ul { margin: 3px 0 0; padding-left: 13px; }
        li { margin-bottom: 1px; }

        .top-bar { height: 6px; background: {{ $color }}; }
        .card { background: {{ $card }}; }
        .card td { vertical-align: middle; }
        .card-photo { width: 92px; padding: 12px 0 12px 16px; }
        .photo { width: 76px; height: 76px; border-radius: 38px; border: 3px solid #ffffff; }
        .initials { width: 76px; height: 50px; padding-top: 26px; border-radius: 38px; border: 3px solid #ffffff; background: {{ $color }}; color: #ffffff; text-align: center; font-family: 'Montserrat', sans-serif; font-size: 24px; font-weight: bold; line-height: 1; }
        .card-name { padding: 12px 10px 12px 14px; }
        .name { margin: 0; font-family: 'Montserrat', sans-serif; font-size: 24px; font-weight: bold; letter-spacing: 0.5px; text-transform: uppercase; color: {{ $dark }}; line-height: 1.1; }
        .role { margin-top: 4px; font-family: 'Montserrat SemiBold', sans-serif; font-size: 10px; letter-spacing: 0.8px; text-transform: uppercase; color: {{ $color }}; }
        .card-contact { width: 40%; padding: 12px 16px 12px 0; text-align: right; font-size: 8.5px; line-height: 1.6; }
        .card-contact b { color: {{ $dark }}; }

        /* The rule is the heading's border, so "avoid" keeps it with the content below. */
        .sh { margin: 15px 0 7px; padding-bottom: 5px; border-bottom: 1px solid {{ $line }}; page-break-after: avoid; font-family: 'Montserrat', sans-serif; font-size: 12px; font-weight: bold; letter-spacing: 0.8px; text-transform: uppercase; color: {{ $dark }}; }
        .badge { width: 11px; height: 11px; padding: 4px; margin-right: 8px; border-radius: 10px; background: {{ $color }}; vertical-align: middle; }

        .entry { margin-bottom: 8px; }
        .entry-head td { padding: 0; }
        .entry-title { font-size: 10px; font-weight: bold; color: #111827; }
        .entry-date { width: 1%; white-space: nowrap; text-align: right; font-size: 8.5px; color: #4b5563; }
        .entry-sub { color: #4b5563; }
        .entry-place { width: 1%; white-space: nowrap; text-align: right; font-size: 8.5px; color: #4b5563; }
        .entry-extra { font-size: 8.5px; color: #6b7280; }

        .tags { line-height: 2; }
        .tag { display: inline-block; padding: 0 7px; margin: 0 4px 4px 0; font-size: 8.5px; line-height: 1.8; color: #1f2937; border: 1px solid {{ $line }}; border-left: 3px solid {{ $color }}; border-radius: 3px; }
        .chip-dots { padding-left: 3px; }
        .dot { display: inline-block; width: 4px; height: 4px; margin-left: 1.5px; border-radius: 2px; vertical-align: middle; }
        .dot-on { background: {{ $color }}; }
        .dot-off { background: {{ $line }}; }
        .lite { margin-bottom: 6px; }
        .lite-title { font-weight: bold; color: #111827; }
        .lite-meta { font-size: 8.5px; color: #4b5563; }

        .entry, .item, .side-item, .lite { page-break-inside: avoid; }
        [data-page-break="1"] { page-break-before: always; }
 .editorial-header { text-align: center; padding: 15px 16px 18px; border-top: 7px solid {{ $color }}; background: {{ $card }}; }
        .editorial-header .name { font-family: DejaVu Serif, serif; font-size: 27px; letter-spacing: 2px; color: #1f2937; }
        .editorial-header .role { margin-top: 6px; letter-spacing: 2px; }
        .editorial-photo { width: 74px; height: 74px; border-radius: 37px; margin-bottom: 8px; }
        .editorial-contacts { margin-top: 10px; font-size: 8.5px; line-height: 1.6; overflow-wrap: break-word; }
        .editorial-contacts b { color: {{ $color }}; }
        .sh { font-family: Montserrat, sans-serif; font-size: 13px; letter-spacing: 1.2px; border-bottom: 2px solid {{ $color }}; margin: 17px 0 8px; }
        .editorial-content { padding: 0 8px; }
        .entry, .lite { background: {{ $card }}; padding: 7px 9px; margin-bottom: 6px; }

    </style>
</head>
<body>

<div class="editorial-header">
    @if ($photo)<img src="{{ $photo }}" class="editorial-photo" alt="">@endif
    <h1 class="name">{{ $p['full_name'] }}</h1>
    @if ($p['job_title'])<div class="role">{{ $p['job_title'] }}</div>@endif
    @if (count($contacts))
        <div class="editorial-contacts">
            @foreach ($contacts as [$type, $value, $label])
                <span>{{ in_array($type, ['nationality', 'date_of_birth']) ? $label.': ' : '' }}{{ in_array($type, ['website', 'linkedin', 'github']) ? F::shortUrl($value) : $value }}</span>@if (! $loop->last) <b> / </b> @endif
            @endforeach
        </div>
    @endif
</div>

@if ($p['summary'])
    <div class="sh">Profile Summary</div>
    <div class="editorial-content">@include('resumes.partials.text', ['text' => $p['summary']])</div>
@endif

@foreach ($sections as $section)
    @if (count($s[$section]))
        <div class="sh" data-page-break="{{ in_array($section, $data['settings']['page_break_before'] ?? []) ? 1 : 0 }}">{{ $titles[$section] }}</div>
        <div class="editorial-content">@include('resumes.partials.section', ['section' => $section, 'opts' => ['levels' => 'chips', 'plain' => 'tags', 'orgFirst' => true]])</div>
    @endif
@endforeach

</body>
</html>
