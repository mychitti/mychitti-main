@extends('layouts.admin.app')

@section('title', 'Webpage Templates')

@push('css_or_js')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        .thumb-img {
            width: 70px;
            height: 50px;
            object-fit: cover;
            border-radius: 6px;
            cursor: zoom-in;
            border: 1px solid #e0e0e0;
            transition: opacity .15s;
        }
        .thumb-img:hover { opacity: .8; }
        .thumb-placeholder {
            width: 70px;
            height: 50px;
            border-radius: 6px;
            background: #f0f0f0;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #aaa;
            font-size: 11px;
            border: 1px dashed #ccc;
        }
        /* Lightbox overlay */
        #imgLightbox {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,.82);
            z-index: 9999;
            align-items: center;
            justify-content: center;
        }
        #imgLightbox.show { display: flex; }
        #imgLightbox img {
            max-width: 90vw;
            max-height: 88vh;
            border-radius: 10px;
            box-shadow: 0 8px 40px rgba(0,0,0,.6);
        }
        #imgLightbox .close-lb {
            position: absolute;
            top: 18px;
            right: 24px;
            font-size: 32px;
            color: #fff;
            cursor: pointer;
            line-height: 1;
        }
    </style>
@endpush

@section('content')
<div class="content container-fluid">
    <div class="page-header d-flex flex-wrap align-items-center justify-content-between __gap-15px">
        <h1 class="page-header-title">
            <span class="page-header-icon"><i class="tio-layout"></i></span>
            <span>Webpage Templates</span>
        </h1>
    </div>

    {{-- GST Settings --}}
    <div class="card mb-3">
        <div class="card-header py-3">
            <h5 class="card-title mb-0"><i class="tio-receipt-outlined mr-1"></i> Template Pricing GST Settings</h5>
        </div>
        <div class="card-body">
            <div class="row align-items-end g-3">
                <div class="col-sm-4">
                    <label class="form-label">GST Rate (%)</label>
                    <input type="number" id="gstPercent" class="form-control" min="0" max="100" step="0.01"
                        value="{{ $gstPercent }}">
                </div>
                <div class="col-sm-4">
                    <label class="form-label d-block">GST in Template Price</label>
                    <div class="d-flex gap-3 mt-1">
                        <div class="custom-control custom-radio custom-control-inline">
                            <input type="radio" id="gstExclude" name="gst_include" class="custom-control-input" value="0"
                                {{ $gstInclude ? '' : 'checked' }}>
                            <label class="custom-control-label" for="gstExclude">Exclude (add on top)</label>
                        </div>
                        <div class="custom-control custom-radio custom-control-inline">
                            <input type="radio" id="gstInclude" name="gst_include" class="custom-control-input" value="1"
                                {{ $gstInclude ? 'checked' : '' }}>
                            <label class="custom-control-label" for="gstInclude">Include (already in price)</label>
                        </div>
                    </div>
                </div>
                <div class="col-sm-4">
                    <button class="btn btn-primary" id="saveGstBtn">Save GST Settings</button>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-borderless table-thead-bordered table-nowrap table-align-middle card-table">
                    <thead class="thead-light">
                        <tr>
                            <th>#</th>
                            <th>Preview</th>
                            <th>Template</th>
                            <th>Name</th>
                            <th>Price ({{ \App\CentralLogics\Helpers::currency_symbol() }})</th>
                            <th>Status</th>
                            <th class="text-right">Action</th>
                            <th>Assign to Store</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($templates as $template)
                        <tr id="row-{{ $template->id }}">
                            <td>{{ $loop->iteration }}</td>
                            <td>
                                @if (!empty($template->thumbnail))
                                    <img
                                        src="{{ \App\CentralLogics\Helpers::onerror_image_helper($template->thumbnail, asset('storage/app/public/uploaded/templates/' . $template->thumbnail), asset('public/assets/admin/img/160x160/img2.jpg'), 'uploaded/templates/') }}"
                                        class="thumb-img onerror-image"
                                        data-full="{{ asset('storage/app/public/uploaded/templates/' . $template->thumbnail) }}"
                                        data-onerror-image="{{ asset('public/assets/admin/img/160x160/img2.jpg') }}"
                                        alt="Template {{ $template->id }}">
                                @else
                                    <div class="thumb-placeholder">No image</div>
                                @endif
                            </td>
                            <td>
                                <span class="badge badge-soft-info">Template {{ $template->id }}</span>
                            </td>
                            <td>
                                <input type="text"
                                    class="form-control form-control-sm"
                                    style="min-width:160px;"
                                    id="name-{{ $template->id }}"
                                    value="{{ $template->name ?? 'Template ' . $template->id }}">
                            </td>
                            <td>
                                <div class="input-group input-group-sm" style="max-width:140px;">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text">{{ \App\CentralLogics\Helpers::currency_symbol() }}</span>
                                    </div>
                                    <input type="number"
                                        class="form-control"
                                        id="price-{{ $template->id }}"
                                        value="{{ $template->price ?? 0 }}"
                                        min="0"
                                        step="0.01">
                                </div>
                            </td>
                            <td>
                                <label class="toggle-switch toggle-switch-sm d-flex align-items-center" style="gap:8px;">
                                    <input type="checkbox"
                                        class="toggle-switch-input status-toggle"
                                        data-id="{{ $template->id }}"
                                        {{ $template->status ? 'checked' : '' }}>
                                    <span class="toggle-switch-label">
                                        <span class="toggle-switch-indicator"></span>
                                    </span>
                                    <span class="toggle-switch-content">
                                        <span class="d-block status-label-{{ $template->id }}">
                                            {{ $template->status ? 'Active' : 'Inactive' }}
                                        </span>
                                    </span>
                                </label>
                            </td>
                            <td class="text-right">
                                <button class="btn btn-sm btn-primary save-btn" data-id="{{ $template->id }}">Save</button>
                            </td>
                            <td>
                                <button class="btn btn-sm btn-outline-success assign-btn"
                                    data-id="{{ $template->id }}"
                                    data-name="{{ $template->name ?? 'Template '.$template->id }}"
                                    data-price="{{ $template->price ?? 0 }}">
                                    <i class="tio-store-outlined"></i> Assign
                                </button>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

