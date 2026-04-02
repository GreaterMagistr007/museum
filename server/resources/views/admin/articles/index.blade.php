@extends('layouts.admin')

@section('title', 'Статьи')
@section('page-title', 'Статьи')

@section('content')
<div class="admin-form__actions" style="margin-top: 0; margin-bottom: 20px;">
    <a href="{{ route('admin.articles.create') }}" class="admin-form__button admin-form__button--primary" style="text-decoration: none;">Добавить статью</a>
    <button type="button" id="sortToggle" class="admin-form__button admin-form__button--secondary">Сортировка</button>
</div>

<div class="admin-table-wrap">
    <table class="admin-table">
        <thead>
            <tr>
                <th>Slug</th>
                <th>Название</th>
                <th>Родитель</th>
                <th>Порядок</th>
                <th>Статус</th>
                <th>Действия</th>
            </tr>
        </thead>
        <tbody>
            @forelse($articles as $article)
            <tr data-id="{{ $article->id }}">
                <td>{{ $article->slug }}</td>
                <td><strong>{{ $article->title }}</strong></td>
                <td>—</td>
                <td>{{ $article->sort_order }}</td>
                <td>
                    @if($article->trashed())
                        <span style="color: #c00;">Удалена</span>
                    @elseif($article->is_published)
                        <span style="color: #2e7d32;">Опубликовано</span>
                    @else
                        <span style="color: #999;">Черновик</span>
                    @endif
                </td>
                <td>
                    <div class="admin-table__actions">
                        @unless($article->trashed())
                        <a href="{{ route('admin.articles.edit', $article) }}" class="admin-table__link">Редактировать</a>
                        <a href="{{ route('article.show', $article) }}" class="admin-table__link" target="_blank">Просмотр</a>
                        <form method="POST" action="{{ route('admin.articles.destroy', $article) }}" onsubmit="return confirm('Удалить статью?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="admin-table__link admin-table__link--danger" style="background: none; border: none; cursor: pointer; font-family: inherit; font-size: inherit; padding: 0;">Удалить</button>
                        </form>
                        @endunless
                    </div>
                </td>
            </tr>
            {{-- Дочерние статьи --}}
            @foreach($article->children as $child)
            <tr data-id="{{ $child->id }}" data-parent="{{ $article->id }}" style="background: #faf7f4;">
                <td style="padding-left: 28px;">↳ {{ $child->slug }}</td>
                <td style="padding-left: 28px;">{{ $child->title }}</td>
                <td>{{ $article->title }}</td>
                <td>{{ $child->sort_order }}</td>
                <td>
                    @if($child->trashed())
                        <span style="color: #c00;">Удалена</span>
                    @elseif($child->is_published)
                        <span style="color: #2e7d32;">Опубликовано</span>
                    @else
                        <span style="color: #999;">Черновик</span>
                    @endif
                </td>
                <td>
                    <div class="admin-table__actions">
                        @unless($child->trashed())
                        <a href="{{ route('admin.articles.edit', $child) }}" class="admin-table__link">Редактировать</a>
                        <a href="{{ route('article.show', $child) }}" class="admin-table__link" target="_blank">Просмотр</a>
                        <form method="POST" action="{{ route('admin.articles.destroy', $child) }}" onsubmit="return confirm('Удалить статью?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="admin-table__link admin-table__link--danger" style="background: none; border: none; cursor: pointer; font-family: inherit; font-size: inherit; padding: 0;">Удалить</button>
                        </form>
                        @endunless
                    </div>
                </td>
            </tr>
            @endforeach
            @empty
            <tr>
                <td colspan="6">Статей пока нет.</td>
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
                fetch('{{ route("admin.reorder", "articles") }}', {
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
