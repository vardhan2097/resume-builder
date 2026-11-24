@use('App\Support\ResumeFormat', 'F')
@php
    $s = F::prepare($data);
    extract($s);
    $dark = F::shade($color, 0.55);
    $side = $dark;
    $line = F::tint($color, 0.48);

    // Sidebar sections that don't fit beside page 1 continue in the main column.
    $sideOrder = F::sectionOrder($data, ['skills', 'tools', 'languages', 'certifications', 'hobbies', 'references']);
    $identityLines = (int) ceil(mb_strlen($p['full_name']) / 23) * 2 + (int) ceil(mb_strlen($p['job_title']) / 25) + ($photo ? 7 : 0);
    $contactLines = array_sum(array_map(fn ($contact) => max(1, (int) ceil(mb_strlen($contact[1]) / 29)) + 1, $contacts));
    [$sideSections, $overflow] = F::fitSidebar($data, $s, $sideOrder, 62 - $identityLines - $contactLines, 28);
    $mainSections = [...F::sectionOrder($data, ['experience', 'education', 'projects']), ...$overflow];

    $titles = ['experience' => 'Work Experience', 'tools' => 'Software'] + F::TITLES;
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $p['full_name'] ?: 'Resume' }}</title>
    <style>
        {!! F::fontFaces(['Montserrat', 'Montserrat Light', 'Montserrat SemiBold', 'Lato'], $pdf) !!}

        @page { margin: {{ $pdf ? '28px' : '0' }}; }
        body { margin: 0; padding: {{ $pdf ? '0' : '28px' }}; font-family: 'Lato', 'DejaVu Sans', Arial, sans-serif; font-size: 9.5px; line-height: 1.35; color: #374151; background: #ffffff; }
        table { border-collapse: collapse; width: 100%; }
        td { vertical-align: top; padding: 0; }
        p { margin: 0 0 6px; }
        ul { margin: 3px 0 0; padding-left: 13px; }
        li { margin-bottom: 1px; }

        .body { position: relative; {{ $pdf ? '' : 'min-height: 980px;' }} }
        .side-bg { position: fixed; top: 0; bottom: 0; left: 0; width: 32%; background: {{ $side }}; z-index: -1; }
        .side { position: absolute; top: 0; left: 0; width: 32%; color: #ffffff; {{ $pdf ? '' : 'bottom: 0; background: '.$side.';' }} }
        .side-in { padding: 20px 16px; }
        .name { margin: 0; font: bold 20px/1.1 'Montserrat', sans-serif; text-transform: uppercase; color: #ffffff; word-wrap: break-word; }
        .role { margin: 4px 0 14px; color: {{ F::tint($color, 0.8) }}; font-size: 10px; }
        .photo { width: 62px; height: 62px; border-radius: 31px; margin-bottom: 8px; }
        .main { margin-left: 32%; padding: 18px 0 0 20px; }
        .sh { margin: 14px 0 7px; padding-top: 8px; border-top: 1px solid {{ F::tint($color, 0.35) }}; font: bold 10px 'Montserrat', sans-serif; text-transform: uppercase; color: #ffffff; }
        .contact { margin: 12px 0; }
        .contact td { padding-bottom: 5px; font-size: 8.5px; color: #ffffff; word-wrap: break-word; }
        .contact .ci { width: 20px; }
        .contact img { width: 11px; height: 11px; }
        .mh { margin: 14px 0 8px; padding-bottom: 5px; border-bottom: 1px solid {{ $line }}; page-break-after: avoid; font: bold 11px 'Montserrat', sans-serif; text-transform: uppercase; color: {{ $dark }}; }
        .mh-first { margin-top: 0; }
        .main .entry { border-left: 1px solid {{ $line }}; margin-left: 4px; padding-left: 12px; }

        .entry { margin-bottom: 9px; }
        .entry-sub-row { margin-bottom: 1px; }
        .entry-head td { padding: 0; }
        .entry-title { font-size: 10.5px; font-weight: bold; color: #111827; }
        .entry-date { width: 1%; white-space: nowrap; text-align: right; font-size: 8.5px; color: #4b5563; }
        .entry-sub { color: #4b5563; }
        .entry-place { width: 1%; white-space: nowrap; text-align: right; font-size: 8.5px; color: #6b7280; }
        .entry-extra { font-size: 8.5px; color: #6b7280; }

        .lvl { margin-bottom: 5px; }
        .lvl-name { margin-bottom: 2px; }
        .bar { height: 4px; background: {{ F::tint($color, 0.72) }}; border-radius: 2px; }
        .main .bar { background: #e5e7eb; }
        .main .bar-fill { background: {{ $dark }}; }
        .side .lite-title, .side .lite-meta { color: #ffffff; }
        .bar-fill { height: 4px; background: {{ F::tint($color, 0.5) }}; border-radius: 2px; }
        .plain-list { margin: 0; padding-left: 12px; }
        .tags { line-height: 1.9; }
        .tag { display: inline-block; padding: 0 6px; margin: 0 3px 3px 0; font-size: 8.5px; line-height: 1.7; background: {{ $side }}; border-radius: 3px; }
        .lite { margin-bottom: 7px; }
        .lite-title { font-weight: bold; color: #111827; }
        .lite-meta { font-size: 8.5px; color: #4b5563; }

        .entry, .item, .side-item, .lite { page-break-inside: avoid; }
        [data-page-break="1"] { page-break-before: always; }
    </style>
</head>
<body>
@if ($pdf)
    <div class="side-bg"></div>
@endif

<div class="body">
    <div class="side"><div class="side-in">
            @if ($photo)<img src="{{ $photo }}" class="photo" alt="">@endif
            <h1 class="name">{{ $p['full_name'] }}</h1>
            @if ($p['job_title'])<div class="role">{{ $p['job_title'] }}</div>@endif
            @if (count($contacts))
                <table class="contact">
                    @foreach ($contacts as [$type, $value, $label])
                        <tr>
                            <td class="ci"><img src="{{ F::icon($type === 'date_of_birth' ? 'calendar' : ($type === 'nationality' ? 'user' : $type), '#ffffff') }}" alt=""></td>
                            <td>{{ in_array($type, ['nationality', 'date_of_birth']) ? $label.': ' : '' }}{{ in_array($type, ['website', 'linkedin', 'github']) ? F::shortUrl($value) : $value }}</td>
                        </tr>
                    @endforeach
                </table>
            @endif

            @foreach ($sideSections as $section)
                @if (count($s[$section]))
                    <div class="sh" data-page-break="{{ in_array($section, $data['settings']['page_break_before'] ?? []) ? 1 : 0 }}">{{ $titles[$section] }}</div>
                    @include('resumes.partials.section', ['section' => $section, 'opts' => ['levels' => 'bars', 'plain' => 'list']])
                @endif
            @endforeach
        </div></div>

    <div class="main">
            <div class="timeline">
                @php($first = true)
                @if ($p['summary'])
                    <div class="mh mh-first">Profile</div>
                    @include('resumes.partials.text', ['text' => $p['summary']])
                    @php($first = false)
                @endif

                @foreach ($mainSections as $section)
                    @if (count($s[$section]))
                        <div class="mh {{ $first ? 'mh-first' : '' }}" data-page-break="{{ in_array($section, $data['settings']['page_break_before'] ?? []) ? 1 : 0 }}">{{ $titles[$section] }}</div>
                            @include('resumes.partials.section', ['section' => $section, 'opts' => ['levels' => 'bars', 'plain' => 'tags', 'orgFirst' => $section === 'experience']])
                        @php($first = false)
                    @endif
                @endforeach
            </div>
        </div>
</div>

</body>
</html>
