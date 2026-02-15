@props(['items' => []])

<div class="breadcrumbs">
    @foreach ($items as $item)
        @if ($loop->last)
            <span>{{ $item['title'] }}</span>
        @else
            <a href="{{ $item['url'] ?? '#' }}">{{ $item['title'] }}</a>&nbsp;/&nbsp;
        @endif
    @endforeach
</div>