{{-- Assign to Store Modal --}}
<div class="modal fade" id="assignModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Assign Template to Store</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info mb-3" id="assignTemplateSummary"></div>
                <div class="form-group">
                    <label class="form-label font-weight-bold">Select Store</label>
                    <select class="form-control" id="assignStoreSelect">
                        <option value="">-- Select a store --</option>
                        @foreach($stores as $store)
                        <option value="{{ $store->id }}">{{ $store->name }}</option>
                        @endforeach
                    </select>
                </div>
                <p class="text-muted small mb-0">
                    <i class="tio-info-outined"></i>
                    If the template price is greater than ₹0, a bill will be automatically generated for the selected store.
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success" id="confirmAssignBtn">
                    <i class="tio-checkmark-circle-outlined"></i> Assign &amp; Generate Bill
                </button>
            </div>
        </div>
    </div>
</div>

{{-- Lightbox --}}
<div id="imgLightbox">
    <span class="close-lb">&times;</span>
    <img src="" id="lightboxImg" alt="Preview">
</div>
@endsection

@push('script_2')
<script>
    var csrfToken = $('meta[name="csrf-token"]').attr('content');

    // Thumbnail lightbox
    $(document).on('click', '.thumb-img', function () {
        $('#lightboxImg').attr('src', $(this).data('full'));
        $('#imgLightbox').addClass('show');
    });
    $(document).on('click', '#imgLightbox', function (e) {
        if ($(e.target).is('#imgLightbox') || $(e.target).is('.close-lb')) {
            $('#imgLightbox').removeClass('show');
        }
    });

    // Save price + name
    $(document).on('click', '.save-btn', function () {
        var id     = $(this).data('id');
        var price  = $('#price-' + id).val();
        var name   = $('#name-' + id).val();
        var status = $('#row-' + id + ' .status-toggle').is(':checked') ? 1 : 0;
        var btn    = $(this);

        btn.prop('disabled', true).text('Saving...');

        $.ajax({
            url: "{{ route('admin.webpage-templates.update') }}",
            method: 'POST',
            data: { _token: csrfToken, id: id, price: price, name: name, status: status },
            success: function (res) {
                toastr.success(res.message || 'Template updated.');
            },
            error: function (xhr) {
                var msg = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Error saving.';
                toastr.error(msg);
            },
            complete: function () {
                btn.prop('disabled', false).text('Save');
            }
        });
    });

    // Status toggle
    $(document).on('change', '.status-toggle', function () {
        var id      = $(this).data('id');
        var $toggle = $(this);

        $.ajax({
            url: "{{ route('admin.webpage-templates.toggle') }}",
            method: 'POST',
            data: { _token: csrfToken, id: id },
            success: function (res) {
                $('.status-label-' + id).text(res.status ? 'Active' : 'Inactive');
                toastr.success('Status updated.');
            },
            error: function () {
                $toggle.prop('checked', !$toggle.prop('checked'));
                toastr.error('Failed to update status.');
            }
        });
    });

    // GST settings save
    $('#saveGstBtn').on('click', function () {
        var btn     = $(this);
        var percent = $('#gstPercent').val();
        var include = $('input[name="gst_include"]:checked').val();

        btn.prop('disabled', true).text('Saving...');
        $.ajax({
            url: "{{ route('admin.webpage-templates.gst-settings') }}",
            method: 'POST',
            data: { _token: csrfToken, template_gst_percent: percent, template_gst_include: include },
            success: function (res) { toastr.success(res.message || 'GST settings saved.'); },
            error: function (xhr) {
                var msg = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Error saving.';
                toastr.error(msg);
            },
            complete: function () { btn.prop('disabled', false).text('Save GST Settings'); }
        });
    });

    // Assign to Store — open modal
    var assignTemplateId = null;
    $(document).on('click', '.assign-btn', function () {
        assignTemplateId = $(this).data('id');
        var name  = $(this).data('name');
        var price = parseFloat($(this).data('price')) || 0;
        var sym   = '{{ \App\CentralLogics\Helpers::currency_symbol() }}';

        var summary = '<strong>' + name + '</strong>';
        if (price > 0) {
            summary += ' — ' + sym + price.toLocaleString('en-IN', { minimumFractionDigits: 2 });
            summary += '<br><span class="text-success font-weight-bold">A bill will be generated for the store.</span>';
        } else {
            summary += ' — <span class="badge badge-success">Free</span> (no bill generated)';
        }
        $('#assignTemplateSummary').html(summary);
        $('#assignStoreSelect').val('');
        $('#assignModal').modal('show');
    });

    // Confirm assign
    $('#confirmAssignBtn').on('click', function () {
        var storeId = $('#assignStoreSelect').val();
        if (!storeId) { toastr.warning('Please select a store.'); return; }

        var btn = $(this);
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Processing...');

        $.ajax({
            url: "{{ route('admin.webpage-templates.assign-to-store') }}",
            method: 'POST',
            data: { _token: csrfToken, store_id: storeId, template_id: assignTemplateId },
            success: function (res) {
                toastr.success(res.message || 'Assigned successfully.');
                $('#assignModal').modal('hide');
                if (res.pdf_url) {
                    window.open(res.pdf_url, '_blank');
                }
            },
            error: function (xhr) {
                var msg = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Error assigning template.';
                toastr.error(msg);
            },
            complete: function () {
                btn.prop('disabled', false).html('<i class="tio-checkmark-circle-outlined"></i> Assign &amp; Generate Bill');
            }
        });
    });
</script>
@endpush
