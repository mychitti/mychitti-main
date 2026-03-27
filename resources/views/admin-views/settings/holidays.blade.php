@extends('layouts.admin.app')

@section('title', 'Holidays')

@push('css_or_js')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        .form-row {
            margin-top: 6px;
        }

        .ck.ck-reset {
            width: 100% !important;
        }

        @keyframes scaleFocus {
            0% { 
                transform: scale(1);
            }

            50% {
                transform: scale(1.1);
            }

            100% {
                transform: scale(1);
            }
        }

        .scale-animate {
            animation: scaleFocus 0.6s ease-in-out;
        }
    </style>
@endpush

@section('content')
    <div class="content container-fluid">
        <!-- Page Header -->
        <div class="page-header d-flex w-100 justify-content-between">
            <h1 class="page-header-title"><i class="tio-filter-list"></i> Holidays Configuration</h1>
        </div>

        <!-- End Page Header -->

        <div class="">
            <div class="card mb-1">
                <!-- Body -->
                <div class="card-body row">
                    <div class="col-md-6">
                        <form action="{{ route('admin.holidays.add') }}">
                            @csrf
                            <label for="title">Title</label>
                            <input required type="text" name="title" id="title" placeholder="Title"
                                class="form-control">
                            <label for="date">Date</label>
                            <input required type="date" name="date" id="date" class="form-control">
                            <button type="submit" class="btn btn-primary float-right mt-2">Save</button>
                        </form>
                    </div>
                    <div class="col-md-6">
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
                                        <th class="border-0">Holiday Name</th>
                                        <th class="border-0">Date</th>
                                        <th class="text-center border-0">{{ translate('messages.action') }}</th>
                                    </tr>
                                </thead>

                                <tbody id="set-rows">
                                    @foreach ($holidays as $key => $hl)
                                        <tr>
                                            <td>{{ $loop->iteration }}</td>
                                            <td>
                                                <span class="text-dark fw-bold">{{ $hl->title }}</span>
                                            </td>
                                            <td>
                                                {{ $hl->date }}
                                            </td>
                                            <td>
                                                <div class="btn--container justify-content-center">
                                                    <input type="hidden" value="{{ $hl->id }}"
                                                        class="hlid_{{ $key }}">
                                                    <input type="hidden" value="{{ $hl->date }}"
                                                        class="old_date_{{ $key }}">
                                                    <input type="hidden" value="{{ $hl->title }}"
                                                        class="old_title_{{ $key }}">
                                                    <a class="btn action-btn btn--primary btn-outline-primary edit_btn"
                                                        data-toggle="modal" data-target="#holidayEditModal"
                                                        data-id="{{ $key }}"
                                                        title="{{ translate('messages.edit') }} Holiday"><i
                                                            class="tio-edit"></i>
                                                    </a>
                                                    <a class="btn action-btn btn--danger btn-outline-danger form-alert"
                                                        href="javascript:" data-id="attribute-{{ $hl->holiday_id }}"
                                                        data-message="{{ translate('Want to delete this holiday ?') }}"
                                                        title="{{ translate('messages.delete') }}"><i
                                                            class="tio-delete-outlined"></i></a>
                                                    <form action="{{ route('admin.holidays.delete', [$hl->holiday_id]) }}"
                                                        method="get" id="attribute-{{ $hl->holiday_id }}">
                                                        @csrf @method('get')
                                                    </form>
                                                </div>

                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                            @if (count($holidays))
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
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal -->
        <div class="modal fade" id="holidayEditModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="exampleModalLabel">Edit Holiday</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <form method="post" action="{{ route('admin.holidays.update') }}">
                            @csrf
                            <input type="hidden" name="hl_id" id="hl_id">
                            <label for="title_inp">Title</label>
                            <input required type="text" name="title" id="title_inp" placeholder="Title"
                                class="form-control">
                            <label for="date_inp">Date</label>
                            <input required type="date" name="date" id="date_inp" class="form-control">
                            <button type="submit" class="btn btn-primary float-right mt-2">Save</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    @endsection

    @push('script_2')
        <script>
            $(".edit_btn").on('click', function() {
                var id = $(this).attr('data-id');
                var title = $(".old_title_" + id).val();
                var date = $(".old_date_" + id).val();
                var hlid = $(".hlid_" + id).val();

                $("#title_inp").val(title)
                $("#date_inp").val(date)
                $("#hl_id").val(hlid)
            })
        </script>
    @endpush
