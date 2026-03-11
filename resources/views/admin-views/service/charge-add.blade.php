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
                <form enctype="multipart/form-data" class="w-100" action="{{route('admin.service.lead-charge-save')}}" method="post">
                    @csrf
                    <div class="col-md-12">
                        <div class="card h-100">
                            <small>All fields are mandatory</small>
                            <div class="card-body row">
                                 <div class="form-row col-3">
                                    <label for="exampleInputEmail1">Category <span class="text-danger">*</span></label>
                                    <select name="category" class="form-control js-select2-custom">
                                        <option value=""></option>
                                        
                                        @foreach($categories as $cat)
                                        <option value="{{$cat->id}}">{{$cat->name}}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-row col-3">
                                    <label for="exampleInputEmail1">Zone <span class="text-danger">*</span></label>
                                    <select required name="zone" class="form-control js-select2-custom">
                                           <!--<option value="" selected disabled>Zone</option>-->
                                           <option value=""></option>
                                           @foreach($zones as $zone)
                                          <option value="{{$zone->id}}">{{$zone->name}}</option>
                                           @endforeach
                                    </select>
                                </div>
                                <div class="form-row col-2">
                                    <label for="exampleInputEmail1">Service <small class="text-muted">(Optional)</small></label>
                                    <select name="item_id" id="item_id" class="form-control">
                                        <option value="">All Services (Category level)</option>
                                    </select>
                                    <small class="text-muted">Leave empty for category-level charges</small>
                                </div>
                                <div class="form-row col-2">
                                    <label for="exampleInputEmail1">Confirmation Charges</i> </label>
                                    <input type="text" name="confirmation_charge" required placeholder="Amount"
                                        class="form-control">
                                </div>



                                <div class="section-header  col-12 my-5">
                                     <h4>Different Charges</h4>
                                       <small> <i>If more than <span class="ven_count">30</span> vendors available in particular zone and category, below mentioned charges will be applied</i></small>
                                </div>
                               
                                <div class="form-row col-2">
                                    <label for="exampleInputEmail1">First Vendor<span class="text-danger">*</span></label>
                                    <input type="number" name="first_ven_charge" required placeholder="Amount"
                                        class="form-control">
                                </div>
                                <div class="form-row col-2">
                                    <label for="exampleInputEmail1">Second Vendor <span class="text-danger">*</span></label>
                                    <input type="number" name="sec_ven_charge" required placeholder="Amount"
                                        class="form-control">
                                </div>
                                <div class="form-row col-2">
                                    <label for="exampleInputEmail1">Third Vendor<span class="text-danger">*</span></label>
                                    <input type="number" name="third_ven_charge" required placeholder="Amount"
                                        class="form-control">
                                </div>
                                <div class="form-row col-2">
                                    <label for="exampleInputEmail1">Other Vendors</i> <span class="text-danger">*</span></label>
                                    <input type="text" name="other_ven_charge" required placeholder="Amount"
                                        class="form-control">
                                </div>
                                
                                  <div class="section-header  col-12 my-5">
                                 <h4>Same Charges</h4>
                               <small> <i>If <span class="ven_count">30</span> or less vendors available in particular zone and category, below mentioned charges will be applied</i></small>
                                  </div>
                                <div class="form-row col-2">
                                    <label for="exampleInputEmail1">Amount <span class="text-danger">*</span></label>
                                    <input type="number" name="same_charge" required placeholder="Amount" class="form-control">
                                </div>
                                <div class="form-row col-2">
                                    <label for="exampleInputEmail1">Vendors Count<span class="text-danger">*</span></label>
                                    <input type="number" value="30" id="vendor_count" name="vendor_count" required placeholder="Count"
                                        class="form-control">
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
                });

                $('select[name="category"]').on('change', function(){
                    var catId = $(this).val();
                    var $itemSelect = $('#item_id');
                    $itemSelect.html('<option value="">All Services (Category level)</option>');
                    if(catId) {
                        $.get("{{ url('admin/service/get-items-by-category') }}/" + catId, function(data){
                            data.forEach(function(item){
                                $itemSelect.append('<option value="'+item.id+'">'+item.name+'</option>');
                            });
                        });
                    }
                });
            </script>
            @endpush
