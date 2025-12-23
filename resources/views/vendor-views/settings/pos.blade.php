 @extends('layouts.vendor.app')

 @section('title', 'POS Settings')

 @push('css_or_js')
     <meta name="csrf-token" content="{{ csrf_token() }}">
     <style>
         .form-row {
             margin-top: 6px;
         }

         .ck.ck-reset {
             width: 100% !important;
         }
     </style>
     <style>
         .template-option {
             display: inline-block;
             border: 2px solid #ccc;
             border-radius: 6px;
             padding: 5px;
             {{-- margin: 5px; --}} cursor: pointer;
             text-align: center;
             transition: border-color 0.2s ease;
             width: 100%;
             /* adjust to your preview size */
         }

         .template-option input[type="radio"] {
             display: none;
         }

         .template-option img {
             max-width: 100%;
             height: auto;
             display: block;
         }

         .template-option.selected {
             border-color: #007bff;
             background: #0000ff0d;
         }
     </style>
 @endpush

 @section('content')
     <div class="content container-fluid">
         <!-- Page Header -->
         <div class="page-header">
             <h1 class="page-header-title"><i class="tio-filter-list"></i>POS Settings</h1>
         </div>
         <!-- End Page Header -->

         <form class="w-100 p-0 " enctype="multipart/form-data" action="{{ route('vendor.business-settings.settings.save') }}"
             method="post">
             @csrf
             <div class="card mb-1">
                 <div class="card-header">
                     <h2 class="card-title h4">
                         <i class="tio-settings"></i>
                         <span>{{ translate('messages.configuration') }}</span>
                     </h2>
                 </div>

                 <!-- Body -->
                 <div class="card-body row align-items-end">
                     <div class="col-sm-4 p-2 ">
                         <div class="form-group mb-0 ">
                             <label class="d-flex justify-content-between switch toggle-switch-sm text-dark"
                                 for="token_footer_line_status">
                                 <span>Footer Line <span class="form-label-secondary" data-toggle="tooltip"
                                         data-placement="right"
                                         data-original-title="{{ translate('messages.if enabled, this line will show in footer in token ') }}"><img
                                             src="{{ asset('/public/assets/admin/img/info-circle.svg') }}"></span></span>
                                 <input type="hidden" name="token_footer_line_status" value="0">
                                 <input type="checkbox" class="toggle-switch-input" name="token_footer_line_status"
                                     id="token_footer_line_status" value="1"
                                     {{ $store?->token_footer_line_status ? 'checked' : '' }}>
                                 <span class="toggle-switch-label">
                                     <span class="toggle-switch-indicator"></span>
                                 </span>
                             </label>
                             <input type="text" id="token_footer_line" name="token_footer_line"
                                 value="{{ $store?->token_footer_line }}" class="form-control">
                         </div>
                     </div>
                     <div class="col-sm-4 p-2 ">
                         <div class="form-group mb-0  p-1 border rounded ">
                             <label class="d-flex justify-content-between switch toggle-switch-sm text-dark"
                                 for="token_det_in_account">
                                 <span>Show Token Details in Accounts <span class="form-label-secondary"
                                         data-toggle="tooltip" data-placement="right"
                                         data-original-title="{{ translate('messages.if enabled, token details will show up in accounts') }}"><img
                                             src="{{ asset('/public/assets/admin/img/info-circle.svg') }}"></span></span>
                                 <input type="hidden" name="token_det_in_account" value="0">
                                 <input type="checkbox" class="toggle-switch-input" name="token_det_in_account"
                                     id="token_det_in_account" value="1"
                                     {{ $store?->token_det_in_account ? 'checked' : '' }}>
                                 <span class="toggle-switch-label">
                                     <span class="toggle-switch-indicator"></span>
                                 </span>
                             </label>
                         </div>
                     </div>
                     <div class="col-sm-4 p-2 ">
                         <div class="form-group mb-0  p-1 border rounded ">
                             <label class="d-flex justify-content-between switch toggle-switch-sm text-dark"
                                 for="auto_invoice_pos">
                                 <span>Automatically Invoice Generate <span class="form-label-secondary"
                                         data-toggle="tooltip" data-placement="right"
                                         data-original-title="{{ translate('messages.when a token is generated, an invoice will be created automatically') }}"><img
                                             src="{{ asset('/public/assets/admin/img/info-circle.svg') }}"></span></span>
                                 <input type="hidden" name="auto_invoice_pos" value="0">
                                 <input type="checkbox" class="toggle-switch-input" name="auto_invoice_pos"
                                     id="auto_invoice_pos" value="1" {{ $store?->auto_invoice_pos ? 'checked' : '' }}>
                                 <span class="toggle-switch-label">
                                     <span class="toggle-switch-indicator"></span>
                                 </span>
                             </label>
                         </div>
                     </div>
                     <div class="col-sm-4 p-2 mb-3">
                         <label class="form-label" for="pos_daybook_entry">POS Daybook entry time</label>
                         <select class="form-control" name="pos_daybook_entry" id="pos_daybook_entry">
                             <option value="end_of_day" {{ $store?->pos_daybook_entry == 'end_of_day' ? 'selected' : '' }}>
                                 End of Day</option>
                             <option value="token_generate"
                                 {{ $store?->pos_daybook_entry == 'token_generate' ? 'selected' : '' }}>On Token Generate
                             </option>
                         </select>
                     </div>
                     <div class="col-sm-4 p-2 mb-3">
                         <label class="form-label" for="pos_account">POS Account entry time</label>
                         <select class="form-control" name="pos_account_entry" id="pos_account">
                             <option value="end_of_day" {{ $store?->pos_account_entry == 'end_of_day' ? 'selected' : '' }}>
                                 End of Day</option>
                             <option value="token_generate"
                                 {{ $store?->pos_account_entry == 'token_generate' ? 'selected' : '' }}>On Token Generate
                             </option>
                         </select>
                     </div>
                     <div class="col-sm-4 p-2 mb-3">
                         <label class="form-label" for="address_on_token">Address on Token</label>
                         <select class="form-control" name="address_on_token" id="address_on_token">
                             <option value="branch" {{ $store?->address_on_token == 'branch' ? 'selected' : '' }}>
                                 Branch Address</option>
                             <option value="store" {{ $store?->address_on_token == 'store' ? 'selected' : '' }}>Store
                                 Address
                             </option>
                         </select>
                     </div>
                     {{-- <div class="col-sm-4 p-2 mb-3">
                         <div class="form-group mb-0  p-1 border rounded ">
                             <label class="d-flex justify-content-between switch toggle-switch-sm text-dark"
                                 for="kitchen_token">
                                 <span>Kitchen Token <span class="form-label-secondary" data-toggle="tooltip"
                                         data-placement="right"
                                         data-original-title="{{ translate('messages.When a customer token is created, a kitchen token will also be generated.') }}"><img
                                             src="{{ asset('/public/assets/admin/img/info-circle.svg') }}"></span></span>
                                 <input type="hidden" name="kitchen_token" value="0">
                                 <input type="checkbox" class="toggle-switch-input" name="kitchen_token"
                                     id="kitchen_token" value="1" {{ $store?->kitchen_token ? 'checked' : '' }}>
                                 <span class="toggle-switch-label">
                                     <span class="toggle-switch-indicator"></span>
                                 </span>
                             </label>
                         </div>
                     </div> --}}
                     <div class="col-md-7">
                         <label class="form-label" for="">Token Menu Design</label>
                         <div class="d-flex flex-wrap">
                             @php $tempCount = [1,2,3,4]; @endphp
                             @foreach ($tempCount as $key => $value)
                                 @php  $select = isset($store) && $store && $store->pos_menu_design == $value ?  true : false; @endphp
                                 <div class="col-md-3 p-1">
                                     <div class="  template-option {{ $select ? 'selected' : '' }}">
                                         <label>
                                             <input class="" {{ $select ? 'checked' : '' }} type="radio"
                                                 name="pos_menu_design" value="{{ $value }}">
                                             <img src="{{ asset('storage/app/public/preview/') . '/' . 'token_generate_' . $value . '.png' }}"
                                                 alt=" Design {{ $value }} Preview">
                                             <div> Design {{ $value }}</div>
                                         </label>
                                     </div>
                                 </div>
                             @endforeach
                         </div>
                     </div>
                     <div class="col-md-3">
                         <label class="form-label" for="">Branch Card Colors</label>
                         <div class="" style="display:grid; grid-template-columns: repeat(2, 1fr);gap: 9px;">
                             @php $colors = _posDashboardColors(); @endphp
                             @foreach ($colors as $key => $value)
                                 <div class="d-flex">
                                     <input type="color" value="{{ $value }}" name="{{ $key }}"
                                         id="">
                                     &nbsp;<label for=""> {{ translate('messages.' . $key) }}</label>
                                 </div>
                             @endforeach
                         </div>
                     </div> 

                     <div class="col-12  mb-3">
                         <button style="float:right" class="btn btn-lg btn-primary my-2">Update</button>
                     </div>
                 </div>
             </div>
         </form>
         <div class="page-header">
             <h1 class="page-header-title"><i class="tio-filter-list"></i>Order Type</h1>
         </div>
         <!-- End Page Header -->
         <div class="row">
             <div class="col-md-6">
                 <form class="w-100 p-0" enctype="multipart/form-data"
                     action="{{ route('vendor.pos.order-type.store') }}" method="post">
                     @csrf
                     <div class="card mb-1">
                         <div class="card-header">
                             <h2 class="card-title h4">
                                 <i class="tio-settings"></i>
                                 <span>{{ translate('messages.add new') }}</span>
                             </h2>
                         </div>

                         <!-- Body -->
                         <div class="card-body row align-items-end">
                             <div class="col-md-6">
                                 <div class="form-group">
                                     <label for="order_type_name">{{ translate('messages.order type name') }}</label>
                                     <input type="text" name="order_type_name" id="order_type_name"
                                         class="form-control"
                                         placeholder="{{ translate('messages.enter order type name') }}" required>
                                 </div>
                             </div>
                             <div class="col-md-6">
                                 <div class="form-group">
                                     <label for="order_type_icon">{{ translate('messages.icon') }}</label>
                                     <input type="file" name="icon" id="order_type_icon" class="form-control">
                                 </div>
                             </div>

                             <div class="col-12  mb-3">
                                 <button style="float:right" class="btn btn-primary my-2">Update</button>
                             </div>
                         </div>
                     </div>
                 </form>
             </div>
             <div class="col-md-6">
                 <div class="table-responsive datatable-custom">
                     <table id="columnSearchDatatable"
                         class="table table-borderless table-thead-bordered table-align-middle"
                         data-hs-datatables-options='{
                            "isResponsive": false,
                            "isShowPaging": false,
                            "paging":false,
                        }'>
                         <thead class="thead-light">
                             <tr>
                                 <th class="border-0">{{ translate('sl') }}</th>
                                 <th class="border-0 ">Order type</th>
                                 <th class="border-0 ">Action</th>
                             </tr>
                         </thead>

                         <tbody>
                             @foreach ($order_type as $key => $value)
                                 <tr>
                                     <td>{{ $key + 1 }}</td>
                                     <td>
                                         <div class="d-flex align-items-center">
                                             <span class="media align-items-center">
                                                 <img class="img--ratio-1 w-auto h--50px rounded mr-2 onerror-image"
                                                     src="{{ \App\CentralLogics\Helpers::onerror_image_helper($value['icon'], asset('storage/app/public/store/order_type/') . '/' . $value['icon'], asset('public/assets/admin/img/900x400/img1.jpg'), 'store/order_type/') }}"
                                                     data-onerror-image="{{ asset('/public/assets/admin/img/900x400/img1.jpg') }}"
                                                     alt="{{ $value->name }} image">
                                             </span>
                                             <span class="d-block font-size-sm text-body">
                                                 {{ $value->name }}
                                             </span>
                                         </div>
                                     </td>
                                     <td>
                                         <div class="btn--container">
                                             <a type="button" data-toggle="modal" data-target="#editOrderTypeModal"
                                                 class="btn action-btn btn--primary btn-outline-primary edit_btn"
                                                 title="{{ translate('messages.edit branch') }}"
                                                 data-id = "{{ $value->id }}"
                                                 data-icon ="{{ asset('storage/app/public/store/order_type') . '/' . $value->icon }}"
                                                 data-icon_name ="{{ $value->icon }}" data-name = "{{ $value->name }}"
                                                 data-type="{{ $value->type }}"><i class="tio-edit"></i>
                                             </a>
                                             <a class="btn action-btn btn--danger btn-outline-danger form-alert"
                                                 href="javascript:" data-id="category-{{ $value->id }}"
                                                 data-message="{{ translate('Want to delete this order type') }}"
                                                 title="{{ translate('messages.delete_order_type') }}"><i
                                                     class="tio-delete-outlined"></i>
                                             </a>
                                             <form action="{{ route('vendor.pos.order-type.delete', [$value->id]) }}"
                                                 method="get" id="category-{{ $value->id }}">
                                                 @csrf @method('get')
                                             </form>
                                         </div>

                                     </td>
                                 </tr>
                             @endforeach
                         </tbody>
                     </table>
                 </div>
             </div>

         </div>

     </div>
     <div class="modal fade" id="editOrderTypeModal" tabindex="-1" aria-labelledby="exampleModalLabel"
         aria-hidden="true">
         <div class="modal-dialog">
             <div class="modal-content">
                 <div class="modal-header">
                     <h5 class="modal-title" id="editBranchModalTitle">Edit Branch</h5>
                     <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                         <span aria-hidden="true">&times;</span>
                     </button>
                 </div>
                 <form action="{{ route('vendor.pos.order-type.update') }}" method="post"
                     enctype="multipart/form-data">
                     <div class="modal-body">
                         @csrf
                         <input type="hidden" name="edit_id" class="edit_id">
                         <label for="">Order Type Name</label>
                         <input type="text" name="name" placeholder="Name" class="form-control edit_name">

                         <label for="">Icon <i><a href="" target="_blank" class="edit_icon">View
                                     Current</a></i></label>
                         <input type="file" name="icon" class="form-control ">

                     </div>
                     <div class="modal-footer">
                         <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                         <button type="submit" class="btn btn-primary">Update</button>
                     </div>
                 </form>
             </div>
         </div>
     </div>
 @endsection

 @push('script_2')
     <script src="{{ asset('public/assets/admin') }}/js/view-pages/vendor/product-index.js"></script>
     <script>
         $(".edit_btn").on('click', function() {
             $(".edit_name").val($(this).attr('data-name'))
             $(".edit_id").val($(this).attr('data-id'))
             var icon = $(this).attr('data-icon');
             var filename = $(this).attr('data-icon_name');
             if (filename) {
                 $(".edit_icon").show();
                 $(".edit_icon").attr('href', icon);
             } else {
                 $(".edit_icon").hide()
             }
         })
     </script>
     <script>
         // Highlight selected template
         $('input[name="pos_menu_design"]').on('change', function() {
             $('.template-option').removeClass('selected');
             $(this).closest('.template-option').addClass('selected');
         });
     </script>
 @endpush
