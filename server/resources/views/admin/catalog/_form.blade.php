{{-- Partial-форма для создания и редактирования элемента каталога --}}
<div class="admin-card">
    <form method="POST"
          action="{{ $catalogItem ? route('admin.catalog.update', [$type, $catalogItem]) : route('admin.catalog.store', $type) }}"
          enctype="multipart/form-data">
        @csrf
        @if($catalogItem)
            @method('PUT')
        @endif

        <div class="admin-form__group">
            <label class="admin-form__label" for="title">Заголовок</label>
            <input class="admin-form__input" type="text" id="title" name="title"
                   value="{{ old('title', $catalogItem?->title) }}">
            @error('title')
                <span class="admin-form__error">{{ $message }}</span>
            @enderror
        </div>

        <div class="admin-form__group">
            <label class="admin-form__label" for="description">Описание</label>
            <textarea class="admin-form__textarea wysiwyg" id="description" name="description" rows="6">{{ old('description', $catalogItem?->description) }}</textarea>
            @error('description')
                <span class="admin-form__error">{{ $message }}</span>
            @enderror
        </div>

        <div class="admin-form__group">
            <label class="admin-form__label" for="imageInput">Изображение</label>
            @if($catalogItem?->image_url)
                <div style="margin-bottom: 10px;">
                    <img id="imagePreview" src="{{ $catalogItem->image_url }}" alt="{{ $catalogItem->title }}"
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
            <label class="admin-form__label" for="link_url">Ссылка</label>
            <input class="admin-form__input" type="url" id="link_url" name="link_url"
                   value="{{ old('link_url', $catalogItem?->link_url) }}" placeholder="https://">
            <span class="admin-form__hint">URL для кнопки &laquo;Подробнее&raquo;. Только http:// или https://.</span>
            @error('link_url')
                <span class="admin-form__error">{{ $message }}</span>
            @enderror
        </div>

        <div class="admin-form__group">
            <label>
                <input type="hidden" name="is_published" value="0">
                <input type="checkbox" name="is_published" value="1"
                       {{ old('is_published', $catalogItem?->is_published ?? true) ? 'checked' : '' }}>
                Опубликовано
            </label>
        </div>

        <div class="admin-form__actions">
            <button type="submit" class="admin-form__button admin-form__button--primary">Сохранить</button>
            <a href="{{ route('admin.catalog.index', $type) }}" class="admin-form__button admin-form__button--secondary" style="text-decoration: none;">Отмена</a>
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
