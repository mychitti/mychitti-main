<script>
$(document).ready(function() {
    // Fetch saved structure from backend
   const formName = $('#fbFormFields').attr("data-form");
   url = "{{ route('admin.form-builder.get-form') }}" + '/' + formName;
    $.ajax({
        url: url,
        method: 'GET',
        success: function(data) {
            var savedStructure = data.structure;
            if (typeof savedStructure === 'string') {
                savedStructure = JSON.parse(savedStructure);
            }
            if (savedStructure && savedStructure.length) {
                fbFormStructure = savedStructure;
                // Update counter to avoid ID conflicts
                fbSectionCounter = fbFormStructure.length;
                fbFormStructure.forEach(section => {
                    if (section.fields) {
                        fbFieldCounter = Math.max(fbFieldCounter, ...section.fields.map(f => 
                            parseInt(f.id.replace('fb_field_', '')) || 0
                        ));
                    }
                });
                fbRenderBuilder();
                fbRenderPreview();
            }
            $(".form_id").val(data.form_id);
        },
        error: function() {
            console.log('No saved form structure found.');
        }
    });

    $('#fbPreviewForm').on('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        const data = {
            form_structure: fbFormStructure,
            form_data: {}
        };

        for (let [key, value] of formData.entries()) {
            if (key.endsWith('[]')) {
                const baseKey = key.slice(0, -2);
                if (!data.form_data[baseKey]) {
                    data.form_data[baseKey] = [];
                }
                data.form_data[baseKey].push(value);
            } else {
                data.form_data[key] = value;
            }
        }

        toastr.success('Form submitted! Check console for data.');

        $.ajax({
            url: '/api/forms/submit',
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify(data),
            success: function(response) {
                toastr.success('Form submitted successfully!');
            },
            error: function(xhr) {
                toastr.error('Error submitting form');
            }
        });
    });

    $('#saveFormStructure').click(function() {
        const structure = fbFormStructure;
        var form_id = $(".form_id").val();
        $.ajax({
            url: "{{ route('admin.form-builder.save-form') }}",
            type: 'POST',
            data: {
                name: formName,
                form_id: form_id,
                form_name: formName,
                structure: JSON.stringify(structure),
                _token: '{{ csrf_token() }}'
            },
            success: function(res) {
                toastr.success('Additional field structure saved!');
            }
        });
    });
});

let fbFieldCounter = 0;
let fbSectionCounter = 0;
let fbFormStructure = [];
let fbCurrentSection = null;

// Add Section
function fbAddSection() {
    fbSectionCounter++;
    const sectionId = `fb_section_${fbSectionCounter}`;
    
    const sectionData = {
        id: sectionId,
        type: 'section',
        title: `Section ${fbSectionCounter}`,
        fields: []
    };
    
    fbFormStructure.push(sectionData);
    fbCurrentSection = sectionId; // Auto-select the new section
    fbRenderBuilder();
    fbRenderPreview();
}

// Remove Section
function fbRemoveSection(sectionId) {
    fbFormStructure = fbFormStructure.filter(s => s.id !== sectionId);
    if (fbCurrentSection === sectionId) {
        fbCurrentSection = null;
    }
    fbRenderBuilder();
    fbRenderPreview();
}

// Update Section Title
function fbUpdateSectionTitle(sectionId, title) {
    const section = fbFormStructure.find(s => s.id === sectionId);
    if (section) {
        section.title = title;
        fbRenderPreview();
    }
}

// Add Field to Section
function fbAddField(type) {
    if (!fbCurrentSection) {
        toastr.warning('Please select a section first!');
        return;
    }
    
    fbFieldCounter++;
    const fieldId = `fb_field_${fbFieldCounter}`;
    
    const fieldData = {
        id: fieldId,
        type: type,
        label: '',
        name: `field_${fbFieldCounter}`,
        placeholder: '',
        required: false,
        options: ['select', 'radio', 'checkbox-group'].includes(type) ? ['Option 1'] : [],
        attributes: {}
    };
    
    if (type === 'range') {
        fieldData.attributes = { min: 0, max: 100, step: 1 };
    } else if (type === 'file') {
        fieldData.attributes = { accept: '', multiple: false };
    } else if (type === 'textarea') {
        fieldData.attributes = { rows: 4, cols: 50 };
    } else if (type === 'number') {
        fieldData.attributes = { min: '', max: '', step: '' };
    }
    
    const section = fbFormStructure.find(s => s.id === fbCurrentSection);
    if (section) {
        section.fields.push(fieldData);
        fbRenderBuilder();
        fbRenderPreview();
    }
}

