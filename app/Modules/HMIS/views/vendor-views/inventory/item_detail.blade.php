@extends('layouts.vendor.app')

@section('title', ucfirst($item->item_name))

@push('css_or_js')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <script src="https://unpkg.com/html5-qrcode"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.3.4/assets/owl.carousel.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.3.4/assets/owl.theme.default.min.css">
    <style>
        .owl-carousel .owl-item img {
            width: 100%;
            aspect-ratio: 1;
            object-fit: contain;
            border-radius: 8px;
            background: #f8f9fa;
        }
        .owl-nav button {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            background: rgba(0,0,0,0.4) !important;
            color: #fff !important;
            border-radius: 50% !important;
            width: 32px;
            height: 32px;
            font-size: 18px !important;
            line-height: 1 !important;
        }
        .owl-nav .owl-prev { left: 4px; }
        .owl-nav .owl-next { right: 4px; }
        .owl-dots { margin-top: 8px; text-align: center; }

        .specs_div {
            height: 250px;
            overflow: hidden;
        }

        .desc_card {
            {{-- height: fit-content; --}}
        }

        .back-btn {
            background: none;
            border: none;
            font-size: 1.5rem;
            margin-right: 1rem;
            cursor: pointer;
            color: #7f8c8d;
            transition: color 0.2s;
        }

        .back-btn:hover {
            color: #2c3e50;
        }

        .header h1 {
            font-size: 1.8rem;
            font-weight: 600;
            color: #2c3e50;
        }

        .main-content {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 1.5rem;
            margin-bottom: 1rem;
            min-width: 0;
        }
        .main-content > * {
            min-width: 0;
        }

        .image-section {
            background: white;
            border-radius: 12px;
            padding: 2rem;
            box-shadow: 0 2px 20px rgba(0, 0, 0, 0.08);
            min-width: 0;
            overflow: hidden;
        }

        .product-image {
            width: 100%;
            border-radius: 8px;
        }

        .product-image img {
            width: 100% !important;
            /* height: auto; */
            aspect-ratio: 1;
            object-fit: contain;
        }


        .info-section {
            background: white;
            border-radius: 12px;
            padding: 13px;
            box-shadow: 0 2px 20px rgba(0, 0, 0, 0.08);
        }

        .product-title {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: #2c3e50;
        }

        .product-sku {
            color: #7f8c8d;
            font-size: 0.95rem;
            margin-bottom: 1rem;
        }

        .status-badge {
            display: inline-block;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
            margin-bottom: 2rem;

        }

        .success-badge {
            background: #d5f4e6;
            color: #27ae60;
        }

        .error-badge {
            background: #f8d7da;
            color: #c82333;
        }

        .details-grid {
            display: grid;
            gap: 1.5rem;
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0;
            border-bottom: 1px solid #f8f9fa;
        }

        .detail-row:last-child {
            border-bottom: none;
        }

        .detail-label {
            font-weight: 500;
            color: #7f8c8d;
            font-size: 0.95rem;
        }

        .detail-value {
            font-weight: 600;
            color: #2c3e50;
        }

        .quantity {
            font-size: 1.2rem;
            color: #27ae60;
        }

        .specifications {
            background: white;
            border-radius: 12px;
            padding: 13px;
            box-shadow: 0 2px 20px rgba(0, 0, 0, 0.08);
        }

        .spec-title {
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
            color: #2c3e50;
        }

        .spec-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
        }

        .spec-item {
            display: flex;
            justify-content: space-between;
            gap: 0.5rem;
        }

        .spec-label {
            font-size: 0.9rem;
            color: #7f8c8d;
            font-weight: 500;
        }

        .spec-value {
            font-weight: 600;
            color: #2c3e50;
        }

        .action-buttons {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
        }


        @media (max-width: 768px) {
            .container {
                padding: 0.5rem;
            }

            .main-content {
                grid-template-columns: 1fr;
                gap: 1rem;
            }

            .spec-grid {
                grid-template-columns: 1fr;
                gap: 0.75rem;
            }

            .action-buttons {
                flex-direction: column;
                gap: 0.5rem;
            }

            .product-image {
                height: 250px;
            }
        }

        .item-img {
            width: 100px;
            height: 100px;
            object-fit: cover;
            border-radius: 8px;
        }

        .section-title {
            font-size: 14px;
            font-weight: 600;
            color: #333;
        }

        .details-table td {
            font-size: 14px;
        }

        .highlight-icons img {
            width: 40px;
            margin-right: 10px;
        }

        .highlight-icons {
            display: flex;
            margin-top: 10px;
        }

        .specifications {
            color: #555;
        }

        .price-info {
            font-weight: bold;
            font-size: 14px;
            color: #2d3e50;
        }

        .btn-action {
            margin-top: 15px;
        }
    </style>
