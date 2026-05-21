$(document).ready(function() {
var seoCounter = $('[id^="seo_editor_"]').length;
var faqCounter = $('[id^="faq_editor_"]').length;

// Add SEO Content
$('#addSeoContent').on('click', function() {
seoCounter++;
var editorId = 'seo_editor_' + seoCounter;

var seoHtml = `
<div class="seo-content-item border rounded p-3 mb-3" data-index="${seoCounter}">
    <div class="form-group mb-0">
        <label class="form-label">SEO Content (Option ${seoCounter})</label>
        <textarea class="form-control ck_editor" id="${editorId}" placeholder="Enter SEO-friendly content for your service"></textarea>
    </div>
    <button type="button" class="btn btn-sm btn-outline-danger mt-2 remove-seo-content">
        <i class="tio-delete"></i>
    </button>
</div>
`;
$('#seoContentContainer').append(seoHtml);
initCKEditor(editorId);

});

function initCKEditor(elementId) {
const el = document.getElementById(elementId);

if (!el) return;

ClassicEditor.create(el, config)
.then(editor => {
window.ckeditors[elementId] = editor;
})
.catch(error => {
console.error('CKEditor init error:', error);
});
}
// Remove SEO Content
$(document).on('click', '.remove-seo-content', function() {
$(this).closest('.seo-content-item').fadeOut(300, function() {
$(this).remove();
updateSeoLabels();
});
});

// Update SEO Content labels
function updateSeoLabels() {
$('#seoContentContainer .seo-content-item').each(function(index) {
$(this).attr('data-index', index + 1);
$(this).find('.form-label').text('SEO Content (Option ' + (index + 1) + ')');
});
seoCounter = $('#seoContentContainer .seo-content-item').length;
}

// Add FAQ
$('#addFaq').on('click', function() {
faqCounter++;
var editorId = 'faq_editor_' + faqCounter;

var faqHtml = `
<div class="faq-item border rounded p-3 mb-3" data-index="${faqCounter}">
    <div class="form-group mb-0">
        <label class="form-label">FAQ (Option ${faqCounter})</label>
        <textarea class="form-control ck_editor" id="${editorId}" placeholder="Enter FAQ content"></textarea>
    </div>
    <button type="button" class="btn btn-sm btn-outline-danger mt-2 remove-faq">
        <i class="tio-delete"></i>
    </button>
</div>
`;
$('#faqContainer').append(faqHtml);
initCKEditor(editorId);

});

// Remove FAQ
$(document).on('click', '.remove-faq', function() {
const $item = $(this).closest('.faq-item');

destroyCKEditorFromItem($item);

$item.fadeOut(300, function() {
$(this).remove();
updateFaqLabels();
});
});
// Remove SEO Content
$(document).on('click', '.remove-seo-content', function() {
const $item = $(this).closest('.seo-content-item');

destroyCKEditorFromItem($item);

$item.fadeOut(300, function() {
$(this).remove();
updateSeoLabels();
});
});

function destroyCKEditorFromItem(wrapper, textareaSelector = 'textarea') {
const textarea = wrapper.find(textareaSelector)[0];

if (textarea && window.ckeditors && window.ckeditors[textarea.id]) {
window.ckeditors[textarea.id].destroy();
delete window.ckeditors[textarea.id];
}
}

// Update FAQ labels
function updateFaqLabels() {
$('#faqContainer .faq-item').each(function(index) {
$(this).attr('data-index', index + 1);
$(this).find('.form-label').text('FAQ (Option ' + (index + 1) + ')');
});
faqCounter = $('#faqContainer .faq-item').length;
}


$('#product_form').on('submit', function(e) {
$('#submitButton').attr('disabled', true);
e.preventDefault();

let formData = new FormData(this);
let editors2 = window.ckeditorInstances || {};
let ckeditors = window.ckeditors || {};


// Update textareas with current editor content before form submission
document.querySelectorAll('.ck_editor').forEach(editorElement => {
if (ckeditors[editorElement.id]) {
let encodedData = btoa(unescape(encodeURIComponent(ckeditors[editorElement.id]
.getData())));
formData.append(editorElement.id, encodedData);
}
});
// Update textareas with current editor content before form submission
document.querySelectorAll('.editor').forEach(editorElement => {
if (editors2[editorElement.id]) {
let encodedData = btoa(unescape(encodeURIComponent(editors2[editorElement.id]
.getData())));
formData.append(editorElement.id, encodedData);
}
});
// Rest of your form submission code

document.querySelectorAll('#maineditor').forEach(editorElement => {
let editorInstance5 = editors[editorElement.id];
if (editorInstance5) {
let encodedData5 = btoa(unescape(encodeURIComponent(editorInstance5.getData())));
formData.append('specifications', encodedData5);
}
});

$.ajaxSetup({
headers: {
'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
}
});

$.ajax({
url: $('.route_url').val(),
type: 'POST',
data: formData,
cache: false,
contentType: false,
processData: false, 
beforeSend: function() {
$('#loading').show();
},
success: function(data) {
$('#loading').hide();
if (data.errors) {
for (let i = 0; i < data.errors.length; i++) { toastr.error(data.errors[i].message, { CloseButton: true, ProgressBar:
    true }); } } else { toastr.success("{{ translate('messages.product_added_successfully') }}", { CloseButton: true,
    ProgressBar: true }); setTimeout(function() { {{-- location.href = "{{ route('admin.item.list') }}"; --}} }, 1000); } } }); }); });
