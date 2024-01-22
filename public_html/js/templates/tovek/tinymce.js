// Requires jQuery to be loaded

$( function() {
  tinymce.init({
    selector: 'textarea.editor',
    skin: 'jam',
    icons: 'material',
    plugins: 'autosave code link autolink lists',
    toolbar: 'undo redo | bold italic | numlist bullist',
    // toolbar_mode: 'floating',
    menubar: false,
    tinycomments_mode: 'embedded',
    tinycomments_author: 'Author name',
    relative_urls : false,
    remove_script_host : true,
  });
} );