// Remove Field
function fbRemoveField(sectionId, fieldId) {
    const section = fbFormStructure.find(s => s.id === sectionId);
    if (section) {
        section.fields = section.fields.filter(f => f.id !== fieldId);
        fbRenderBuilder();
        fbRenderPreview();
    }
}

// Update Field
function fbUpdateField(sectionId, fieldId, property, value) {
    const section = fbFormStructure.find(s => s.id === sectionId);
    if (section) {
        const field = section.fields.find(f => f.id === fieldId);
        if (field) {
            if (property === 'required') {
                field[property] = value;
            } else if (property.startsWith('attr_')) {
                const attrName = property.replace('attr_', '');
                field.attributes[attrName] = value;
            } else {
                field[property] = value;
            }
            fbRenderPreview();
        }
    }
}

// Add Option
function fbAddOption(sectionId, fieldId) {
    const section = fbFormStructure.find(s => s.id === sectionId);
    if (section) {
        const field = section.fields.find(f => f.id === fieldId);
        if (field) {
            field.options.push(`Option ${field.options.length + 1}`);
            fbRenderBuilder();
            fbRenderPreview();
        }
    }
}

// Remove Option
function fbRemoveOption(sectionId, fieldId, optionIndex) {
    const section = fbFormStructure.find(s => s.id === sectionId);
    if (section) {
        const field = section.fields.find(f => f.id === fieldId);
        if (field && field.options.length > 1) {
            field.options.splice(optionIndex, 1);
            fbRenderBuilder();
            fbRenderPreview();
        }
    }
}

// Update Option
function fbUpdateOption(sectionId, fieldId, optionIndex, value) {
    const section = fbFormStructure.find(s => s.id === sectionId);
    if (section) {
        const field = section.fields.find(f => f.id === fieldId);
        if (field) {
            field.options[optionIndex] = value;
            fbRenderPreview();
        }
    }
}

// Select Section
function fbSelectSection(sectionId) {
    fbCurrentSection = sectionId;
    $('.fb-section-box').removeClass('fb-section-active');
    $(`#${sectionId}`).addClass('fb-section-active');
}

