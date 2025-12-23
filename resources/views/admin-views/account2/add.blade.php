@extends('layouts.admin.app')

@section('title', 'Add New')

@push('css_or_js')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        .form-row{
            margin-top: 6px;
        }
    </style>
@endpush 

@section('content')
    <div class="content container-fluid">
        <div class="content container-fluid">
            <!-- Page Header -->
            <div class="page-header">
                <h1 class="page-header-title"><i class="tio-filter-list"></i> Add New </h1>
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
                <form enctype="multipart/form-data" class="w-100" action="{{ route('admin.account.save') }}" method="post">
                    @csrf
                    <input type="hidden" id="staff_id" name="account_id" value="">
                    <div class="col-md-12">
                        <div class="card h-100">
                            <div class="card-body row">
                                <div class="form-row col-4">
                                    <label for="exampleInputEmail1">Date <span class="text-danger">*</span></label>
                                    <input type="date" required name="date" class="form-control">
                                </div>
                                <div class="form-row col-4">
                                    <label for="exampleInputEmail1">Type <span class="text-danger">*</span></label>
                                    <select required name="type" class="form-control js-select2-custom">
                                        <option value="expense">Expense</option>
                                        <option value="income">Income</option>
                                    </select>
                                </div>
                                <div class="form-row col-4">
                                    <label for="exampleInputEmail1">Classification </label>
                                    <select name="classification" class="form-control js-select2-custom">
                                        <option value="" selected disabled>--select--</option>
                                        <option value="expense">Account</option>
                                        <option value="income">Asset</option>
                                        <option value="income">Liability</option>
                                    </select>
                                </div>
                                <div class="form-row col-4">
                                    <label for="exampleInputEmail1">Status <span class="text-danger">*</span></label>
                                    <select name="status" required class="form-control js-select2-custom">
                                        <option value="completed">Completed</option>
                                        <option value="pending">Pending</option>
                                    </select>
                                </div>
                                <div class="form-row col-4">
                                    <label for="exampleInputEmail1">Payment Mode <span class="text-danger">*</span></label>
                                    <select name="payment_mode" required class="form-control js-select2-custom">
                                        <option value="" selected disabled>--select--</option>
                                        <option value="bank">Bank</option>
                                        <option value="upi">UPI</option>
                                        <option value="cash">Cash</option>
                                    </select>
                                </div>
                                <div class="form-row col-4">
                                    <label for="exampleInputEmail1">Name of Company / Person</i> <span class="text-danger">*</span></label>
                                    <input type="text" name="name" required placeholder="Name of Company / Person"
                                        class="form-control">
                                </div>
                                <div class="form-row col-4">
                                    <label for="exampleInputEmail1">Amount <span class="text-danger">*</span></label>
                                    <input type="number" name="amount" required placeholder="Amount" class="form-control">
                                </div>
                                <div class="form-row col-4">
                                    <label for="exampleInputEmail1">Bill Number / Details </label>
                                    <input type="number" name="bill_numer"  placeholder="Bill Number / Details" class="form-control">
                                </div>
                                <div class="form-row col-4">
                                    <label for="exampleInputEmail1">Additional Note</label>
                                    <textarea name="note"  placeholder="Additional Note" class="form-control"></textarea>
                                </div>
                                <div class="form-row col-4">
                                    <label for="exampleInputEmail1">Document <i>(Optional)</i></label>
                                    <input type="file" name="file" id="" accept=".doc, .docx, .pdf" class="form-control">
                                </div>
                               
                                <div class="form-row col-12">
                                    <div class="col my-2">
                                        <button class="btn  btn--primary btn-outline-primary">Save</button>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>

                </form>



            @endsection

            @push('script_2')
            @endpush
