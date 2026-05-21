@extends('layouts.vendor.app')

@section('title', 'Service Details')

@push('css_or_js')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/lightgallery/2.7.2/css/lightgallery.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/lightgallery/css/lightgallery.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/lightgallery/css/lg-thumbnail.css">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        .add_more_btn { display: block; }
        .add_more_btn_mobile { display: none; }

        /* ── Gatepass meta bar ── */
        .gp-meta-bar {
            background: #fff;
            border: 1px solid #e7eaf3;
            border-radius: 10px;
            padding: 14px 18px;
            margin-bottom: 14px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 10px;
        }
        .gp-meta-left { display: flex; flex-direction: column; }
        .gp-meta-id   { font-size: 1rem; font-weight: 700; color: #1e2022; }
        .gp-meta-date { font-size: 0.76rem; color: #8c98a4; margin-top: 2px; }

        .gp-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 4px 12px;
            border-radius: 50px;
            font-size: 0.78rem;
            font-weight: 600;
            letter-spacing: .02em;
        }
        .gp-badge.approved { background: #d4edda; color: #155724; }
        .gp-badge.pending  { background: #fff3cd; color: #856404; }
        .gp-badge.rejected { background: #f8d7da; color: #721c24; }
        .gp-badge .dot {
            width: 7px; height: 7px; border-radius: 50%;
            background: currentColor; flex-shrink: 0;
        }

        /* ── Item cards ── */
        .gp-item {
            background: #fff;
            border: 1px solid #e7eaf3;
            border-radius: 10px;
            margin-bottom: 12px;
            overflow: hidden;
        }
        .gp-item-head {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 11px 16px;
            background: #f8fafc;
            border-bottom: 1px solid #e7eaf3;
        }
        .gp-num {
            min-width: 26px;
            height: 26px;
            border-radius: 50%;
            background: #dde9ff;
            color: #2e6ff3;
            font-size: 0.75rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .gp-item-title {
            font-weight: 600;
            font-size: 0.9rem;
            color: #1e2022;
            margin: 0;
        }
        .gp-item-body { padding: 12px 16px; }
        .gp-desc {
            font-size: 0.84rem;
            color: #677788;
            line-height: 1.55;
            margin-bottom: 12px;
        }

        /* ── Photo grid ── */
        .gp-photos {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }
        .gp-photos a {
            display: block;
            border-radius: 8px;
            overflow: hidden;
            border: 2px solid #e7eaf3;
            transition: border-color .15s, transform .15s, box-shadow .15s;
        }
        .gp-photos a:hover {
            border-color: #3766e8;
            transform: translateY(-2px);
            box-shadow: 0 6px 14px rgba(55,102,232,.18);
        }
        .gp-photos a img {
            width: 90px;
            height: 90px;
            object-fit: cover;
            display: block;
            cursor: zoom-in;
        }
        .gp-photo-label {
            margin-top: 6px;
            font-size: 0.74rem;
            color: #8c98a4;
        }

        /* ── Customer card ── */
        .cust-card {
            background: #fff;
            border: 1px solid #e7eaf3;
            border-radius: 10px;
            padding: 18px 20px;
        }
        .cust-card .cust-name {
            font-size: 1rem;
            font-weight: 700;
            color: #1e2022;
            margin-bottom: 12px;
        }
        .cust-row {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.84rem;
            color: #677788;
            margin-bottom: 8px;
        }
        .cust-row i { width: 15px; color: #3766e8; flex-shrink: 0; }
        .cust-row a { color: #677788; text-decoration: none; }
        .cust-row a:hover { color: #3766e8; }
        .cust-since {
            font-size: 0.76rem;
            color: #aab0b7;
            margin-top: 10px;
            padding-top: 10px;
            border-top: 1px solid #f0f3f7;
        }

        @media (max-width: 768px) {
            .add_more_btn_mobile { display: block; }
            .gp-photos a img { width: 72px; height: 72px; }
        }
    </style>
@endpush

@section('content')
    <div class="content container-fluid">
        <div class="row">
            @php $gp = $allGatepasses; @endphp

            {{-- LEFT: gatepass view / edit / create --}}
            <div class="col-md-7">
                <h4 class="resturant-card" style="background-color:#f0f0f0">{{ $serviceDetails->item_name }}</h4>

                @if (($serviceDetails->current_status == 'Cancelled' || $serviceDetails->current_status == 'Completed') && !_gatepassExist($serviceDetails->service_id))

                    <div class="text-center py-5 text-muted">
                        <i class="tio-file-text-outlined" style="font-size:2.5rem; opacity:.3;"></i>
                        <p class="mt-2">No gatepass found.</p>
                    </div>

                @elseif (_gatepassExist($serviceDetails->service_id))

                    {{-- ── VIEW MODE (default) ──────────────────────────────── --}}
                    <div id="gatepass-view">

                        {{-- Meta bar --}}
                        <div class="gp-meta-bar">
                            <div class="gp-meta-left">
                                <span class="gp-meta-id">
                                    <i class="tio-document-text mr-1" style="color:#3766e8;"></i>
                                    Gatepass #{{ $gp->id }}
                                </span>
                                <span class="gp-meta-date">
                                    Created {{ _formatted_datetime($gp->created_at) }}
                                    &nbsp;·&nbsp; {{ count($gpItems) }} {{ Str::plural('item', count($gpItems)) }}
                                </span>
                            </div>
                            <div class="d-flex align-items-center" style="gap:10px;">
                                @php
                                    $statusClass = $gp->approved == 1 ? 'approved' : ($gp->approved == 2 ? 'rejected' : 'pending');
                                    $statusLabel = $gp->approved == 1 ? 'Approved' : ($gp->approved == 2 ? 'Rejected' : 'Pending Approval');
                                @endphp
                                <span class="gp-badge {{ $statusClass }}">
                                    <span class="dot"></span>
                                    {{ $statusLabel }}
                                </span>
                                @if (hasPermission('leads_gatepass', 'edit'))
                                    <button class="btn btn-sm btn-outline-primary" onclick="toggleGatepassEdit()">
                                        <i class="tio-edit"></i> Edit
                                    </button>
                                @endif
                            </div>
                        </div>

                        {{-- Items --}}
                        @forelse ($gpItems as $key => $item)
                            @php
                                $imgs = [];
                                if ($item->image) {
                                    $decoded = json_decode($item->image, true);
                                    $imgs = is_array($decoded) ? $decoded : [$item->image];
                                }
                            @endphp
                            <div class="gp-item">
                                <div class="gp-item-head">
                                    <span class="gp-num">{{ $key + 1 }}</span>
                                    <span class="gp-item-title">{{ $item->title }}</span>
                                </div>
                                <div class="gp-item-body">
                                    @if ($item->description)
                                        <p class="gp-desc">{{ $item->description }}</p>
                                    @endif
                                    @if (!empty($imgs))
                                        <div class="gp-photos lightgallery-view-{{ $key }}">
                                            @foreach ($imgs as $img)
                                                <a href="{{ asset('storage/app/public/gatepass/' . $img) }}"
                                                   data-src="{{ asset('storage/app/public/gatepass/' . $img) }}">
                                                    <img src="{{ asset('storage/app/public/gatepass/' . $img) }}"
                                                         onerror="this.src='{{ asset('public/assets/admin/img/160x160/img1.jpg') }}'">
                                                </a>
                                            @endforeach
                                        </div>
                                        <div class="gp-photo-label">
                                            {{ count($imgs) }} {{ Str::plural('photo', count($imgs)) }}
                                            &nbsp;·&nbsp; click to enlarge
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @empty
                            <div class="text-center py-4 text-muted" style="font-size:.9rem;">
                                No items in this gatepass.
                            </div>
                        @endforelse

                    </div>

                    {{-- ── EDIT MODE (hidden) ────────────────────────────── --}}
                    @if (hasPermission('leads_gatepass', 'edit'))
                        <div id="gatepass-edit" style="display:none;">
                            <div class="d-flex align-items-center justify-content-between mb-3">
                                <h5 class="mb-0">Edit Gatepass</h5>
                                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="toggleGatepassEdit()">
                                View
                                </button>
                            </div>

                            <form class="w-100" id="quote_form" enctype="multipart/form-data"
                                  action="{{ route('vendor.service.gatepass-update') }}" method="post">
                                @csrf
                                @if ($gp->approved == 2)
                                    <input type="hidden" name="gp_status" value="rejected">
                                @else
                                    <input type="hidden" name="gp_status" value="new">
                                @endif
                                <input type="hidden" name="service_id" value="{{ $serviceDetails->service_id }}">

                                <table class="table">
                                    <thead style="background:#75b8b8; color:#fff;">
                                        <tr>
                                            <th>Title</th>
                                            {{-- <th>Images</th>  --}}
                                            <th>
                                                <button type="button" class="btn btn-dark btn-sm add_more_btn"
                                                        onclick="addMoreRow()">
                                                    <span class="d-none d-md-inline">Add More</span>
                                                    <span class="d-inline d-md-none">+</span>
                                                </button>
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody class="rows_parent">
                                        @foreach ($gpItems as $key => $item)
                                            @php
                                                $imgs = [];
                                                if ($item->image) {
                                                    $decoded = json_decode($item->image, true);
                                                    $imgs = is_array($decoded) ? $decoded : [$item->image];
                                                }
                                            @endphp
                                            <tr class="existing_item_row row_edit_{{ $key + 1 }}" data-id="{{ $key + 1 }}">
                                                <input type="hidden" name="ids[]" value="{{ $item->id }}">
                                                <td>
                                                    <input type="text" name="title[]" value="{{ $item->title }}"
                                                           placeholder="Title" class="form-control">
                                                </td>
                                                
                                                <td>
                                                    <a href="{{ route('vendor.service.delete-gatepass-item', [$item->id]) }}"
                                                       class="btn action-btn btn--danger btn-outline-danger">
                                                        <i class="tio-delete-outlined"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                       
                                            <tr class="row_edit_{{ $key + 1 }}">
                                               <td>
                                                    <input type="file" name="existing_image[{{ $key }}][]"
                                                           multiple accept="image/*"
                                                           class="form-control image-input" data-id="{{ $key + 1 }}">
                                                    <div class="image-error text-danger mt-1" style="font-size:14px;"></div>
                                                    @if (!empty($imgs))
                                                        <input type="hidden" value="{{ count($imgs) }}"
                                                               id="image_count_{{ $key + 1 }}">
                                                        <div class="d-flex flex-wrap mt-2" style="gap:6px;">
                                                            @foreach ($imgs as $img)
                                                                <img src="{{ asset('storage/app/public/gatepass/' . $img) }}"
                                                                     style="width:48px;height:48px;object-fit:cover;border-radius:6px;border:1px solid #e7eaf3;"
                                                                     onerror="this.src='{{ asset('public/assets/admin/img/160x160/img1.jpg') }}'">
                                                            @endforeach
                                                        </div>
                                                    @endif
                                                </td>
                                            </tr>
                                       
                                            <tr class="row_edit_{{ $key + 1 }}">
                                                <td colspan="3">
                                                    <textarea name="desc[]" placeholder="Description"
                                                              class="form-control">{{ $item->description }}</textarea>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>

                                <div class="form-row col-12 my-2">
                                    <button type="button" class="btn btn--primary btn-outline-primary submit_btn">Update</button>
                                </div>
                            </form>
                        </div>
                    @endif

                @elseif (hasPermission('leads_gatepass', 'add'))

                    {{-- ── CREATE FORM ──────────────────────────────────── --}}
                    <form class="w-100" id="quote_form" enctype="multipart/form-data"
                          action="{{ route('vendor.service.gatepass-add') }}" method="post">
                        @csrf
                        <input type="hidden" name="service_id" value="{{ $serviceDetails->service_id }}">
                        <input type="hidden" name="acc_id" value="{{ $serviceDetails->id }}">

                        <h4 class="m-3 mb-0">Create Gatepass</h4>
                        <table class="table">
                            <thead style="background:#75b8b8; color:#fff;">
                                <tr>
                                    <th>Title</th>
                                    {{-- <th>Image <span class="text-danger">*</span></th> --}}
                                    <th>
                                        <button type="button" class="btn btn-dark btn-sm add_more_btn"
                                                onclick="addMoreRow()"><span class="d-none d-md-inline">Add More</span>
                                                    <span class="d-inline d-md-none">+</span></button>
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="rows_parent">
                                <tr class="item_row row_1" data-id="1">
                                    <td><input type="text" name="title[]" placeholder="Title" class="form-control"></td>
                                
                                    <td>
                                        <button type="button" onclick="deleteRow(1)"
                                                class="btn action-btn btn--danger btn-outline-danger">
                                            <i class="tio-delete-outlined"></i>
                                        </button>
                                    </td>
                                </tr>
                                <tr class="row_1">
                                       <td  colspan="3">
                                        <input type="file" name="image[0][]" multiple required
                                               class="form-control image-input" data-id="1" accept="image/*,capture=camera">
                                        <div class="image-error text-danger mt-1" style="font-size:14px;"></div>
                                    </td>
                                </tr>
                                <tr class="row_1">
                                    <td colspan="3">
                                        <textarea name="desc[]" placeholder="Description" class="form-control"></textarea>
                                    </td>
                                </tr>
                            </tbody>
                        </table>

                        <div class="form-row col-12 my-2">
                            <button type="button" class="btn btn--primary btn-outline-primary submit_btn">Create</button>
                        </div>
                    </form>

                @endif
            </div>

            {{-- RIGHT: customer details --}}
            <div class="col-md-5" style="height:90vh; overflow:auto;">
                <h5 class="mb-3" style="color:#677788; font-size:.78rem; text-transform:uppercase; letter-spacing:.06em; font-weight:600;">Customer Details</h5>
                @php $custDet = _customerDetByserviceId($serviceDetails->service_id); @endphp
                @if ($custDet)
                    <div class="cust-card">
                        @if ($custDet->image)
                            <div class="mb-3 d-flex align-items-center" style="gap:12px;">
                                <img src="{{ asset('storage/app/public/profile/' . $custDet->image) }}"
                                     onerror="this.src='{{ asset('public/assets/admin/img/160x160/img1.jpg') }}'"
                                     style="width:48px;height:48px;border-radius:50%;object-fit:cover;border:2px solid #e7eaf3;">
                                <span class="cust-name mb-0">{{ $custDet->f_name . ' ' . $custDet->l_name }}</span>
                            </div>
                        @else
                            <div class="cust-name">{{ $custDet->f_name . ' ' . $custDet->l_name }}</div>
                        @endif

                        <div class="cust-row">
                            <i class="tio-email"></i>
                            <span>{{ $custDet->email }}</span>
                        </div>
                        <div class="cust-row">
                            <i class="tio-call"></i>
                            <span>{{ $custDet->phone }}</span>
                        </div>
                        @if (!empty($custDet->latitude) && !empty($custDet->longitude))
                            <div class="cust-row">
                                <i class="tio-map-marker-outlined"></i>
                                <a href="https://www.google.com/maps?q={{ $custDet->latitude }},{{ $custDet->longitude }}"
                                   target="_blank">View Location</a>
                            </div>
                        @endif
                        <div class="cust-since">
                            Customer since {{ _monthNYear($custDet->created_at) }}
                        </div>
                    </div>
                @endif
            </div>

        </div>
    </div>
@endsection

@push('script_2')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/lightgallery/2.7.2/lightgallery.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/lightgallery/2.7.2/plugins/video/lg-video.umd.min.js"></script>
    <script>
        document.querySelectorAll('[class*="lightgallery-view-"]').forEach(function(el) {
            lightGallery(el, { thumbnail: true, animateThumb: true, showThumbByDefault: true });
        });

        function toggleGatepassEdit() {
            $('#gatepass-view').toggle();
            $('#gatepass-edit').toggle();
        }

        $(document).on('click', '.submit_btn', function() {
            var $itemRows = $('.item_row');
            var $existingRows = $('.existing_item_row');
            if ($itemRows.length === 0 && $existingRows.length === 0) {
                toastr.error('Please add at least one item');
                return;
            }
            $(this).data('label', $(this).text());
            compressAndSubmit();
        });

        function deleteRow(rowId) {
            $('.row_' + rowId).remove();
        }

        function addMoreRow() {
            var $last = $('.item_row').last();
            var dataId = $last.length ? Number($last.data('id')) + 1 : 1;

            var html = `<tr class="item_row row_` + dataId + `" data-id="` + dataId + `">
                <input type="hidden" name="new_item[]" value="">
                <td><input type="text" name="title[]" placeholder="Title" class="form-control"></td>
             
                <td>
                    <button type="button" onclick="deleteRow(` + dataId + `)"
                            class="btn action-btn btn--danger btn-outline-danger">
                        <i class="tio-delete-outlined"></i>
                    </button>
                </td>
            </tr>
            <tr class="row_` + dataId + `">
                <td colspan="3">
                     <input type="file" name="image[` + (dataId - 1) + `][]" multiple required
                           accept="image/*" class="form-control image-input" data-id="` + dataId + `">
                    <div class="image-error text-danger mt-1" style="font-size:14px;"></div>
                </td>
            </tr><tr class="row_` + dataId + `">
                <td colspan="3">
                    <textarea name="desc[]" placeholder="Description" class="form-control"></textarea>
                </td>
            </tr>`;

            $('.rows_parent').append(html);
        }

        var HARD_LIMIT_MB  = 30;
        var COMPRESS_ABOVE = 2;   // compress files larger than 2 MB
        var imgCountLimit  = {{ \App\Models\BusinessSetting::where('key', 'gatepass_max_image')->value('value') ?? 4 }};

        /* ── On-select validation (count + hard 30MB limit) ── */
        document.addEventListener('change', function(e) {
            if (!e.target.matches('.image-input')) return;
            var input    = e.target;
            var files    = input.files;
            var errorBox = input.nextElementSibling;
            var dataId   = input.getAttribute('data-id');
            var existing = parseInt($('#image_count_' + dataId).val()) || 0;

            errorBox.textContent = '';
            input.style.border = '1px solid #ededed';

            if (files.length + existing > imgCountLimit) {
                errorBox.textContent = 'Max ' + imgCountLimit + ' photos per item.';
                input.value = ''; input.style.border = '2px solid red'; return;
            }
            for (var i = 0; i < files.length; i++) {
                if (!files[i].type.startsWith('image/')) {
                    errorBox.textContent = 'Only image files are allowed.';
                    input.value = ''; input.style.border = '2px solid red'; return;
                }
                if (files[i].size > HARD_LIMIT_MB * 1024 * 1024) {
                    errorBox.textContent = '"' + files[i].name + '" exceeds the 30 MB limit.';
                    input.value = ''; input.style.border = '2px solid red'; return;
                }
            }
        });

        /* ── Canvas compression ── */
        function compressImage(file) {
            return new Promise(function(resolve, reject) {
                if (file.size <= COMPRESS_ABOVE * 1024 * 1024) { resolve(file); return; }

                var url = URL.createObjectURL(file);
                var img = new Image();
                img.onerror = function() { URL.revokeObjectURL(url); resolve(file); };
                img.onload = function() {
                    URL.revokeObjectURL(url);
                    var MAX_DIM = 2048;
                    var w = img.naturalWidth, h = img.naturalHeight;
                    if (w > MAX_DIM || h > MAX_DIM) {
                        if (w >= h) { h = Math.round(h * MAX_DIM / w); w = MAX_DIM; }
                        else        { w = Math.round(w * MAX_DIM / h); h = MAX_DIM; }
                    }
                    var canvas = document.createElement('canvas');
                    canvas.width = w; canvas.height = h;
                    canvas.getContext('2d').drawImage(img, 0, 0, w, h);

                    var quality = 0.85;
                    var attempt = function() {
                        canvas.toBlob(function(blob) {
                            if (!blob) { resolve(file); return; }
                            if (blob.size <= HARD_LIMIT_MB * 1024 * 1024 || quality <= 0.2) {
                                resolve(new File([blob], file.name.replace(/\.[^.]+$/, '') + '.jpg',
                                    { type: 'image/jpeg', lastModified: Date.now() }));
                            } else {
                                quality = Math.max(quality - 0.15, 0.2);
                                attempt();
                            }
                        }, 'image/jpeg', quality);
                    };
                    attempt();
                };
                img.src = url;
            });
        }

        /* ── Compress all inputs, then submit ── */
        async function compressAndSubmit() {
            var $btn = $('.submit_btn');
            $btn.prop('disabled', true).text('Uploading…');

            var inputs = document.querySelectorAll('.image-input');
            for (var idx = 0; idx < inputs.length; idx++) {
                var input = inputs[idx];
                if (!input.files || input.files.length === 0) continue;
                var errorBox = input.nextElementSibling;
                var dt = new DataTransfer();
                var ok = true;

                for (var j = 0; j < input.files.length; j++) {
                    try {
                        var compressed = await compressImage(input.files[j]);
                        if (compressed.size > HARD_LIMIT_MB * 1024 * 1024) {
                            errorBox.textContent = '"' + input.files[j].name + '" is too large even after compression (max 30 MB).';
                            input.style.border = '2px solid red';
                            ok = false; break;
                        }
                        dt.items.add(compressed);
                    } catch(err) {
                        dt.items.add(input.files[j]);
                    }
                }

                if (!ok) {
                    $btn.prop('disabled', false).text($btn.data('label'));
                    return;
                }
                input.files = dt.files;
            }

            document.getElementById('quote_form').submit();
        }
    </script>
@endpush
