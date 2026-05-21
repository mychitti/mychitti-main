@extends('layouts.vendor.app')

@section('title', 'Recievable Receipts List')

@push('css_or_js')
    <meta name="csrf-token" content="{{ csrf_token() }}">
@endpush

@section('content')
    <div class="content container-fluid">
        <!-- Page Header -->
        <div class="page-header d-flex flex-wrap w-100 justify-content-between align-items-center">
            <h1 class="page-header-title text-capitalize">
                Recievable Receipts List
            </h1>
            <a href="{{route('vendor.documents.receivable-receipt.create')}}" class="btn_sm btn--primary btn">Create New Receipt</a>
        </div>
        <!-- End Page Header -->

        <!-- Card -->
        <div class="card">
            <!-- Header -->
            <div class="card-header py-2 border-0">
                <div class="search--button-wrapper justify-content-end">

                </div>
                <!-- End Row -->
            </div>
            <!-- End Header -->
            <div class="card-body p-0">
                <!-- Table -->
                <div class="table-responsive datatable-custom">
                    <table id="datatable"
                        class="table table-hover table-borderless table-thead-bordered table-nowrap table-align-middle card-table">
                        <thead class="thead-light">
                            <tr>
                                <th class="border-0">
                                    {{ translate('messages.#') }}
                                </th>
                                <th class="border-0 table-column-pl-0">{{ translate('messages.receipt_number') }}</th>
                                <th class="border-0 table-column-pl-0">{{ translate('messages.client_id') }}</th>
                                <th class="border-0">{{ translate('messages.recieved_by') }}</th>
                                {{-- <th class="border-0">{{ translate('messages.items') }}</th> --}}
                                <th class="border-0">{{ translate('messages.status') }}</th>
                                <th class="border-0 text-center">{{ translate('messages.created_at') }}</th>
                                <th class="border-0 text-center">{{ translate('messages.actions') }}</th>
                            </tr>
                        </thead>

                        <tbody id="set-rows">
                            @foreach ($receipts as $key => $r)
                                <tr>
                                    <td class="">
                                        {{ $key + $receipts->firstItem() }}
                                    </td>
                                    <td>{{$r->receipt_number}}</td>
                                    <td class="table-column-pl-0">
                                        <a href="{{$r->client ? route('vendor.customer.view', $r->client->id) : '#'}}">{{ $r->client?->f_name }}</a>
                                    </td>
                                    <td class="table-column-pl-0"> 
                                        <a href="{{ $r->employee ? route('vendor.employee.view', $r->employee->id) : '#' }}">{{ $r->employee?->f_name . ' ' . $r->employee?->l_name }}</a>
                                    </td>
                                    {{-- <td>
                                        @php 
                                            $items = json_decode($r->items ?? '[]', true) ?? [];
                                        @endphp

                                       <span class="badge badge-light"> {{ count($items) }}</span>
                                    </td> --}}
                                    <td>

                                     @if($r->status)
<span class="badge badge-soft-success">Approved</span>
                                    @else
<span class="badge badge-soft-warning">Pending</span>

                                    @endif
                                    </td>
                                    <td>{{$r->created_at}}</td>

                                    <td>
                                        <div class="btn--container justify-content-center">
                                            <a class="btn btn-sm btn--primary btn-outline-primary action-btn"
                                                style="width:fit-content;padding: 0px 7px!important; " target="_blank"
                                                href="{{ $r->pdf ? asset('storage/app/public/store/recivable-receipts/' . $r->pdf) : '#' }}">PDF</a>
                                              
                                                <a class="btn action-btn btn--danger btn-outline-danger form-alert"
                                                href="javascript:" data-id="category-{{ $r['id'] }}"
                                                data-message="{{ translate('Want to delete this receipt') }}"
                                                title="{{ translate('messages.delete_receipt') }}"><i
                                                    class="tio-delete-outlined"></i>
                                            </a>
                                            <form action="{{ route('vendor.documents.receivable-receipt.delete', [$r['id']]) }}"
                                                method="get" id="category-{{ $r['id'] }}">
                                                @csrf @method('get') 
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    @if (count($receipts) === 0)
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
            <!-- Footer -->
            <div class="card-footer">
                {!! $receipts->links() !!}
            </div>
            <!-- End Footer -->
        </div>
        <!-- End Card -->

    @endsection

    @push('script_2')
        <script></script>
    @endpush
