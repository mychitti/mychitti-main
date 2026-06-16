@extends('layouts.vendor.app')

@section('title', translate('New Lead'))

@push('css_or_js')
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <style>
        .template_img {
            border-radius: 5px;
            border: 3px solid #e5e5e5;
            height: 119px;
            width: 100px;
            padding: 2px;
            margin: 5px;
        }

        .template_img.active {
            border: 3px solid #5dacef;
        }

        .template_img img {
            width: 100%;
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
                url: "@php echo url('vendor/lead/lead_approval') @endphp",
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
        @if (_isHospital())
            @include('hmis::vendor.hospital._hospital_submenu_header')
        @endif
        <!-- Page Header -->
        <div class="page-header d-flex flex-wrap justify-content-between">
            <h1 class="page-header-title"><i class="tio-filter-list"></i> New Lead </h1>
            <div class="page-header-select-wrapper mb-2">

            </div>
        </div>
        <!-- End Page Header -->


        @if (session()->has('msg'))
            <div class="alert alert-success" role="alert">
                {{ session('msg') }}
            </div>
        @endif
        <div class="row g-2">
            <form class="w-100 p-0" id="quotation_form" action="{{ route('vendor.lead.save-info') }}" method="post">
                @csrf
                <input type="hidden" id="lead_id" name="lead_id" value="">
                <div class="card h-100">
                    {{-- <h4 class="m-3 mb-0">Lead Details</h4> --}}
                    <div class="card-body row">
                        <div class="form-row col-md-3">
                            <label for="exampleInputEmail1" class="mb-0">Mychitti Client<i
                                    class="tio-info-outined text-body ml-1" data-toggle="tooltip" data-placement="right"
                                    title="This section shows only customers who have signed up on the My Chitti platform. Kindly request your customer to register on the My Chitti App before adding them."></i></label>
                            <select id="customer_id" name="client_name"
                                class="form-control js-select2-custom select2-search__field">
                                <!-- <option value="" selected disabled></option> -->

                            </select>
                        </div>
                        <div class="form-row col-md-3">
                            <label class="mb-0" for="inputState">Service</label>
                            <select name="service" id="inputState" class="form-control js-select2-custom">
                                @foreach ($services as $service)
                                    <option value="{{ $service->id }}">{{ ucwords($service->name) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-row col-md-3">
                            <label for="exampleInputEmail1" class="mb-0">Reported Issue</label>
                            <select name="remarks" class="form-control js-select2-custom remarks_select" id="">
                                <!-- <option value="" selected disabled></option> -->
                                @foreach ($reported_issue_list as $iss)
                                    <option value="{{ $iss->issue }}">{{ $iss->issue }}</option>
                                @endforeach
                                <option value="other">Other</option>
                            </select>

                        </div>
                        <div class="form-row col-md-3">
                            <label for="exampleInputEmail1" class="mb-0">Other</label>
                            <textarea disabled placeholder="issue" class="form-control other_issue"></textarea>
                        </div>

                        <div class="col-md-3">
                            <label for="exampleInputEmail1" class="mb-0">Quotation Price</label>
                            <input type="number" name="final_price" step="0.001" placeholder="Price"
                                class="form-control">

                        </div>
                        <div class="col-md-12" style="display: flex;justify-content: end;">
                            <div class="d-flex gap-2 align-items-center">
                                @if (hasPermission('leads_manage', 'jobcard'))
                                    <a class="btn btn--primary cursor-pointer m-1 btn_sm" type="button" data-toggle="modal"
                                        data-target="#addJobCardModal"> Job Card</a>
                                @endif
                                @if (hasPermission('leads_manage', 'receivable_receipt'))
                                    <a class="btn btn--warning cursor-pointer  m-1 btn_sm" data-toggle="modal"
                                        data-target="#addReceivableRModal"> Receivable Receipt</a>
                                @endif
                                <button class="btn  btn--primary btn-primary">Save</button>
                            </div>
                        </div>
                    </div>
                </div>
                <input type="hidden" name="quotation_check" id="quotation_check">
                <input type="hidden" name="receivable_receipt" id="receivable_receipt">
                <input type="hidden" name="job_card" id="job_card">
                @include('vendor-views/form_modals/job_card_modal')
                @include('vendor-views/form_modals/receivable_receipt_modal')

                {{-- inventory modal  --}}
                @include('vendor-views.form_modals.inventory_item_select')

            </form>
        </div>
    </div>
@endsection

@push('script_2')
    @include('vendor-views/js/custom-buttons-js')
    <script>
        function add_inv_items() {
            var selectedData = $('#inventory_items').select2('data');
            let totalRequests = selectedData.length;
            let completed = 0;

            if (totalRequests === 0) {
                $('#inventoryItemModal').modal('hide');
                return;
            }
            $(".inv_item_add_btn").attr('disabled', true);
            $('.inv_item_add_btn').html('<i class="tio-spinner tio-spin"></i> Adding...');
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });

            selectedData.forEach(function(item) {
                $.post({
                    url: "{{ route('vendor.inventory.get-item-info') }}",
                    data: {
                        id: item.id,
                    },
                    success: function(data) {
                        addMoreJCRow(data);
                    },
                    complete: function() {
                        completed++;
                        if (completed === totalRequests) {
                            $('#inventory_items').val(null).trigger('change');
                            $('.inv_modal_close').click()

                            $(".inv_item_add_btn").removeAttr('disabled');
                            $('.inv_item_add_btn').html('Add');
                        }

                    }
                });
            });
        }
        $(document).ready(function() {
            // Allow only number input in Select2 search box
            $('#userSelect').on('select2:open', function() {
                $('.select2-search__field').attr('inputmode', 'numeric'); // mobile numeric keyboard

                $('.select2-search__field').on('keypress', function(e) {
                    let charCode = e.which ? e.which : e.keyCode;
                    if (charCode < 48 || charCode > 57) {
                        e.preventDefault(); // block non-digits
                    }
                });
            });

            $('#userSelect').select2({


                placeholder: 'Search phone number...',
                minimumInputLength: 1,
                ajax: {
                    url: "{{ route('vendor.customer.search') }}", // Your Laravel route
                    dataType: 'json',
                    delay: 250,
                    data: function(params) {
                        return {
                            q: params.term // search term
                        };
                    },
                    processResults: function(data) {
                        return {
                            results: data.map(function(user) {
                                return {
                                    id: user.id,
                                    text: user.f_name + ' ' + user.l_name + ' | ' + user.phone
                                };
                            })
                        };
                    },
                    cache: true
                },
                language: {
                    noResults: function() {
                        return "Customer not registered in my chitti. Please tell them to register on My Chitti app.";
                    }
                },
                escapeMarkup: function(markup) {
                    return markup; // allow custom HTML
                }
            });
        });
    </script>

    <script>
        $("#preview_quotation").on('click', function() {
            var formData = new FormData($('#quotation_form')[0]);
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });
            $.post({
                url: "@php echo route('vendor.lead.preview')  @endphp",
                data: formData,
                cache: false,
                processData: false,
                contentType: false,

                success: function(data) {
                    $('#preview_div').html(data)
                },
            });
        })
        $('.done_rr').on('click', function() {
            $('#receivable_receipt').val(1)
            $(".close_rr").click()
        })
        $('.done_jc').on('click', function() {
            $('#job_card').val(1)
            $(".close_jc").click()
        })

        $(".template_img").on('click', function() {
            $(".template_img").removeClass('active')
            $("[data-id='" + $(this).attr('data-id') + "']").addClass('active');
            $('#template_id').val($(this).attr('data-id'))
        })
        $(".remarks_select").on('change', function() {
            console.log($(this).val())
            if ($(this).val() == 'other') {
                $(this).removeAttr('name')
                $(".other_issue").removeAttr('disabled').attr('name', 'remarks')
                setTimeout(() => {
                    $(".other_issue").focus()
                }, 100);
            } else {
                $(".other_issue").attr('disabled', true).removeAttr('name').val('')
                $(this).attr('name', 'remarks')
            }
        })
    </script>
@endpush
