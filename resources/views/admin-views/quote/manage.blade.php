@extends('layouts.admin.app')

@section('title', translate('Edit Quotation'))

@push('css_or_js')
<meta name="csrf-token" content="{{ csrf_token() }}">

<style>
    .sr_inp {
        height: 40px;
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


@endpush

@section('content')
<div class="content container-fluid">
    <!-- Page Header -->
    <div class="page-header">
        <h1 class="page-header-title"><i class="tio-filter-list"></i>Edit Quotation </h1>
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
        <form class="w-100" action="{{ route('admin.quotation.save-info') }}" method="post">
            @csrf
            <input type="hidden" id="quote_id" name="quote_id" value="{{$quote->id}}">
            <div class="col-md-12 mt-3">
                <div class="card h-100">
                    <div class="row justify-content-between">
                        <h4 class="m-3 mb-0 col-7">Quotation Overview</h4>
                          <a href="{{route('admin.quotation.send-quote', ['id'=> $quote->id])}}" style="height: 50px; width: 148px;padding: 14px 24px;" class="btn  btn--primary ">Send to Client</a>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="form-row col-4">
                                <div class="col">
                                    <label for="exampleInputEmail1">Subject</label>
                                    <input name="subject" type="text" placeholder="subject" value="{{$quote->subject}}" class="form-control">
                                </div>
                            </div>
                            <div class="form-row col-4">

                                <div class="col">
                                    <label for="inputState">Quotation Status</label>
                                    <select name="status" id="inputState" class="form-control">
                                        <option {{$quote->status == 'New' ? 'selected' : '' ;}} value="New">New </option>
                                        <option {{$quote->status == 'Accepted' ? 'selected' : '' ;}} value="Accepted"> Accepted</option>
                                        <option {{$quote->status == 'Declined' ? 'selected' : '' ;}}  value="Declined">Declined</option>
                                    </select>
                                </div>
                            </div>

                            <div class="form-row col-4">

                                <div class="col">
                                    <label for="exampleInputEmail1">Quotation Date</label>
                                    <input type="date" value="{{$quote->q_date}}" name="q_date" placeholder="Price" class="form-control">
                                </div>
                            </div>
                            <div class="form-row col-4">

                                <div class="col">
                                    <label for="exampleInputEmail1">Expiry Date</label>
                                    <input type="date" value="{{$quote->exp_date}}" name="exp_date" placeholder="Price" class="form-control">
                                </div>
                            </div>
                            <div class="form-row col-8">
                                <div class="col">
                                    <label for="exampleInputEmail1">Remarks</label>
                                    <textarea class="form-control" placeholder="Remarks" name="remarks" id="exampleFormControlTextarea1" rows="1">{{$quote->remarks}}</textarea>
                                </div>
                            </div>


                        </div>

                    </div>
                </div>
            </div>
            <div class="col-md-12 my-3">
                <div class="card h-100">
                    <div class="d-flex justify-content-between align-items-center pr-2">

                        <h4 class="m-3 mb-0">Services on Quote</h4>
                        <!--<button type="button" id="addMoreBtn" class="btn btn-sm btn-primary"> + Add More </button>-->
                    </div>
                    <table id="columnSearchDatatable" class="table table-borderless table-thead-bordered table-nowrap table-align-middle card-table" data-hs-datatables-options='{
                            "order": [],
                            "orderCellsTop": true,
                            "paging":false

                        }'>
                        <thead class="thead-light">
                            <tr>
                                <th class="border-0">Product / Service</th>
                                <th class="border-0">Unit</th>
                                <th class="border-0">Quantity</th>
                                <th class="border-0">Amount (per unit)</th>
                            </tr>
                        </thead>

                        <tbody id="set-rows" class="service_table">
                               @php 
                               $ser_arr = json_decode($quote->services)->service;
                                $unit_arr = json_decode($quote->services)->unit;
                                 $amount_arr = json_decode($quote->services)->amount;
                                  $qty_arr = json_decode($quote->services)->qty;
                               $count = 1;
                                    foreach($ser_arr as $key => $ser1){@endphp
                                    
                                    
                                     <tr class="service_tr " id="tr_{{$count}}">
                                <td style="width:250px">
                                    <select class="form-control sr_pr_name" required name="service_name[]">
                                        <option value="" selected disabled>-- select --</option>
                                        @foreach($services as $ser)
                                        <option {{$ser->id == $ser1 ? 'selected' : ''}} value="{{$ser->id}}">{{$ser->name}}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td><input type="text" placeholder="unit" value="{{$unit_arr[ $key]}}" class="form-control sr_inp " name="service_unit[]" /></td>
                                <!--<td><input type="text" placeholder="rate" class="form-control sr_inp rate_inp" name="service_rate[]" /></td>-->
                                <td><input type="text" placeholder="qty" value="{{$qty_arr[ $key]}}" class="form-control sr_inp" name="service_qty[]" /></td>
                                <td><input type="text" placeholder="amount" value="{{$amount_arr[ $key]}}" class="form-control sr_inp" name="service_amount[]" /></td>
                            </tr>
                            
                                    
                               
                               @php $count ++ ;  } @endphp
                                    
                                    
                            
                           
                            
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="col-md-12">
                <div class="card h-100">
                    <h4 class="m-3 mb-0">Client Details</h4>
                    <div class="card-body row">
                        <div class="form-row col-3">
                            <label for="exampleInputEmail1">Client Name<span class="text-danger">*</span></label>
                            <input type="text" value="{{$quote->client_name}}" name="client_name" required placeholder="Client Name" class="form-control">
                        </div>
                        <div class="form-row col-3">
                            <label for="exampleInputEmail1">Client Mobile<span class="text-danger">*</span></label>
                            <input type="number" name="client_mobile" value="{{$quote->client_mobile}}" required placeholder="Client Mobile" class="form-control">
                        </div>
                        <div class="form-row col-6">
                            <label for="exampleInputEmail1">Client Email<span class="text-danger">*</span></label>
                            <input type="email" required name="client_email" value="{{$quote->client_email}}" placeholder="Client Email" class="form-control">
                        </div>

                    </div>
                </div>
            </div>
            <div class="form-row">

                <div class="col m-3 d-flex justify-content-between">
                    <button style="height: 50px; width: 100px;" class="btn  btn--primary ">Update</button>
                    
                 <!--<div><h4>Total: {{\App\CentralLogics\Helpers::currency_symbol()}}<span id= "totalAmount">0</span> </h4> </div>-->
                </div>
            </div>

        </form>



        @endsection

        @push('script_2')
        <script>
    //  $(".rate_inp").on("keyup", function() {
    //     var total = 0;
        
    //     $(".rate_inp").each(function() {
    //         total += parseFloat($(this).val()) || 0;
    //     });
        
    //     $('#totalAmount').text(total);
    // });

            $('#addMoreBtn').on('click', function() {
                var lastid = $(".service_table .service_tr:last").attr('id');
                var myArray = lastid.split("_");

                var id = Number(myArray[myArray.length - 1]) + 1;
               //   <td><input type="text" placeholder="rate" class="form-control sr_inp" name="service_rate[]"/></td>
                var selectHtml = $('.sr_pr_name').html();
                var html = `<tr class="service_tr" id="tr_` + id + `">
                <td style="width:250px"><select required class="form-control sr_pr_name" name="service_name[]">` + selectHtml + `</select></td>
                <td><input type="text" placeholder="unit" class="form-control sr_inp" name="service_unit[]"/></td>
              
                <td><input type="text" placeholder="qty" class="form-control sr_inp" name="service_qty[]"/></td>
                <td><input type="text" placeholder="amount" class="form-control sr_inp" name="service_amount[]"/></td>
                <td><button class="btn btn-sm btn-danger" type="button" onclick="deleteRow(` + id + `)">x</button></td>
            </tr>`;
                $('.service_table').append(html)
            })

            function deleteRow(id) {
                console.log(id)
                $('#tr_' + id).remove();
            }
        </script>
        @endpush