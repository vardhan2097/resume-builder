{{-- Rated items (1–5). Style per $opts['levels']; chips wrap inline like unrated tags. --}}
@if ($opts['levels'] === 'chips')<div class="tags">@endif
@foreach ($items as $item)
    @php($level = max(1, min(5, (int) $item['level'])))
    @switch($opts['levels'])
        @case('chips')
            <span class="tag tag-lvl">{{ $item['name'] }} <span class="chip-dots">@for ($i = 1; $i <= 5; $i++)<span class="{{ $i <= $level ? 'dot dot-on' : 'dot dot-off' }}"></span>@endfor</span></span>
            @break

        @case('dots')
            <table class="lvl-row"><tr>
                <td class="lvl-name">{{ $item['name'] }}</td>
                <td class="lvl-dots">@for ($i = 1; $i <= 5; $i++)<span class="{{ $i <= $level ? 'dot dot-on' : 'dot dot-off' }}"></span>@endfor</td>
            </tr></table>
            @break

        @case('percent')
            <table class="lvl-row"><tr>
                <td class="lvl-name">{{ $item['name'] }}</td>
                <td class="lvl-track"><div class="bar"><div class="bar-fill" style="width: {{ $level * 20 }}%;"></div></div></td>
                <td class="lvl-pct">{{ $level * 20 }}%</td>
            </tr></table>
            @break

        @case('segments')
            <div class="lvl">
                <div class="lvl-name">{{ $item['name'] }}</div>
                <table class="segments"><tr>
                    @for ($i = 1; $i <= 5; $i++)
                        <td><div class="{{ $i <= $level ? 'seg seg-on' : 'seg seg-off' }}"></div></td>
                    @endfor
                </tr></table>
            </div>
            @break

        @default
            <div class="lvl">
                <div class="lvl-name">{{ $item['name'] }}</div>
                <div class="bar"><div class="bar-fill" style="width: {{ $level * 20 }}%;"></div></div>
            </div>
    @endswitch
@endforeach
@if ($opts['levels'] === 'chips')</div>@endif
