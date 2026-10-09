<?php
// Classic (TinyMCE) editor loader. Usage: $extraScripts = editor_scripts('#content');
function editor_scripts($selector) {
    $upload = url('admin/upload_inline.php');
    $csrf = csrf_token();
    return <<<HTML
<script src="https://cdnjs.cloudflare.com/ajax/libs/tinymce/6.8.3/tinymce.min.js" referrerpolicy="origin"></script>
<script>
tinymce.init({
  selector: '$selector',
  height: 520,
  menubar: false,
  branding: false,
  promotion: false,
  plugins: 'lists link image table code media autolink',
  toolbar: 'blocks | bold italic underline | alignleft aligncenter alignright | bullist numlist blockquote | link image media table | removeformat code',
  block_formats: 'Paragraph=p; Heading 2=h2; Heading 3=h3; Heading 4=h4',
  convert_urls: false,
  images_upload_handler: function (blobInfo) {
    return new Promise(function (resolve, reject) {
      var fd = new FormData();
      fd.append('file', blobInfo.blob(), blobInfo.filename());
      fd.append('_csrf', '$csrf');
      fetch('$upload', { method: 'POST', body: fd, credentials: 'same-origin' })
        .then(function (r) { return r.json(); })
        .then(function (j) { j.location ? resolve(j.location) : reject(j.error || 'Upload failed'); })
        .catch(function () { reject('Upload failed'); });
    });
  }
});
</script>
HTML;
}
