@extends('layouts.vendor.app')

@section('title', 'Add Quotation')

@push('css_or_js')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @if (\App\CentralLogics\Helpers::get_store_data()->module_id == 6)
        <style>
            .hidden_hsn {
                display: none;
            }
        </style>
    @endif
    <style>
      .item_row_quote td {
            padding: 2px !important;
        }
        /* .select2-results__option:nth-child(2) {
                color: rgb(13, 96, 252) !important;
            } */

        .select2-results__option:last-child {
            color: rgb(90, 123, 186) !important;
            font-weight: bold;
        }

        .custom-input {
            padding-left: 0;
            border: 1px solid #e8e6e6;
            box-shadow: none;
            border-left: none;
        }

        .custom-input:focus {
            box-shadow: none;
            border: 1px solid #ececec;
            outline: none;
            border-left: none;
        }

        #totalWithoutGST,
        #totalWithGST,
        .currency {
            font-size: 18px;
            color: black;
        }

        .item_row_inv td {
            padding: 2px !important;
        }

        .form-row {
            margin-top: 6px;
        }

        .hidden_tax {
            display: none;
        }

        @media (max-width: 768px) {
            table {
                display: block;
                /* Make table block */
                border: none;
            }

            thead {
                display: none;
                /* Hide headers */
            }

            tbody tr {
                display: block;
                margin-bottom: 10px;
                border: 1px solid #ddd;
                /* Add border around cards */
                padding: 10px;
            }

            tbody td {
                display: flex;
                justify-content: space-between;
                padding: 5px 10px;
            }

            tbody td::before {
                content: attr(data-label);
                /* Use data-label for headings */
                font-weight: bold;
                flex: 1;
            }

            td {
                flex: 2;
            }
        }

        .table th {
            padding: 5px !important;
        }

        #toast {
            visibility: hidden;
            min-width: 250px;
            margin-left: -125px;
            background-color: #333;
            color: #fff;
            text-align: center;
            border-radius: 5px;
            padding: 16px;
            position: fixed;
            z-index: 1111;
            left: 50%;
            bottom: 30px;
            font-size: 17px;
            opacity: 0;
            transition: opacity 0.5s, bottom 0.5s;
        }

        #toast.show {
            visibility: visible;
            opacity: 1;
            bottom: 50px;
        }
    </style>
@endpush

