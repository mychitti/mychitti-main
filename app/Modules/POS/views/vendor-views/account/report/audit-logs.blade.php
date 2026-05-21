 @extends('layouts.vendor.app')
 @section('title', 'Audit Logs')
 @push('css_or_js')
     <link href="{{ asset('public/assets/admin/css/date_range.css') }}" rel="stylesheet">
 @endpush

 @section('content')
     <div class="content container-fluid">
         <div class="page-header">
             <div class="d-flex flex-wrap justify-content-between align-items-center">
                 <h1 class="page-header-title mb-2">
                     <span class="page-header-icon">
                     </span>
                     <span>
                         Audit Logs
                         <span class="badge badge-soft-dark ml-2" id="itemCount"></span>
                     </span>
                 </h1>


                 <div class="d-flex align-items-center">

                     <div class="d-flex gap-2">
                         <form action="" class=" date-range-form">
                             @include('vendor-views/form_modals/date_range')
                             <button style="width:fit-content; white-space:nowrap" class="btn btn-outline-warning"
                                 type="button" data-toggle="modal"
                                 data-target="#dateRangeModal">{{ translate($preset) }}</button>
                         </form>
                         <form action="" class="input-group" style="max-width: 270px;">
                             <input type="text" value="{{ request('search') ?? '' }}" name="search" class="form-control"
                                 placeholder="Search By Action">
                             <div class="input-group-append">
                                 <button class="btn btn-secondary" type="submit">
                                     <i class="tio-search"></i>
                                 </button>
                             </div>
                         </form>
         @if (hasPermission('reports_audit_logs', 'export'))

                         <a href="#"
                             class="btn btn_sm btn-outline-primary ">
                             Export
                         </a>
                         @endif
                     </div>
                 </div>
             </div>
         </div>
         <!-- Page Heading -->
         @if (hasPermission('reports_audit_logs', 'list'))

         <div class="card">
             <div class="card-body p-0">
                 <div class="table-responsive">
                     <table id="datatable"
                         class="table table-hover table-borderless table-thead-bordered table-nowrap table-align-middle card-table"
                         data-hs-datatables-options='{
                    "order": [],
                    "orderCellsTop": true,
                    "paging":false
                }'>
                         <thead class="thead-light">
                             <tr>
                                 <th class="border-0">{{ translate('messages.#') }}</th>
                                 <th class="border-0">{{ translate('messages.Date') }}</th>
                                 <th class="border-0">{{ translate('messages.Action') }}</th>
                                 <th class="border-0">{{ translate('messages.Action Performed By') }}</th>
                             </tr>
                         </thead>
                         <tbody id="set-rows">
                             @foreach ($audit_logs as $k => $log)
                                 <tr>
                                     <td>{{ $k + 1 }}</td>
                                     <td>{{ $log['created_at'] }}</td>
                                     <td>{{ $log['action'] }}</td>
                                     <td>
                                         @if ($log['created_by'] == 0)
                                             @php $vendor = \App\CentralLogics\Helpers::get_vendor_data(); @endphp
                                             {{ $vendor->f_name . ' ' . $vendor->l_name }}
                                         @else
                                             <a href="{{ route('vendor.employee.view', [$log['created_by']]) }}">
                                                 {{ $log['employee']?->f_name . ' ' . $log['employee']?->l_name }}
                                             </a>
                                         @endif
                                     </td>
                                 </tr>
                             @endforeach
                         </tbody>
                     </table>
                 </div>
             </div>
             @if (count($audit_logs) === 0)
                 <div class="empty--data">
                     <img src="{{ asset('/public/assets/admin/svg/illustrations/sorry.svg') }}" alt="public">
                     <h5>
                         {{ translate('no_data_found') }}
                     </h5>
                 </div>
             @endif
         </div>
         @endif
     </div>
 @endsection

 @push('script_2')
     @include('vendor-views/js/date_range')
 @endpush
