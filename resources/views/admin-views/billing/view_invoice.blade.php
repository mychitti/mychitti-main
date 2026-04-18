<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Document</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
        <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css">

    <style>
    body{
        background: #eff6ff;
    }
        .invoice-actions {
            display: flex;
            gap: 12px;
            justify-content: flex-end;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .btn-designer {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 10px 18px;
            font-size: 16px;
            font-weight: 500;
            border: none;
            border-radius: 30px;
            text-decoration: none;
            color: white;
            background: linear-gradient(135deg, #667eea, #764ba2);
            box-shadow: 0 4px 14px rgba(0, 0, 0, 0.2);
            transition: all 0.3s ease-in-out;
            cursor: pointer;
        }

        .btn-designer:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.25);
        }

        .btn-designer.email {
            background: linear-gradient(135deg, #11998e, #38ef7d);
        }

        .icon {
            width: 20px;
            height: 20px;
        }
    </style>

</head>

<body>
    <div class="my-2 container d-flex flex-column align-items-center">
        <div class="invoice-actions">
            <a href="{{ asset('storage/app/public/invoice') . '/' . $invoice->pdf }}" download class="btn-designer download text-white">
                <svg xmlns="http://www.w3.org/2000/svg" class="icon" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 10l5 5m0 0l5-5m-5 5V4" />
                </svg>
                Download
            </a>

            @if($invoice->pdf)
            <button type="button" class="btn-designer email" data-bs-toggle="modal" data-bs-target="#sendInvoiceEmailModal">
                <i class="fas fa-envelope"></i>
                Send Email
            </button>
            @endif

            <button onclick="sharePDF()" class="btn-designer share">
                <i class="fas fa-share-nodes"></i>
                Share
            </button>
            <button onclick="printInvoice()" class="btn-designer print">
                <svg xmlns="http://www.w3.org/2000/svg" class="icon" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M6 9V2h12v7M6 18h12a2 2 0 002-2v-5H4v5a2 2 0 002 2zM6 14h.01" />
                </svg>
                Print
            </button>
        </div>

       <iframe id="invoiceFrame"
        src="{{ asset('storage/app/public/invoice') . '/' . $invoice->pdf }}#toolbar=0&navpanes=0"
      width="750" height="1062"></iframe>
    </div>

    <!-- Send Invoice Email Modal -->
    <div class="modal fade" id="sendInvoiceEmailModal" tabindex="-1" aria-labelledby="sendInvoiceEmailModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="sendInvoiceEmailModalLabel">Send Invoice Email</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="sendInvoiceEmailForm" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="invoice_id" value="{{ $invoice->id }}">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Select Customer <span class="text-danger">*</span></label>
                                    <select name="customer_id" id="inv_modal_customer_select" class="form-control" style="width:100%"></select>
                                    <input type="hidden" name="recipient_email" id="inv_recipient_email">
                                </div>
                                <div class="mb-3" id="inv_email_input_wrapper" style="display:none;">
                                    <label class="form-label">Customer Email <span class="text-danger">*</span></label>
                                    <input type="email" name="customer_email_new" id="inv_customer_email_new" class="form-control" placeholder="Enter customer email">
                                    <small class="text-muted">This email will be saved to the customer record.</small>
                                </div>
                                <small id="inv_email_check_msg" class="form-text"></small>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Email Subject <span class="text-danger">*</span></label>
                                    <input type="text" name="email_subject" id="inv_email_subject" class="form-control" required
                                        value="Invoice {{ $invoice->invoice_id }}">
                                </div>
                            </div>
                        </div>

                        <!-- Template Picker -->
                        <div class="mb-3">
                            <label class="d-block mb-2">Email Template <span class="text-danger">*</span></label>
                            <div class="d-flex" style="gap:12px;">
                                @foreach([1=>'Classic',2=>'Modern',3=>'Executive'] as $tid => $tlabel)
                                <label class="inv-tpl-card {{ ($invoice_email_settings['email_template'] ?? '1') == $tid ? 'active' : '' }}" data-tpl="{{ $tid }}"
                                    style="cursor:pointer;border:2px solid {{ ($invoice_email_settings['email_template'] ?? '1') == $tid ? '#006161' : '#dee2e6' }};border-radius:8px;padding:10px 14px;flex:1;text-align:center;transition:all .2s;">
                                    <input type="radio" name="email_template" value="{{ $tid }}" style="display:none;" {{ ($invoice_email_settings['email_template'] ?? '1') == $tid ? 'checked' : '' }}>
                                    <div style="font-weight:600;font-size:13px;color:#333;">Template {{ $tid }}</div>
                                    <div style="font-size:11px;color:#888;margin-top:2px;">{{ $tlabel }}</div>
                                    <div class="inv-tpl-thumb mt-2" style="height:36px;border-radius:4px;background:{{ $tid==3 ? '#1a1a2e' : ($invoice_email_settings['theme_color'] ?? '#a51d1d') }};opacity:{{ $tid==3 ? '1' : '0.85' }};"></div>
                                </label>
                                @endforeach
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Theme Color</label>
                                    <input type="color" name="theme_color" id="inv_theme_color" class="form-control"
                                        value="{{ $invoice_email_settings['theme_color'] ?? '#a51d1d' }}" style="height:40px;padding:3px;">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3 d-flex align-items-end" style="padding-bottom:10px;">
                                    <label>
                                        <input type="checkbox" name="save_as_default" value="1"> Save as default template
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Email Greeting</label>
                            <input type="text" name="email_greeting" id="inv_email_greeting" class="form-control"
                                value="{{ $invoice_email_settings['greeting'] ?? 'Dear {client_name},' }}" placeholder="e.g. Dear {client_name},">
                            <small class="text-muted">Use {client_name} for client name, {invoice_id} for invoice number</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email Body <span class="text-danger">*</span></label>
                            <textarea name="email_body" id="inv_email_body" class="form-control" rows="6" required
                                placeholder="Write your email body here...">{{ $invoice_email_settings['body'] ?? "Please find attached the invoice for the services provided. We hope everything meets your expectations.\n\nKindly review the attached invoice and let us know if you have any questions.\n\nWe look forward to your continued association." }}</textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Footer Text</label>
                            <input type="text" name="email_footer" id="inv_email_footer" class="form-control"
                                value="{{ $invoice_email_settings['footer'] ?? 'Thank you for your business!' }}" placeholder="Footer text">
                        </div>

                        <!-- Email Preview -->
                        <div class="card mt-3">
                            <div class="card-header py-2 d-flex justify-content-between align-items-center">
                                <h6 class="mb-0">Email Preview</h6>
                                <button type="button" class="btn btn-sm btn-outline-primary" id="invRefreshPreview">Refresh Preview</button>
                            </div>
                            <div class="card-body p-0">
                                <div id="invEmailPreviewContainer" style="overflow-y:auto; padding:10px; background:#f9f9f9;"></div>
                            </div>
                        </div>

                        <div class="mb-3 mt-3">
                            <label class="form-label"><i class="fas fa-paperclip"></i> Attach Requirement Document <span class="text-muted">(optional — PDF, Word, Image, max 10MB)</span></label>
                            <input type="file" name="requirement_doc" id="inv_requirement_doc" class="form-control"
                                accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
                            <small class="text-muted">The invoice PDF will also be attached automatically.</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary" id="invSendEmailBtn">
                            <i class="fas fa-paper-plane"></i> Send Email
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- End Send Invoice Email Modal -->

    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"
        integrity="sha384-IQsoLXl5PILFhosVNubq5LC7Qb9DXgDA9i+tQ8Zj3iwWAwPtgFTxbJ8NT4GN1R8p" crossorigin="anonymous">
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.min.js"
        integrity="sha384-cVKIPhGWiC2Al4u+LWgxfKTRIcfu0JTxR+EQDz/bgldoEyl4H0zUF0QKbrJ0EcQF" crossorigin="anonymous">
    </script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>
    <script>
        function sharePDF() {
            const pdfUrl = '{{ asset('storage/app/public/invoice') . '/' . $invoice->pdf }}';
            if (navigator.share) {
                navigator.share({
                    title: 'Invoice',
                    text: 'Check out this invoice',
                    url: pdfUrl
                }).catch(err => console.error('Share failed:', err));
            } else {
                alert('Sharing not supported. You can manually copy this URL:\n' + pdfUrl);
            }
        }

        function printInvoice() {
            const iframe = document.getElementById('invoiceFrame');
            iframe.focus();
            iframe.contentWindow.print();
        }

        // ---- Send Invoice Email Modal JS ----
        $(function() {
            // Initialize Select2 for customer search
            $('#inv_modal_customer_select').select2({
                dropdownParent: $('#sendInvoiceEmailModal'),
                placeholder: 'Search customer by name or phone',
                minimumInputLength: 3,
                ajax: {
                    url: "{{ route('admin.client.get-matches') }}",
                    dataType: 'json',
                    delay: 250,
                    data: function(params) { return { q: params.term }; },
                    processResults: function(data) {
                        return {
                            results: $.map(data, function(item) {
                                return { id: item.id, text: item.f_name + ' (' + item.phone + ')' };
                            })
                        };
                    },
                    cache: true
                }
            });

            // Pre-populate with invoice's own customer on modal open
            $('#sendInvoiceEmailModal').on('show.bs.modal', function() {
                let customerId = {{ $invoice->bill_to ?? 'null' }};
                let billToType = '{{ $invoice->bill_to_type ?? '' }}';
                if (customerId && billToType !== 'vendor') {
                    $.ajax({
                        url: '{{ route("admin.quotation.check-email") }}',
                        type: 'GET',
                        data: { customer_id: customerId },
                        success: function(resp) {
                            if (resp.name) {
                                let option = new Option(resp.name + (resp.phone ? ' (' + resp.phone + ')' : ''), customerId, true, true);
                                $('#inv_modal_customer_select').append(option).trigger('change');
                            }
                        }
                    });
                }
                invUpdatePreview();
            });

            // When customer is selected, fetch/check email
            $('#inv_modal_customer_select').on('change', function() {
                let customerId = $(this).val();
                if (!customerId) {
                    $('#inv_email_input_wrapper').hide();
                    $('#inv_email_check_msg').html('');
                    $('#inv_recipient_email').val('');
                    return;
                }
                $.ajax({
                    url: '{{ route("admin.quotation.check-email") }}',
                    type: 'GET',
                    data: { customer_id: customerId },
                    success: function(resp) {
                        if (resp.has_email) {
                            $('#inv_recipient_email').val(resp.email);
                            $('#inv_email_input_wrapper').hide();
                            $('#inv_customer_email_new').removeAttr('required');
                            $('#inv_email_check_msg').html('<span class="text-success"><i class="fas fa-check-circle"></i> Email: ' + resp.email + '</span>');
                        } else {
                            $('#inv_recipient_email').val('');
                            $('#inv_email_input_wrapper').show();
                            $('#inv_customer_email_new').attr('required', true).val('');
                            $('#inv_email_check_msg').html('<span class="text-warning"><i class="fas fa-info-circle"></i> No email on record. Please enter one below.</span>');
                        }
                        window._invClientName = resp.name || 'Customer';
                        invUpdatePreview();
                    }
                });
            });

            $('#inv_customer_email_new').on('input', function() {
                $('#inv_recipient_email').val($(this).val());
            });

            // Template card selection
            $(document).on('click', '.inv-tpl-card', function() {
                $('.inv-tpl-card').css('border-color', '#dee2e6');
                $(this).css('border-color', '#006161');
                $(this).find('input[type=radio]').prop('checked', true);
                invUpdatePreview();
            });

            $('#invRefreshPreview, #inv_theme_color').on('change click', function() {
                $('.inv-tpl-thumb').not('[data-dark]').css('background', $('#inv_theme_color').val());
                invUpdatePreview();
            });

            $('#inv_email_greeting, #inv_email_body, #inv_email_footer, #inv_email_subject').on('input', function() {
                clearTimeout(window._invPreviewTimeout);
                window._invPreviewTimeout = setTimeout(invUpdatePreview, 300);
            });

            function invUpdatePreview() {
                let themeColor  = $('#inv_theme_color').val();
                let greeting    = $('#inv_email_greeting').val();
                let body        = $('#inv_email_body').val().replace(/\n/g, '<br>');
                let footer      = $('#inv_email_footer').val();
                let subject     = $('#inv_email_subject').val();
                let clientName  = window._invClientName || 'Customer';
                let invoiceId   = '{{ $invoice->invoice_id }}';
                let storeName   = @json(\App\Models\BusinessSetting::where('key', 'business_name')->first()?->value ?? '');

                greeting = greeting.replace('{client_name}', clientName).replace('{invoice_id}', invoiceId);
                body     = body.replace(/{client_name}/g, clientName).replace(/{invoice_id}/g, invoiceId);

                let html = `
                    <div style="font-family:Arial,sans-serif; max-width:500px; margin:0 auto; background:#fff; border-radius:8px; overflow:hidden; box-shadow:0 2px 8px rgba(0,0,0,0.1);">
                        <div style="background:${themeColor}; color:#fff; text-align:center; padding:20px;">
                            <h2 style="margin:0; font-size:20px; font-weight:700; letter-spacing:1px;">INVOICE</h2>
                            <p style="margin:5px 0 0; font-size:13px; opacity:0.85;">${subject}</p>
                        </div>
                        <div style="padding:20px 20px 10px;">
                            <p style="margin:0 0 12px; color:${themeColor}; font-weight:700; font-size:14px;">${greeting}</p>
                            <div style="color:#555; font-size:13px; line-height:1.7;">${body}</div>
                        </div>
                        <div style="padding:0 20px 15px;">
                            <div style="background:#f9f9f9; border-radius:6px; border-left:4px solid ${themeColor}; padding:12px 16px; display:flex; justify-content:space-between;">
                                <div><span style="font-size:11px; color:#777;">Invoice Number</span><br><strong style="font-size:14px; color:#333;">${invoiceId}</strong></div>
                                <div style="text-align:right;"><span style="font-size:11px; color:#777;">Date</span><br><span style="font-size:13px; color:#333;">${new Date().toLocaleDateString('en-GB', {day:'2-digit', month:'short', year:'numeric'})}</span></div>
                            </div>
                        </div>
                        <div style="padding:0 20px 15px;">
                            <div style="background:#eef7ff; border-radius:6px; padding:10px 16px; font-size:12px; color:#336;">
                                &#128206; <strong>The invoice PDF is attached with this email.</strong>
                            </div>
                        </div>
                        <div style="padding:0 20px 20px;">
                            <p style="color:#888; font-size:12px; margin:0 0 10px;">${footer}</p>
                            <p style="color:#555; font-size:12px; margin:0;">Thanks & Regards,</p>
                            <p style="color:#333; font-size:13px; font-weight:700; margin:3px 0 0;">${storeName}</p>
                        </div>
                        <div style="background:${themeColor}; padding:12px; text-align:center;">
                            <span style="color:rgba(255,255,255,0.8); font-size:11px;">&copy; ${new Date().getFullYear()} ${storeName}. All rights reserved.</span>
                        </div>
                    </div>
                `;
                $('#invEmailPreviewContainer').html(html);
            }

            // Submit form
            $('#sendInvoiceEmailForm').on('submit', function(e) {
                e.preventDefault();

                let email = $('#inv_recipient_email').val() || $('#inv_customer_email_new').val();
                if (!email) {
                    alert('Please select a customer with email or enter an email address.');
                    return;
                }
                $('#inv_recipient_email').val(email);

                let btn = $('#invSendEmailBtn');
                btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Sending...');

                let formData = new FormData(this);
                formData.set('recipient_email', email);

                $.ajax({
                    url: '{{ route("admin.billing.send-invoice-email") }}',
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                    success: function(resp) {
                        if (resp.success) {
                            bootstrap.Modal.getInstance(document.getElementById('sendInvoiceEmailModal')).hide();
                            $('#inv_requirement_doc').val('');
                            alert(resp.message || 'Email sent successfully!');
                        } else {
                            alert(resp.message || 'Failed to send email.');
                        }
                    },
                    error: function(xhr) {
                        let msg = xhr.responseJSON?.message || 'Something went wrong.';
                        alert(msg);
                    },
                    complete: function() {
                        btn.prop('disabled', false).html('<i class="fas fa-paper-plane"></i> Send Email');
                    }
                });
            });
        });
    </script>

</body>

</html>
