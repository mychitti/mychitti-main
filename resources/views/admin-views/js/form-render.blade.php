<script>
    // Paste the Form Renderer Script here
    $(document).ready(function() {
        loadAndRenderForm();
    });
    const formName = $('#dynamicFormContainer').attr("data-form");
    url = "{{ route('admin.form-builder.get-form') }}" + '/' + formName;

    function loadAndRenderForm() {
        $.ajax({
            url: url,
            method: 'GET',
            success: function(data) {
                if (data.structure && data.structure.length > 0) {
                    var formStructure = data.structure;
                    if (typeof formStructure === 'string') {
                        formStructure = JSON.parse(formStructure);
                    }
                    renderDynamicForm(formStructure);
                } else {
                    $('#dynamicFormContainer').html(
                        '<p class="text-center text-muted">No form available</p>');
                }
            },
            error: function() {
                console.log('Error loading form');
                $('#dynamicFormContainer').html(
                '<p class="text-center text-danger">Error loading form</p>');
            }
        });
    }

    function renderDynamicForm(formStructure) {
        const container = $('#dynamicFormContainer');
        container.empty();


        $.each(formStructure, function(sIndex, section) {
            if (!section.fields || section.fields.length === 0) return;

            const sectionDiv = $('<div class="form-section mb-4 card my-2 "></div>');

            if (section.title) {
                sectionDiv.append(`<h4 class="tf-card-title">${section.title}</h4>`);
            }
            const cardBodyDiv = $('<div class="card-body p-3"></div>');
            sectionDiv.append(cardBodyDiv)
            const rowDiv = $('<div class="row g-0"></div>');
            cardBodyDiv.append(rowDiv)


            $.each(section.fields, function(fIndex, field) {
                const fieldHtml = renderField(field);
                if (fieldHtml) {
                    rowDiv.append(fieldHtml);
                }
            });

            container.append(sectionDiv);
        });

        // $('#dynamicForm').on('submit', handleFormSubmit);
    }

    function renderField(field) {
        // Copy the renderField function from the artifact above
        const requiredAttr = field.required ? 'required' : '';
        const requiredLabel = field.required ? '<span class="text-danger">*</span>' : '';
        let fieldHtml = '';

        switch (field.type) {
            case 'text':
            case 'email':
            case 'password':
            case 'number':
            case 'tel':
            case 'url':
            case 'date':
            case 'time':
            case 'datetime-local':
            case 'month':
            case 'week':
                const attrs = field.type === 'number' && field.attributes && field.attributes.min ?
                    `min="${field.attributes.min}" max="${field.attributes.max}" step="${field.attributes.step}"` : '';
                fieldHtml = `
                    <div class="col-md-3 p-1">
                        <label class="tf-label">${field.label || 'Field'} ${requiredLabel}</label>
                        <input type="${field.type}" 
                               name="${field.name}" 
                               class="tf-input"
                               placeholder="${field.placeholder || ''}"
                               ${attrs}
                               ${requiredAttr}>
                    </div>
                `;
                break;

            case 'textarea':
                fieldHtml = `
                    <div class="col-md-3 p-1">
                        <label class="tf-label">${field.label || 'Field'} ${requiredLabel}</label>
                        <textarea name="${field.name}" 
                                  class="tf-input"
                                  rows="${field.attributes?.rows || 4}" 
                                  placeholder="${field.placeholder || ''}"
                                  ${requiredAttr}></textarea>
                    </div>
                `;
                break;

            case 'select':
                let options = '<option value="">Select an option</option>';
                if (field.options && field.options.length) {
                    $.each(field.options, function(i, opt) {
                        options += `<option value="${opt}">${opt}</option>`;
                    });
                }
                fieldHtml = `
                    <div class="col-md-3 p-1">
                        <label class="tf-label">${field.label || 'Field'} ${requiredLabel}</label>
                        <select name="${field.name}" 
                                class="tf-select"
                                ${requiredAttr}>
                            ${options}
                        </select>
                    </div>
                `;
                break;

            case 'checkbox':
                fieldHtml = `
                    <div class="col-md-3 p-1">
                        <div class="form-check">
                            <input class="form-check-input" 
                                   type="checkbox" 
                                   id="${field.name}" 
                                   name="${field.name}" 
                                   value="1"
                                   ${requiredAttr}>
                            <label class="form-check-label" for="${field.name}">
                                ${field.label || 'Field'} ${requiredLabel}
                            </label>
                        </div>
                    </div>
                `;
                break;

                // Add other field types as needed...
        }

        return fieldHtml;
    }

    // function handleFormSubmit(e) {
    //     e.preventDefault();
    //
    //     const formData = new FormData(this);
    //     const data = {};
    //
    //     for (let [key, value] of formData.entries()) {
    //         if (key.endsWith('[]')) {
    //             const baseKey = key.slice(0, -2);
    //             if (!data[baseKey]) {
    //                 data[baseKey] = [];
    //             }
    //             data[baseKey].push(value);
    //         } else {
    //             data[key] = value;
    //         }
    //     }
    //
    //     console.log('Form Data:', data);
    //
    //     $.ajax({
    //         url: "{{ route('admin.task.store') }}",
    //         method: 'POST',
    //         data: {
    //             form_data: data,
    //             _token: '{{ csrf_token() }}'
    //         },
    //         success: function(response) {
    //             toastr.success('Form submitted successfully!');
    //         },
    //         error: function(xhr) {
    //             toastr.error('Error submitting form');
    //             console.error(xhr);
    //         }
    //     });
    // }

    $(document).on('input', '.dynamic-range', function() {
        $(this).siblings('div').find('.range-value').text($(this).val());
    });
</script>
