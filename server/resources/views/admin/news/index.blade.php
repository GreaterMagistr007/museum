@extends('layouts.admin')

@section('title', 'Новости')
@section('page-title', 'Новости')

@section('content')
<div class="admin-form__actions" style="margin-top: 0; margin-bottom: 20px;">
    <a href="{{ route('admin.news.create') }}" class="admin-form__button admin-form__button--primary" style="text-decoration: none;">Добавить новость</a>
</div>

<div class="admin-table-wrap">
    <table class="admin-table">
        <thead>
            <tr>
                <th>Дата</th>
                <th>Заголовок</th>
                <th>Статус</th>
                <th>Действия</th>
            </tr>
        </thead>
        <tbody>
            @forelse($news as $item)
            <tr>
                <td>{{ $item->formatted_date }}</td>
                <td>{{ $item->title }}</td>
                <td>
                    @if($item->is_published)
                        <span style="color: #2e7d32;">Опубликовано</span>
                    @else
                        <span style="color: #999;">Черновик</span>
                    @endif
                </td>
                <td>
                    <div class="admin-table__actions">
                        <a href="{{ route('admin.news.edit', $item) }}" class="admin-table__link">Редактировать</a>
                        <form method="POST" action="{{ route('admin.news.destroy', $item) }}" onsubmit="return confirm('Удалить новость?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="admin-table__link admin-table__link--danger" style="background: none; border: none; cursor: pointer; font-family: inherit; font-size: inherit; padding: 0;">Удалить</button>
                        </form>
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="4">Новостей пока нет.</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

{{ $news->links() }}
@endsection
