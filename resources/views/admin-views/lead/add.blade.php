@extends('layouts.admin.app')

@section('title', translate('Add Lead '))

@push('css_or_js')
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <style>
        .select2-container .select2-selection--single {
            height: auto !important;
        }

        .select2-container--default .select2-selection--single {
            border: 1px solid #e9e9e9 !important;
        }

        .form-row {
            margin-top: 6px;
        }

        .cardTop {

            background: #FAC24F;
            border-radius: 20px;
            display: flex;
            overflow: hidden;
            align-items: flex-end;

        }


        .timeline {

            width: 100%;
            background: #ECF1F524;
            mix-blend-mode: normal;
            backdrop-filter: blur(15px);

            overflow: hidden;

            border-radius: 10px;




            label {

                font-family: Open Sans;
                font-style: normal;
                font-weight: normal;
                font-size: 16px;
                line-height: 22px;
                /* identical to box height */
                margin-left: 66px;
                margin-top: 10px;

                color: #FFFFFF;

            }

            .box {
                width: 100%;
                background: #fbfcfd;


                .container {

                    width: 100%;
                    display: flex;

                    .lines {
                        margin-left: 40px;
                        margin-top: 6px;


                        .dot {
                            width: 14px;
                            height: 14px;
                            background: #D1D6E6;
                            border-radius: 7px;
                        }

                        .line {
                            height: 103px;
                            width: 2px;
                            background: #D1D6E6;
                            margin-left: 5.3px;
                        }
                    }

                    .cards {

                        margin-left: 12px;

                        .card {
                            padding-top: 25px;
                            background: #FFFFFF;
                            box-shadow: 0 2px 2px 0 #eeeeee40;
                            border-radius: 10px;

                            box-shadow: 0px 16px 15px -10px rgba(105, 96, 215, 0.0944602);
                            margin-bottom: 10px;

                            &.mid {

                                height: 71px;
                            }

                            h4 {

                                font-family: Open Sans;
                                font-style: normal;
                                font-weight: bold;
                                font-size: 14px;
                                line-height: 19px;
                                margin-left: 25px;
                                margin-bottom: 5px;




                                color: #2B2862;

                            }

                            p {

                                font-family: Open Sans;
                                font-style: normal;
                                font-weight: normal;
                                font-size: 16px;
                                line-height: 22px;

                                color: #2B2862;
                                margin-left: 25px;
                            }
                        }
                    }

                }


            }
        }
    </style>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.4/jquery.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.js" integrity="sha256-eKhayi8LEQwp4NKxN+CfCh+3qOVUtJn3QNZ0TciWLP4="
        crossorigin="anonymous"></script>
    <script>
        $(document).on('click', '.lead_approval', function() {
            console.log('fds');
            var status = $(this).attr('data-id');
            $.ajax({
                url: '{{ url('
                                                                                            admin / lead / lead_approval ') }}',
                type: "POST",
                data: {
                    _token: $('[name="_token"]').val(),
                    lead_id: $('#lead_id').val(),
                    approval: status
                },
                success: function(resp) {
                    if (resp.status) {
                        if (status == 'accept') {
                            $('.approval-status').html('<h3 class="text-success p-3">Accepted</h3>')
                        } else {
                            $('.approval-status').html('<h3 class="text-danger p-3">Rejected</h3>')
                        }
                    }

                },
            });
        })
    </script>
@endpush

@section('content')
    <div class="content container-fluid">
        <!-- Page Header -->
        <div class="page-header">
            <h1 class="page-header-title"><i class="tio-filter-list"></i> Add Lead </h1>
            <div class="page-header-select-wrapper">

            </div>
        </div>
        <!-- End Page Header -->


        @if (session()->has('msg'))
            <div class="alert alert-success" role="alert">
                {{ session('msg') }}
            </div>
        @endif
        <div class="row g-2">
            <form class="w-100" action="{{ route('admin.lead.save-info') }}" method="post">
                @csrf
                <input type="hidden" id="lead_id" name="lead_id" value="">
                <div class="col-md-12">
                    <div class="card h-100">
                        <!-- <h4 class="m-3 mb-0">Client Details</h4> -->
                        <div class="card-body row">
                            <div class="form-row col-3">
                                <label for="exampleInputEmail1">Client</label>
                                <select name="client_name" id="client_select" class="form-control">
                                    <option value="">Select Client</option>
                                </select>
                            </div>

                            <div class="form-row col-3">
                                <label for="inputState">
                                    @if (Config::get('module.current_module_id') == 6)
                                        Service
                                    @else
                                        Product
                                    @endif
                                </label>
                                <select name="service" id="service_select" class="form-control">
                                    <option value="">Select Service</option>
                                </select>
                            </div>
                            <div class="form-row col-3">
                                <label for="">City</label>
                                <select name="zone" id="zone_select" class="form-control">
                                    <option value="">Select City</option>
                                </select>
                            </div>
                            <div class="form-row col-3">
                                <label for="">Vendor</label>
                                <select name="vendor_id" id="vendor_select" class="form-control">
                                    <option value="">Select Vendor</option>
                                </select>
                            </div>
                        </div>
                        <div class="mx-3 mb-2 d-flex justify-content-end">
                            <button class="btn   btn--primary">Save</button>
                        </div>
                    </div>

                </div>
            </form>
        </div>
    </div>

@endsection

@push('script_2')
    <link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.full.min.js"></script>
    <script>
        $('#client_select').select2({
            placeholder: 'Search client by name or phone',
            allowClear: true,
            ajax: {
                url: '{{ route('admin.lead.search-clients') }}',
                dataType: 'json',
                delay: 300,
                data: function(params) {
                    return {
                        q: params.term
                    };
                },
                processResults: function(data) {
                    return {
                        results: data.map(function(client) {
                            return {
                                id: client.id,
                                text: (client.phone || '') + ' | ' + (client.f_name || '') + ' ' + (
                                    client.l_name || '')
                            };
                        })
                    };
                }
            },
            minimumInputLength: 2
        });

        $('#service_select').select2({
            placeholder: 'Search service by name',
            allowClear: true,
            ajax: {
                url: '{{ route('admin.lead.search-services') }}',
                dataType: 'json',
                delay: 300,
                data: function(params) {
                    return {
                        q: params.term
                    };
                },
                processResults: function(data) {
                    return {
                        results: data.map(function(item) {
                            return {
                                id: item.id,
                                text: item.name
                            };
                        })
                    };
                }
            },
            minimumInputLength: 2
        });

        $('#zone_select').select2({
            placeholder: 'Search city by name',
            allowClear: true,
            ajax: {
                url: '{{ route('admin.lead.search-zones') }}',
                dataType: 'json',
                delay: 300,
                data: function(params) {
                    return {
                        q: params.term
                    };
                },
                processResults: function(data) {
                    return {
                        results: data.map(function(zone) {
                            return {
                                id: zone.id,
                                text: zone.name
                            };
                        })
                    };
                }
            },
            minimumInputLength: 1
        });

        $('#vendor_select').select2({
            placeholder: 'Search vendor by name or phone',
            allowClear: true,
            ajax: {
                url: '{{ route('admin.lead.search-vendors') }}',
                dataType: 'json',
                delay: 300,
                data: function(params) {
                    return {
                        q: params.term
                    };
                },
                processResults: function(data) {
                    return {
                        results: data.map(function(vendor) {
                            return {
                                id: vendor.id,
                                text: vendor.name + (vendor.phone ? ' | ' + vendor.phone : '')
                            };
                        })
                    };
                }
            },
            minimumInputLength: 2
        });
    </script>
@endpush
