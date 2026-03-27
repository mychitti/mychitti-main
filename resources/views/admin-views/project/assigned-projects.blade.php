@extends('layouts.admin.app')

@section('title', translate('Assigned Project List'))

@push('css_or_js')
<meta name="csrf-token" content="{{ csrf_token() }}">
<!--jquery-->
@endpush

@section('content')
<div class="content container-fluid">
    <!-- Page Header -->
    <div class="page-header">
        <h1 class="page-header-title"><i class="tio-filter-list"></i> Projects<span class="badge badge-soft-dark ml-2"
                id="itemCount">{{ count($projects) }}</span></h1>
        <div class="page-header-select-wrapper">

            {{-- <div class="select-item">
                    <select name="module_id" class="form-control js-select2-custom"
                            onchange="set_filter('{{url()->full()}}',this.valuea,'module_id')" title="{{translate('messages.select')}} {{translate('messages.modules')}}">
            <option value="" {{!request('module_id') ? 'selected':''}}>{{translate('messages.all')}} {{translate('messages.modules')}}</option>
            @foreach (\App\Models\Module::notParcel()->get() as $module)
            <option
                value="{{$module->id}}" {{request('module_id') == $module->id?'selected':''}}>
                {{$module['module_name']}}
            </option>
            @endforeach
            </select>
        </div> --}}

    </div>
</div>
<!-- End Page Header -->
<div class="row">
    <!-- Card -->
    <div class="card  col-12 my-3">
        <!-- Header -->
        <div class="card-header py-2">
            <div class="search--button-wrapper">
                <h5 class="card-title">Projects List</h5>
                <form action="javascript:" id="search-form" class="search-form">
                    <!-- Search -->
                    @csrf
                    <div class="input-group input--group">
                        <input id="datatableSearch_" type="search" name="search" class="form-control"
                            placeholder="Search Project" aria-label="{{ translate('messages.search') }}" required>
                        <button type="submit" class="btn btn--secondary"><i class="tio-search"></i></button>
                    </div>
                    <!-- End Search -->
                </form>

                <!-- End Unfold -->
            </div>
        </div>
        <!-- End Header -->

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
                        <th class="border-0">Title</th>
                        <th class="border-0">Team Leader</th>
                        <th class="border-0">Progress Status</th>
                        <th class="border-0">Start Date</th>
                        <th class="border-0">Deadline</th>
                        <th class="border-0">Action</th>
                    </tr>
                </thead>

                <tbody id="set-rows">
                    @foreach ($projects as $key => $lead)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>
                            <div>
                                <a href="javascript:;" class="table-rest-info" alt="view store">

                                    <div class="info">
                                        <div class="text--title">
                                            {{ $lead->project_title }}
                                        </div>
                                    </div>
                                </a>
                            </div>
                        </td>
                        <td>
                            <div>
                                <a href="javascript:;" class="" alt="view store">

                                    <div class="info">
                                        <div class="text--title">
                                            @php $empInfo = _getWhere('vendor_employees', ['id'=> $lead->team_leader]);
                                            if($empInfo[0])
                                            echo $empInfo[0]->f_name . ' ' . $empInfo[0]->l_name; @endphp


                                        </div>
                                    </div>
                                </a>
                            </div>
                        </td>
                        <td>
                            <div>
                                <a href="javascript:;" class="" alt="view store">

                                    <div class="info">
                                        <div class="text--title">
                                            {{ $lead->progress_status }}
                                        </div>
                                    </div>
                                </a>
                            </div>
                        </td>
                        <td>
                            <div>
                                <a href="javascript:;" class="" alt="view store">

                                    <div class="info">
                                        <div class="text--title">
                                            {{ $lead->start_date }}
                                        </div>
                                    </div>
                                </a>
                            </div>
                        </td>
                        <td>
                            <div>
                                <a href="javascript:;" class="" alt="view store">

                                    <div class="info">
                                        <div class="text--title">
                                            {{ $lead->end_date  }}
                                        </div>
                                    </div>
                                </a>
                            </div>
                        </td>
                        <td>
                            <div class="btn--container justify-content-center">
                                 <a style="min-width:50px;" class="btn action-btn btn--warning btn-outline-warning"
                                    href="{{ route('admin.project.details', [$lead->id]) }}"
                                    title="View Details">
                                    <i class="tio-visible-outlined"></i>
                                </a> 
                                <a style="min-width:100px;" class="btn action-btn btn--primary btn-outline-primary" data-toggle="modal" data-target="#exampleModal{{$key}}" alt="view store">
                                   Project Status
                                </a>
                            </div>
                        </td>
                    </tr>
                    <div class="modal fade" id="exampleModal{{$key}}" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="exampleModalLabel"> {{ $lead->project_title }}</h5>
                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                                <div class="modal-body">
                                    <form action="{{route('admin.prog-update')}}" method="post">
                                        @csrf
                                        <input type="hidden" value="{{$lead->id}}" name="pr_id">
                                        <div class="form-row ">
                                            <label for="inputState">Progress Status</label>
                                            <select name="prog_status" id="inputState" class="form-control js-select2-custom">
                                                <option {{ $lead->progress_status  == 'New' ? 'selected' : ''}} value="New">New</option>
                                                <option {{ $lead->progress_status  == 'Open' ? 'selected' : ''}} value="Open">Open</option>
                                                <option {{ $lead->progress_status  == 'In Progress' ? 'selected' : ''}} value="In Progress">In Progress</option>
                                                <option {{ $lead->progress_status  == 'Completed' ? 'selected' : ''}} value="Completed">Completed</option>
                                                <option {{ $lead->progress_status  == 'Cancelled' ? 'selected' : ''}} value="Cancelled">Cancelled</option>
                                                <option {{ $lead->progress_status  == 'On Hold' ? 'selected' : ''}} value="On Hold">On Hold</option>
                                            </select>
                                        </div>
                                        <div class="form-row mb-2">
                                            <label for="inputState">Progress (in %)</label>
                                            <input type="number" placeholder="Ex: 99" value="{{ $lead->prog_percent}}" name="prog_percent" max="100" id="myNumberInput"
                                                class="form-control">
                                        </div>
                                        <button type="submit" class="btn btn-primary">Save changes</button> 
                                    </form>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </tbody>
            </table>
            @if (count($projects))
            <hr>
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
        <!-- End Table -->
    </div>