// Render Builder
function fbRenderBuilder() {
    $('#fbFormFields').empty();
    
    $.each(fbFormStructure, function(index, section) {
        // Ensure fields array exists
        if (!section.fields) {
            section.fields = [];
        }
        
        let fieldsHtml = '';
        
        $.each(section.fields, function(fIndex, field) {
            let specificFields = '';
            
            if (['select', 'radio', 'checkbox-group'].includes(field.type)) {
                specificFields += `<label class="fb-label-text d-flex justify-content-between">Options: <a type="button" class="text-underline" onclick="fbAddOption('${section.id}', '${field.id}')">+ Add More Option</a></label>`;
                $.each(field.options, function(i, opt) {
                    specificFields += `
                        <div class="fb-option-row">
                            <input type="text" 
                                   class="fb-option-input form-control" 
                                   value="${opt}" 
                                   onchange="fbUpdateOption('${section.id}', '${field.id}', ${i}, this.value)"
                                   placeholder="Option ${i + 1}">
                            <button type="button" 
                                    class="fb-option-remove-btn btn action-btn text-danger" 
                                    onclick="fbRemoveOption('${section.id}', '${field.id}', ${i})"
                                    ${field.options.length === 1 ? 'disabled' : ''}><i class="tio-delete-outlined"></i></button>
                        </div>
                    `;
                });
                specificFields += ``;
            }
            
            if (field.type === 'range') {
                specificFields += `
                    <div class="fb-input-group">
                        <input type="number" class="fb-input-small" 
                               value="${field.attributes.min || 0}" 
                               onchange="fbUpdateField('${section.id}', '${field.id}', 'attr_min', this.value)"
                               placeholder="Min">
                        <input type="number" class="fb-input-small" 
                               value="${field.attributes.max || 100}" 
                               onchange="fbUpdateField('${section.id}', '${field.id}', 'attr_max', this.value)"
                               placeholder="Max">
                        <input type="number" class="fb-input-small" 
                               value="${field.attributes.step || 1}" 
                               onchange="fbUpdateField('${section.id}', '${field.id}', 'attr_step', this.value)"
                               placeholder="Step">
                    </div>
                `;
            }
            
            if (field.type === 'file') {
                specificFields += `
                    <label class="fb-label-text">Accept (file types):</label>
                    <input type="text" class="fb-input-field form-control" 
                           value="${field.attributes.accept || ''}" 
                           onchange="fbUpdateField('${section.id}', '${field.id}', 'attr_accept', this.value)"
                           placeholder="e.g., .jpg,.png,.pdf">
                    <label class="fb-checkbox-inline">
                        <input type="checkbox" 
                               ${field.attributes.multiple ? 'checked' : ''}
                               onchange="fbUpdateField('${section.id}', '${field.id}', 'attr_multiple', this.checked)">
                        Allow Multiple Files
                    </label>
                `;
            }
            
            fieldsHtml += `
                <div class="col-md-4 p-2">
                    <div class="fb-field-box">
                        <div class="fb-field-top">
                            <span class="fb-field-type-label">${field.type} Field</span>
                            <button type="button" class="fb-remove-field-btn btn action-btn btn-outline-danger" onclick="fbRemoveField('${section.id}', '${field.id}')"><i class="tio-delete-outlined"></i></button>
                        </div>
                        ${field.type !== 'hidden' && field.type !== 'button' ? `
                            <label class="fb-label-text">Field Label:</label>
                            <input type="text" 
                                   class="fb-input-field form-control" 
                                   value="${field.label != '' ? field.label : field.type}" 
                                   onchange="fbUpdateField('${section.id}', '${field.id}', 'label', this.value)"
                                   placeholder="Enter field label">
                        ` : ''}
                        ${!['checkbox', 'radio', 'file', 'hidden', 'button', 'checkbox-group'].includes(field.type) ? `
                            <label class="fb-label-text">Placeholder:</label>
                            <input type="text" 
                                   class="fb-input-field form-control" 
                                   value="${field.placeholder}" 
                                   onchange="fbUpdateField('${section.id}', '${field.id}', 'placeholder', this.value)"
                                   placeholder="Enter placeholder text">
                        ` : ''}
                        ${field.type !== 'hidden' && field.type !== 'button' ? `
                            <label class="fb-checkbox-inline">
                                <input type="checkbox" 
                                       ${field.required ? 'checked' : ''}
                                       onchange="fbUpdateField('${section.id}', '${field.id}', 'required', this.checked)">
                                Required Field
                            </label>
                        ` : ''}
                        ${specificFields}
                    </div>
                </div>
            `;
        });
        
        const sectionHtml = `
            <div class="col-md-12 mb-3">
                <div class="fb-section-box ${fbCurrentSection === section.id ? 'fb-section-active' : ''}" 
                     id="${section.id}" 
                     onclick="fbSelectSection('${section.id}')">
                    <div class="fb-section-header">
                        <input type="text" 
                               class="fb-section-title-input" 
                               value="${section.title}" 
                               onchange="fbUpdateSectionTitle('${section.id}', this.value)"
                               onclick="event.stopPropagation()"
                               placeholder="Section Title">
                        <button type="button" 
                                class="fb-remove-section-btn btn-sm btn btn-outline-danger" 
                                onclick="event.stopPropagation(); fbRemoveSection('${section.id}')">
                            Remove Section
                        </button>
                    </div>
                    <div class="row g-0">
                        ${fieldsHtml}
                    </div>
                    ${(!section.fields || section.fields.length === 0) ? '<p class="fb-empty-section">No fields yet. First select this section. Then, click field buttons above to add fields to this section.</p>' : ''}
                </div>
            </div>
        `;
        
        $('#fbFormFields').append(sectionHtml);
    });
}

