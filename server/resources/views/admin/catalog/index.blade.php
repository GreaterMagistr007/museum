@extends('layouts.admin')

@section('title', $labels['title'])
@section('page-title', $labels['title'])

@section('content')
<div class="admin-form__actions" style="margin-top: 0; margin-bottom: 20px;">
    <a href="{{ route('admin.catalog.create', $type) }}" class="admin-form__button admin-form__button--primary" style="text-decoration: none;">Добавить {{ $labels['singular'] }}</a>
    <button type="button" id="sortToggle" class="admin-form__button admin-form__button--secondary">Сортировка</button>
</div>

<div class="admin-table-wrap">
    <table class="admin-table">
        <thead>
            <tr>
                <th style="width: 80px;">Фото</th>
                <th>Заголовок</th>
                <th>Ссылка</th>
                <th>Статус</th>
                <th>Действия</th>
            </tr>
        </thead>
        <tbody>
            @forelse($items as $item)
            <tr data-id="{{ $item->id }}">
                <td>
                    @if($item->image_url)
                        <img src="{{ $item->image_url }}" alt="{{ $item->title }}" style="width: 60px; height: 40px; object-fit: cover; border-radius: 4px;">
                    @else
                        <span style="color: #999;">&mdash;</span>
                    @endif
                </td>
                <td>{{ $item->title }}</td>
                <td>
                    @if($item->link_url)
                        <a href="{{ $item->link_url }}" target="_blank" rel="noopener" style="word-break: break-all;">{{ Str::limit($item->link_url, 40) }}</a>
                    @else
                        <span style="color: #999;">&mdash;</span>
                    @endif
                </td>
                <td>
                    @if($item->is_published)
                        <span style="color: #2e7d32;">Опубликовано</span>
                    @else
                        <span style="color: #999;">Черновик</span>
                    @endif
                </td>
                <td>
                    <div class="admin-table__actions">
                        <a href="{{ route('admin.catalog.edit', [$type, $item]) }}" class="admin-table__link">Редактировать</a>
                        <form method="POST" action="{{ route('admin.catalog.destroy', [$type, $item]) }}" onsubmit="return confirm('Удалить {{ $labels['singular'] }}?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="admin-table__link admin-table__link--danger" style="background: none; border: none; cursor: pointer; font-family: inherit; font-size: inherit; padding: 0;">Удалить</button>
                        </form>
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="5">Элементов пока нет.</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

<script src="{{ asset('vendor/sortable/Sortable.min.js') }}"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var sortBtn = document.getElementById('sortToggle');
    var tbody = document.querySelector('.admin-table tbody');
    if (!sortBtn || !tbody) return;

    var entity = 'catalog-{{ $type }}';
    var sortable = null;
    sortBtn.addEventListener('click', function() {
        if (sortable) {
            sortable.destroy();
            sortable = null;
            sortBtn.textContent = 'Сортировка';
            sortBtn.classList.remove('admin-form__button--active');
            return;
        }
        sortable = new Sortable(tbody, {
            animation: 150,
            handle: 'tr',
            onEnd: function() {
                var ids = Array.from(tbody.querySelectorAll('tr[data-id]')).map(function(r) { return r.dataset.id; });
                fetch('/admin/reorder/' + entity, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({ ids: ids })
                });
            }
        });
        sortBtn.textContent = 'Завершить сортировку';
        sortBtn.classList.add('admin-form__button--active');
    });
});
</script>
@endsection
