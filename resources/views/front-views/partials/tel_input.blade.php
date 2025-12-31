<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/intl-tel-input@18.2.1/build/css/intlTelInput.css">
<script src="https://cdn.jsdelivr.net/npm/intl-tel-input@18.2.1/build/js/intlTelInput.min.js"></script>

<script>
const inputs = document.querySelectorAll('input[type="tel"]');
        inputs.forEach(input => {
            const iti = window.intlTelInput(input, {
                initialCountry: "in",
                utilsScript: "https://mychitti.net/public/assets/admin/intltelinput/js/utils.js",
                autoInsertDialCode: true,
                nationalMode: false,
                formatOnDisplay: false
            });

            // Initialize dial code
            const initDialCode = () => {
                const dialCode = '+' + iti.getSelectedCountryData().dialCode;
                if (!input.value || input.value.trim() === '') {
                    input.value = dialCode;
                }
            };

            initDialCode();

            // Protect dial code from deletion
            let isUpdating = false;

            $(input).on('input', function(e) {
                if (isUpdating) return;

                isUpdating = true;

                const dialCode = '+' + iti.getSelectedCountryData().dialCode;
                let currentValue = this.value;

                // Remove all spaces
                currentValue = currentValue.replace(/\s+/g, '');

                // If dial code is missing or corrupted, restore it
                if (!currentValue.startsWith(dialCode)) {
                    // Extract only the numeric part after any dial code
                    let numberPart = currentValue.replace(/^\+?\d+/, '');
                    // Remove non-numeric characters
                    numberPart = numberPart.replace(/\D/g, '');
                    // Limit to 10 digits
                    numberPart = numberPart.substring(0, 10);
                    this.value = dialCode + numberPart;

                    // Set cursor at end
                    const cursorPos = this.value.length;
                    this.setSelectionRange(cursorPos, cursorPos);
                } else {
                    // Extract number part after dial code
                    let numberPart = currentValue.substring(dialCode.length);
                    // Remove non-numeric characters
                    numberPart = numberPart.replace(/\D/g, '');
                    // Limit to 10 digits
                    numberPart = numberPart.substring(0, 10);

                    const newValue = dialCode + numberPart;
                    const cursorOffset = this.value.length - currentValue.length;
                    const newCursorPos = Math.max(dialCode.length, this.selectionStart + cursorOffset);

                    this.value = newValue;
                    this.setSelectionRange(newCursorPos, newCursorPos);
                }

                isUpdating = false;
            });

            // Prevent backspace/delete from removing dial code
            $(input).on('keydown', function(e) {
                const dialCode = '+' + iti.getSelectedCountryData().dialCode;
                const cursorPos = this.selectionStart;

                // If backspace or delete key
                if (e.key === 'Backspace' || e.key === 'Delete') {
                    // Prevent if cursor is in dial code area
                    if (cursorPos <= dialCode.length) {
                        e.preventDefault();
                        return false;
                    }
                }

                // Prevent moving cursor into dial code area with arrow keys
                if (e.key === 'ArrowLeft' && cursorPos <= dialCode.length) {
                    e.preventDefault();
                    return false;
                }

                // Prevent Home key from going before dial code
                if (e.key === 'Home') {
                    e.preventDefault();
                    this.setSelectionRange(dialCode.length, dialCode.length);
                    return false;
                }

                // Prevent typing if already at 10 digit limit
                const numberPart = this.value.substring(dialCode.length).replace(/\D/g, '');
                if (numberPart.length >= 10 && e.key.match(/[0-9]/) && this.selectionStart === this
                    .selectionEnd) {
                    e.preventDefault();
                    return false;
                }
            });

            // Handle mouse clicks - prevent cursor placement in dial code
            $(input).on('click mouseup', function(e) {
                const dialCode = '+' + iti.getSelectedCountryData().dialCode;

                setTimeout(() => {
                    if (this.selectionStart < dialCode.length) {
                        this.setSelectionRange(dialCode.length, dialCode.length);
                    }
                }, 0);
            });

            // Handle country change
            $(input).on('countrychange', function() {
                if (isUpdating) return;

                isUpdating = true;

                const newDialCode = '+' + iti.getSelectedCountryData().dialCode;
                const oldValue = this.value;

                // Extract number part (remove old dial code and spaces)
                const numberPart = oldValue.replace(/^\+?\d+\s*/, '').replace(/\D/g, '').substring(0, 10);

                // Set new value with new dial code (no spaces)
                this.value = newDialCode + numberPart;

                // Set cursor at end
                const cursorPos = this.value.length;
                this.setSelectionRange(cursorPos, cursorPos);

                isUpdating = false;
            });

            // Handle focus - ensure dial code is present
            $(input).on('focus', function() {
                const dialCode = '+' + iti.getSelectedCountryData().dialCode;

                // If empty or just has a +, set the current country's dial code
                if (!this.value || this.value.trim() === '' || this.value.trim() === '+') {
                    this.value = dialCode;
                }
                // If has a different dial code, keep it as is
                else if (!this.value.startsWith(dialCode)) {
                    // Value already has a different country code, don't change it
                    const existingDialCode = this.value.match(/^\+\d+/);
                    if (existingDialCode) {
                        // Keep existing dial code
                    } else {
                        // No valid dial code found, set current one
                        this.value = dialCode;
                    }
                }

                // Move cursor after dial code
                setTimeout(() => {
                    const currentDialCode = this.value.match(/^\+\d+/);
                    if (currentDialCode) {
                        const dialCodeLength = currentDialCode[0].length;
                        if (this.selectionStart < dialCodeLength) {
                            this.setSelectionRange(dialCodeLength, dialCodeLength);
                        }
                    }
                }, 0);
            });

            // Handle blur - clean up if needed
            $(input).on('blur', function() {
                const dialCode = '+' + iti.getSelectedCountryData().dialCode;

                // Remove any spaces
                this.value = this.value.replace(/\s+/g, '');

                if (this.value.trim() === dialCode || this.value.trim() === '') {
                    // If only dial code remains or empty, keep the dial code
                    this.value = dialCode;
                }
            });

            // Handle paste event
            $(input).on('paste', function(e) {
                e.preventDefault();

                const dialCode = '+' + iti.getSelectedCountryData().dialCode;
                const pastedText = (e.originalEvent || e).clipboardData.getData('text/plain');

                // Extract only numbers from pasted text
                let numbers = pastedText.replace(/\D/g, '');

                // Remove country code if pasted with it
                if (numbers.startsWith('91')) {
                    numbers = numbers.substring(2);
                }

                // Limit to 10 digits
                numbers = numbers.substring(0, 10);

                // Set the value
                this.value = dialCode + numbers;

                // Set cursor at end
                this.setSelectionRange(this.value.length, this.value.length);
            });
        });</script>