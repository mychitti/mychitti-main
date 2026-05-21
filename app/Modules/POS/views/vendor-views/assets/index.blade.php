 @extends('layouts.vendor.app')

 @section('title', translate('Company Assets (Properties)'))

 @push('css_or_js')
     <meta name="csrf-token" content="{{ csrf_token() }}">
 @endpush

 @section('content')
     <div class="content container-fluid">
         <div class="page-header d-flex flex-wrap w-100 justify-content-between">
             <h1 class="page-header-title"><i class="tio-filter-list"></i> Company Assets (Properties)<span
                     class="badge badge-soft-dark ml-2" id="itemCount">{{ count($assets) }}</span></h1>
             <div class="d-flex align-items-center">
                 @if (hasPermission('assets_company_assets', 'alot'))
                     <a type="button" data-toggle="modal" style="white-space:nowrap" data-target="#assetAlotModal"
                         class="btn btn--primary">Alot to
                         Staff</a>
                 @endif
                 @if (hasPermission('assets_company_assets', 'add'))
                     <a type="button" data-toggle="modal" style="white-space:nowrap" data-target="#assetModal"
                         class="btn btn--primary mx-2">+ Add New
                     </a>
                 @endif
             </div>
         </div>
         @if (hasPermission('assets_company_assets', 'list'))

             <div class="card">
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
                                 <th class="border-0">Name</th>
                                 <th class="border-0">brand</th>
                                 <th class="border-0">Model Number</th>
                                 <th class="border-0">Alotted Qty</th>
                                 <th class="border-0">Free Quantity</th>
                                 <th class="text-center border-0">{{ translate('messages.action') }}</th>
                             </tr>
                         </thead>

                         <tbody id="set-rows">
                             @foreach ($assets as $key => $asset)
                                 <tr>
                                     <td>{{ $key + $assets->firstItem() }}</td>
                                     <td>
                                         <img class="avatar avatar-lg mr-3 onerror-image"
                                             src="{{ \App\CentralLogics\Helpers::onerror_image_helper($asset->inventoryItem?->image, asset('storage/app/public/inventory-item/') . '/' . $asset->inventoryItem?->image, asset('public/assets/admin/img/160x160/img2.jpg'), 'inventory-item/') }}"
                                             data-onerror-image="{{ asset('public/assets/admin/img/160x160/img2.jpg') }}"
                                             alt="{{ $asset->inventoryItem?->name }} image">
                                         {{ $asset->inventoryItem?->item_name }}
                                     </td>
                                     {{-- <td>{{ $asset->inventoryItem?->category }}</td> --}}
                                     <td>{{ $asset->inventoryItem?->brand }}</td>
                                     <td>{{ $asset->inventoryItem?->model_number }}</td>
                                     <td><span class="badge badge-soft-warning">{{ $asset->alotted_quantity }}</span></td>
                                     <td><span
                                             class="badge badge-soft-success">{{ $asset->quantity - $asset->alotted_quantity }}</span>
                                     </td>
                                     <td>
                                         {{-- <div class="btn--container justify-content-center">
                                         <a class="btn action-btn btn--danger btn-outline-danger form-alert"
                                             href="javascript:" data-id="category-{{ $asset['id'] }}"
                                             data-message="{{ translate('Want to delete this asset') }}"
                                             title="{{ translate('messages.delete_asset') }}"><i
                                                 class="tio-delete-outlined"></i>
                                         </a>
                                         <form action="{{ route('vendor.asset.delete', $asset->id) }}"
                                             id="category-{{ $asset['id'] }}" method="get">
                                             @csrf
                                         </form>
                                     </div> --}}
                                     </td>
                                 </tr>
                             @endforeach
                         </tbody>
                     </table>
                     @if (count($assets))
                         <hr>
                         {!! $assets->links() !!}
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
                 <div class="modal fade" id="editAdModal" data-backdrop="static" data-keyboard="false" tabindex="-1"
                     aria-labelledby="staticBackdropLabel" aria-hidden="true">
                     <div class="modal-dialog">
                         <div class="modal-content">
                             <div class="modal-header">
                                 <h5 class="modal-title" id="staticBackdropLabel">Edit Ad</h5>
                                 <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                     <span aria-hidden="true">&times;</span>
                                 </button>
                             </div>
                             <div class="modal-body">
                                 <form id="edit_form" method="POST"
                                     action="{{ route('vendor.task-salary-categories.update', 0) }}">
                                     @csrf
                                     @method('PUT')
                                     <input type="hidden" name="edit_id" class="edit_id">
                                     <label for="">Name</label>
                                     <input type="text" required placeholder="Enter Category Name" name="name"
                                         class="form-control ad_name">
                                     <label for="">Amount</label>
                                     <input type="number" step="0.001" required placeholder="Enter Amount" name="amount"
                                         class="form-control ad_amount">
                                     <button class="btn btn--primary mt-2">Update</button>
                                 </form>
                             </div>
                         </div>
                     </div>
                 </div>
                 <!-- End Table -->
             </div>
             <div class="page-header d-flex w-100 justify-content-between">
                 <h1 class="page-header-title"><i class="tio-filter-list"></i> Asset Alotments<span
                         class="badge badge-soft-dark ml-2" id="itemCount">{{ count($asset_alotments) }}</span></h1>

             </div>
             <div class="card">
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
                                 <th class="border-0">Asset</th>
                                 <th class="border-0">Alotted To</th>
                                 <th class="border-0">Alotted Qty</th>
                                 <th class="border-0">Issued At</th>
                                 <th class="border-0">Status</th>
                                 <th class="border-0">Returned At</th>
                                 <th class="border-0">Action</th>
                             </tr>
                         </thead>

                         <tbody id="set-rows">
                             @foreach ($asset_alotments as $key => $asset)
                                 <tr>
                                     <td>{{ $key + $asset_alotments->firstItem() }}</td>
                                     <td>
                                         <img class="avatar avatar-lg mr-3 onerror-image"
                                             src="{{ \App\CentralLogics\Helpers::onerror_image_helper($asset->inventoryItem?->image, asset('storage/app/public/inventory-item/') . '/' . $asset->inventoryItem?->image, asset('public/assets/admin/img/160x160/img2.jpg'), 'inventory-item/') }}"
                                             data-onerror-image="{{ asset('public/assets/admin/img/160x160/img2.jpg') }}"
                                             alt="{{ $asset->inventoryItem?->name }} image">
                                         <div>
                                             <span>{{ $asset->inventoryItem?->item_name }}</span>
                                             <small>{{ $asset->inventoryItem?->brand }} |
                                                 {{ $asset->inventoryItem?->model_number }}</small>
                                         </div>
                                     </td>
                                     <td>{{ $asset->employee?->f_name . ' ' . $asset->employee?->l_name }}</td>
                                     <td><span class="badge badge-soft-warning">{{ $asset->alotted_qty }}</span></td>
                                     <td>{{ $asset->created_at }}
                                     </td>
                                     <td>
                                         @if ($asset->returned)
                                             <span class="badge badge-soft-danger">Returned</span>
                                         @else
                                             <span class="badge badge-soft-success">Alotted</span>
                                         @endif
                                     </td>
                                     <td>{{ $asset->returned_at }}</td>
                                     <td>
                                         <div class="btn--container justify-content-center">
                                             <a class="btn action-btn btn--primary btn-outline-primary show_details"
                                                 data-id="{{ $asset->id }}" data-toggle="modal"
                                                 data-target="#alotmentDetailsModal"><i class="tio-visible"></i>
                                             </a>
                                         </div>
                                     </td>
                                 </tr>
                             @endforeach
                         </tbody>
                     </table>
                     @if (count($assets))
                         <hr>
                         {!! $assets->links() !!}
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
                 <div class="modal fade" id="alotmentDetailsModal" tabindex="-1" aria-labelledby="exampleModalLabel"
                     aria-hidden="true">
                     <div class="modal-dialog">
                         <div class="modal-content">
                             <div class="modal-header">
                                 <h5 class="modal-title" id="exampleModalLabel">Alotment Details</h5>
                                 <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                     <span aria-hidden="true">&times;</span>
                                 </button>
                             </div>
                             <div class="modal-body" id="detailsBody">

                             </div>
                         </div>
                     </div>
                 </div>
                 <div class="modal fade" id="editAdModal" data-backdrop="static" data-keyboard="false" tabindex="-1"
                     aria-labelledby="staticBackdropLabel" aria-hidden="true">
                     <div class="modal-dialog">
                         <div class="modal-content">
                             <div class="modal-header">
                                 <h5 class="modal-title" id="staticBackdropLabel">Edit Ad</h5>
                                 <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                     <span aria-hidden="true">&times;</span>
                                 </button>
                             </div>
                             <div class="modal-body">
                                 <form id="edit_form" method="POST"
                                     action="{{ route('vendor.task-salary-categories.update', 0) }}">
                                     @csrf
                                     @method('PUT')
                                     <input type="hidden" name="edit_id" class="edit_id">
                                     <label for="">Name</label>
                                     <input type="text" required placeholder="Enter Category Name" name="name"
                                         class="form-control ad_name">
                                     <label for="">Amount</label>
                                     <input type="number" step="0.001" required placeholder="Enter Amount"
                                         name="amount" class="form-control ad_amount">
                                     <button class="btn btn--primary mt-2">Update</button>
                                 </form>
                             </div>
                         </div>
                     </div>
                 </div>
                 <!-- End Table -->
             </div>
         @endif

         <!-- End Card -->
     </div>
     <div class="modal fade" id="assetAlotModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
         <div class="modal-dialog">
             <div class="modal-content">
                 <div class="modal-header">
                     <h5 class="modal-title" id="exampleModalLabel">Alot to Staff</h5>
                     <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                         <span aria-hidden="true">&times;</span>
                     </button>
                 </div>
                 <form method="POST" action="{{ route('vendor.asset.alot') }}">
                     @csrf

                     <div class="modal-body">
                         <div class="row">

                             <div class="col-md-12">
                                 <label for="">Asset</label>
                                 <select name="item_id" id="alot_item_id" required
                                     class="form-control js-select2-custom">
                                     <option value=""></option>
                                     @foreach ($assets as $key => $value)
                                         <option data-qty = "{{ $value->quantity }}"
                                             value="{{ $value->inventoryItem?->id }}">
                                             {{ $value->inventoryItem?->item_name }}</option>
                                     @endforeach
                                 </select>
                             </div>

                             <div class="col-md-6">
                                 <label for="">Alot to</label>
                                 <select name="staff_id" required id="" class="form-control js-select2-custom">
                                     <option value=""></option>
                                     @foreach ($staff as $key => $value)
                                         <option value="{{ $value->id }}">
                                             {{ $value->f_name . ' ' . $value->l_name . '( ID: ' . ($value->employee_id ?? $value->id) . ')' }}
                                         </option>
                                     @endforeach
                                 </select>
                             </div>
                             <div class="col-md-6">
                                 <label for="">Alot Quantity</label>
                                 <input type="number" name="qty" id="qty_inp2" class="form-control">
                                 <small class="alert_text2 text-danger"></small>

                             </div>
                             <div class="col-md-12">
                                 <label for="">File Upload</label>
                                 <input type="file" name="file" accept="image/*" class="form-control"
                                     id="">
                             </div>
                             <div class="col-md-12">
                                 <label for="">Condition (Optional)</label>
                                 <textarea name="condition" placeholder="Start typing..." id="" class="form-control"></textarea>
                             </div>
                         </div>
                     </div>
                     <div class="modal-footer">
                         <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                         <button type="submit" class="btn btn-primary">Save</button>
                     </div>
                 </form>
             </div>
         </div>
     </div>
     <div class="modal fade" id="assetModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
         <div class="modal-dialog">
             <div class="modal-content">
                 <div class="modal-header">
                     <h5 class="modal-title" id="exampleModalLabel">Add Asset</h5>
                     <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                         <span aria-hidden="true">&times;</span>
                     </button>
                 </div>
                 <form method="POST" action="{{ route('vendor.asset.store') }}">
                     @csrf

                     <div class="modal-body">
                         <div class="row">
                             <div class="col-md-12">
                                 <label for="">Category</label>
                                 <select name="category" class="js-example-tags"
                                     data-placeholder="Ex: Computer Equipment" id="statusSelect2">
                                     <option value=""></option>
                                     @foreach ($categories as $sc)
                                         <option value="{{ $sc->name }}">{{ $sc->name }}</option>
                                     @endforeach
                                 </select>
                             </div>
                             <div class="col-md-6">
                                 <label for="">Item</label>
                                 <select name="item_id" id="item_id" class="form-control js-select2-custom">
                                     <option value=""></option>
                                     @foreach ($inventory_items as $key => $value)
                                         <option data-qty = "{{ $value->stock }}" value="{{ $value->id }}">
                                             {{ $value->item_name }}</option>
                                     @endforeach
                                 </select>
                             </div>
                             <div class="col-md-6">
                                 <label for="">Qty</label>
                                 <input type="number" placeholder="Ex: 12" name="qty" id="qty_inp"
                                     class="form-control">
                                 <small class="alert_text text-danger"></small>
                             </div>
                             <div class="col-md-6">
                                 <label>Depreciation Method <span data-toggle="tooltip" data-placement="right"
                                         title="Straight Line: same value loss each year. Reducing Balance: higher loss in early years.">
                                         <i class="tio-info-outined"></i>
                                     </span></label>
                                 <select name="depreciation_method" class="form-control js-select2-custom">
                                     <option value="straight_line">Straight Line</option>
                                     <option value="reducing_balance">Reducing Balance</option>
                                 </select>
                             </div>
                             <div class="col-md-6">
                                 <label>Useful Life</label>
                                 <div class="d-flex">
                                     <input type="number" placeholder="Ex: 2" class="form-control"
                                         name="useful_life_count" required>
                                     <select name="useful_life_unit" class="form-control js-select2-custom">
                                         <option value="month">Months</option>
                                         <option value="year">Years</option>
                                         <option value="day">Days</option>
                                     </select>
                                 </div>
                             </div>




                         </div>
                     </div>
                     <div class="modal-footer">
                         <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                         <button type="submit" class="btn btn-primary">Save</button>
                     </div>
                 </form>
             </div>
         </div>
     </div>

 @endsection

 @push('script_2')
     <script src="{{ asset('public/assets/admin') }}/js/view-pages/product-index.js"></script>

     <script>
         // trigger on qty input change or keyup
         $("#qty_inp").on('keyup', function(e) {
             validateQty($(this));
         });
         $(function() {
             $('[data-toggle="tooltip"]').tooltip()
         })

         function validateQty($input) {
             var input_qty = parseFloat($input.val());
             console.log(input_qty)
             if (isNaN(input_qty) || input_qty <= 0) return;

             var selectedData = $("#item_id").select2('data');
             if (selectedData.length > 0) {
                 var dataQty = parseFloat(selectedData[0].element.dataset.qty);
                 console.log(dataQty)
                 if (input_qty > dataQty) {
                     $('.alert_text').text("Entered quantity exceeds available stock: " + dataQty);
                     $input.val(dataQty);

                     setTimeout(function() {
                         $('.alert_text').text("");
                     }, 3000);
                 }
             }
         }
         // trigger on qty input change or keyup
         $("#qty_inp2").on('keyup', function(e) {
             validateQty2($(this));
         });

         function validateQty2($input) {
             var input_qty = parseFloat($input.val());
             console.log(input_qty)
             if (isNaN(input_qty) || input_qty <= 0) return;

             var selectedData = $("#alot_item_id").select2('data');
             if (selectedData.length > 0) {
                 var dataQty = parseFloat(selectedData[0].element.dataset.qty);
                 console.log(dataQty)
                 if (input_qty > dataQty) {
                     $('.alert_text2').text("Entered quantity exceeds available stock: " + dataQty);
                     $input.val(dataQty);

                     setTimeout(function() {
                         $('.alert_text2').text("");
                     }, 3000);
                 }
             }
         }




         $(".show_details").on('click', function() {
             var id = $(this).attr('data-id')
             $.ajaxSetup({
                 headers: {
                     'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                 }
             });
             $.ajax({
                 url: "{{ route('vendor.asset.get-alotment-details') }}",
                 method: 'POST',
                 data: {
                     id: id,
                 },
                 success: function(data) {
                     $("#detailsBody").html(data)
                 }
             });
         })
         var routeTemplate = "{{ route('vendor.task-salary-categories.update', ['__ID__']) }}";

         $(".edit_btn").on('click', function() {
             var id = $(this).attr('data-id')
             var amount = $(this).attr('data-amount')
             var ad_name = $(this).attr('data-name')
             $('.edit_id').val(id);
             $('.ad_amount').val(amount);
             $('.ad_name').val(ad_name);

             var url = routeTemplate.replace('__ID__', id);
             $('#edit_form').attr('action', url);
         })
     </script>
 @endpush
