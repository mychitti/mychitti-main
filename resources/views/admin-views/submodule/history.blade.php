@extends('layouts.admin.app')
@section('title', 'Free Trial History')
@push('css_or_js') 
@endpush

@section('content')
    <div class="content container-fluid">
        <div class="page-header">
            <!-- Page Heading -->
            <div class="d-flex flex-wrap align-items-center justify-content-between">
                <h1 class="page-header-title mb-3 mr-1">
                    <span class="page-header-icon">
                        <img src="{{ asset('public/assets/admin/img/role.png') }}" class="w--26" alt="">
                    </span>
                    <span>
                        Free Trial History 
                    </span>
                </h1>

            </div>
        </div>

        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header py-2 border-0">

                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table id="datatable"
                                class="table table-hover table-borderless table-thead-bordered table-nowrap table-align-middle card-table w-100"
                                data-hs-datatables-options='{
                                 "order": [],
                                 "orderCellsTop": true,
                                 "paging":false
                               }'>
                                <thead class="thead-light">
                                    <tr>
                                        <th class="border-0">{{ translate('sl') }}</th>
                                        <th class="border-0">Store Name</th>
                                        <th class="border-0">Service Name</th>
                                        <th class="border-0">Free Trial Days</th>
                                        <th class="border-0">Assigned By </th>
                                        <th class="border-0">Trial Status</th>
                                        <th class="border-0 ">Start Date</th>
                                        <th class="border-0">Trial Ends On</th>
                                    </tr>
                                </thead>
                                <tbody id="set-rows">
                                    @foreach ($history as $k => $h)
                                        <tr>
                                            <th scope="row">{{ $k + 1 }}</th>
                                            <td class="text-capitalize">{{ $h->store?->name }}</td>
                                            <td>
                                                {{ $h->submodule?->name }}
                                            </td>
                                            <td>{{ $h->trial_days }}</td>
                                            <td>{{ $h->admin?->f_name . ' ' . $h->admin?->l_name }}</td>
                                            <td>
                                                @if ($h->trial_ends_on < now())
                                                    <label class="badge badge-soft-danger">
                                                        Expired
                                                    </label>
                                                @else
                                                    <label class="badge badge-soft-success">
                                                        Active
                                                    </label>
                                                @endif
                                            </td>
                                            <td>{{ $h->start_date }}</td>
                                            <td>{{ $h->trial_ends_on }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                    @if (count($history) !== 0)
                        <hr>
                    @endif
                    <div class="page-area">
                        {!! $history->links() !!}
                    </div>
                    @if (count($history) === 0)
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
    </div>
@endsection

@push('script_2')
@endpush
