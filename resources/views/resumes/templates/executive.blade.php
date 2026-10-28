@use('App\Support\ResumeFormat', 'F')
@php
    $s = F::prepare($data);
    extract($s);
    $dark = F::shade($color, 0.55);
    $side = F::tint($color, 0.9);
    $line = F::tint($color, 0.55);

    // Sidebar sections that don't fit beside page 1 continue in the main column.
    $sideOrder = F::sectionOrder($data, ['skills', 'tools', 'languages', 'certifications', 'hobbies', 'references']);
    [$sideSections, $overflow] = F::fitSidebar($data, $s, $sideOrder, 52 - (int) ceil(count($contacts) * 1.6), 34);
    $mainSections = [...F::sectionOrder($data, ['experience', 'education', 'projects']), ...$overflow];

    $titles = ['experience' => 'Work Experience', 'tools' => 'Software'] + F::TITLES;
    $icons = ['experience' => 'briefcase', 'education' => 'education', 'projects' => 'project', 'skills' => 'star', 'tools' => 'tools',
        'languages' => 'language', 'certifications' => 'award', 'hobbies' => 'heart', 'references' => 'people'];
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

        .head-photo { width: 33%; background: {{ $side }}; padding: 18px 0 10px; text-align: center; }
        .photo { width: 118px; height: 118px; border-radius: 59px; border: 4px solid #ffffff; }
        .initials { width: 118px; height: 80px; padding-top: 38px; margin: 0 auto; border-radius: 59px; border: 4px solid #ffffff; background: {{ $dark }}; color: #ffffff; font-family: 'Montserrat', sans-serif; font-size: 36px; font-weight: bold; line-height: 1; }
        .head-band { background: {{ $dark }}; padding: 0 26px; vertical-align: middle; }
        .name { margin: 0; font-family: 'Montserrat', sans-serif; font-size: 27px; font-weight: bold; letter-spacing: 1px; text-transform: uppercase; color: #ffffff; line-height: 1.15; }
        .role { margin-top: 5px; font-family: 'Montserrat Light', sans-serif; font-size: 12px; letter-spacing: 1.5px; text-transform: uppercase; color: {{ F::tint($color, 0.75) }}; }

        /* The sidebar is positioned and the main column is plain block flow, so DomPDF can
           break the main column across pages (it can't split a table row). The grey
           column behind the sidebar is fixed in the PDF so it repeats on every page. */
        .body { position: relative; {{ $pdf ? '' : 'min-height: 940px;' }} }
        .side-bg { position: fixed; top: 0; bottom: 0; left: 0; width: 33%; background: {{ $side }}; z-index: -1; }
        .side { position: absolute; top: 0; left: 0; {{ $pdf ? '' : 'bottom: 0; background: '.$side.';' }} width: 33%; }
        .side-in { padding: 6px 18px 18px; }
        .main { margin-left: 33%; padding: 14px 0 0 22px; }

        .sh { margin: 16px 0 7px; padding-bottom: 4px; border-bottom: 1.5px solid {{ $dark }}; font-family: 'Montserrat', sans-serif; font-size: 11px; font-weight: bold; letter-spacing: 1.5px; text-transform: uppercase; color: {{ $dark }}; }
        .contact { margin-bottom: 6px; }
        .contact td { padding-bottom: 5px; font-size: 8.5px; word-wrap: break-word; }
        .contact .ci { width: 16px; padding-top: 1px; }
        .contact img { width: 9px; height: 9px; }

        .timeline { margin-left: 11px; padding-left: 22px; border-left: 1.5px solid {{ $line }}; }
        .mh { margin: 0 0 8px -35px; padding-top: 12px; font-family: 'Montserrat', sans-serif; font-size: 13px; font-weight: bold; letter-spacing: 1.5px; text-transform: uppercase; color: {{ $dark }}; }
        .mh-first { padding-top: 0; }
        .badge { width: 14px; height: 14px; padding: 5px; margin-right: 9px; border-radius: 12px; background: {{ $dark }}; vertical-align: middle; }
        .mh-rule { margin: -2px 0 8px; height: 1px; background: {{ $line }}; }

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
    </style>
</head>
<body>
@if ($pdf)
    <div class="side-bg"></div>
@endif

<table>
    <tr>
        <td class="head-photo">
            @if ($photo)
                <img src="{{ $photo }}" class="photo" alt="">
            @else
                <div class="initials">{{ F::initials($p['full_name']) }}</div>
            @endif
        </td>
        <td class="head-band">
            <h1 class="name">{{ $p['full_name'] ?: 'Your Name' }}</h1>
            @if ($p['job_title'])
                <div class="role">{{ $p['job_title'] }}</div>
            @endif
        </td>
    </tr>
</table>

<div class="body">
    <div class="side"><div class="side-in">
            @if (count($contacts))
                <div class="sh">Contact</div>
                <table class="contact">
                    @foreach ($contacts as [$type, $value])
                        <tr>
                            <td class="ci"><img src="{{ F::icon($type, $dark) }}" alt=""></td>
                            <td>{{ in_array($type, ['website', 'linkedin', 'github']) ? F::shortUrl($value) : $value }}</td>
                        </tr>
                    @endforeach
                </table>
            @endif

            @foreach ($sideSections as $section)
                @if (count($s[$section]))
                    <div class="sh">{{ $titles[$section] }}</div>
                    @include('resumes.partials.section', ['section' => $section, 'opts' => ['levels' => 'bars', 'plain' => 'list']])
                @endif
            @endforeach
        </div></div>

    <div class="main">
            <div class="timeline">
                @php($first = true)
                @if ($p['summary'])
                    <div class="mh mh-first"><img src="{{ F::icon('user', '#ffffff') }}" class="badge" alt="">Profile</div>
                    <div class="mh-rule"></div>
                    @include('resumes.partials.text', ['text' => $p['summary']])
                    @php($first = false)
                @endif

                @foreach ($mainSections as $section)
                    @if (count($s[$section]))
                        <div class="mh {{ $first ? 'mh-first' : '' }}"><img src="{{ F::icon($icons[$section], '#ffffff') }}" class="badge" alt="">{{ $titles[$section] }}</div>
                        <div class="mh-rule"></div>
                        @include('resumes.partials.section', ['section' => $section, 'opts' => ['levels' => 'bars', 'plain' => 'tags', 'orgFirst' => $section === 'experience']])
                        @php($first = false)
                    @endif
                @endforeach
            </div>
        </div>
</div>

</body>
</html>