// Render Preview
function fbRenderPreview() {
    $('#fbPreviewFields').empty();
    
    $.each(fbFormStructure, function(sIndex, section) {
        let sectionFieldsHtml = '';
        
        $.each(section.fields, function(fIndex, field) {
            let fieldHtml = '';
            const requiredAttr = field.required ? 'required' : '';
            
            switch (field.type) {
                case 'text':
                case 'email':
                case 'password':
                case 'number':
                case 'tel':
                case 'url':
                case 'search':
                case 'date':
                case 'time':
                case 'datetime-local':
                case 'month':
                case 'week':
                    const attrs = field.type === 'number' && field.attributes.min ?
                        `min="${field.attributes.min}" max="${field.attributes.max}" step="${field.attributes.step}"` : '';
                    fieldHtml = `
                        <label>${field.label || 'Untitled Field'}${field.required ? ' *' : ''}</label>
                        <input type="${field.type}" 
                               name="${field.name}" 
                               placeholder="${field.placeholder}"
                               ${attrs}
                               ${requiredAttr}>
                    `;
                    break;
                    
                case 'color':
                    fieldHtml = `
                        <label>${field.label || 'Untitled Field'}${field.required ? ' *' : ''}</label>
                        <input type="color" name="${field.name}" ${requiredAttr}>
                    `;
                    break;
                    
                case 'range':
                    const midVal = (parseInt(field.attributes.min || 0) + parseInt(field.attributes.max || 100)) / 2;
                    fieldHtml = `
                        <label>${field.label || 'Untitled Field'}${field.required ? ' *' : ''}</label>
                        <input type="range" 
                               name="${field.name}" 
                               min="${field.attributes.min || 0}" 
                               max="${field.attributes.max || 100}" 
                               step="${field.attributes.step || 1}"
                               class="fb-range-input"
                               ${requiredAttr}>
                        <div class="fb-range-display">
                            <span>${field.attributes.min || 0}</span>
                            <span class="fb-range-val">${midVal}</span>
                            <span>${field.attributes.max || 100}</span>
                        </div>
                    `;
                    break;
                    
                case 'file':
                    fieldHtml = `
                        <label>${field.label || 'Untitled Field'}${field.required ? ' *' : ''}</label>
                        <input type="file" 
                               name="${field.name}" 
                               accept="${field.attributes.accept || ''}"
                               ${field.attributes.multiple ? 'multiple' : ''}
                               ${requiredAttr}>
                    `;
                    break;
                    
                case 'textarea':
                    fieldHtml = `
                        <label>${field.label || 'Untitled Field'}${field.required ? ' *' : ''}</label>
                        <textarea name="${field.name}" 
                                  rows="${field.attributes.rows || 4}" 
                                  placeholder="${field.placeholder}"
                                  ${requiredAttr}></textarea>
                    `;
                    break;
                    
                case 'select':
                    let options = '<option value="">Select an option</option>';
                    $.each(field.options, function(i, opt) {
                        options += `<option value="${opt}">${opt}</option>`;
                    });
                    fieldHtml = `
                        <label>${field.label || 'Untitled Field'}${field.required ? ' *' : ''}</label>
                        <select name="${field.name}" ${requiredAttr}>${options}</select>
                    `;
                    break;
                    
                case 'radio':
                    let radioItems = '';
                    $.each(field.options, function(i, opt) {
                        radioItems += `
                            <div class="fb-radio-item">
                                <input type="radio" 
                                       id="${field.name}_${i}" 
                                       name="${field.name}" 
                                       value="${opt}"
                                       ${requiredAttr}>
                                <label for="${field.name}_${i}">${opt}</label>
                            </div>
                        `;
                    });
                    fieldHtml = `
                        <label>${field.label || 'Untitled Field'}${field.required ? ' *' : ''}</label>
                        <div class="fb-radio-group">${radioItems}</div>
                    `;
                    break;
                    
                case 'checkbox':
                    fieldHtml = `
                        <div class="fb-checkbox-item">
                            <input type="checkbox" 
                                   id="${field.name}" 
                                   name="${field.name}" 
                                   value="1"
                                   ${requiredAttr}>
                            <label for="${field.name}">${field.label || 'Untitled Field'}${field.required ? ' *' : ''}</label>
                        </div>
                    `;
                    break;
                    
                case 'checkbox-group':
                    let checkboxItems = '';
                    $.each(field.options, function(i, opt) {
                        checkboxItems += `
                            <div class="fb-checkbox-item">
                                <input type="checkbox" 
                                       id="${field.name}_${i}" 
                                       name="${field.name}[]" 
                                       value="${opt}">
                                <label for="${field.name}_${i}">${opt}</label>
                            </div>
                        `;
                    });
                    fieldHtml = `
                        <label>${field.label || 'Untitled Field'}${field.required ? ' *' : ''}</label>
                        <div class="fb-checkbox-group">${checkboxItems}</div>
                    `;
                    break;
                    
                case 'hidden':
                    fieldHtml = `
                        <input type="hidden" name="${field.name}" value="">
                        <div class="fb-file-info">Hidden field: ${field.name}</div>
                    `;
                    break;
                    
                case 'button':
                    fieldHtml = `
                        <button type="button" class="fb-button-secondary">${field.label || 'Button'}</button>
                    `;
                    break;
            }
            
            sectionFieldsHtml += `<div class="fb-preview-field col-md-3">${fieldHtml}</div>`;
        });
        
        const sectionPreviewHtml = `
            <div class="fb-preview-section-block">
                <h3 class="fb-preview-section-title">${section.title}</h3>
                <div class="row">
                ${sectionFieldsHtml}
                </div>
            </div>
        `;
        
        $('#fbPreviewFields').append(sectionPreviewHtml);
    });
    
    // Bind range input events
    $(document).on('input', '.fb-range-input', function() {
        $(this).next('.fb-range-display').find('.fb-range-val').text($(this).val());
    });
}
</script>