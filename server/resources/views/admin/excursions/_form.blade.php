{{-- Partial-форма для создания и редактирования экскурсии --}}
<div class="admin-card">
    <form method="POST"
          action="{{ $excursion ? route('admin.excursions.update', $excursion) : route('admin.excursions.store') }}"
          enctype="multipart/form-data">
        @csrf
        @if($excursion)
            @method('PUT')
        @endif

        <div class="admin-form__group">
            <label class="admin-form__label" for="slug">Slug</label>
            <input class="admin-form__input" type="text" id="slug" name="slug"
                   value="{{ old('slug', $excursion?->slug) }}" required>
            <span class="admin-form__hint">Часть URL, например: overview, junker-school</span>
            @error('slug')
                <span class="admin-form__error">{{ $message }}</span>
            @enderror
        </div>

        <div class="admin-form__group">
            <label class="admin-form__label" for="title">Название</label>
            <input class="admin-form__input" type="text" id="title" name="title"
                   value="{{ old('title', $excursion?->title) }}" required>
            @error('title')
                <span class="admin-form__error">{{ $message }}</span>
            @enderror
        </div>

        <div class="admin-form__group">
            <label class="admin-form__label" for="short_title">Короткое название</label>
            <input class="admin-form__input" type="text" id="short_title" name="short_title"
                   value="{{ old('short_title', $excursion?->short_title) }}" maxlength="100">
            <span class="admin-form__hint">Для кнопок на главной странице (до 100 символов)</span>
            @error('short_title')
                <span class="admin-form__error">{{ $message }}</span>
            @enderror
        </div>

        <div class="admin-form__group">
            <label class="admin-form__label" for="short_description">Краткое описание</label>
            <textarea class="admin-form__textarea" id="short_description" name="short_description" rows="3" required>{{ old('short_description', $excursion?->short_description) }}</textarea>
            <span class="admin-form__hint">Отображается в списке экскурсий</span>
            @error('short_description')
                <span class="admin-form__error">{{ $message }}</span>
            @enderror
        </div>

        <div class="admin-form__group" style="display: flex; gap: 20px; flex-wrap: wrap;">
            <div style="flex: 1; min-width: 150px;">
                <label class="admin-form__label" for="duration_minutes">Длительность (мин.)</label>
                <input class="admin-form__input" type="number" id="duration_minutes" name="duration_minutes"
                       value="{{ old('duration_minutes', $excursion?->duration_minutes) }}" min="1" max="480" required>
                @error('duration_minutes')
                    <span class="admin-form__error">{{ $message }}</span>
                @enderror
            </div>
            <div style="flex: 1; min-width: 150px;">
                <label class="admin-form__label" for="group_size_min">Мин. размер группы</label>
                <input class="admin-form__input" type="number" id="group_size_min" name="group_size_min"
                       value="{{ old('group_size_min', $excursion?->group_size_min ?? 5) }}" min="1" required>
                @error('group_size_min')
                    <span class="admin-form__error">{{ $message }}</span>
                @enderror
            </div>
            <div style="flex: 1; min-width: 150px;">
                <label class="admin-form__label" for="group_size_max">Макс. размер группы</label>
                <input class="admin-form__input" type="number" id="group_size_max" name="group_size_max"
                       value="{{ old('group_size_max', $excursion?->group_size_max ?? 25) }}" min="1" required>
                @error('group_size_max')
                    <span class="admin-form__error">{{ $message }}</span>
                @enderror
            </div>
        </div>

        <div class="admin-form__group">
            <label class="admin-form__label" for="description">Описание экскурсии</label>
            <textarea class="admin-form__textarea wysiwyg" id="description" name="description" rows="10" required>{{ old('description', $excursion?->description) }}</textarea>
            @error('description')
                <span class="admin-form__error">{{ $message }}</span>
            @enderror
        </div>

        <div class="admin-form__group">
            <label class="admin-form__label" for="what_you_see">Что вы увидите</label>
            <textarea class="admin-form__textarea wysiwyg" id="what_you_see" name="what_you_see" rows="8">{{ old('what_you_see', $excursion?->what_you_see) }}</textarea>
            @error('what_you_see')
                <span class="admin-form__error">{{ $message }}</span>
            @enderror
        </div>

        <div class="admin-form__group">
            <label class="admin-form__label" for="interesting_facts">Интересные факты</label>
            <textarea class="admin-form__textarea wysiwyg" id="interesting_facts" name="interesting_facts" rows="8">{{ old('interesting_facts', $excursion?->interesting_facts) }}</textarea>
            @error('interesting_facts')
                <span class="admin-form__error">{{ $message }}</span>
            @enderror
        </div>

        <div class="admin-form__group">
            <label class="admin-form__label" for="imageInput">Изображение</label>
            @if($excursion?->image_url)
                <div style="margin-bottom: 10px;">
                    <img id="imagePreview" src="{{ $excursion->image_url }}" alt="{{ $excursion->title }}"
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
                       {{ old('is_published', $excursion?->is_published ?? true) ? 'checked' : '' }}>
                Опубликовано
            </label>
        </div>

        <div class="admin-form__group">
            <label class="admin-form__label" for="meta_title">Meta Title</label>
            <input class="admin-form__input" type="text" id="meta_title" name="meta_title"
                   value="{{ old('meta_title', $excursion?->meta_title) }}" maxlength="255">
            @error('meta_title')
                <span class="admin-form__error">{{ $message }}</span>
            @enderror
        </div>

        <div class="admin-form__group">
            <label class="admin-form__label" for="meta_description">Meta Description</label>
            <textarea class="admin-form__textarea" id="meta_description" name="meta_description" rows="2" maxlength="500">{{ old('meta_description', $excursion?->meta_description) }}</textarea>
            @error('meta_description')
                <span class="admin-form__error">{{ $message }}</span>
            @enderror
        </div>

        <div class="admin-form__actions">
            <button type="submit" class="admin-form__button admin-form__button--primary">Сохранить</button>
            <a href="{{ route('admin.excursions.index') }}" class="admin-form__button admin-form__button--secondary" style="text-decoration: none;">Отмена</a>
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
