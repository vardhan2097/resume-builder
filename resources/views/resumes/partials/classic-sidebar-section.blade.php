@use('App\Support\ResumeFormat', 'F')
@switch($section)
    @case('skills')
    @case('tools')
    @case('languages')
        @php($items = ['skills' => $skills, 'tools' => $tools, 'languages' => $languages][$section])
        @if (count($items))
            <div class="heading" data-page-break="{{ in_array($section, $data['settings']['page_break_before'] ?? []) ? 1 : 0 }}">{{ ['skills' => 'Skills', 'tools' => 'Software / Tools', 'languages' => 'Languages'][$section] }}</div>
            <div class="rule"></div>
            @if (F::showLevels($data, $section))
                @foreach ($items as $item)
                    <div class="level-name">{{ $item['name'] }}</div>
                    <div class="bar">
                        <div class="bar-fill" style="width: {{ (int) $item['level'] * 20 }}%;"></div>
                    </div>
                @endforeach
            @else
                {{-- No ratings: names wrap as compact tags instead of one per line. --}}
                <div class="tags">
                    @foreach ($items as $item)
                        <span class="tag">{{ $item['name'] }}</span>
                    @endforeach
                </div>
            @endif
        @endif
        @break

    @case('education')
        @if (count($education))
            <div class="heading" data-page-break="{{ in_array($section, $data['settings']['page_break_before'] ?? []) ? 1 : 0 }}">Education</div>
            <div class="rule"></div>
            @foreach ($education as $edu)
                <div class="side-item" data-page-break="{{ !empty($edu['page_break_before']) ? 1 : 0 }}">
                    @if ($edu['degree'])
                        <div class="side-title">{{ $edu['degree'] }}</div>
                    @endif
                    @if ($edu['institution'])
                        <div class="{{ $edu['degree'] ? '' : 'side-title' }}">{{ $edu['institution'] }}</div>
                    @endif
                    @if (count(F::strings([F::range($edu['start_date'] ?? null, $edu['end_date'] ?? null), $edu['score'] ?? '', $edu['location'] ?? ''])))
                        <div class="side-meta">{{ implode(' · ', F::strings([F::range($edu['start_date'] ?? null, $edu['end_date'] ?? null), $edu['score'] ?? '', $edu['location'] ?? ''])) }}</div>
                    @endif
                    @if (count(F::lines($edu['description'] ?? '')))
                        <ul>
                            @foreach (F::lines($edu['description'] ?? '') as $line)
                                <li>{{ $line }}</li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            @endforeach
        @endif
        @break

    @case('certifications')
        @if (count($certifications))
            <div class="heading" data-page-break="{{ in_array($section, $data['settings']['page_break_before'] ?? []) ? 1 : 0 }}">Certifications</div>
            <div class="rule"></div>
            @foreach ($certifications as $cert)
                <div class="side-item" data-page-break="{{ !empty($cert['page_break_before']) ? 1 : 0 }}">
                    <div class="side-title">{{ $cert['name'] }}</div>
                    @if (count(F::strings([$cert['issuer'] ?? '', F::month($cert['date'] ?? null)])))
                        <div class="side-meta">{{ implode(' · ', F::strings([$cert['issuer'] ?? '', F::month($cert['date'] ?? null)])) }}</div>
                    @endif
                </div>
            @endforeach
        @endif
        @break

    @case('hobbies')
        @if (count($hobbies))
            <div class="heading" data-page-break="{{ in_array($section, $data['settings']['page_break_before'] ?? []) ? 1 : 0 }}">Hobbies</div>
            <div class="rule"></div>
            @foreach ($hobbies as $hobby)
                <div>{{ $hobby }}</div>
            @endforeach
        @endif
        @break
@endswitch
