@extends('layouts.admin')

@section('title', 'Экскурсии')
@section('page-title', 'Экскурсии')

@section('content')
<div class="admin-form__actions" style="margin-top: 0; margin-bottom: 20px;">
    <a href="{{ route('admin.excursions.create') }}" class="admin-form__button admin-form__button--primary" style="text-decoration: none;">Добавить экскурсию</a>
    <button type="button" id="sortToggle" class="admin-form__button admin-form__button--secondary">Сортировка</button>
</div>

<div class="admin-table-wrap">
    <table class="admin-table">
        <thead>
            <tr>
                <th>Slug</th>
                <th>Название</th>
                <th>Длительность</th>
                <th>Статус</th>
                <th>Действия</th>
            </tr>
        </thead>
        <tbody>
            @forelse($excursions as $excursion)
            <tr data-id="{{ $excursion->id }}">
                <td>{{ $excursion->slug }}</td>
                <td>{{ $excursion->title }}</td>
                <td>{{ $excursion->duration }}</td>
                <td>
                    @if($excursion->is_published)
                        <span style="color: #2e7d32;">Опубликовано</span>
                    @else
                        <span style="color: #999;">Черновик</span>
                    @endif
                </td>
                <td>
                    <div class="admin-table__actions">
                        <a href="{{ route('admin.excursions.edit', $excursion) }}" class="admin-table__link">Редактировать</a>
                        <form method="POST" action="{{ route('admin.excursions.destroy', $excursion) }}" onsubmit="return confirm('Удалить экскурсию?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="admin-table__link admin-table__link--danger" style="background: none; border: none; cursor: pointer; font-family: inherit; font-size: inherit; padding: 0;">Удалить</button>
                        </form>
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="5">Экскурсий пока нет.</td>
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
                fetch('{{ route("admin.reorder", "excursions") }}', {
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
