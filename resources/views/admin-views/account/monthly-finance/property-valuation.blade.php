@extends('layouts.admin.app')

@section('title', 'Property Valuation')

@push('css_or_js')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        {{-- .table-wrapper {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }


        thead th {
            padding: 18px 15px;
            text-align: left;
            font-weight: 600;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            white-space: nowrap;
        }

        tbody tr {
            border-bottom: 1px solid #e2e8f0;
            transition: all 0.2s ease;
        }

        tbody tr:hover {
            background: #f8faff;
            transform: scale(1.01);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.1);
        }

        tbody tr:last-child {
            border-bottom: none;
        }

        tbody td {
            padding: 20px 15px;
            color: #1e293b;
            font-size: 14px;
        } --}} .req-number {
            font-weight: 700;
            color: #667eea;
            font-size: 15px;
        }

        .status-badge {
            display: inline-block;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            white-space: nowrap;
        }

        .status-pending {
            background: #fef3c7;
            color: #92400e;
        }

        .status-approved {
            background: #d1fae5;
            color: #065f46;
        }

        .status-rejected {
            background: #fecaca;
            color: #991b1b;
        }

   
    

        @media (max-width: 768px) {
            .header {
                padding: 25px 20px;
            }

            .header h1 {
                font-size: 24px;
            }

            thead th,
            tbody td {
                padding: 15px 10px;
                font-size: 12px;
            }
        }
    </style>
@endpush

@section('content')
    <div class="content container-fluid">
        <!-- Page Header -->
        <div class="page-header d-flex flex-wrap justify-content-between align-items-center w-100">
            <div>
                    <h1 class="page-header-title"><i class="tio-filter-list"></i> Property Valuation</h1>
            </div>
            <div class="page-header-select-wrapper">

            </div>
        </div>
        <!-- End Page Header -->
        <!-- Button trigger modal -->

        <!-- Modal -->
        @if (hasPermission('rmf_property_valuation', 'list'))

        <div class="table-container">
            <div class="table-wrapper">
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
                                <th>Sl.</th>
                                <th>Asset</th>
                                <th>Depreciation Date</th>
                                <th>Opening Value</th>
                                <th>Depreciation Amount</th>
                                <th>Closing Value</th>
                                <th>Created At</th>
                                
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($asset_depreciations as $key => $dep)
                                <tr>
                                    <td>{{ $key + 1 }}</td>
                                    <td>
                                    @if($dep->asset?->inventoryItem)
                                    <a href="{{route('admin.inventory.item.detail', [$dep->asset?->inventoryItem?->id])}}">
                                    {{$dep->asset?->inventoryItem?->item_name}}
                                    </a>
                                    @endif
                                    </td>
                                    <td><span class="type-badge">{{ translate($dep->depreciation_date) }}</span></td>
                                 
                                    <td><span class="">{{ _price($dep->opening_value, null, 3) }}</span></td>
                                    <td><span class="">{{ _price($dep->depreciation_amount, null, 3) }}</span></td>
                                    <td><span class="">{{ _price($dep->closing_value, null, 3) }}</span></td>
                                    <td class="dept-info">{{ $dep->created_at }}</td>

                                  
                                   
                                  
                                </tr>
                            @endforeach

                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        @endif


    @endsection

    @push('script_2')
        <script>
            $(".forward_btn").on('click', function() {
                let id = $(this).attr('data-id')
                let status = $(this).attr('data-status')

                $(".fwd_id_inp").val(id)
                $(".fwd_status_inp").val(status)

            })
        </script>
    @endpush
