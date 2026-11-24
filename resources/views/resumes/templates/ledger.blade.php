@use('App\Support\ResumeFormat', 'F')
@php
    $s = F::prepare($data);
    extract($s);
    $sections = F::sectionOrder($data);
    $dark = F::shade($color, 0.3);
    $line = F::tint($color, 0.72);
    $titles = F::TITLES;
    $number = 0;
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $p['full_name'] ?: 'Resume' }}</title>
    <style>
        {!! F::fontFaces(['Montserrat', 'Montserrat SemiBold', 'Lato'], $pdf) !!}
        @page { margin: {{ $pdf ? '30px 34px' : '0' }}; }
        body { margin: 0; padding: {{ $pdf ? '0' : '30px 34px' }}; font-family: 'Lato', 'DejaVu Sans', sans-serif; font-size: 9.5px; line-height: 1.35; color: #374151; }
        table { border-collapse: collapse; width: 100%; }
        td { padding: 0; vertical-align: top; }
        p { margin: 0 0 6px; }
        ul { margin: 3px 0 0; padding-left: 13px; }
        li { margin-bottom: 1px; }
        .masthead { border-top: 7px solid {{ $color }}; border-bottom: 2px solid #111827; }
        .identity { padding: 18px 18px 16px 0; vertical-align: middle; }
        .name { margin: 0; font: bold 30px/1.1 'Montserrat', sans-serif; text-transform: uppercase; letter-spacing: -0.5px; color: #111827; }
        .role { margin-top: 8px; font: bold 10px 'Montserrat SemiBold', sans-serif; letter-spacing: 2px; text-transform: uppercase; color: {{ $dark }}; }
        .details { width: 33%; border-left: 1px solid {{ $line }}; padding: 16px 0 14px 16px; font-size: 8.5px; line-height: 1.55; word-wrap: break-word; }
        .details b { color: {{ $dark }}; }
        .photo { width: 54px; height: 54px; margin-bottom: 7px; }
        .section-heading { margin: 15px 0 8px; padding-bottom: 5px; border-bottom: 1px solid #111827; page-break-after: avoid; font: bold 12px 'Montserrat', sans-serif; text-transform: uppercase; color: #111827; }
        .section-number { display: inline-block; width: 28px; color: {{ $color }}; font-size: 10px; }
        .section-body { margin-left: 28px; }
        .entry { margin-bottom: 9px; }
        .entry-head td { padding: 0; }
        .entry-title { font-weight: bold; font-size: 10.5px; color: #111827; }
        .entry-date, .entry-place { width: 1%; white-space: nowrap; text-align: right; font-size: 8.5px; color: #4b5563; }
        .entry-sub { color: {{ $dark }}; }
        .entry-extra, .lite-meta { font-size: 8.5px; color: #6b7280; }
        .lite { margin-bottom: 6px; }
        .lite-title { font-weight: bold; color: #111827; }
        .tags { line-height: 2; }
        .tag { display: inline-block; margin: 0 4px 3px 0; padding: 1px 6px; border: 1px solid {{ $line }}; font-size: 8.5px; }
        .chip-dots { padding-left: 3px; }
        .dot { display: inline-block; width: 4px; height: 4px; margin-left: 1px; vertical-align: middle; }
        .dot-on { background: {{ $color }}; }
        .dot-off { background: {{ $line }}; }
        .entry, .lite { page-break-inside: avoid; }
        [data-page-break="1"] { page-break-before: always; }
    </style>
</head>
<body>
<table class="masthead"><tr>
    <td class="identity">
        <h1 class="name">{{ $p['full_name'] }}</h1>
        @if ($p['job_title'])<div class="role">{{ $p['job_title'] }}</div>@endif
    </td>
    @if ($photo || count($contacts))
        <td class="details">
            @if ($photo)<img src="{{ $photo }}" class="photo" alt="">@endif
            @foreach ($contacts as [$type, $value, $label])
                <div><b>{{ $label }}:</b> {{ in_array($type, ['website', 'linkedin', 'github']) ? F::shortUrl($value) : $value }}</div>
            @endforeach
        </td>
    @endif
</tr></table>

@if ($p['summary'])
    @php($number++)
    <div class="section-heading"><span class="section-number">{{ str_pad($number, 2, '0', STR_PAD_LEFT) }}</span>Profile</div>
    <div class="section-body">@include('resumes.partials.text', ['text' => $p['summary']])</div>
@endif
@foreach ($sections as $section)
    @if (count($s[$section]))
        @php($number++)
        <div class="section-heading" data-page-break="{{ in_array($section, $data['settings']['page_break_before'] ?? []) ? 1 : 0 }}"><span class="section-number">{{ str_pad($number, 2, '0', STR_PAD_LEFT) }}</span>{{ $titles[$section] }}</div>
        <div class="section-body">@include('resumes.partials.section', ['section' => $section, 'opts' => ['levels' => 'chips', 'plain' => 'tags']])</div>
    @endif
@endforeach
</body>
</html>
