 @extends('layouts.admin.app')

 @section('title', 'Tax Rates')

 @push('css_or_js')
     <meta name="csrf-token" content="{{ csrf_token() }}">
 @endpush

 @section('content')
     <div class="content container-fluid">
         <!-- Page Header -->
         <div class="page-header">
             <h1 class="page-header-title mr-3">
                 <span class="page-header-icon">
                     <img src="{{ asset('public/assets/admin/img/business.png') }}" class="w--26" alt="">
                 </span>
                 <span>
                     {{ translate('messages.business_settings') }}
                 </span>
             </h1>
             @include('admin-views.business-settings.partials.nav-menu')
         </div>
         <!-- End Page Header -->

         <div class="row g-3">
             <!-- Card -->
             <div class="col-md-6">
                 <div class="card">
                     <!-- Header -->
                     <div class="card-header py-2">
                         <div class="search--button-wrapper d-flex justify-content-between">
                             <h5 class="card-title">TDS Rates List</h5>
                             <button class="btn btn-outline-primary action-btn add_tax" data-type="TDS" type="button" data-toggle="modal"
                                 data-target="#addTaxModal">+</button>
                             <!-- End Unfold -->
                         </div>
                     </div>
                     <!-- End Header -->

                     <!-- Table -->
                     <div class="table-responsive datatable-custom">
                         <table id="columnSearchDatatable"
                             class="table table-borderless table-thead-bordered table-nowrap table-align-middle card-table"
                             data-hs-datatables-options='{
                        "order": [],
                        "orderCellsTop": true,
                        "paging":false

                    }'>
                             <thead class="thead-light">
                                 <tr>
                                     <th class="border-0">{{ translate('sl') }}</th>
                                     <th class="border-0">Section</th>
                                     <th class="border-0">Tax</th>
                                     <th class="border-0">Description</th>
                                     <th class="text-uppercase border-0">Action</th>
                                 </tr>
                             </thead>

                             <tbody id="set-rows">
                                 @foreach ($tds_rates as $key => $rate)
                                     <tr>
                                         <td>{{ $key + 1 }}</td>
                                         <td>{{ $rate->section }}</td>
                                         <td>{{ $rate->rate }}</td>
                                         <td>{{ $rate->description }}</td>
                                         <td>
                                             <div class="btn--container justify-content-center">
                                                 <a class="btn action-btn btn--primary btn-outline-primary edit_tax_btn"
                                                     data-id="{{ $rate->id }}" type="button" data-toggle="modal"
                                                     data-target="#editTaxModal">
                                                     <i class="tio-edit"></i>
                                                 </a>
                                                 <a class="btn action-btn btn--danger btn-outline-danger form-alert"
                                                     href="javascript:;" data-id="unit-{{ $rate['id'] }}"
                                                     data-message="{{ translate('Want to delete this tax rate ?') }}"
                                                     title="{{ translate('messages.delete') }}">
                                                     <i class="tio-delete"></i>
                                                 </a>
                                                 <form
                                                     action="{{ route('admin.business-settings.tax-rate.delete', [$rate->id]) }}"
                                                     method="get" id="unit-{{ $rate['id'] }}">
                                                     @csrf @method('get')
                                                 </form>
                                             </div>
                                         </td>
                                     </tr>
                                 @endforeach
                             </tbody>
                         </table>
                         <div class="modal fade" id="editTaxModal" tabindex="-1" aria-labelledby="exampleModalLabel"
                             aria-hidden="true">
                             <div class="modal-dialog">
                                 <div class="modal-content">
                                     <div class="modal-header">
                                         <h5 class="modal-title" id="exampleModalLabel">Edit <span id="tax_type"></span>
                                             Rates</h5>
                                         <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                             <span aria-hidden="true">&times;</span>
                                         </button>
                                     </div>
                                     <div class="modal-body">
                                         <form action="{{ route('admin.business-settings.tax-rate.update') }}"
                                             method="post">
                                             @csrf
                                             <input type="hidden" name="tax_id" id="tax_id">
                                             <label for="tax_rate" class="form-label">Tax Rate</label>
                                             <input type="number" step="0.001" class="form-control" id="tax_rate"
                                                 name="rate">
                                             <label for="tax_section" class="form-label">Section</label>
                                             <input type="text" class="form-control" id="tax_section" name="section">
                                             <label for="tax_desc" class="form-label">Description</label>
                                             <input type="text" class="form-control" id="tax_desc" name="description">
                                             <button class="btn btn--primary mt-2">Save</button>
                                         </form>
                                     </div>

                                 </div>
                             </div>
                         </div>
                         @if (count($tds_rates))
                             <hr>
                         @else
                             <div class="page-area">
                             </div>
                             <div class="empty--data">
                                 <img src="{{ asset('/public/assets/admin/svg/illustrations/sorry.svg') }}" alt="public">
                                 <h5>
                                     {{ translate('no_data_found') }}
                                 </h5>
                             </div>
                         @endif
                     </div>
                     <!-- End Table -->
                 </div>
             </div>
             <div class="col-md-6">

                 <div class="card  ">
                     <!-- Header -->
                     <div class="card-header py-2">
                         <div class="search--button-wrapper d-flex justify-content-between">
                             <h5 class="card-title">TCS Rates List</h5>
                             <button class="btn btn-outline-primary action-btn add_tax" data-type="TCS" type="button" data-toggle="modal"
                                 data-target="#addTaxModal">+</button>
                             <!-- End Unfold -->
                         </div>
                     </div>
                     <!-- End Header -->

                     <!-- Table -->
                     <div class="table-responsive datatable-custom">
                         <table id="columnSearchDatatable"
                             class="table table-borderless table-thead-bordered table-nowrap table-align-middle card-table"
                             data-hs-datatables-options='{
                        "order": [],
                        "orderCellsTop": true,
                        "paging":false

                    }'>
                             <thead class="thead-light">
                                 <tr>
                                     <th class="border-0">{{ translate('sl') }}</th>
                                     <th class="border-0">Section</th>
                                     <th class="border-0">Tax</th>
                                     <th class="border-0">Description</th>
                                     <th class="text-uppercase border-0">Action</th>
                                 </tr>
                             </thead>

                             <tbody id="set-rows">
                                 @foreach ($tcs_rates as $key => $rate)
                                     <tr>
                                         <td>{{ $key + 1 }}</td>
                                         <td>{{ $rate->section }}</td>
                                         <td>{{ $rate->rate }}</td>
                                         <td>{{ $rate->description }}</td>
                                         <td>
                                             <div class="btn--container justify-content-center">
                                                 <a class="btn action-btn btn--primary btn-outline-primary edit_tax_btn"
                                                     data-id="{{ $rate->id }}" type="button" data-toggle="modal"
                                                     data-target="#editTaxModal">
                                                     <i class="tio-edit"></i>
                                                 </a>
                                                 <a class="btn action-btn btn--danger btn-outline-danger form-alert"
                                                     href="javascript:;" data-id="unit-{{ $rate['id'] }}"
                                                     data-message="{{ translate('Want to delete this tax rate ?') }}"
                                                     title="{{ translate('messages.delete') }}">
                                                     <i class="tio-delete"></i>
                                                 </a>
                                                 <form
                                                     action="{{ route('admin.business-settings.tax-rate.delete', [$rate->id]) }}"
                                                     method="get" id="unit-{{ $rate['id'] }}">
                                                     @csrf @method('get')
                                                 </form>
                                             </div>
                                         </td>
                                     </tr>
                                 @endforeach
                             </tbody>
                         </table>
                         @if (count($tds_rates))
                             <hr>
                         @else
                             <div class="page-area">
                             </div>
                             <div class="empty--data">
                                 <img src="{{ asset('/public/assets/admin/svg/illustrations/sorry.svg') }}"
                                     alt="public">
                                 <h5>
                                     {{ translate('no_data_found') }}
                                 </h5>
                             </div>
                         @endif
                     </div>
                     <!-- End Table -->
                 </div>
             </div>

         </div>

         <!-- End Card -->
     </div>
     <div class="modal fade" id="addTaxModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
         <div class="modal-dialog">
             <div class="modal-content">
                 <div class="modal-header">
                     <h5 class="modal-title" id="exampleModalLabel">Add New Tax
                         Rates</h5>
                     <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                         <span aria-hidden="true">&times;</span>
                     </button>
                 </div>
                 <div class="modal-body">
                     <form action="{{ route('admin.business-settings.tax-rate.save') }}" method="post">
                         @csrf
                         
                         <input type="hidden"  id="tax_type_new" name="tax_type">
                         <label for="tax_rate" class="form-label">Tax Rate</label>
                         <input type="number" step="0.001" class="form-control" id="tax_rate" name="rate">
                         <label for="tax_section" class="form-label">Section</label>
                         <input type="text" class="form-control" id="tax_section" name="section">
                         <label for="tax_desc" class="form-label">Description</label>
                         <input type="text" class="form-control" id="tax_desc" name="description">
                         <button class="btn btn--primary mt-2">Save</button>
                     </form>
                 </div>

             </div>
         </div>
     </div>
 @endsection

 @push('script_2')
     <script>
         function status_change_alert(url, message, e) {
             e.preventDefault();
             Swal.fire({
                 title: 'Are you sure?',
                 text: message,
                 type: 'warning',
                 showCancelButton: true,
                 cancelButtonColor: 'default',
                 confirmButtonColor: '#FC6A57',
                 cancelButtonText: 'No',
                 confirmButtonText: 'Yes',
                 reverseButtons: true
             }).then((result) => {
                 if (result.value) {
                     location.href = url;
                 }
             })
         }
         $(document).on('ready', function() {
             // INITIALIZATION OF DATATABLES
             // =======================================================
             var datatable = $.HSCore.components.HSDatatables.init($('#columnSearchDatatable'));

             $('#column1_search').on('keyup', function() {
                 datatable
                     .columns(1)
                     .search(this.value)
                     .draw();
             });

             $('#column2_search').on('keyup', function() {
                 datatable
                     .columns(2)
                     .search(this.value)
                     .draw();
             });

             $('#column3_search').on('keyup', function() {
                 datatable
                     .columns(3)
                     .search(this.value)
                     .draw();
             });

             $('#column4_search').on('keyup', function() {
                 datatable
                     .columns(4)
                     .search(this.value)
                     .draw();
             });


             // INITIALIZATION OF SELECT2
             // =======================================================
             $('.js-select2-custom').each(function() {
                 var select2 = $.HSCore.components.HSSelect2.init($(this));
             });
         });
         $(".edit_tax_btn").on('click', function() {
             var id = $(this).data('id');

             $.ajaxSetup({
                 headers: {
                     'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                 }
             });
             $.get({
                 url: '{{ route('admin.business-settings.tax-rate.details', '') }}/' + id,
                 success: function(data) {
                     if (data) {
                         $("#tax_id").val(data.id)
                         $("#tax_type").text(data.type)
                         $('#tax_rate').val(data.rate)
                         $('#tax_section').val(data.section)
                         $('#tax_desc').val(data.description)
                     }
                 },
                 error: function(xhr) {
                     console.log('Error:', xhr.responseText);
                 }
             });
         });
         $('.add_tax').on('click', function(){
           $("#tax_type_new").val($(this).attr('data-type')) 
         })
     </script>
 @endpush
