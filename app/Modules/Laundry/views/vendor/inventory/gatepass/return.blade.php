 @extends('layouts.vendor.app')
 @section('title', 'Return ' . ucfirst($gatepass->type) . ' Gatepasses')
 @push('css_or_js')
     <link href="{{ asset('public/assets/admin/css/date_range.css') }}" rel="stylesheet">
     <style>
         .pass-number {
             background: #3498db;
             color: white;
             padding: 5px 15px;
             display: inline-block;
             border-radius: 3px;
             font-size: 14px;
             margin-top: 10px;
             width: 200px;
             margin: 0 auto;
         }

         .info-section {
             margin-bottom: 25px;
             padding: 15px;
             background: #f8f9fa;
             border-radius: 5px;
         }

         .info-row {
             width: 100%;
             margin-bottom: 15px;
         }

         .info-row:after {
             content: "";
             display: table;
             clear: both;
         }

         .info-item col-md-3 p-2 {
             width: 48%;
             float: left;
             margin-right: 4%;
         }

         .info-item col-md-3 p-2:nth-child(2n) {
             margin-right: 0;
         }

         .info-label {
             font-weight: bold;
             color: #2c3e50;
             font-size: 12px;
             margin-bottom: 5px;
             text-transform: uppercase;
         }

         .info-value {
             padding: 5px 0;
             min-height: 25px;
             color: #333;
         }

         .items-section {
             margin: 25px 0;
         }
     </style>
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
                                 <span>{{ 'Return ' . ucfirst($gatepass->type) . ' Gatepasses' }} </span>
                             </div>
                             <span class="badge badge-soft-dark ml-2" id="itemCount">{{ count([]) }}</span>
                         </div>
                     </h1>

                 </div>
             </div>
         </div>




         <div class="card mt-3">

             <div class="gate-pass p-4">
                 <div class="header">
                     <h2>GATE PASS</h2>
                 </div>

                 <div class="info-section">
                     <div class="info-row row">
                         <div class="info-item col-md-3 p-2">
                             <span class="info-label">Vehicle No.:</span>
                             <span class="info-value"></span>
                         </div>
                         <div class="info-item col-md-3 p-2">
                             <span class="info-label">Date:</span>
                             <span class="info-value">{{ date('Y-m-d') }}</span>
                         </div>
                         <div class="info-item col-md-3 p-2">
                             <span class="info-label">Driver Name:</span>
                             <span class="info-value">{{ $driver_data->name ?? '' }}</span>
                         </div>
                         <div class="info-item col-md-3 p-2">
                             <span class="info-label">Driver Phone:</span>
                             <span class="info-value">{{ $driver_data->phone ?? '' }}</span>
                         </div>
                         <div class="info-item col-md-3 p-2">
                             <span class="info-label">Driver Address:</span>
                             <span class="info-value">{{ $driver_data->address ?? '' }}</span>
                         </div>
                         <div class="info-item col-md-3 p-2">
                             <span class="info-label">Pass No:</span>
                             <span class="info-value">{{ $gatepass->gatepass_number }}</span>
                         </div>

                         <div class="info-item col-md-3 p-2">
                             <span class="info-label">Sales Person:</span>
                             <span class="info-value"></span>
                         </div>

                         <div class="info-item col-md-3 p-2">
                             <span class="info-label">Reference Invoice:</span>
                             <span class="info-value">{{ $gatepass->invoice_id }}</span>
                         </div>
                         <div class="info-item col-md-3 p-2">
                             <span class="info-label">Route:</span>
                             <span class="info-value"></span>
                         </div>
                     </div>
                 </div>

                 <div class="items-section">
                     <div class="section-title">ITEMS DETAILS</div>
                     <form method="post" action="{{route('vendor.inventory.gatepass.return-store')}}">
                     @csrf
                     <input type="hidden" name="gatepass_id" value="{{$gatepass->id}}">
                         <div class="table-responsive datatable-custom" id="table">
                             <table id="datatable"
                                 class="table table-borderless table-thead-bordered table-nowrap table-align-middle card-table">
                                 <thead class="thead-light">
                                     <tr>
                                         <th style="width: 5%;">S.No</th>
                                         <th style="width: 10%;">Qty</th>
                                         <th style="width: 30%;">Item Name</th>
                                         <th style="width: 10%;" class="">MRP</th>
                                         <th style="width: 10%;" class="">Return Stock</th>
                                         <th style="width: 10%;" class="">Return Reason</th>
                                         <th style="width: 15%;" class="">Amount</th>
                                     </tr>
                                 </thead>
                                 <tbody>
                                     @foreach ($gp_items as $key => $value)
                                         <tr>
                                             <td>{{ $key + 1 }}</td>
                                             <td>{{ $value->qty }} {{ $value->unitId?->unit }}</td>
                                             <td>{{ $value->name }}</td>
                                             <td class="">{{ $value->price }}/-</td>
                                             <td class="">
                                             <input type="hidden" name="gp_item_id[]" value="{{$value->id}}">
                                                 <div class="d-flex gap-2 align-items-center">
                                                     <input type="number" name="stock[]" class="form-control"
                                                         id="">{{ $value->unitId?->unit }}
                                                 </div>
                                             </td>
                                             <td class="">
                                                 <select name="reason[]" id="" class="form-control">
                                                     <option value="left">Left</option>
                                                     <option value="damage">Damage</option>
                                                 </select>
                                             </td>
                                             <td class="t">{{ _price($value->qty * $value->price) }}</td>
                                         </tr>
                                     @endforeach

                                 </tbody>
                             </table>
                         </div>
                         <div class="d-flex justify-content-end w-100">
                             <button type="submit" class="btn btn-primary">Generate Return Gatepass</button>
                         </div>
                     </form>
                 </div>




             </div>
         </div>
     </div>

 @endsection
 @push('script_2')
 @endpush
