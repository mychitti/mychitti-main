@extends('layouts.admin.app')

@section('title', 'Lead Charge Add')

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
                <form enctype="multipart/form-data" class="w-100" action="{{route('admin.service.lead-charge-update')}}" method="post">
                    @csrf
                    <input type="hidden" value="<?= $charges->id;?>" name="charge_id"/> 
                    <div class="col-md-12">
                        <div class="card h-100">
                            <div class="card-body row">
                                 <div class="form-row col-6">
                                    <label for="exampleInputEmail1">Category <span class="text-danger">*</span></label>
                                    
                                    <select name="category" disabled class="form-control js-select2-custom">
                                        <!--<option value="" selected disabled>Category</option>-->
                                        
                                        @foreach($categories as $cat)
                                        <option {{ $charges->category_id == $cat->id ? 'selected' : ''}} value="{{$cat->id}}">{{$cat->name}}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-row col-6">
                                    <label for="exampleInputEmail1">Zone <span class="text-danger">*</span></label>
                                    <select required name="zone" disabled class="form-control js-select2-custom">
                                           <!--<option value="" selected disabled>Zone</option>-->
                                           @foreach($zones as $zone)
                                          <option {{ $charges->zone_id == $zone->id ? 'selected' : ''}} value="{{$zone->id}}">{{$zone->name}}</option>
                                           @endforeach
                                    </select>
                                </div>
                                <div class="section-header  col-12 my-5">
                                     <h4>Different Charges</h4>
                               <small> <i>If more than <span class="ven_count">{{ $charges->vendor_count}}</span> vendors available in particular zone and category, below mentioned charges will be applied</i></small>
                                </div>
                               
                                <div class="form-row col-3">
                                    <label for="exampleInputEmail1">First Vendor<span class="text-danger">*</span></label>
                                    <input type="number" value="{{ $charges->ven_1_charges}}" name="first_ven_charge" required placeholder="Amount"
                                        class="form-control">
                                </div>
                                <div class="form-row col-3">
                                    <label for="exampleInputEmail1">Second Vendor <span class="text-danger">*</span></label>
                                    <input type="number" value="{{ $charges->ven_2_charges}}" name="sec_ven_charge" required placeholder="Amount"
                                        class="form-control">
                                </div>
                                <div class="form-row col-3">
                                    <label for="exampleInputEmail1">Third Vendor<span class="text-danger">*</span></label>
                                    <input type="number" value="{{ $charges->ven_3_charges}}" name="third_ven_charge" required placeholder="Amount"
                                        class="form-control">
                                </div>
                                <div class="form-row col-3">
                                    <label for="exampleInputEmail1">Other Vendors</i> <span class="text-danger">*</span></label>
                                    <input type="text" value="{{ $charges->ven_other_charges}}" name="other_ven_charge" required placeholder="Amount"
                                        class="form-control">
                                </div>
                                  <div class="section-header  col-12 my-5">
                                 <h4>Same Charges</h4>
                               <small> <i>If <span class="ven_count">{{ $charges->vendor_count}}</span> or less vendors available in particular zone and category, below mentioned charges will be applied</i></small>
                                  </div>
                                <div class="form-row col-4">
                                    <label for="exampleInputEmail1">Amount <span class="text-danger">*</span></label>
                                    <input type="number" value="{{ $charges->ven_same_charges}}" name="same_charge" required placeholder="Amount" class="form-control">
                                </div>
                                 <div class="form-row col-4">
                                    <label for="exampleInputEmail1">Vendor Count <span class="text-danger">*</span></label>
                                    <input type="number" value="{{ $charges->vendor_count}}" id="vendor_count" name="vendor_count" required placeholder="Vendor Count" class="form-control">
                                </div>
                                <div class="form-row col-12">
                                    <div class="col my-2">
                                        <button class="btn  btn--primary btn-outline-primary">Save</button>
                                    </div>
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
