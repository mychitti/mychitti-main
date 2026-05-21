@extends('layouts.vendor.app')

@section('title', 'Invoice Generate')

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
    <div class="content container-fluid">
        <!-- Page Header -->
        <div class="page-header">
            <h1 class="page-header-title"><i class="tio-filter-list"></i>Invoice Generate</h1>
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
            <form class="w-100" action="{{ route('vendor.staff.save') }}" method="post">
                @csrf
                <input type="hidden" id="staff_id" name="staff_id" value="{{isset($staff->id ) ? $staff->id : ''}}">

                <div class="col-md-12">
                    <div class="card h-100">
                        <div class="card-body row item_row">
                            <table class="table">
                                <thead class="" style=" background: #75b8b8; color: white;">
                                    <tr>
                                        <th scope="col">Item</th>
                                        <th scope="col">Price</th>
                                        <th scope="col">Tax <i>(in %)</i></th>
                                        <th scope="col"><button type="button" class="btn btn-dark btn-sm" onclick="addMoreRow()">Add More</button></th>
                                    </tr>
                                </thead>
                                <tbody class="rows_parent">
                                    <tr class="item_row" data-id="1">
                                        <td><input type="text" name="item_name[]" placeholder="Item Name" class="form-control"></td>
                                        <td><input type="number" name="item_price[]" placeholder="Price" class="form-control"></td>
                                        <td><input type="number" name="item_tax[]" placeholder="Tax" class="form-control"></td>
                                        <td><button type="button" onclick="deleteRow(1)" class="btn action-btn btn--danger btn-outline-danger"><i class="tio-delete-outlined"></i></button></td>
                                    </tr>
                                </tbody>
                            </table>

                        </div>
                    </div>
                </div>

            </form>

            @endsection
            @push('script_2')

            <script>
                   function deleteRow(rowId){
        $('[data-id="'+ rowId +'"]').remove()
    }


    function addMoreRow(){

         var $lastItemRow = $('.item_row').last();

        if(!$lastItemRow.length){
             var dataId = 1;
         }else{
            var dataId = Number($lastItemRow.data('id')) + 1;

         }
        console.log(dataId)

        var html  = `<tr  class="item_row" data-id="`+ dataId +`">
                      <td><input type="text" name="item_name[]" placeholder="Item Name" class="form-control"></td>
                      <td><input type="number" name="item_price[]" placeholder="Price" class="form-control"></td>
                      <td><input type="number" name="item_tax[]" placeholder="Tax" class="form-control"></td>
                       <td><button type="button"  onclick="deleteRow(`+dataId+`)" class="btn action-btn btn--danger btn-outline-danger"><i class="tio-delete-outlined"></i></button></td>
                    </tr>` ;

        $('.rows_parent').append(html)
    }
            </script>
            @endpush