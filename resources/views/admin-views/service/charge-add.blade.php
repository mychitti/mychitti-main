@extends('layouts.admin.app')

@section('title', 'Lead Charge Add')

@push('css_or_js')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        .form-row {
            margin-top: 6px;
        }

        /* Make select2 height fit content */
        .select2-container--default .select2-selection--multiple {
            height: auto !important;
            min-height: 40px !important;
            padding: 2px 5px;
        }

        /* Proper alignment of selected tags */
        .select2-container--default .select2-selection__rendered {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            padding: 0;
        }

        /* Tag spacing */
        .select2-container--default .select2-selection__choice {
            margin: 3px 5px 3px 0;
        }

        /* Fix search input alignment */
        .select2-container--default .select2-search--inline .select2-search__field {
            margin-top: 3px !important;
        }
    </style>
@endpush

@section('content')
    <div class="content container-fluid">
        <!-- Page Header -->
        <div class="page-header">
            <h1 class="page-header-title"><i class="tio-filter-list"></i> Add Lead Charges </h1>
            <div class="page-header-select-wrapper">

            </div>
        </div>
        <!-- End Page Header -->


        @if (session()->has('msg'))
            <div class="alert alert-success" role="alert">
                {{ session('msg') }}
            </div>
        @endif
        <div class="row g-2">
            <form enctype="multipart/form-data" class="w-100" action="{{ route('admin.service.lead-charge-save') }}"
                method="post">
                @csrf
                <div class="col-md-12">
                    <div class="card h-100">
                        <div class="card-body">
                            <!-- Basic Info -->
                            <div class="row mb-4">
                                <div class="col-md-3">
                                    <label>Category <span class="text-danger">*</span></label>
                                    <select name="category" class="form-control js-select2-custom">
                                        <option value=""></option>
                                        @foreach ($categories as $cat)
                                            <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label>Zone <span class="text-danger">*</span></label>
                                    <select required name="zone" class="form-control js-select2-custom">
                                        <option value=""></option>
                                        @foreach ($zones as $zone)
                                            <option value="{{ $zone->id }}">{{ $zone->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label>Service <small class="text-muted">(Optional)</small></label>
                                    <select name="item_id[]" id="item_id" class="form-control" multiple>
                                        <option value="">All Services (Category level)</option>
                                    </select>
                                    <small class="text-muted">Leave empty for category-level charges</small>
                                </div>
                            </div>

                            <hr>

                            <!-- 1. Lead Acceptance Charges -->
                            <div class="mb-4">
                                <h5 class="mb-1"><span class="badge badge-soft-primary mr-1">1</span> Lead Acceptance
                                    Charges</h5>
                                <small class="text-muted d-block mb-3">Charged to vendor when they accept a lead</small>

                                <div class="p-3 bg-light rounded mb-3">
                                    {{-- <h6 class="text-muted mb-2">When more than <span class="badge badge-warning ven_count">30</span> vendors available in this zone & category</h6> --}}
                                    <div class="row">
                                        <div class="col-3">
                                            <label>1st Vendor <span class="text-danger">*</span></label>
                                            <input type="number" name="first_ven_charge" required placeholder="Amount"
                                                class="form-control">
                                        </div>
                                        <div class="col-3">
                                            <label>2nd Vendor <span class="text-danger">*</span></label>
                                            <input type="number" name="sec_ven_charge" required placeholder="Amount"
                                                class="form-control">
                                        </div>
                                        <div class="col-3">
                                            <label>3rd Vendor <span class="text-danger">*</span></label>
                                            <input type="number" name="third_ven_charge" required placeholder="Amount"
                                                class="form-control">
                                        </div>
                                        <div class="col-3">
                                            <label>Other Vendors <span class="text-danger">*</span></label>
                                            <input type="text" name="other_ven_charge" required placeholder="Amount"
                                                class="form-control">
                                        </div>
                                    </div>
                                </div>

                                {{-- <div class="p-3 bg-light rounded">
                                        <h6 class="text-muted mb-2">When <span class="badge badge-warning ven_count">30</span> or fewer vendors available</h6>
                                        <div class="row">
                                            <div class="col-3">
                                                <label>Amount (same for all) <span class="text-danger">*</span></label>
                                                <input type="number" name="same_charge" required placeholder="Amount" class="form-control">
                                            </div>
                                            <div class="col-3">
                                                <label>Vendor Count Threshold <span class="text-danger">*</span></label>
                                                <input type="number" value="30" id="vendor_count" name="vendor_count" required placeholder="Count" class="form-control">
                                            </div>
                                        </div>
                                    </div> --}}
                            </div>

                            <hr>

                            <!-- 2. Dedicated Lead Charges -->
                            <div class="mb-4">
                                <div class="p-3 bg-light rounded">
                                    <h5 class="mb-1"><span class="badge badge-soft-warning mr-1">2</span> Dedicated Lead
                                        Acceptance Charges</h5>
                                    <small class="text-muted d-block mb-3">Charged when vendor accepts a lead that came
                                        through their store page (dedicated lead)</small>
                                    <div class="row">
                                        <div class="col-3">
                                            <label>Amount <span class="text-danger">*</span></label>
                                            <input type="number" name="dedicated_lead_charge" required placeholder="Amount"
                                                class="form-control">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <hr>

                            <!-- 3. Confirmation & Completion Charges -->
                            <div class="mb-4">
                                <div class="row">
                                    <div class="col-6">
                                        <div class="p-3 bg-light rounded h-100">
                                            <h5 class="mb-1"><span class="badge badge-soft-success mr-1">3</span>
                                                Confirmation Charges</h5>
                                            <small class="text-muted d-block mb-3">Charged when user confirms the lead after
                                                vendor acceptance</small>
                                            <div class="row">
                                                <div class="col-6">
                                                    <label>Amount <span class="text-danger">*</span></label>
                                                    <input type="text" name="confirmation_charge" required
                                                        placeholder="Amount" class="form-control">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="p-3 bg-light rounded h-100">
                                            <h5 class="mb-1"><span class="badge badge-soft-info mr-1">4</span> Completion
                                                Charges</h5>
                                            <small class="text-muted d-block mb-3">Charged when the lead is marked as
                                                completed</small>
                                            <div class="row">
                                                <div class="col-6">
                                                    <label>Amount <span class="text-danger">*</span></label>
                                                    <input type="text" name="completion_charge" required
                                                        placeholder="Amount" class="form-control">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-3 w-100 d-flex justify-content-end">
                                <button class="btn btn--primary btn-primary">Save</button>
                            </div>
                        </div>
                    </div>
                </div>

            </form>
        </div>
    </div>


@endsection

@push('script_2')
    <script>
        $('#item_id').select2({
            placeholder: 'All Services (Category level)',
            allowClear: true,
            width: '100%',
            multiple: true
        });

        $('#vendor_count').on('keyup', function() {
            if ($('#vendor_count').val() == '') {
                var count = 1;
            } else {
                var count = $('#vendor_count').val()
            }
            $('.ven_count').text(count)
        });

        $('select[name="category"]').on('change', function() {
            var catId = $(this).val();
            var $itemSelect = $('#item_id');

            // Clear all selected options and reset
            $itemSelect.val(null).html('').trigger('change');

            if (catId) {
                $.get("{{ route('admin.service.get-items-by-category', '') }}/" + catId, function(data) {
                    data.forEach(function(item) {
                        $itemSelect.append('<option value="' + item.id + '">' + item.name +
                            '</option>');
                    });
                    $itemSelect.trigger('change');
                });
            }
        });
    </script>
@endpush
