{{-- Partial-форма для создания и редактирования статьи --}}
<div class="admin-card">
    {{-- Кнопка импорта из Word --}}
    <div style="margin-bottom: 20px; padding: 16px; background: #f5ede2; border-radius: 8px; border: 1px solid #d4a472;">
        <strong>Импорт из Word</strong>
        <p style="margin: 6px 0; font-size: 0.9em; color: #5a3a2a;">Загрузите DOCX-файл — содержимое будет вставлено в редактор.</p>
        <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
            <input type="file" id="docxImportFile" accept=".docx" style="flex: 1; min-width: 200px;">
            <button type="button" id="docxImportBtn" class="admin-form__button admin-form__button--secondary">Импортировать</button>
        </div>
        <div id="docxImportStatus" style="margin-top: 8px; font-size: 0.85em;"></div>
    </div>

    <form method="POST"
          action="{{ $article ? route('admin.articles.update', $article) : route('admin.articles.store') }}"
          enctype="multipart/form-data">
        @csrf
        @if($article)
            @method('PUT')
        @endif

        <div class="admin-form__group">
            <label class="admin-form__label" for="slug">Slug</label>
            <input class="admin-form__input" type="text" id="slug" name="slug"
                   value="{{ old('slug', $article?->slug) }}" required>
            <span class="admin-form__hint">Часть URL, например: military-town, junker-school</span>
            @error('slug')
                <span class="admin-form__error">{{ $message }}</span>
            @enderror
        </div>

        <div class="admin-form__group">
            <label class="admin-form__label" for="title">Заголовок</label>
            <input class="admin-form__input" type="text" id="title" name="title"
                   value="{{ old('title', $article?->title) }}" required>
            @error('title')
                <span class="admin-form__error">{{ $message }}</span>
            @enderror
        </div>

        <div class="admin-form__group">
            <label class="admin-form__label" for="parent_id">Родительская статья</label>
            <select class="admin-form__input" id="parent_id" name="parent_id">
                <option value="">— нет (корневая) —</option>
                @foreach($parents as $parent)
                    <option value="{{ $parent->id }}"
                        {{ old('parent_id', $article?->parent_id) == $parent->id ? 'selected' : '' }}>
                        {{ $parent->title }}
                    </option>
                @endforeach
            </select>
            @error('parent_id')
                <span class="admin-form__error">{{ $message }}</span>
            @enderror
        </div>

        <div class="admin-form__group">
            <label class="admin-form__label" for="content">Содержимое</label>
            <textarea class="admin-form__textarea wysiwyg" id="content" name="content" rows="20" required>{{ old('content', $article?->content) }}</textarea>
            @error('content')
                <span class="admin-form__error">{{ $message }}</span>
            @enderror
        </div>

        <div class="admin-form__group">
            <label class="admin-form__label" for="imageInput">Изображение</label>
            @if($article?->image_url)
                <div style="margin-bottom: 10px;">
                    <img id="imagePreview" src="{{ $article->image_url }}" alt="{{ $article->title }}"
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

        <div class="admin-form__group" style="display: flex; gap: 20px; flex-wrap: wrap;">
            <div style="flex: 1; min-width: 150px;">
                <label class="admin-form__label" for="sort_order">Порядок сортировки</label>
                <input class="admin-form__input" type="number" id="sort_order" name="sort_order"
                       value="{{ old('sort_order', $article?->sort_order ?? 0) }}" min="0">
                @error('sort_order')
                    <span class="admin-form__error">{{ $message }}</span>
                @enderror
            </div>
        </div>

        <div class="admin-form__group">
            <label>
                <input type="hidden" name="is_published" value="0">
                <input type="checkbox" name="is_published" value="1"
                       {{ old('is_published', $article?->is_published ?? true) ? 'checked' : '' }}>
                Опубликовано
            </label>
        </div>

        <div class="admin-form__group">
            <label class="admin-form__label" for="meta_title">Meta Title</label>
            <input class="admin-form__input" type="text" id="meta_title" name="meta_title"
                   value="{{ old('meta_title', $article?->meta_title) }}" maxlength="255">
            @error('meta_title')
                <span class="admin-form__error">{{ $message }}</span>
            @enderror
        </div>

        <div class="admin-form__group">
            <label class="admin-form__label" for="meta_description">Meta Description</label>
            <textarea class="admin-form__textarea" id="meta_description" name="meta_description" rows="2" maxlength="500">{{ old('meta_description', $article?->meta_description) }}</textarea>
            @error('meta_description')
                <span class="admin-form__error">{{ $message }}</span>
            @enderror
        </div>

        <div class="admin-form__actions">
            <button type="submit" class="admin-form__button admin-form__button--primary">Сохранить</button>
            <a href="{{ route('admin.articles.index') }}" class="admin-form__button admin-form__button--secondary" style="text-decoration: none;">Отмена</a>
        </div>
    </form>
</div>

@push('scripts')
@include('admin.partials.tinymce')
<script>
(function() {
    // Предпросмотр изображения
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

    // Импорт из DOCX
    var importBtn = document.getElementById('docxImportBtn');
    var importFile = document.getElementById('docxImportFile');
    var importStatus = document.getElementById('docxImportStatus');

    if (importBtn) {
        importBtn.addEventListener('click', function() {
            var file = importFile.files[0];
            if (!file) {
                importStatus.textContent = 'Выберите DOCX-файл.';
                importStatus.style.color = '#c00';
                return;
            }
            if (!file.name.endsWith('.docx')) {
                importStatus.textContent = 'Допустим только формат DOCX.';
                importStatus.style.color = '#c00';
                return;
            }

            importStatus.textContent = 'Обработка...';
            importStatus.style.color = '#5a3a2a';
            importBtn.disabled = true;

            var formData = new FormData();
            formData.append('file', file);
            formData.append('_token', document.querySelector('meta[name="csrf-token"]').content);

            fetch('{{ route("admin.articles.import") }}', {
                method: 'POST',
                body: formData
            })
            .then(function(resp) { return resp.json(); })
            .then(function(data) {
                if (data.error) {
                    importStatus.textContent = 'Ошибка: ' + data.error;
                    importStatus.style.color = '#c00';
                } else {
                    // Вставить HTML в TinyMCE
                    if (typeof tinymce !== 'undefined' && tinymce.get('content')) {
                        tinymce.get('content').setContent(data.html);
                    } else {
                        document.getElementById('content').value = data.html;
                    }
                    importStatus.textContent = 'Содержимое импортировано успешно.';
                    importStatus.style.color = '#2e7d32';
                }
            })
            .catch(function() {
                importStatus.textContent = 'Ошибка сети при импорте.';
                importStatus.style.color = '#c00';
            })
            .finally(function() {
                importBtn.disabled = false;
            });
        });
    }
})();
</script>
@endpush
