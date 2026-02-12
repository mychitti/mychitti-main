<script>
    $(document).on('click', '.preview-template', function() {

        var url = $(this).data('preview-url');

        $('#templatePreviewFrame').attr('src', url);

    });

    // clear iframe when modal closed
    $('#templatePreviewModal').on('hidden.bs.modal', function() {
        $('#templatePreviewFrame').attr('src', '');
    });
    $(document).on('change', '.select_theme', function() {

        $('.select_theme').closest('label').removeClass('active focus');

        $(this).closest('label').addClass('active focus');

    });
</script>
