@use('App\Support\ResumeFormat', 'F')
@php
    $sideSections = F::sectionOrder($data, ['skills', 'tools', 'languages', 'education', 'certifications', 'hobbies']);
    $p = $data['personal'];
    $skills = F::filled($data['skills'], 'name');
    $tools = F::filled($data['tools'], 'name');
    $languages = F::filled($data['languages'], 'name');
    $hobbies = F::strings($data['hobbies']);
    $experience = F::filled($data['experience'], 'job_title', 'company');
    $education = F::filled($data['education'], 'degree', 'institution');
    $projects = F::filled($data['projects'], 'name');
    $certifications = F::filled($data['certifications'], 'name');
    $references = F::filled($data['references'], 'name');
    $contacts = F::strings([$p['phone'], $p['email'], $p['location'], $p['website'], $p['linkedin'], $p['github']]);
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $p['full_name'] ?: 'Resume' }}</title>
    <style>

        @page { margin: {{ $pdf ? '28px' : '0' }}; }
        body { margin: 0; padding: {{ $pdf ? '0' : '28px' }}; font-family: 'DejaVu Sans', Arial, Helvetica, sans-serif; font-size: 10px; line-height: 1.45; color: #374151; background: #ffffff; }
        table { border-collapse: collapse; width: 100%; }
        td { vertical-align: top; }
        p { margin: 0 0 8px; }
        ul { margin: 0 0 10px; padding-left: 14px; }
        li { margin-bottom: 2px; }

        .header { background: #f8fafc; }
        .header td { padding: 16px; }

        .avatar { width: 84px; height: 58px; padding-top: 26px; border-radius: 42px; background: {{ $color }}; color: #ffffff; font-size: 28px; font-weight: bold; text-align: center; line-height: 1; }
        .avatar-img { width: 84px; height: 84px; border-radius: 42px; }

        .name { margin: 0; font-size: 26px; font-weight: bold; color: #111827; letter-spacing: 0.5px; }
        .role { margin: 2px 0 8px; font-size: 13px; font-weight: bold; color: {{ $color }}; }
        .contact span { margin-right: 14px; font-size: 9px; color: #4b5563; }

        /* The browser lays out independent columns; PDF columns are paginated before rendering. */
        .body { display: grid; grid-template-columns: 31% 69%; margin-top: 10px; }
        .sidebar { min-width: 0; }
        .sidebar-inner { padding-right: 16px; }
        .main { min-width: 0; padding-left: 16px; border-left: 1px solid #e5e7eb; }

        .heading { page-break-after: avoid; margin: 18px 0 2px; font-size: 10px; font-weight: bold; letter-spacing: 2px; text-transform: uppercase; color: #111827; }
        .rule { width: 28px; height: 2px; background: {{ $color }}; margin-bottom: 8px; }

        .level-name { margin-top: 6px; font-size: 9.5px; }
        .bar { height: 5px; margin-top: 2px; background: #e5e7eb; border-radius: 3px; }
        .bar-fill { height: 5px; background: {{ $color }}; border-radius: 3px; }

        .tags { margin-top: 6px; line-height: 1.9; }
        .tag { display: inline-block; padding: 0 6px; margin: 0 3px 3px 0; font-size: 9px; line-height: 1.7; color: #111827; background: #f1f5f9; border: 1px solid #e2e8f0; border-radius: 3px; }

        .side-item { margin: 6px 0 8px; font-size: 9.5px; }
        .side-title { font-weight: bold; color: #111827; }
        .side-meta { margin-top: 1px; font-size: 8.5px; color: #6b7280; }

        .item { margin-bottom: 10px; }
        .link { text-transform: none; letter-spacing: 0; }
        .item-title { font-size: 11.5px; font-weight: bold; color: #111827; }
        .item-meta { margin: 1px 0 4px; font-size: 8.5px; color: #6b7280; text-transform: uppercase; letter-spacing: 0.5px; }

        .entry, .item, .side-item, .lite { page-break-inside: avoid; }
    </style>
</head>
<body>

<table class="header">
    <tr>
        <td style="width: 100px;">
            @if ($photo)
                <img src="{{ $photo }}" class="avatar-img" alt="">
            @else
                <div class="avatar">{{ F::initials($p['full_name']) }}</div>
            @endif
        </td>
        <td style="vertical-align: middle;">
            <h1 class="name">{{ $p['full_name'] ?: 'Your Name' }}</h1>
            @if ($p['job_title'])
                <div class="role">{{ $p['job_title'] }}</div>
            @endif
            <div class="contact">
                @foreach ($contacts as $contact)
                    <span>{{ $contact }}</span>
                @endforeach
            </div>
        </td>
    </tr>
</table>

<div class="body">
        <div class="sidebar"><div class="sidebar-inner">
            @foreach ($sideSections as $section)
                @include('resumes.partials.classic-sidebar-section')
            @endforeach
        </div></div>

        <div class="main">
            @if ($p['summary'])
                <div class="heading">Summary</div>
                <div class="rule"></div>
                @include('resumes.partials.text', ['text' => $p['summary']])
            @endif

            @foreach (F::sectionOrder($data, ['experience', 'projects', 'references']) as $section)
                @switch($section)
                    @case('experience')
                        @if (count($experience))
                            <div class="heading" data-page-break="{{ in_array('experience', $data['settings']['page_break_before'] ?? []) ? 1 : 0 }}">Employment History</div>
                            <div class="rule"></div>
                            @foreach ($experience as $job)
                                <div class="item" data-page-break="{{ !empty($job['page_break_before']) ? 1 : 0 }}">
                                    <div class="item-title">
                                        {{ $job['job_title'] }}@if ($job['job_title'] && $job['company']), @endif{{ $job['company'] }}
                                    </div>
                                    <div class="item-meta">
                                        {{ F::range($job['start_date'] ?? null, $job['end_date'] ?? null, (bool) ($job['current'] ?? false)) }}
                                        @if (! empty($job['location'])) · {{ $job['location'] }} @endif
                                    </div>
                                    @if (count(F::lines($job['description'] ?? '')))
                                        <ul>
                                            @foreach (F::lines($job['description'] ?? '') as $line)
                                                <li>{{ $line }}</li>
                                            @endforeach
                                        </ul>
                                    @endif
                                </div>
                            @endforeach
                        @endif
                        @break

                    @case('projects')
                        @if (count($projects))
                            <div class="heading" data-page-break="{{ in_array('projects', $data['settings']['page_break_before'] ?? []) ? 1 : 0 }}">Projects</div>
                            <div class="rule"></div>
                            @foreach ($projects as $project)
                                <div class="item" data-page-break="{{ !empty($project['page_break_before']) ? 1 : 0 }}">
                                    <div class="item-title">{{ $project['name'] }}</div>
                                    @if (! empty($project['duration']) || ! empty($project['link']))
                                        <div class="item-meta">
                                            {{ $project['duration'] ?? '' }}@if (! empty($project['duration']) && ! empty($project['link'])) · @endif
                                            @if (! empty($project['link']))<span class="link">{{ $project['link'] }}</span>@endif
                                        </div>
                                    @endif
                                    @if (count(F::lines($project['description'] ?? '')))
                                        <ul>
                                            @foreach (F::lines($project['description'] ?? '') as $line)
                                                <li>{{ $line }}</li>
                                            @endforeach
                                        </ul>
                                    @endif
                                </div>
                            @endforeach
                        @endif
                        @break

                    @case('references')
                        @if (count($references))
                            <div class="heading" data-page-break="{{ in_array('references', $data['settings']['page_break_before'] ?? []) ? 1 : 0 }}">References</div>
                            <div class="rule"></div>
                            @foreach ($references as $ref)
                                <div class="item" data-page-break="{{ !empty($ref['page_break_before']) ? 1 : 0 }}">
                                    <div class="item-title">{{ $ref['name'] }}</div>
                                    <div>{{ implode(', ', F::strings([$ref['position'] ?? '', $ref['company'] ?? ''])) }}</div>
                                    <div>{{ implode(' | ', F::strings([$ref['phone'] ?? '', $ref['email'] ?? ''])) }}</div>
                                </div>
                            @endforeach
                        @endif
                        @break
                @endswitch
            @endforeach
        </div>
</div>

</body>
</html>