    <script>
        // Carry the chosen template's language along with its name — Meta stores a template per
        // language, and sending the wrong one is answered with error 132001.
        document.querySelectorAll('.js-tr-select').forEach(function (select) {
            var sync = function () {
                var opt = select.options[select.selectedIndex];
                var lang = opt ? opt.getAttribute('data-lang') : '';
                var field = select.form.querySelector('.js-tr-lang');
                if (field) { field.value = lang || ''; }
            };
            sync();
            select.addEventListener('change', sync);
        });
    </script>

<script>
    $(document).on('click', '.kn-edit', function () {
        var d = $(this).data();
        $('#knId').val(d.id);
        $('#knType').val(d.type);
        $('#knTitle').val(d.title);
        $('#knContent').val(d.content);
        $('#knEditModal').modal('show');
    });
</script>
