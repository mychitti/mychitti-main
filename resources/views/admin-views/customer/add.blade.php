@extends('layouts.admin.app')

@section('title', 'Add New')

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
        <h1 class="page-header-title"><i class="tio-filter-list"></i> Add New Customer</h1>
        <div class="page-header-select-wrapper">

        </div>
    </div>
    <!-- End Page Header -->


    <div class="row ">
        <form enctype="multipart/form-data" class="w-100" action="{{route('admin.users.customer.save')}}" method="post">
            @csrf
            <input type="hidden" id="staff_id" name="account_id" value="">
            <div class="col-md-12">
                <div class="card h-100">
                    <div class="card-body row">
                        <div class="col-sm-6">
                            <div class="form-group mb-3">
                                <label class="input-label"
                                    for="exampleFormControlInput1">{{ translate('messages.first_name') }}</label>
                                <input type="text" name="f_name" class="form-control __form-control"
                                    placeholder="{{ translate('messages.first_name') }}" required
                                    value="{{ old('f_name') }}">
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="form-group mb-3">
                                <label class="input-label"
                                    for="exampleFormControlInput1">{{ translate('messages.last_name') }}</label>
                                <input type="text" name="l_name" class="form-control __form-control"
                                    placeholder="{{ translate('messages.last_name') }}"
                                    value="{{ old('l_name') }}" required>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="form-group mb-3">
                                <label class="input-label"
                                    for="exampleFormControlInput1">{{ translate('messages.email') }}</label>
                                <input type="email" name="email" class="form-control __form-control"
                                    placeholder="{{ translate('messages.Ex:') }} ex@example.com" value="{{ old('email') }}" required>
                            </div>
                        </div>
                        <div class="col-sm-6 mb-3">
                            <label for="phoneInp" class="form-label">Phone Number</label>
                            <input type="tel" class="form-control " style="width: 100%;" name="phone" id="phoneInp" placeholder="Ex: 9988776655">
                            <div class="form-text text-danger response__phone"></div>
                        </div>
                        <div class="col-sm-6">
                            <div class="form-group">
                                <label class="input-label" for="exampleInputPassword">{{ translate('messages.password') }}

                                </label>
                                <input type="password" name="password"
                                    placeholder="{{ translate('messages.password_length_placeholder', ['length' => '6+']) }}"
                                    class="form-control __form-control form-control __form-control-user" minlength="6"
                                    id="exampleInputPassword" required value="{{ old('password') }}">
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="form-group">
                                <label class="input-label" for="exampleRepeatPassword">{{
                                translate('messages.confirm_password')
                                }}</label>
                                <input type="password" name="confirm-password"
                                    class="form-control __form-control form-control __form-control-user" minlength="6"
                                    id="exampleRepeatPassword"
                                    placeholder="{{ translate('messages.password_length_placeholder', ['length' => '6+']) }}"
                                    required value="{{ old('confirm-password') }}">
                                <div class="pass invalid-feedback">{{ translate('messages.password_not_matched') }}</div>
                            </div>

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
    @include('front-views.partials.tel_input')

        @endpush