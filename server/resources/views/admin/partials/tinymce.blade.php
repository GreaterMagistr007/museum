<script src="{{ asset('vendor/tinymce/tinymce.min.js') }}"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof tinymce === 'undefined') return;
    tinymce.init({
        selector: 'textarea.wysiwyg',
        language: 'ru',
        language_url: '{{ asset("vendor/tinymce/langs/ru.js") }}',
        height: 400,
        menubar: false,
        plugins: 'lists link image code table',
        toolbar: 'undo redo | blocks | bold italic | bullist numlist | link image table | code | removeformat',
        valid_elements: 'p,br,strong/b,em/i,u,h2,h3,h4,ul,ol,li,a[href|target],img[src|alt|width|height|loading],blockquote,table,thead,tbody,tr,td,th,figure[class],figcaption',
        invalid_elements: 'script,iframe,form,input,object,embed',
        paste_word_valid_elements: 'p,b,strong,i,em,u,h2,h3,h4,ul,ol,li,a[href],table,tr,td,th',
        images_upload_url: '{{ route("admin.upload.image") }}',
        images_upload_credentials: true,
        automatic_uploads: true,
        images_upload_handler: function(blobInfo, progress) {
            return new Promise(function(resolve, reject) {
                var formData = new FormData();
                formData.append('file', blobInfo.blob(), blobInfo.filename());
                fetch('{{ route("admin.upload.image") }}', {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                    body: formData
                })
                .then(function(resp) { return resp.json(); })
                .then(function(json) {
                    if (json.location) resolve(json.location);
                    else reject('Ошибка загрузки');
                })
                .catch(function() { reject('Ошибка сети'); });
            });
        }
    });
});
</script>
