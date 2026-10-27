{{-- Free text with the user's line breaks kept: one <p> per paragraph, <br> between lines. --}}
@foreach (\App\Support\ResumeFormat::paragraphs($text ?? '') as $paragraph)
<p class="{{ $class ?? '' }}">@foreach ($paragraph as $line)@if (! $loop->first)<br>@endif{{ $line }}@endforeach</p>
@endforeach
