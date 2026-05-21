@extends('layouts.vendor.app')

@section('title', 'Invoice Generate')

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
            <h1 class="page-header-title"><i class="tio-filter-list"></i> Add Staff </h1>
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
                        <h4 class="m-3 mb-0">Personal Information</h4>
                        <div class="card-body row">
                            <div class="form-row col-4">
                                <label for="exampleInputEmail1">Username <span class="text-danger">*</span></label>
                                <input type="text" name="username" required placeholder="Username" class="form-control">
                            </div>
                            <div class="form-row col-4">
                                <label for="exampleInputEmail1">Full Name <span class="text-danger">*</span></label>
                                <input type="text" name="name" required placeholder="Full Name" class="form-control">
                            </div>
                            <div class="form-row col-4">
                                <label for="exampleInputEmail1">Mobile <span class="text-danger">*</span></label>
                                <input type="number" name="mobile" required placeholder="Mobile" class="form-control">
                            </div>
                            <div class="form-row col-6">
                                <label for="exampleInputEmail1">Email <span class="text-danger">*</span></label>
                                <input type="email" name="email" required placeholder="Email" class="form-control">
                            </div>
                            <div class="form-row col-6">
                                <label for="exampleInputEmail1">Address</label>
                                <textarea class="form-control" placeholder="Address" name="address" id="exampleFormControlTextarea1" rows="1"></textarea>
                            </div>
                            <div class="form-row col-4">
                                <label for="exampleInputEmail1">DOB </label>
                                <input type="date" name="dob"  class="form-control">
                            </div>
                            <div class="form-row col-4">
                                <label for="exampleInputEmail1">City <span class="text-danger">*</span></label>
                                <input type="text" name="city" required placeholder="City" class="form-control">
                            </div>
                            <div class="form-row col-4">
                                <label for="exampleInputEmail1">Pincode <span class="text-danger">*</span></label>
                                <input type="number" name="pincode" required placeholder="Pincode" class="form-control">
                            </div>

                        </div>
                    </div>
                </div>
                <div class="col-md-12 mt-3">
                    <div class="card h-100">
                        <div class="row">
                            <h4 class="m-3 mb-0 col-7">Other Information</h4>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="form-row col-6">
                                    <label for="inputState">Deparetment <span class="text-danger">*</span></label>
                                    <select name="department_id" required id="inputState" class="form-control">
                                        <option value="" disabled selected>--select--</option>
                                        @foreach ($departments as $dep)
                                            <option value="{{ $dep->id }}">{{ $dep->title }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-row col-6">
                                    <label for="exampleInputEmail1">Salary <i>(/ month)</i> <span class="text-danger">*</span></label>
                                    <input type="number" required name="salary_per_month" placeholder="Salary Per Month" class="form-control">
                                </div>
                                <div class="form-row">
                                    <div class="col my-2">
                                        <button class="btn  btn--primary btn-outline-primary">Save</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>



        @endsection

        @push('script_2')
        @endpush
