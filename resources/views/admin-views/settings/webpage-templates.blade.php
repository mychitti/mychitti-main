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
            <span class="page-header-icon">
                <i class="tio-layout"></i>
            </span>
            <span>Webpage Templates</span>
        </h1>
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
                                <button class="btn btn-sm btn-primary save-btn" data-id="{{ $template->id }}">
                                    Save
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
</script>
@endpush
