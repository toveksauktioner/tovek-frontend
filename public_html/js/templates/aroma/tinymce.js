// Requires jQuery to be loaded

$( function() {
  tinymce.init({
    selector: 'textarea.editor',
    skin: 'jam',
    icons: 'material',
    plugins: 'autosave code link autolink lists media table tinydrive imagetools',
    toolbar: 'undo redo | styleselect | bold italic | link unlink | alignleft aligncenter alignright | numlist bullist table | code | restoredraft',
    // toolbar_mode: 'floating',
    menubar: false,
    tinycomments_mode: 'embedded',
    tinycomments_author: 'Author name',
    relative_urls : false,
    remove_script_host : true,
    document_base_url : 'https://tovek.se',
    verify_html: false,
    valid_elements : '*[*]', 
  });
} );
