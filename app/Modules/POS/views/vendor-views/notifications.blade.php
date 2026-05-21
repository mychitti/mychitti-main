@extends('layouts.vendor.app')

@section('title', 'Notifications')

@push('css_or_js')
    <meta name="csrf-token" content="{{ csrf_token() }}">
@endpush

@section('content')
    <div class="content container-fluid">
        <!-- Page Header -->
        <div class="page-header">
            <h1 class="page-header-title"><i class="tio-filter-list"></i> Notifications <span class="badge badge-soft-dark ml-2"
                    id="itemCount">{{ count($notifications) }}</span></h1>
            
        </div>
        <!-- End Page Header -->



        <!-- Card -->
            <!-- Header -->
            <!-- Table -->
            <div class="row">
                @foreach ($notifications as $notf)
                <div class="col-lg-6 p-1">
                    <div class=" m-1 card" style="height: 100%;">
                    <div class="card-body">
                        <h5 class="card-title"> {{$notf->title}}</h5>
                        <small>{{_formatted_datetime($notf->created_at)}}</small>
                        <p class="card-text"> {{$notf->message}}</p>
                        @if( $notf->url)
                        <a style="float: right;" href="{{$notf->url}}" class="btn btn-sm btn-primary">View</a>
                        @endif
                    </div>
                    </div>
                </div>
                @endforeach
            </div>
                @if (!count($notifications))
              
                    <div class="empty--data">
                        <img src="{{ asset('/public/assets/admin/svg/illustrations/sorry.svg') }}" alt="public">
                        <h5>
                            {{ translate('no_data_found') }}
                        </h5>
                    </div>
                @endif
            <!-- End Table -->
        
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
@endpush
