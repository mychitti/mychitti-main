<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/intl-tel-input@18.2.1/build/css/intlTelInput.css">
<script src="https://cdn.jsdelivr.net/npm/intl-tel-input@18.2.1/build/js/intlTelInput.min.js"></script>
<script>
$(document).ready(function(){
    
document.querySelectorAll('.intl_input').forEach(input => {
    initIntlPhone(input);
});
})
</script>
<script>
function initIntlPhone(input) {
    // ❌ prevent double init
    if (input.dataset.intlInit === "1") return;
    input.dataset.intlInit = "1";

    const iti = window.intlTelInput(input, {
        initialCountry: "in",
        utilsScript: "https://mychitti.net/public/assets/admin/intltelinput/js/utils.js",
        autoInsertDialCode: true,
        nationalMode: false,
        formatOnDisplay: false
    });

    // ----------------------------
    // INIT DIAL CODE
    // ----------------------------
    const setDialCode = () => {
        const dialCode = '+' + iti.getSelectedCountryData().dialCode;
        if (!input.value || input.value.trim() === '') {
            input.value = dialCode;
        }
    };
    setDialCode();

    let isUpdating = false;

    // ----------------------------
    // INPUT EVENT
    // ----------------------------
    $(input).on('input', function () {
        if (isUpdating) return;
        isUpdating = true;

        const dialCode = '+' + iti.getSelectedCountryData().dialCode;
        let value = this.value.replace(/\s+/g, '');

        if (!value.startsWith(dialCode)) {
            let numberPart = value.replace(/^\+?\d+/, '').replace(/\D/g, '').substring(0, 10);
            this.value = dialCode + numberPart;
        } else {
            let numberPart = value.substring(dialCode.length).replace(/\D/g, '').substring(0, 10);
            this.value = dialCode + numberPart;
        }

        this.setSelectionRange(this.value.length, this.value.length);
        isUpdating = false;
    });

    // ----------------------------
    // KEYDOWN PROTECTION
    // ----------------------------
    $(input).on('keydown', function (e) {
        const dialCode = '+' + iti.getSelectedCountryData().dialCode;
        const cursorPos = this.selectionStart;

        if ((e.key === 'Backspace' || e.key === 'Delete') && cursorPos <= dialCode.length) {
            e.preventDefault();
        }

        if (e.key === 'ArrowLeft' && cursorPos <= dialCode.length) {
            e.preventDefault();
        }

        if (e.key === 'Home') {
            e.preventDefault();
            this.setSelectionRange(dialCode.length, dialCode.length);
        }

        const numberPart = this.value.substring(dialCode.length).replace(/\D/g, '');
        if (numberPart.length >= 10 && /\d/.test(e.key) && this.selectionStart === this.selectionEnd) {
            e.preventDefault();
        }
    });

    // ----------------------------
    // CLICK FIX
    // ----------------------------
    $(input).on('click mouseup', function () {
        const dialCode = '+' + iti.getSelectedCountryData().dialCode;
        setTimeout(() => {
            if (this.selectionStart < dialCode.length) {
                this.setSelectionRange(dialCode.length, dialCode.length);
            }
        }, 0);
    });

    // ----------------------------
    // COUNTRY CHANGE
    // ----------------------------
    $(input).on('countrychange', function () {
        if (isUpdating) return;
        isUpdating = true;

        const dialCode = '+' + iti.getSelectedCountryData().dialCode;
        const numberPart = this.value.replace(/^\+?\d+/, '').replace(/\D/g, '').substring(0, 10);
        this.value = dialCode + numberPart;

        this.setSelectionRange(this.value.length, this.value.length);
        isUpdating = false;
    });

    // ----------------------------
    // PASTE
    // ----------------------------
    $(input).on('paste', function (e) {
        e.preventDefault();

        const dialCode = '+' + iti.getSelectedCountryData().dialCode;
        let numbers = (e.originalEvent || e).clipboardData
            .getData('text')
            .replace(/\D/g, '');

        if (numbers.startsWith('91')) numbers = numbers.substring(2);
        numbers = numbers.substring(0, 10);

        this.value = dialCode + numbers;
        this.setSelectionRange(this.value.length, this.value.length);
    });
}
</script>