@section('content')
    <div id="toast" class="toast">This is a toaster notification!</div>

    {{-- @include('vendor-views/sub-module/partials/billing') --}}

    <div class="content container-fluid">
        <!-- Page Header -->
        <div class="page-header">
            <h1 class="page-header-title"><i class="tio-filter-list"></i>Quotation Edit</h1>
            <div class="page-header-select-wrapper">
                @if($quote->quote_detail?->pdf)
                    <a href="javascript:void(0);" class="btn btn-sm btn-primary send-quote-email-btn"
                        data-toggle="modal" data-target="#sendEmailModal"
                        data-quote-id="{{ $quote->id }}"
                        data-client-id="{{ $quote->client_name }}"
                        data-client-name="{{ $quote->storeCustomer?->f_name . ' ' . $quote->storeCustomer?->l_name }}"
                        data-quotation-id="{{ $quote->quotation_id }}"
                        data-subject="{{ $quote->subject }}">
                        <i class="tio-email-outlined"></i> Send Email
                    </a>
                @endif
            </div>
        </div>
        <!-- End Page Header -->

        <div class="row g-2">
            @include('vendor-views/forms/quote_edit')
            @include('vendor-views.form_modals.inventory_item_select')
        </div>
    </div>

    <!-- Send Email Modal -->
    <div class="modal fade" id="sendEmailModal" tabindex="-1" role="dialog" aria-labelledby="sendEmailModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Send Quotation Email</h5>
                    <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                </div>
                <form id="sendQuoteEmailForm" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="quote_id" id="modal_quote_id">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Select Customer <span class="text-danger">*</span></label>
                                    <select name="customer_id" id="modal_customer_select" class="form-control" style="width:100%">
                                        <option value=""></option>
                                    </select>
                                    <input type="hidden" name="recipient_email" id="recipient_email">
                                </div>
                                <div class="form-group" id="email_input_wrapper" style="display:none;">
                                    <label>Customer Email <span class="text-danger">*</span></label>
                                    <input type="email" name="customer_email_new" id="customer_email_new" class="form-control" placeholder="Enter customer email">
                                    <small class="text-muted">This email will be saved to the customer record.</small>
                                </div>
                                <small id="email_check_msg" class="form-text"></small>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Email Subject <span class="text-danger">*</span></label>
                                    <input type="text" name="email_subject" id="email_subject" class="form-control" required placeholder="Email subject">
                                </div>
                            </div>
                        </div>

                        <!-- Template Picker -->
                        <div class="form-group">
                            <label class="d-block mb-2">Email Template <span class="text-danger">*</span></label>
                            <div class="d-flex" style="gap:12px;">
                                @foreach([1=>'Classic',2=>'Modern',3=>'Executive'] as $tid => $tlabel)
                                <label class="tpl-card {{ ($quote_email_settings['email_template'] ?? '1') == $tid ? 'active' : '' }}" data-tpl="{{ $tid }}" style="cursor:pointer;border:2px solid {{ ($quote_email_settings['email_template'] ?? '1') == $tid ? '#006161' : '#dee2e6' }};border-radius:8px;padding:10px 14px;flex:1;text-align:center;transition:all .2s;">
                                    <input type="radio" name="email_template" value="{{ $tid }}" style="display:none;" {{ ($quote_email_settings['email_template'] ?? '1') == $tid ? 'checked' : '' }}>
                                    <div style="font-weight:600;font-size:13px;color:#333;">Template {{ $tid }}</div>
                                    <div style="font-size:11px;color:#888;margin-top:2px;">{{ $tlabel }}</div>
                                    <div class="tpl-preview-thumb mt-2" style="height:36px;border-radius:4px;background:{{ $tid==3 ? '#1a1a2e' : ($quote_email_settings['theme_color'] ?? '#a51d1d') }};opacity:{{ $tid==3 ? '1' : '0.85' }};"></div>
                                </label>
                                @endforeach
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Theme Color</label>
                                    <input type="color" name="theme_color" id="theme_color" class="form-control" value="{{ $quote_email_settings['theme_color'] ?? '#a51d1d' }}" style="height:40px;padding:3px;">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group" style="margin-top:28px;">
                                    <label><input type="checkbox" name="save_as_default" value="1"> Save as default template</label>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Email Greeting</label>
                            <input type="text" name="email_greeting" id="email_greeting" class="form-control" value="{{ $quote_email_settings['greeting'] ?? 'Dear {client_name},' }}" placeholder="e.g. Dear {client_name},">
                            <small class="text-muted">Use {client_name} and {quotation_id} as placeholders.</small>
                        </div>
                        <div class="form-group">
                            <label>Email Body <span class="text-danger">*</span></label>
                            <textarea name="email_body" id="email_body" class="form-control" rows="5" required>{{ $quote_email_settings['body'] ?? "Please find attached the quotation for the services requested. We hope it meets your expectations.\n\nKindly review and let us know if you have any questions.\n\nWe look forward to hearing from you." }}</textarea>
                        </div>
                        <div class="form-group">
                            <label>Footer Text</label>
                            <input type="text" name="email_footer" id="email_footer" class="form-control" value="{{ $quote_email_settings['footer'] ?? 'Thank you for your business!' }}">
                        </div>

                        <!-- Preview -->
                        <div class="card mt-3">
                            <div class="card-header py-2 d-flex justify-content-between align-items-center">
                                <h6 class="mb-0">Email Preview</h6>
                                <button type="button" class="btn btn-sm btn-outline-primary" id="refreshPreview">Refresh Preview</button>
                            </div>
                            <div class="card-body p-0">
                                <div id="emailPreviewContainer" style="overflow-y:auto;padding:10px;background:#f9f9f9;"></div>
                            </div>
                        </div>

                        <!-- Requirement doc -->
                        <div class="form-group mt-3">
                            <label><i class="tio-attachment"></i> Attach Requirement Document <span class="text-muted">(optional — PDF, Word, Image, max 10MB)</span></label>
                            <input type="file" name="requirement_doc" id="requirement_doc" class="form-control" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
                            <small class="text-muted">The quotation PDF will also be attached automatically.</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary" id="sendEmailBtn"><i class="tio-send"></i> Send Email</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- End Send Email Modal -->

