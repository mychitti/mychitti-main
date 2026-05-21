@extends('layouts.vendor.app')

@section('title', 'Job Cards List')

@push('css_or_js')
    <meta name="csrf-token" content="{{ csrf_token() }}">
@endpush

@section('content')
    <div class="content container-fluid">
        <!-- Page Header -->
        <div class="page-header d-flex justify-content-between align-items-center">
            <h1 class="page-header-title text-capitalize">
                Job Cards List
            </h1>
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
                                <th class="border-0 table-column-pl-0">{{ translate('messages.task_id') }}</th>
                                <th class="border-0 table-column-pl-0">{{ translate('messages.pdf') }}</th>
                                <th class="border-0 table-column-pl-0">{{ translate('messages.status') }}</th>
                                <th class="border-0 text-center">{{ translate('messages.actions') }}</th>
                            </tr>
                        </thead>

                        <tbody id="set-rows">
                            @foreach ($job_cards as $key => $r)
                                <tr>
                                    <td class="">
                                        {{ $key + $job_cards->firstItem() }}
                                    </td>
                                    <td class="table-column-pl-0">
                                        <a
                                            href="{{ $r->task_id ? route('vendor.task.detail', $r->task_id) : '#' }}">{{ $r->task_id }}</a>
                                    </td>
                                    <td>
                                            <a class="btn btn-sm btn--primary btn-outline-primary action-btn"
                                                style="width:fit-content;padding: 0px 7px!important; " target="_blank"
                                                href="{{ $r->pdf ? asset('storage/app/public/store/jobcards/' . $r->pdf) : '#' }}">View PDF</a>
                                    </td>
                                    <td>
                                    @if($r->status)
<span class="badge badge-soft-success">Approved</span>
                                    @else
<span class="badge badge-soft-warning">Pending</span>

                                    @endif
                                    </td>

                                    <td>
                                        <div class="btn--container justify-content-center">
                                            <a class="btn action-btn btn--danger btn-outline-danger form-alert"
                                                href="javascript:" data-id="category-{{ $r['id'] }}"
                                                data-message="{{ translate('Want to delete this jobcard') }}"
                                                title="{{ translate('messages.delete_jobcard') }}"><i
                                                    class="tio-delete-outlined"></i>
                                            </a>
                                            <form
                                                action="{{ route('vendor.documents.job-card.delete', [$r['id']]) }}"
                                                method="get" id="category-{{ $r['id'] }}">
                                                @csrf @method('get')
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    @if (count($job_cards) === 0)
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
                {!! $job_cards->links() !!}
            </div>
            <!-- End Footer -->
        </div>
        <!-- End Card -->

    @endsection

    @push('script_2')
        <script></script>
    @endpush
