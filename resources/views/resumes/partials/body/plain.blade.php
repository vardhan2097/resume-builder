{{-- A list of names without ratings, as wrapping tags, a bullet list or one comma-separated line. --}}
@if (count($names))
    @switch($style)
        @case('list')
            <ul class="plain-list">
                @foreach ($names as $name)
                    <li>{{ $name }}</li>
                @endforeach
            </ul>
            @break

        @case('inline')
            <p class="plain-inline">{{ implode(', ', $names) }}</p>
            @break

        @default
            <div class="tags">
                @foreach ($names as $name)
                    <span class="tag">{{ $name }}</span>
                @endforeach
            </div>
    @endswitch
@endif
