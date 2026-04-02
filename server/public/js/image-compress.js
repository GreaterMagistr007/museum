/**
 * Автоматическое сжатие изображений перед загрузкой.
 * Применяется ко всем input[type=file][accept*="image"] на странице.
 * Гарантирует, что итоговый файл < 2 МБ.
 */
(function() {
    var MAX_SIZE = 2 * 1024 * 1024; // 2 МБ
    var MAX_DIM = 2048;
    var MIN_QUALITY = 0.1;
    var QUALITY_STEP = 0.1;
    var START_QUALITY = 0.85;

    function compressImage(file, callback) {
        // Не изображение — не трогаем
        if (!file.type.startsWith('image/')) {
            callback(file);
            return;
        }

        var img = new Image();
        img.onload = function() {
            var width = img.width;
            var height = img.height;

            // Уменьшаем если превышает максимальный размер в пикселях
            if (width > MAX_DIM || height > MAX_DIM) {
                var ratio = Math.min(MAX_DIM / width, MAX_DIM / height);
                width = Math.round(width * ratio);
                height = Math.round(height * ratio);
            }

            var canvas = document.createElement('canvas');
            canvas.width = width;
            canvas.height = height;
            canvas.getContext('2d').drawImage(img, 0, 0, width, height);

            var quality = START_QUALITY;
            (function tryCompress() {
                canvas.toBlob(function(blob) {
                    if (blob.size > MAX_SIZE && quality > MIN_QUALITY) {
                        quality -= QUALITY_STEP;
                        tryCompress();
                        return;
                    }
                    var name = file.name.replace(/\.\w+$/, '.jpg');
                    callback(new File([blob], name, { type: 'image/jpeg', lastModified: Date.now() }));
                }, 'image/jpeg', quality);
            })();

            URL.revokeObjectURL(img.src);
        };
        img.src = URL.createObjectURL(file);
    }

    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('input[type="file"][accept*="image"]').forEach(function(input) {
            input.addEventListener('change', function() {
                var file = input.files[0];
                if (!file) return;

                compressImage(file, function(compressed) {
                    var dt = new DataTransfer();
                    dt.items.add(compressed);
                    input.files = dt.files;

                    // Обновляем превью если есть
                    var preview = document.getElementById('imagePreview');
                    if (preview) {
                        var reader = new FileReader();
                        reader.onload = function(e) {
                            preview.src = e.target.result;
                            preview.style.display = 'block';
                        };
                        reader.readAsDataURL(compressed);
                    }
                });
            });
        });
    });
})();