@endsection
@push('script_2')
    @include('vendor-views/quote/quote-js')

    <script>
        // Template card selection
        $(document).on('click', '.tpl-card', function() {
            $('.tpl-card').css('border-color', '#dee2e6');
            $(this).css('border-color', '#006161');
            $(this).find('input[type=radio]').prop('checked', true);
            updatePreview();
        });

        // Send Email modal — init customer select2
        $('#modal_customer_select').select2({
            dropdownParent: $('#sendEmailModal'),
            placeholder: 'Search customer by name or phone',
            minimumInputLength: 2,
            ajax: {
                url: "{{ route('vendor.client.get-matches') }}",
                dataType: 'json',
                delay: 250,
                data: function(params) { return { q: params.term }; },
                processResults: function(data) {
                    return { results: $.map(data, function(item) {
                        return { id: item.id, text: item.f_name + ' (' + item.phone + ')' };
                    })};
                },
                cache: true
            }
        });

        $('#modal_customer_select').on('change', function() {
            let customerId = $(this).val();
            if (!customerId) {
                $('#email_input_wrapper').hide();
                $('#email_check_msg').html('');
                $('#recipient_email').val('');
                return;
            }
            $.get('{{ route("vendor.quotation.check-email") }}', { customer_id: customerId }, function(resp) {
                if (resp.has_email) {
                    $('#recipient_email').val(resp.email);
                    $('#email_input_wrapper').hide();
                    $('#customer_email_new').removeAttr('required');
                    $('#email_check_msg').html('<span class="text-success"><i class="tio-checkmark-circle"></i> Email: ' + resp.email + '</span>');
                } else {
                    $('#recipient_email').val('');
                    $('#email_input_wrapper').show();
                    $('#customer_email_new').attr('required', true).val('');
                    $('#email_check_msg').html('<span class="text-warning"><i class="tio-info"></i> No email on record. Please enter one below.</span>');
                }
                window._selectedClientName = resp.name || 'Customer';
                updatePreview();
            });
        });

        $('#customer_email_new').on('input', function() { $('#recipient_email').val($(this).val()); });

        $('.send-quote-email-btn').on('click', function() {
            let quoteId     = $(this).data('quote-id');
            let clientName  = $(this).data('client-name');
            let clientId    = $(this).data('client-id');
            let quotationId = $(this).data('quotation-id');
            let subject     = $(this).data('subject');

            $('#modal_quote_id').val(quoteId);
            $('#email_subject').val('Quotation ' + quotationId + (subject ? ' - ' + subject : ''));
            $('#email_input_wrapper').hide();
            $('#email_check_msg').html('');
            $('#recipient_email').val('');
            $('#customer_email_new').val('').removeAttr('required');
            window._selectedClientName = clientName || 'Customer';

            if (clientId) {
                $.get('{{ route("vendor.quotation.check-email") }}', { customer_id: clientId }, function(resp) {
                    let option = new Option(resp.name + (resp.phone ? ' (' + resp.phone + ')' : ''), clientId, true, true);
                    $('#modal_customer_select').append(option).trigger('change');
                });
            } else {
                $('#modal_customer_select').val(null).trigger('change.select2');
            }
            updatePreview();
        });

        function updatePreview() {
            let themeColor  = $('#theme_color').val();
            let greeting    = $('#email_greeting').val();
            let body        = $('#email_body').val().replace(/\n/g, '<br>');
            let footer      = $('#email_footer').val();
            let subject     = $('#email_subject').val();
            let clientName  = window._selectedClientName || 'Customer';
            let clientBtn   = $('.send-quote-email-btn[data-quote-id="' + $('#modal_quote_id').val() + '"]');
            let quotationId = clientBtn.data('quotation-id') || '';
            let storeName   = @json(\App\Models\BusinessSetting::where('key', 'business_name')->first()?->value ?? '');
            let tpl         = $('input[name=email_template]:checked').val() || '1';

            greeting = greeting.replace('{client_name}', clientName).replace('{quotation_id}', quotationId);
            body     = body.replace(/{client_name}/g, clientName).replace(/{quotation_id}/g, quotationId);

            let headerBg  = tpl == '3' ? '#1a1a2e' : themeColor;
            let headerHtml = tpl == '2'
                ? `<div style="padding:22px 28px;border-bottom:1px solid #f0f0f0;"><strong style="color:${themeColor};font-size:18px;letter-spacing:1px;">QUOTATION</strong> <span style="color:#aaa;font-size:12px;">#${quotationId}</span></div>`
                : `<div style="background:${headerBg};color:#fff;text-align:center;padding:22px;"><strong style="font-size:18px;letter-spacing:1px;">QUOTATION</strong><br><small style="opacity:.8;">${subject}</small></div>`;

            let html = `
                <div style="font-family:Arial,sans-serif;max-width:500px;margin:0 auto;background:#fff;border-radius:8px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,.1);">
                    ${headerHtml}
                    <div style="padding:20px;">
                        <p style="color:${themeColor};font-weight:700;margin:0 0 10px;">${greeting}</p>
                        <div style="color:#555;font-size:13px;line-height:1.7;">${body}</div>
                    </div>
                    <div style="padding:0 20px 12px;">
                        <div style="background:#f9f9f9;border-left:4px solid ${themeColor};padding:10px 14px;font-size:12px;color:#666;">
                            &#128206; <strong>Quotation PDF attached.</strong>
                        </div>
                    </div>
                    <div style="padding:0 20px 20px;">
                        <p style="color:#888;font-size:12px;margin:0 0 8px;">${footer}</p>
                        <p style="color:#555;font-size:12px;margin:0;">Thanks &amp; Regards,</p>
                        <p style="color:#333;font-size:13px;font-weight:700;margin:3px 0 0;">${storeName}</p>
                    </div>
                    <div style="background:${headerBg};padding:12px;text-align:center;">
                        <span style="color:rgba(255,255,255,.75);font-size:11px;">&copy; ${new Date().getFullYear()} ${storeName}</span>
                    </div>
                </div>`;
            $('#emailPreviewContainer').html(html);
        }

        $('#refreshPreview, #theme_color').on('change click', function() {
            $('.tpl-preview-thumb').not('[data-dark]').css('background', $('#theme_color').val());
            updatePreview();
        });
        $('#email_greeting, #email_body, #email_footer, #email_subject').on('input', function() {
            clearTimeout(window._previewTimeout);
            window._previewTimeout = setTimeout(updatePreview, 300);
        });

        // Submit with FormData to support file upload
        $('#sendQuoteEmailForm').on('submit', function(e) {
            e.preventDefault();
            let email = $('#recipient_email').val() || $('#customer_email_new').val();
            if (!email) { toastr.error('Please select a customer with email or enter an email address.'); return; }
            $('#recipient_email').val(email);

            let btn = $('#sendEmailBtn');
            btn.prop('disabled', true).html('<i class="tio-loading tio-spin"></i> Sending...');

            let formData = new FormData(this);
            formData.set('recipient_email', email);

            $.ajax({
                url: '{{ route("vendor.quotation.send-quote-email") }}',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(resp) {
                    if (resp.success) {
                        $('#sendEmailModal').modal('hide');
                        $('#requirement_doc').val('');
                        toastr.success(resp.message || 'Email sent successfully!');
                    } else {
                        toastr.error(resp.message || 'Failed to send email.');
                    }
                },
                error: function(xhr) { toastr.error(xhr.responseJSON?.message || 'Something went wrong.'); },
                complete: function() { btn.prop('disabled', false).html('<i class="tio-send"></i> Send Email'); }
            });
        });
    </script>
@endpush