@endpush
@section('content')
    <div class="p-3">
        <div class="header d-flex align-items-center gap-2 justify-content-between">
            <div class="d-flex align-items-center gap-2">
                <h1>{{ ucwords($item->item_name) }} </h1>
                <div class="status-badge success-badge mb-0 py-1 px-2">{{ ucfirst($item->item_type) }}</div>
            </div>
            <a href="{{ route('vendor.inventory.edit-item', [$item->id]) }}" class="btn btn-outline-primary"><i
                    class="tio-edit"></i> Edit</a>
        </div>

        <div class="main-content">
            <div class="image-section">
                @php
                    $galleryImages = is_array($item->images) ? $item->images : (json_decode($item->images, true) ?? []);
                    $mainImageSrc  = \App\CentralLogics\Helpers::onerror_image_helper(
                        $item['image'],
                        asset('storage/app/public/inventory-item/') . '/' . $item['image'],
                        asset('public/assets/admin/img/160x160/img2.jpg'),
                        'inventory-item/'
                    );
                @endphp 
                <div class="owl-carousel owl-theme" id="itemImageCarousel">
                    {{-- Main image always first --}}
                    <div class="item">
                        <img src="{{ $mainImageSrc }}"
                             data-onerror-image="{{ asset('public/assets/admin/img/160x160/img2.jpg') }}"
                             alt="{{ $item->item_name }}" class="onerror-image">
                    </div>
                    {{-- Gallery images --}}
                    @foreach($galleryImages as $img)
                        <div class="item">
                            <img src="{{ asset('storage/app/public/inventory-item/' . $img) }}"
                                 data-onerror-image="{{ asset('public/assets/admin/img/160x160/img2.jpg') }}"
                                 alt="{{ $item->item_name }}" class="onerror-image">
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="info-section">
                <div class="product-sku">SKU: {{ $item->sku_id }}</div>

                @if ($item->stock)
                    <div class="status-badge success-badge">In Stock</div>
                @else
                    <div class="status-badge error-badge">Out of Stock</div>
                @endif

                <div class="details-grid">

                    <div class="detail-row">
                        <span class="detail-label">Quantity Available</span>
                        <span class="detail-value">
                            <span class=" quantity">
                                {{ $item->stock }} {{ $item->itemunit?->unit }}
                            </span><br>
                        </span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Storage Unit</span>
                        <span class="detail-value"> {{ ucfirst($item->storage_unit?->full_hierarchy_name) }}</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Last Updated</span>
                        <span class="detail-value">{{ \Carbon\Carbon::parse($item->updated_at)->format('M d, Y') }}</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">MRP</span>
                        <span class="detail-value">{{ _price($item->mrp) }}</span>
                    </div>
                    @if ($item->barcode || $item->sku_id)
                        <div class="detail-row">
                            <span class="detail-label">Barcode</span>
                            <div style="width:fit-content;padding:8px;border:1px dashed #ccc;">
                                @if ($item->barcode)
                                    <img width=" 140px;"
                                        src="{{ asset('storage/app/public/inventory-item/barcode/') . '/' . $item->barcode }}"
                                        alt="">
                                @else
                                    {!! DNS1D::getBarcodeSVG($item->sku_id, 'C128', 2, 60, 'black', false) !!}
                                @endif
                            </div>  
                        </div> 
                        @php $variations = json_decode($item->variations); @endphp
                        @if ($variations && count($variations) > 0)
                            <div class="mb-2 w-100">
                                <label class="lbl-fld font-weight-bold" style="font-size:12px; display:block;">Variation</label>
                                <select class="form-control form-control-sm lbl-variation-quick" style="max-width:200px;" onchange="updatePrintUrls(this, this.value)">
                                    <option value="">Default (Main Item)</option>
                                    @foreach ($variations as $vr)
                                        <option value="{{ $vr->type }}">{{ ucwords($vr->type) }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <script>
                                function updatePrintUrls(selectEl, val) {
                                    var suffix = val ? '?variation=' + encodeURIComponent(val) : '';
                                    var container = selectEl.closest('.details-grid') || selectEl.parentElement.parentElement;
                                    container.querySelectorAll('.btn-print-action').forEach(function(a) {
                                        var base = a.getAttribute('data-base-href');
                                        a.setAttribute('href', base + suffix);
                                    });
                                }
                            </script>
                        @endif
                        <div class="d-flex gap-2 flex-wrap">
                            <a style="padding: 10px 8px;white-space: nowrap;"
                                data-base-href="{{ route('vendor.inventory.item.print', [$item->id, 'barcode']) }}"
                                href="{{ route('vendor.inventory.item.print', [$item->id, 'barcode']) }}"
                                class="btn btn-sm btn--primary btn-print-action"><i class="tio-print"></i> Print Barcode</a>
                            <a style="padding: 10px 8px;white-space: nowrap;"
                                data-base-href="{{ route('vendor.inventory.item.print', [$item->id, 'description']) }}"
                                href="{{ route('vendor.inventory.item.print', [$item->id, 'description']) }}"
                                class="btn btn-sm btn--primary btn-print-action"><i class="tio-print"></i> Print
                                Description</a>
                            <a style="padding: 10px 8px;white-space: nowrap;"
                                data-base-href="{{ route('vendor.inventory.item.print', [$item->id, 'full']) }}"
                                href="{{ route('vendor.inventory.item.print', [$item->id, 'full']) }}"
                                class="btn btn-sm btn--primary btn-print-action"><i class="tio-print"></i> Print Full Label</a>
                        </div>
                    @endif
                </div>
            </div>
            <div class="specifications">
                <h3 class="spec-title">Attributes</h3>
                <div class="spec-grid">
                    <div class="spec-item">
                        <span class="spec-label">Category</span>
                        <span class="spec-value">{{ ucfirst($item->category?->name) }}</span>
                    </div>
                    <div class="spec-item">
                        <span class="spec-label">Brand</span>
                        <span class="spec-value">{{ $item->brand }}</span>
                    </div>
                    <div class="spec-item">
                        <span class="spec-label">Model Number</span>
                        <span class="spec-value">{{ $item->model_number }}</span>
                    </div>

                    @if ($item->custom_attributes)
                        @foreach (json_decode($item->custom_attributes) as $key => $value)
                            <div class="spec-item">
                                <span class="spec-label">{{ $key }}</span>
                                <span class="spec-value">{{ $value }}</span>
                            </div>
                        @endforeach
                    @endif
                </div>
            </div>
        </div>


        <div class="d-flex gap-2 my-2">
            <div class="col-md-6 card p-3 desc_card">
                <h4>Specifications</h4>
                <div class="specs_div">{!! $item->specifications !!} </div>
                <div class="d-flex justify-content-end">
                    <button class="btn btn-sm btn-outline-primary read_more_btn">Read More</button>
                    <button class="btn btn-sm btn-outline-primary read_less_btn" style="display:none;">Less</button>
                </div>
            </div>
            <div class="col-md-6 card p-3 desc_card">
                <h4>Highlights</h4>
                {!! $item->description !!}
            </div>
        </div>
        @php $variations  = json_decode($item->variations); @endphp
        @if ($variations)
            <div class="info-section">
                <h3 class="spec-title">Variations</h3>
                <table id="datatable"
                    class="table table-borderless table-thead-bordered table-nowrap table-align-middle card-table">
                    <thead class="thead-light">
                        <tr>
                            <th class="border-0">{{ translate('sl') }}</th>
                            <th class="border-0"> Name</th>
                            <th class="border-0 ">MRP</th>
                            <th class="border-0 ">Selling Price</th>
                            <th class="border-0 ">Images</th>
                            <th class="border-0 ">SKU</th>
                            <th class="border-0 ">Stock</th>
                            <th class="border-0 ">Action</th>
                        </tr>
                    </thead>

                    <tbody id="set-rows">

                        @foreach ($variations as $key2 => $vr)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>
                                    {{ ucwords($vr->type) }}
                                </td>
                                <td>
                                    {{ _price($vr->mrpprice) }}
                                </td>
                                <td>
                                    {{ _price($vr->price) }}
                                </td>
                                <td>
                                    <div class="d-flex gap-1">
                                        @php
                                            $vr_det = \App\Models\InvItemVariationDetail::where('type', $vr->type)
                                                ->where('item_id', $item->id)
                                                ->first();
                                            $images = $vr_det ? json_decode($vr_det->images) : [];
                                        @endphp
                                        @if ($images)
                                            @foreach ($images as $image)
                                                <img src="{{ asset('storage/app/public/inventory-variations/' . $image) }}"
                                                    alt="Variation Image" class="img-thumbnail"
                                                    style="width: 50px; height: 50px;">
                                            @endforeach
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    {{ $vr_det->sku }}
                                </td>
                                <td>
                                    {{ $vr->stock ?? 0 }}
                                </td>
                                <td>
                                    <div class="btn--container justify-content-warning">
                                        <a class="btn action-btn btn--warning btn-outline-warning" data-toggle="modal"
                                            data-target="#detModal{{ $key2 }}"
                                            title="{{ translate('messages.detail') }}"><i class="tio-visible"></i>
                                        </a>
                                        <a class="btn action-btn btn--primary btn-outline-primary"
                                            href="{{ route('vendor.inventory.edit-item', [$item->id]) }}"
                                            title="{{ translate('messages.edit') }}"><i class="tio-edit"></i>
                                        </a>
                                    </div>
                                    <div class="modal fade" id="detModal{{ $key2 }}" tabindex="-1"
                                        aria-labelledby="exampleModalLabel" aria-hidden="true">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title" id="exampleModalLabel">
                                                        {{ ucwords($vr->type) }}
                                                    </h5>
                                                    <button type="button" class="close" data-dismiss="modal"
                                                        aria-label="Close">
                                                        <span aria-hidden="true">&times;</span>
                                                    </button>
                                                </div>
                                                <div class="modal-body">
                                                    <div class="">
                                                        <div class="card p-3">
                                                            <h5 class="section-title">Images</h5>

                                                            <div class="highlight-icons">
                                                                @if ($images)
                                                                    @foreach ($images as $image)
                                                                        <img src="{{ asset('storage/app/public/inventory-variations/' . $image) }}"
                                                                            alt="Variation Image">
                                                                    @endforeach
                                                                @endif
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <!-- Right Side: Item Details -->
                                                    <div class="">
                                                        <h4 class="section-title">Item Details</h4>


                                                        <!-- sku barcode -->
                                                        @if ($vr_det)
                                                            <div class="card mb-1 p-3" style="white-space: break-spaces;">
                                                                <h5 class="section-title">Barcode</h5>
                                                                @if ($vr_det->sku)
                                                                    {!! DNS1D::getBarcodeSVG($vr_det->sku, 'C128', 2, 60, 'black', false) !!}
                                                                    <span>{{ $vr_det->sku }}</span>
                                                                @endif
                                                            </div>
                                                        @endif
                                                        <!-- Highlights Section -->
                                                        <div class="card mb-1 p-3" style="white-space: break-spaces;">
                                                            <h5 class="section-title">Highlights</h5>
                                                            <p>{{ $vr_det ? $vr_det->description : '' }}</p>
                                                        </div>

                                                        <!-- Specifications -->
                                                        <div class="card mb-1 p-3">
                                                            <h5 class="section-title">Specifications</h5>
                                                            <p class="">{!! $vr_det ? $vr_det->specifications : '' !!}</p>
                                                        </div>

                                                        <!-- Pricing Section -->
                                                        <div class="card p-3">
                                                            <h5 class="section-title">Pricing</h5>
                                                            <table class="table details-table">
                                                                <tr>
                                                                    <td><strong>MRP:</strong></td>
                                                                    <td>{{ _price($vr->mrpprice) }}</td>
                                                                </tr>
                                                                <tr>
                                                                    <td><strong>Selling Price:</strong></td>
                                                                    <td>{{ _price($vr->price) }}</td>
                                                                </tr>
                                                                <tr>
                                                                    <td><strong>Purchase Price:</strong></td>
                                                                    <td>{{ _price($vr->purchaseprice) }}</td>
                                                                </tr>
                                                            </table>
                                                        </div>


                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary"
                                                        data-dismiss="modal">Close</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </td>

                            </tr>
                        @endforeach

                    </tbody>
                </table>
            </div>
        @endif

    </div>

 
@endsection

@push('script_2')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.3.4/owl.carousel.min.js"></script>
    <script>
        $(document).ready(function () {
            $('#itemImageCarousel').owlCarousel({
                items: 1,
                loop: false,
                nav: true,
                dots: true,
                navText: ['&#8249;', '&#8250;'],
                autoplay: false,
                smartSpeed: 400,
            });
        });
    </script>
    <script>
        $(".read_more_btn").on('click', function() {
            $(".specs_div").css('height', 'fit-content');
            $(".read_more_btn").hide()
            $(".read_less_btn").show()
        })
        $(".read_less_btn").on('click', function() {
            $(".specs_div").css('height', '250px');
            $(".read_more_btn").show()
            $(".read_less_btn").hide()
        })
        document.addEventListener('DOMContentLoaded', function() {
            const detailRows = document.querySelectorAll('.detail-row');
            detailRows.forEach(row => {
                row.addEventListener('mouseenter', function() {
                    this.style.backgroundColor = '#f8f9fa';
                });
                row.addEventListener('mouseleave', function() {
                    this.style.backgroundColor = 'transparent';
                });
            });

            document.querySelector('.btn-primary').addEventListener('click', function() {
                alert('Update Quantity functionality would be implemented here');
            });

            const secondaryBtns = document.querySelectorAll('.btn-secondary');
            secondaryBtns.forEach((btn, index) => {
                btn.addEventListener('click', function() {
                    const actions = ['Edit Details', 'Generate Report'];
                    alert(`${actions[index] || 'Action'} functionality would be implemented here`);
                });
            });
        });
    </script>
@endpush
