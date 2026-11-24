@use('App\Support\ResumeFormat', 'F')
@php
    $s = F::prepare($data);
    extract($s);
    $dark = F::shade($color, 0.55);
    $side = F::tint($color, 0.94);
    $line = F::tint($color, 0.55);

    // Sidebar sections that don't fit beside page 1 continue in the main column.
    $sideOrder = F::sectionOrder($data, ['skills', 'tools', 'languages', 'certifications', 'hobbies', 'references']);
    [$sideSections, $overflow] = F::fitSidebar($data, $s, $sideOrder, 56 - (int) ceil(count($contacts) * 1.6), 30);
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

        /* A compact masthead and a right-hand notes column give Folio its own structure. */
        .head-band { padding: 15px 20px 16px 0; border-bottom: 2px solid {{ $dark }}; vertical-align: bottom; }
        .head-photo { width: 86px; padding: 8px 0 14px; border-bottom: 2px solid {{ $dark }}; text-align: right; vertical-align: bottom; }
        .photo { width: 72px; height: 72px; }
        .initials { width: 72px; height: 52px; padding-top: 20px; background: {{ $dark }}; color: #fff; text-align: center; font-family: 'DejaVu Serif', serif; font-size: 25px; line-height: 1; }
        .name { margin: 0; font-family: 'DejaVu Serif', serif; font-size: 30px; font-weight: normal; letter-spacing: 1px; text-transform: uppercase; color: #111827; line-height: 1.1; }
        .role { margin-top: 7px; font-size: 10px; font-weight: bold; letter-spacing: 2px; text-transform: uppercase; color: {{ $color }}; }

        /* The main column stays in normal flow so it can continue across PDF pages. */
        .body { position: relative; {{ $pdf ? '' : 'min-height: 940px;' }} }
        .side-bg { position: fixed; top: 0; bottom: 0; right: 0; width: 29%; background: {{ $side }}; z-index: -1; }
        .side { position: absolute; top: 0; right: 0; {{ $pdf ? '' : 'bottom: 0; background: '.$side.';' }} width: 29%; }
        .side-in { padding: 5px 14px 18px; }
        .main { margin-right: 29%; padding: 12px 22px 0 0; }

        .sh { margin: 16px 0 7px; padding-bottom: 4px; border-bottom: 1.5px solid {{ $dark }}; font-family: 'Montserrat', sans-serif; font-size: 11px; font-weight: bold; letter-spacing: 1.5px; text-transform: uppercase; color: {{ $dark }}; }
        .contact { margin-bottom: 6px; }
        .contact td { padding-bottom: 5px; font-size: 8.5px; word-wrap: break-word; }
        .contact .ci { width: 16px; padding-top: 1px; }
        .contact img { width: 9px; height: 9px; }

        .timeline { padding: 0; }
        .mh { margin: 16px 0 8px; padding: 0 0 4px; border-bottom: 1px solid {{ $line }}; page-break-after: avoid; font-family: 'DejaVu Serif', serif; font-size: 12px; font-weight: bold; letter-spacing: 0.8px; text-transform: uppercase; color: {{ $dark }}; }
        .mh-first { margin-top: 0; }

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
        .bar-fill { height: 4px; background: {{ $dark }}; border-radius: 2px; }
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

<table>
    <tr>
        <td class="head-band">
            <h1 class="name">{{ $p['full_name'] }}</h1>
            @if ($p['job_title'])
                <div class="role">{{ $p['job_title'] }}</div>
            @endif
        </td>
        <td class="head-photo">
            @if ($photo)
                <img src="{{ $photo }}" class="photo" alt="">
            @else
                <div class="initials">{{ F::initials($p['full_name']) }}</div>
            @endif
        </td>
    </tr>
</table>

<div class="body">
    <div class="side"><div class="side-in">
            @if (count($contacts))
                <div class="sh">Contact</div>
                <table class="contact">
                    @foreach ($contacts as [$type, $value, $label])
                        <tr>
                            <td class="ci"><img src="{{ F::icon($type === 'date_of_birth' ? 'calendar' : ($type === 'nationality' ? 'user' : $type), $dark) }}" alt=""></td>
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