</div>
<!-- End Card -->
</div>

@endsection

@push('script_2')
<script>
    function status_change_alert(url, message, e) {
        e.preventDefault();
        Swal.fire({
            title: 'Are you sure?',
            text: message,
            type: 'warning',
            showCancelButton: true,
            cancelButtonColor: 'default',
            confirmButtonColor: '#FC6A57',
            cancelButtonText: 'No',
            confirmButtonText: 'Yes',
            reverseButtons: true
        }).then((result) => {
            if (result.value) {
                location.href = url;
            }
        })
    }
    $(document).on('ready', function() {
        // INITIALIZATION OF DATATABLES
        // =======================================================
        var datatable = $.HSCore.components.HSDatatables.init($('#columnSearchDatatable'));

        $('#column1_search').on('keyup', function() {
            datatable
                .columns(1)
                .search(this.value)
                .draw();
        });

        $('#column2_search').on('keyup', function() {
            datatable
                .columns(2)
                .search(this.value)
                .draw();
        });

        $('#column3_search').on('keyup', function() {
            datatable
                .columns(3)
                .search(this.value)
                .draw();
        });

        $('#column4_search').on('keyup', function() {
            datatable
                .columns(4)
                .search(this.value)
                .draw();
        });


        // INITIALIZATION OF SELECT2
        // =======================================================
        $('.js-select2-custom').each(function() {
            var select2 = $.HSCore.components.HSSelect2.init($(this));
        });

    });
</script>

{{-- <script src="https://code.jquery.com/jquery-3.7.0.js" integrity="sha256-JlqSTELeR4TLqP0OG9dxM7yDPqX1ox/HfgiSLBj8+kM="
        crossorigin="anonymous"></script>

    <!--select2 -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script> --}}


<script>
    $(".select2_tags").select2({
        placeholder: "Select Team Members",
    });
    $(".js-select2-custom").select2({
        placeholder: "Select Team Members",
    });
</script>
<style>
    .select2-container--default .select2-selection--single {
        height: 43px;
        border: 1px solid #f0f0f0;
    }

    .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height
    }

    .select2-container--default.select2-container--focus .select2-selection--multiple {
        border: solid #e4e4e4 1px;
    }

    .select2-container--default .select2-selection--multiple .select2-selection__choice__remove {
        width: 17px;
        outline: none;
        height: 100%;
    }

    .select2-container--default .select2-selection--multiple .select2-selection__choice {
        padding: 5px !important;
    }

    .select2-container--default .select2-selection--multiple .select2-selection__choice__display {
        padding-left: 15px;
    }

    .select2-container--default .select2-selection--multiple {
        border: 1px solid #dfdfdf;
    }
</style>

<script>
    document.getElementById('myNumberInput').addEventListener('keydown', function(event) {
        var value = parseInt(this.value + event.key);

        if (value > 100) {
            event.preventDefault();
        }
    });
</script>
@endpush