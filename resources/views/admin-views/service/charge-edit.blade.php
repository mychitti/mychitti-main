@extends('layouts.admin.app')

@section('title', 'Edit Lead Charges')

@push('css_or_js')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        .form-row{
            margin-top: 6px;
        }
    </style> 
@endpush
 
@section('content') 
        <div class="content container-fluid">
            <!-- Page Header -->
            <div class="page-header">
                <h1 class="page-header-title"><i class="tio-filter-list"></i> Edit Lead Charges </h1>
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
                <form enctype="multipart/form-data" class="w-100" action="{{route('admin.service.lead-charge-update')}}" method="post">
                    @csrf
                    <input type="hidden" value="<?= $charges->id;?>" name="charge_id"/> 
                    <div class="col-md-12">
                        <div class="card h-100">
                            <div class="card-body">
                                <!-- Basic Info -->
                                <div class="row mb-4">
                                    <div class="col-3">
                                        <label>Category <span class="text-danger">*</span></label>
                                        <select name="category" disabled class="form-control js-select2-custom">
                                            @foreach($categories as $cat)
                                            <option {{ $charges->category_id == $cat->id ? 'selected' : ''}} value="{{$cat->id}}">{{$cat->name}}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-3">
                                        <label>Zone <span class="text-danger">*</span></label>
                                        <select required name="zone" disabled class="form-control js-select2-custom">
                                            @foreach($zones as $zone)
                                            <option {{ $charges->zone_id == $zone->id ? 'selected' : ''}} value="{{$zone->id}}">{{$zone->name}}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-3">
                                        <label>Service <small class="text-muted">(Optional)</small></label>
                                        <select name="item_id" id="item_id" class="form-control" disabled>
                                            <option value="">All Services (Category level)</option>
                                            @foreach($items as $item)
                                                <option value="{{ $item->id }}" {{ $charges->item_id == $item->id ? 'selected' : '' }}>{{ $item->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>

                                <hr>

                                <!-- 1. Lead Acceptance Charges -->
                                <div class="mb-4">
                                    <h5 class="mb-1"><span class="badge badge-soft-primary mr-1">1</span> Lead Acceptance Charges</h5>
                                    <small class="text-muted d-block mb-3">Charged to vendor when they accept a lead</small>

                                    <div class="p-3 bg-light rounded mb-3">
                                        <h6 class="text-muted mb-2">When more than <span class="badge badge-warning ven_count">{{ $charges->vendor_count}}</span> vendors available in this zone & category</h6>
                                        <div class="row">
                                            <div class="col-3">
                                                <label>1st Vendor <span class="text-danger">*</span></label>
                                                <input type="number" value="{{ $charges->ven_1_charges}}" name="first_ven_charge" required placeholder="Amount" class="form-control">
                                            </div>
                                            <div class="col-3">
                                                <label>2nd Vendor <span class="text-danger">*</span></label>
                                                <input type="number" value="{{ $charges->ven_2_charges}}" name="sec_ven_charge" required placeholder="Amount" class="form-control">
                                            </div>
                                            <div class="col-3">
                                                <label>3rd Vendor <span class="text-danger">*</span></label>
                                                <input type="number" value="{{ $charges->ven_3_charges}}" name="third_ven_charge" required placeholder="Amount" class="form-control">
                                            </div>
                                            <div class="col-3">
                                                <label>Other Vendors <span class="text-danger">*</span></label>
                                                <input type="text" value="{{ $charges->ven_other_charges}}" name="other_ven_charge" required placeholder="Amount" class="form-control">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="p-3 bg-light rounded">
                                        <h6 class="text-muted mb-2">When <span class="badge badge-warning ven_count">{{ $charges->vendor_count}}</span> or fewer vendors available</h6>
                                        <div class="row">
                                            <div class="col-3">
                                                <label>Amount (same for all) <span class="text-danger">*</span></label>
                                                <input type="number" value="{{ $charges->ven_same_charges}}" name="same_charge" required placeholder="Amount" class="form-control">
                                            </div>
                                            <div class="col-3">
                                                <label>Vendor Count Threshold <span class="text-danger">*</span></label>
                                                <input type="number" value="{{ $charges->vendor_count}}" id="vendor_count" name="vendor_count" required placeholder="Count" class="form-control">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <hr>

                                <!-- 2. Confirmation & Completion Charges -->
                                <div class="mb-4">
                                    <div class="row">
                                        <div class="col-6">
                                            <div class="p-3 bg-light rounded h-100">
                                                <h5 class="mb-1"><span class="badge badge-soft-success mr-1">2</span> Confirmation Charges</h5>
                                                <small class="text-muted d-block mb-3">Charged when user confirms the lead after vendor acceptance</small>
                                                <div class="row">
                                                    <div class="col-6">
                                                        <label>Amount <span class="text-danger">*</span></label>
                                                        <input type="text" value="{{$charges->confirmation_charge ?? 0}}" name="confirmation_charge" required placeholder="Amount" class="form-control">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="p-3 bg-light rounded h-100">
                                                <h5 class="mb-1"><span class="badge badge-soft-info mr-1">3</span> Completion Charges</h5>
                                                <small class="text-muted d-block mb-3">Charged when the lead is marked as completed</small>
                                                <div class="row">
                                                    <div class="col-6">
                                                        <label>Amount <span class="text-danger">*</span></label>
                                                        <input type="text" value="{{$charges->completion_charge ?? 0}}" name="completion_charge" required placeholder="Amount" class="form-control">
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
   </div>   </div>


            @endsection

            @push('script_2')
             <script>
                $('#vendor_count').on('keyup', function(){
                    if($('#vendor_count').val() == ''){
                        var count= 1;
                    }else{
                        var count = $('#vendor_count').val()
                    }
                   $('.ven_count').text(count)
                })
            </script>
            @endpush
