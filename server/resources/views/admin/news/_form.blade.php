{{-- Partial-форма для создания и редактирования новости --}}
<div class="admin-card">
    <form method="POST"
          action="{{ $news ? route('admin.news.update', $news) : route('admin.news.store') }}"
          enctype="multipart/form-data">
        @csrf
        @if($news)
            @method('PUT')
        @endif

        <div class="admin-form__group">
            <label class="admin-form__label" for="title">Заголовок</label>
            <input class="admin-form__input" type="text" id="title" name="title"
                   value="{{ old('title', $news?->title) }}" required>
            @error('title')
                <span class="admin-form__error">{{ $message }}</span>
            @enderror
        </div>

        <div class="admin-form__group">
            <label class="admin-form__label" for="published_at">Дата публикации</label>
            <input class="admin-form__input" type="date" id="published_at" name="published_at"
                   value="{{ old('published_at', $news?->published_at?->format('Y-m-d') ?? now()->format('Y-m-d')) }}" required>
            @error('published_at')
                <span class="admin-form__error">{{ $message }}</span>
            @enderror
        </div>

        <div class="admin-form__group">
            <label class="admin-form__label" for="text">Текст</label>
            <textarea class="admin-form__textarea wysiwyg" id="text" name="text" rows="8">{{ old('text', $news?->text) }}</textarea>
            @error('text')
                <span class="admin-form__error">{{ $message }}</span>
            @enderror
        </div>

        <div class="admin-form__group">
            <label class="admin-form__label" for="imageInput">Изображение</label>
            @if($news?->image_url)
                <div style="margin-bottom: 10px;">
                    <img id="imagePreview" src="{{ $news->image_url }}" alt="{{ $news->title }}"
                         style="max-width: 300px; max-height: 200px; border-radius: 6px; display: block;">
                </div>
                <div style="margin-bottom: 10px;">
                    <label>
                        <input type="checkbox" name="remove_image" value="1"> Удалить изображение
                    </label>
                </div>
            @else
                <img id="imagePreview" src="" alt="" style="max-width: 300px; max-height: 200px; border-radius: 6px; display: none; margin-bottom: 10px;">
            @endif
            <input class="admin-form__input" type="file" id="imageInput" name="image" accept="image/jpeg,image/png,image/webp">
            <span class="admin-form__hint">JPEG, PNG или WebP. Максимум 5 MB.</span>
            @error('image')
                <span class="admin-form__error">{{ $message }}</span>
            @enderror
        </div>

        <div class="admin-form__group">
            <label>
                <input type="hidden" name="is_published" value="0">
                <input type="checkbox" name="is_published" value="1"
                       {{ old('is_published', $news?->is_published ?? true) ? 'checked' : '' }}>
                Опубликовано
            </label>
        </div>

        <div class="admin-form__actions">
            <button type="submit" class="admin-form__button admin-form__button--primary">Сохранить</button>
            <a href="{{ route('admin.news.index') }}" class="admin-form__button admin-form__button--secondary" style="text-decoration: none;">Отмена</a>
        </div>
    </form>
</div>

@push('scripts')
@include('admin.partials.tinymce')
<script>
(function() {
    var imageInput = document.getElementById('imageInput');
    var preview = document.getElementById('imagePreview');
    if (imageInput) {
        imageInput.addEventListener('change', function() {
            var file = this.files[0];
            if (file) {
                if (file.size > 5 * 1024 * 1024) {
                    alert('Максимальный размер файла: 5 MB');
                    this.value = '';
                    return;
                }
                var reader = new FileReader();
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                };
                reader.readAsDataURL(file);
            }
        });
    }
})();
</script>
@endpush
