{{-- Experience / education / project items: title + date on one row, organisation + place below, then bullets. --}}
@foreach ($items as $item)
    @php
        $e = \App\Support\ResumeFormat::entry($kind, $item);
        [$main, $second] = $opts['orgFirst'] && $e['org'] !== ''
            ? [$e['org'], $e['title']]
            : [$e['title'] ?: $e['org'], $e['title'] !== '' ? $e['org'] : ''];
    @endphp
    <div class="entry">
        <table class="entry-head"><tr>
            <td class="entry-title">{{ $main }}</td>
            @if ($e['date'] !== '')
                <td class="entry-date">{{ $e['date'] }}</td>
            @endif
        </tr></table>
        @if ($second !== '' || $e['place'] !== '')
            <table class="entry-head entry-sub-row"><tr>
                <td class="entry-sub">{{ $second }}</td>
                @if ($e['place'] !== '')
                    <td class="entry-place">{{ $e['place'] }}</td>
                @endif
            </tr></table>
        @endif
        @if ($e['extra'] !== '')
            <div class="entry-extra">{{ $e['extra'] }}</div>
        @endif
        @if (count($e['lines']))
            <ul class="entry-lines">
                @foreach ($e['lines'] as $line)
                    <li>{{ $line }}</li>
                @endforeach
            </ul>
        @endif
    </div>
@endforeach
