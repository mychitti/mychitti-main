@extends('layouts.admin.app')

@section('title', 'Bill Generate')

@push('css_or_js')
<meta name="csrf-token" content="{{ csrf_token() }}">
<style>
    .form-row {
        margin-top: 6px;
    }
</style>
@endpush

@section('content')
<div class="content container-fluid">
    <!-- Page Header -->
    <div class="page-header">
        <h1 class="page-header-title"><i class="tio-filter-list"></i>Bill Generate</h1>
        <div class="page-header-select-wrapper">

        </div> 
    </div>
    <!-- End Page Header -->



    <div class="row g-2">
        <form class="w-100" action="{{ route('admin.billing.save-manual-invoice') }}" method="post">
            @csrf
            <input type="hidden" id="service_id" name="service_id" value="">
            <div class="form-check  col-md-4 col-sm-6">
                <label class="form-check-label" for="flexRadioDefault2">Bill to</label>
                <select required name="bill_to" id="" class="form-control js-select2-custom">
                    <option value=""></option>  
                    @foreach($customers as $cust)
                    <option value="{{$cust->id}}">{{$cust->phone . ' | ' . $cust->f_name . ' ' . $cust->l_name}}</option>
                    @endforeach 
                </select>
            </div>
           

            <div class="col-md-12">
                <div class="card h-100">
                    <div class="card-body row item_row">
                        <table class="table">
                            <thead class="" style=" background: #75b8b8; color: white;">
                                <tr>
                                    <th scope="col">Item</th>
                                    <th scope="col">Price</th>
                                    <th scope="col">Qty</th>
                                    <th scope="col">Tax <i>(in %)</i></th>
                                    <th scope="col">HSN</th>
                                    <th scope="col"><button type="button" class="btn btn-dark btn-sm" onclick="addMoreRow()">Add More</button></th>
                                </tr>
                            </thead>
                            <tbody class="rows_parent">
                               
                        
                                <tr class="item_row" data-id="1">
                                    <input type="hidden" name="invoice_item_id[]">
                                    <td><input type="text"  name="item_name[]" placeholder="Item Name" class="form-control"></td>
                                    <td><input type="number" name="item_price[]" placeholder="Price" class="form-control"></td>
                                    <td><input type="number" name="item_qty[]" placeholder="Quantity" class="form-control"></td>
                                    <td><input type="number" name="item_tax[]" placeholder="Tax" class="form-control"></td>
                                    <td><input type="text" name="item_hsn[]" placeholder="HSN" class="form-control"></td>
                                    <td><button type="button" onclick="deleteNewRow('invoice')" class="btn action-btn btn--danger btn-outline-danger"><i class="tio-delete-outlined"></i></button></td>
                                </tr>
                         

                            </tbody>
                        </table>


                        <div class="form-check mr-5 ml-4">
                            <input class="form-check-input" value="Paid" name="payment_stts" type="radio" name="flexRadioDefault" id="flexRadioDefault1">
                            <label class="form-check-label" for="flexRadioDefault1">
                                Paid
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" value="Unpaid" name="payment_stts" type="radio" name="flexRadioDefault" id="flexRadioDefault2" checked>
                            <label class="form-check-label" for="flexRadioDefault2">
                                Unpaid
                            </label>
                        </div>
                        <br>
                        <div class="form-check payment_date_inp col-md-4 col-sm-6">
                            <label class="form-check-label" for="flexRadioDefault2">Payment Date</label>
                            <input class="form-control" min="{{date('Y-m-d')}}" name="payment_date" type="date"  name="flexRadioDefault" id="flexRadioDefault2" >
                        </div>
                        <div class="form-check reminder_date_inp col-md-4 col-sm-6">
                            <label class="form-check-label" for="flexRadioDefault2">Reminder Date</label>
                            <input class="form-control" min="{{date('Y-m-d')}}" name="reminder_date" type="date"  name="flexRadioDefault" id="flexRadioDefault2" >
                        </div>
                    </div>
                </div>
                <button class="btn btn-primary my-2">Generate Bill</button>
            </div>

        </form>
    </div>
</div>
@endsection
@push('script_2')

<script>
    $(document).on('change', 'input[name="payment_stts"]', function(){
        var val = $(this).val();
        if(val == 'Paid'){
            $(".payment_date_inp").hide() 
            $(".reminder_date_inp").hide() 
        }else{
            $(".payment_date_inp").show()
            $(".reminder_date_inp").show()
        }
    })
  
    function toasterNotification(msg) {
    $("#toast").text(msg);
    $("#toast").addClass("show");
    setTimeout(function () {
        $("#toast").removeClass("show");
    }, 3000);
}

    function deleteNewRow(rowId) {
        $('[data-id="' + rowId + '"]').remove()
    }


    function addMoreRow() {

        var $lastItemRow = $('.item_row').last();

        if (!$lastItemRow.length) {
            var dataId = 1;
        } else {
            var dataId = Number($lastItemRow.data('id')) + 1;

        }
        console.log(dataId)

        var html = `<tr class="item_row" data-id="` + dataId + `">
                       <input type="hidden" name="invoice_item_new[]" value="1" placeholder="Item Name" class="form-control">
                      <td><input type="text" name="item_name_new[]" placeholder="Item Name" class="form-control"></td>
                      <td><input type="number" name="item_price_new[]" placeholder="Price" class="form-control"></td>
                      <td><input type="number" name="item_qty_new[]" placeholder="Qunatity" class="form-control"></td>
                      <td><input type="number" name="item_tax_new[]" placeholder="Tax" class="form-control"></td>
                      <td><input type="text" name="item_hsn_new[]" placeholder="HSN" class="form-control"></td>
                       <td><button type="button"  onclick="deleteNewRow(` + dataId + `)" class="btn action-btn btn--danger btn-outline-danger"><i class="tio-delete-outlined"></i></button></td>
                    </tr>`; 
 
        $('.rows_parent').append(html)
    }
</script>
@endpush