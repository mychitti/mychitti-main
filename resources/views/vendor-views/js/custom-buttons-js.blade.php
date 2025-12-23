<script>
   $(document).ready(function() {
            $('#custom-buttons').on('click', 'button', function() {
                const label = $(this).data('label');
                let inputGroup = '';

                if (label === 'Other') {
                    inputGroup = `
        <div class="form-group custom-field" data-label="${label}">
            <div class="d-flex mb-2">
                <input type="text" class="form-control mr-2" placeholder="Label" name="header_label[]">
                <input type="text" class="form-control mr-2" name="header_field[]">
                <a type="button" class="text-danger remove-field"><i class="tio-delete-outlined"></i></a>
            </div>
        </div>
        `;

                } else {
                    const inputName = label.toLowerCase().replace(/\s+/g, '_'); // e.g., vehicle_no

                    inputGroup = `
        <div class="form-group custom-field" data-label="${label}">
            <label for="${inputName}">${label}</label>
            <div class="d-flex fld_grp">
                <input type="hidden" name="header_label[]" value="${label}" id="${label}">
                <input type="text" class="form-control mr-2" name="header_field[]" id="${inputName}">
                <a type="button" class="text-danger remove-field"><i class="tio-delete-outlined"></i></a>
            </div>
        </div>
        `;
                    // Hide the clicked button
                    $(this).hide();
                }
                console.log(label)

                $('#custom-fields').append(inputGroup);
            });

            //Handle remove
            $('#custom-fields').on('click', '.remove-field', function() {
                console.log('remove')
                const $fieldGroup = $(this).closest('.custom-field');
                const label = $fieldGroup.data('label');

                // Show back the corresponding button
                $('#custom-buttons button').each(function() {
                    if ($(this).data('label') === label) {
                        $(this).show();
                    }
                }); 

                $fieldGroup.remove();
            });

        });</script>