{{--
    Body of one resume section (no heading), shared by templates and styled by
    each template's CSS. Expects: $section, $data (full data), $s (F::prepare())
    and optional $opts:
      levels: bars | dots | percent | segments   (how 1–5 ratings look)
      plain:  tags | list | inline               (rated sections with ratings off, and hobbies)
      orgFirst: bool                             (company/school before role/degree)
      color: accent colour for dots/bars drawn as text
--}}
@php($opts = ($opts ?? []) + ['levels' => 'bars', 'plain' => 'tags', 'orgFirst' => false])
@switch($section)
    @case('experience')
    @case('education')
    @case('projects')
        @include('resumes.partials.body.entries', ['kind' => $section, 'items' => $s[$section], 'opts' => $opts])
        @break

    @case('skills')
    @case('tools')
    @case('languages')
        @if (\App\Support\ResumeFormat::showLevels($data, $section))
            @include('resumes.partials.body.levels', ['items' => $s[$section], 'opts' => $opts])
        @else
            @include('resumes.partials.body.plain', ['names' => array_column($s[$section], 'name'), 'style' => $opts['plain']])
        @endif
        @break

    @case('hobbies')
        @include('resumes.partials.body.plain', ['names' => $s['hobbies'], 'style' => $opts['hobbiesStyle'] ?? $opts['plain']])
        @break

    @case('certifications')
        @foreach ($s['certifications'] as $cert)
            <div class="lite">
                <div class="lite-title">{{ $cert['name'] }}</div>
                @if (count(\App\Support\ResumeFormat::strings([$cert['issuer'] ?? '', \App\Support\ResumeFormat::month($cert['date'] ?? null)])))
                    <div class="lite-meta">{{ implode(' · ', \App\Support\ResumeFormat::strings([$cert['issuer'] ?? '', \App\Support\ResumeFormat::month($cert['date'] ?? null)])) }}</div>
                @endif
            </div>
        @endforeach
        @break

    @case('references')
        @foreach ($s['references'] as $ref)
            <div class="lite">
                <div class="lite-title">{{ $ref['name'] }}</div>
                @if (count(\App\Support\ResumeFormat::strings([$ref['position'] ?? '', $ref['company'] ?? ''])))
                    <div class="lite-meta">{{ implode(', ', \App\Support\ResumeFormat::strings([$ref['position'] ?? '', $ref['company'] ?? ''])) }}</div>
                @endif
                @foreach (\App\Support\ResumeFormat::strings([$ref['phone'] ?? '', $ref['email'] ?? '']) as $line)
                    <div class="lite-meta">{{ $line }}</div>
                @endforeach
            </div>
        @endforeach
        @break
@endswitch
