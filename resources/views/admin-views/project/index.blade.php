@extends('layouts.admin.app')

@section('title', translate('Project List'))

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
            <div class="card col-12">
                <!-- Header -->
                <div class="card-header py-2">
                    <div class="search--button-wrapper">
                        <h5 class="card-title">Add Project</h5>

                    </div>
                </div>
                <form class="w-100" action="{{ route('admin.project.save-info') }}" method="post">
                    @csrf
                    <input type="hidden" id="project_id" name="project_id" value="">
                    <div class="col-md-12">
                        <div class="card h-100">
                            <div class="card-body row">
                                <div class="form-row col-12">
                                    <label for="exampleInputEmail1">Project Title<span class="text-danger">*</span></label>
                                    <input type="text" name="title"  placeholder="Project Title" class="form-control">
                                </div>
                                <div class="form-row col-4">
                                    <label for="exampleInputEmail1">Team Leader</label>
                                    <select name="team_leader" class="form-control js-select2-custom">
                                         <option value="" selected disabled>-- select --</option>
                                          @foreach($employees as $emp)
                                        <option value="{{$emp->id}}">{{$emp->f_name . ' ' . $emp->l_name}}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-row col-8">
                                    <label for="exampleInputEmail1">Team Members<span class="text-danger">*</span></label>
                                    <select name="team_members[]" class="form-control select2_tags" multiple="multiple">
                                        @foreach($employees as $emp)
                                        <option value="{{$emp->id}}">{{$emp->f_name . ' ' . $emp->l_name}}</option>
                                        @endforeach
                                    </select>
                                </div>



                                <div class="form-row col-4">
                                    <label for="inputState">Start Date<span class="text-danger">*</span></label>
                                    <input type="date" name="start_date" id="" class="form-control">
                                </div>
                                <div class="form-row col-4">
                                    <label for="inputState">End Date<span class="text-danger">*</span></label>
                                    <input type="date" name="end_date" id="" class="form-control">
                                </div>

                                <div class="form-row col-4">
                                    <label for="inputState">Cost Estimate</label>
                                    <input type="number" placeholder="Ex: 1000" name="cost_est" id="" class="form-control">
                                </div>
                                <div class="form-row col-4">
                                    <label for="inputState">Advance Pay</label>
                                    <input type="number" placeholder="Ex: 1000" name="advance_pay" id="" class="form-control">
                                </div>
                               
                                <div class="form-row col-4">
                                    <label for="inputState">Progress Status</label>
                                    <select name="prog_status" id="inputState" class="form-control js-select2-custom">
                                        <option value="New">New</option>
                                        <option value="Open">Open</option>
                                        <option value="In Progress">In Progress</option>
                                        <option value="Completed">Completed</option>
                                        <option value="Cancelled">Cancelled</option>
                                        <option value="On Hold">On Hold</option>
                                    </select>
                                </div>
                                <div class="form-row col-4">
                                    <label for="inputState">Progress (in %)</label>
                                    <input type="number" placeholder="Ex: 99" name="prog_percent" max="100" id="myNumberInput"
                                        class="form-control">
                                </div>
                                <div class="form-row col-4">
                                    <label for="inputState">Status</label>
                                    <select name="status" id="inputState" class="form-control js-select2-custom">
                                        <option value="1">Active</option>
                                        <option value="0">Inactive</option>
                                    </select>
                                </div>
                                <div class="form-row d-flex align-items-end col-12 mt-2">
                                    <button class="btn btn--primary ">Save</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        
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
                                <th class="border-0">Project Date</th>
                                <th class="border-0">Cost Est.</th>
                                <th class="border-0">Status</th>
                                <th class="border-0">Action</th>
                            </tr>
                        </thead>

                        <tbody id="set-rows">
                            @foreach ($projects as $lead)
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
                                                        @php $empInfo = _getWhere('admins', ['id'=> $lead->team_leader]);
                                                        if($empInfo[0]) 
                                                        echo $empInfo[0]->f_name . ' ' . $empInfo[0]->l_name;  @endphp
                                                        
                                                       
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
                                                        {{ $lead->start_date . ' to ' . $lead->end_date }}
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
                                                        {{ \App\CentralLogics\Helpers::format_currency($lead->cost) }}
                                                    </div>
                                                </div>
                                            </a>
                                        </div>
                                    </td>


                                    <td>
                                        <label class="toggle-switch toggle-switch-sm"
                                            for="couponCheckbox{{ $lead->id }}">
                                            <input type="checkbox"
                                                onclick="location.href='{{ route('admin.project.status-change', [$lead['id'], $lead->status ? 0 : 1]) }}'"
                                                class="toggle-switch-input" id="couponCheckbox{{ $lead->id }}"
                                                {{ $lead->status ? 'checked' : '' }}>
                                            <span class="toggle-switch-label">
                                                <span class="toggle-switch-indicator"></span>
                                            </span>
                                        </label>
                                    </td>

                                    <td>
                                        <div class="btn--container justify-content-center">
                                            <a style="min-width:50px;" class="btn  btn--danger btn-outline-danger"
                                                href="{{ route('admin.project.delete', [$lead->id]) }}"
                                                title="{{ translate('messages.delete') }} Department"><i
                                                    class="tio-delete-outlined"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
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
        .select2-container--default .select2-selection--single{
            height: 43px;
            border: 1px solid #f0f0f0;
        }
        .select2-container--default .select2-selection--single .select2-selection__rendered{
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
        .select2-container--default .select2-selection--multiple{
            border:1px solid #dfdfdf;
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
