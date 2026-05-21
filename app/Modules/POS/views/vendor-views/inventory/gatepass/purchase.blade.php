 @extends('layouts.vendor.app')
 @section('title', 'Purchase Gatepasses')
 @push('css_or_js')
     <link href="{{ asset('public/assets/admin/css/date_range.css') }}" rel="stylesheet">
 @endpush

 @section('content')

     <div class="content container-fluid p-3">
         <div class="page-header">
             <div class="d-flex flex-wrap px-3 w-100">
                 <div class="d-flex w-100 flex-wrap justify-content-between  align-orders-center">
                     <h1 class="page-header-title mb-2 d-flex gap-2 flex-wrap">
                         <span class="page-header-icon">
                             <img src="{{ asset('public/assets/admin/img/role.png') }}" class="w--26" alt="">
                         </span>
                         <div class="d-flex align-items-start">
                             <div class="d-flex flex-column">
                                 <span>Inventory Gatepasses </span>
                             </div>
                             <span class="badge badge-soft-dark ml-2" id="itemCount">{{ count($gatepass) }}</span>
                         </div>
                     </h1>
                     <div class="d-flex gap-1">
                         <a href="{{ route('vendor.library.gatepass.add', ['purchase']) }}" class="btn btn-primary">+ Purchase
                             Gatepass</a>
                         <a href="{{ route('vendor.library.gatepass.add', ['sale']) }}" class="btn btn-primary">+ Sale
                             Gatepass</a>
                     </div>
                 </div>
             </div>
         </div>
         <!-- Page Heading -->
         <div class="d-flex flex-wrap justify-content-between align-items-center mb-5 mt-4 __gap-12px">
             <div class="js-nav-scroller hs-nav-scroller-horizontal mt-2">
                 <!-- Nav -->
                 <ul class="nav nav-tabs border-0 nav--tabs nav--pills">
                     <li class="nav-item">
                         <a class="nav-link {{ Request::is('documents/gatepass/list/purchase') ? 'active' : '' }}"
                             href="{{ route('vendor.documents.gatepass.list', ['purchase']) }}">
                             Purchase
                         </a>
                     </li>
                     <li class="nav-item">
                         <a class="nav-link {{ Request::is('documents/gatepass/list/purchase-return') ? 'active' : '' }}"
                             href="{{ route('vendor.documents.gatepass.list', ['purchase-return']) }}">
                             Purchase Return
                         </a>
                     </li>
                     <li class="nav-item">
                         <a class="nav-link {{ Request::is('documents/gatepass/list/sale') ? 'active' : '' }}"
                             href="{{ route('vendor.documents.gatepass.list', ['sale']) }}">
                             Sale
                         </a>
                     </li>
                     <li class="nav-item">
                         <a class="nav-link {{ Request::is('documents/gatepass/list/sale-return') ? 'active' : '' }}"
                             href="{{ route('vendor.documents.gatepass.list', ['sale-return']) }}">
                             Sale Return
                         </a>
                     </li>

                 </ul>
                 <!-- End Nav -->
             </div>
         </div>



         <div class="card mt-3">



             <div class="table-responsive datatable-custom" id="table">
                 <table id="datatable"
                     class="table table-borderless table-thead-bordered table-nowrap table-align-middle card-table">
                     <thead class="thead-light">
                         <tr>
                             <th class="border-0">{{ translate('sl') }}</th>
                             <th class="border-0">Gatepass Number</th>
                             <th class="border-0">Invoice Id</th>
                             <th class="border-0">Date</th>
                             <th class="border-0">Total Items</th>
                             <th class="border-0">Total Qty</th>
                             @if ($type == 'return-gp')
                                 <th>Return Qty</th>
                                 <th class="border-0">Return Gatepass</th>
                             @endif
                             <th class="border-0">Driver</th>
                             <th class="border-0">Action</th>
                         </tr>
                     </thead>

                     <tbody id="">
                         @foreach ($gatepass as $key => $gp)
                             <tr>
                                 <td>{{ $key + 1 }}</td>
                                 <td>
                                     <div style="">
                                         <a
                                             href="{{ $gp->pdf ? asset('storage/app/public/inventory/gatepass/') . '/' . $gp->pdf : 'javascript:;' }}">
                                             {{ $gp->gatepass_number ?? 'N/A' }}
                                         </a>
                                     </div>
                                 </td>
                                 <td>
                                     <div style="">
                                         <a
                                             href="{{ $gp->invoice?->pdf ? asset('storage/app/public/invoice/') . '/' . $gp->invoice?->pdf : 'javascript:;' }}">
                                             {{ $gp->invoice_id ?? 'N/A' }}
                                         </a>
                                     </div>
                                 </td>
                                 <td>{{ $gp->created_at }}</td>
                                 <td>{{ count($gp->gpItems) }}</td>
                                 <td>{{ $gp->gpItems?->sum('qty') }}</td>
                                 @if ($type == 'return-gp')
                                     <td>
                                         {{ $gp->gpItems?->sum('qty') }}</td>
                                     <td>
                                         <a style="width: fit-content; padding: 5px 10px !important ;"
                                             class="btn action-btn btn-outline-primary"
                                             href="{{ $gp->return_pdf ? asset('storage/app/public/inventory/gatepass/') . '/' . $gp->return_pdf : 'javascript:;' }}">
                                             View Return Gatepass
                                         </a>
                                     </td>
                                 @endif
                                 <td>
                                     @if ($gp->driver_data)
                                         @php $dd = json_decode($gp->driver_data); @endphp
                                         <b>Name:</b> {{ $dd->name ?? '' }} <br>
                                         <b>Phone:</b> {{ $dd->phone ?? '' }} <br>
                                         <b>Address:</b> {{ $dd->address ?? '' }} <br>
                                     @endif
                                 </td>

                                 <td>
                                     @if ($type !== 'return-gp')
                                         @if (!$gp->returned)
                                             <a href="{{ route('vendor.inventory.gatepass.return', [$gp->id]) }}"
                                                 style="width: fit-content; padding: 5px 10px !important ;"
                                                 class="btn action-btn btn-outline-primary">Return</a>
                                         @endif
                                         <div class="dropdown">
                                             <button class="btn p-1 dropdown-toggle"
                                                 style="position: absolute; right: -5px; top: -22px;" type="button"
                                                 data-toggle="dropdown" aria-expanded="false">
                                                 <img style="width: 24px; filter: contrast(0)"
                                                     src="{{ asset('storage/app/public/util/10025520.png') }}"
                                                     alt="action" />
                                             </button>
                                             <div class="dropdown-menu">
                                                 <a class="dropdown-item text-danger form-alert" href="javascript:"
                                                     data-id="mark-paid-{{ $gp->id }}"
                                                     data-message="{{ translate('Want to delete this gatepass') }}"
                                                     title="{{ translate('messages.delete') }}"><i class="tio-delete"></i>
                                                     Delete
                                                 </a>
                                                 <form action="{{ route('vendor.documents.gatepass.delete', [$gp->id]) }}"
                                                     method="get" id="mark-paid-{{ $gp->id }}">
                                                     @csrf @method('get')
                                                 </form>
                                             </div>
                                         </div>
                                     @endif
                                 </td>
                             </tr>
                         @endforeach

                     </tbody>
                 </table>
                 @if (!count($gatepass))
                     <div class="empty--data">
                         <img src="{{ asset('/public/assets/admin/svg/illustrations/sorry.svg') }}" alt="public">
                         <h5>
                             {{ translate('no_data_found') }}
                         </h5>
                     </div>
                 @endif
             </div>
         </div>
     </div>

 @endsection
 @push('script_2')
     <script>
         $("#check_all").on('change', function() {
             if ($(this).prop('checked') == true) {
                 $("#check_all_label").text('Deselect All');
                 $(".check_select").prop('checked', true)
                 $('.delete_selected_btn').show()
             } else {
                 $("#check_all_label").text('Select All');
                 $(".check_select").prop('checked', false)
                 $('.delete_selected_btn').hide()
             }
         })
         $(".check_select").on('change', function() {
             if ($('.check_select:checked').length > 0) {
                 $('.delete_selected_btn').show();
             } else {
                 $('.delete_selected_btn').hide();
             }
         });
         $("#download_selected").on('click', function() {
             var selectedIds = [];
             $('.check_select:checked').each(function() {
                 selectedIds.push($(this).val());
             });

             if (selectedIds.length === 0) {
                 alert('Please select at least one item.');
                 return;
             }

             var form = $('<form>', {
                 action: '{{ route('vendor.inventory.report.purchase', ['export', 'excel']) }}',
                 method: 'GET'
             });

             form.append($('<input>', {
                 type: 'hidden',
                 name: '_token',
                 value: $('meta[name="csrf-token"]').attr('content')
             }));

             selectedIds.forEach(function(id) {
                 form.append($('<input>', {
                     type: 'hidden',
                     name: 'invoice_ids[]', // Use array syntax
                     value: id
                 }));
             });

             form.appendTo('body').submit();
         });
     </script>
     @include('vendor-views/js/date_range')
 @endpush
