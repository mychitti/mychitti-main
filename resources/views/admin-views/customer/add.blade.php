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
    <div class="page-header">
        <h1 class="page-header-title"><i class="tio-filter-list"></i> Add New Customer</h1>
    </div>

    <div class="row">
        <form enctype="multipart/form-data" class="w-100" action="{{ route('admin.users.customer.save') }}" method="post">
            @csrf
            <input type="hidden" id="staff_id" name="account_id" value="">
            <div class="col-md-12">
                <div class="card h-100">
                    <div class="card-body row">

                        <div class="col-sm-6">
                            <div class="form-group mb-3">
                                <label class="input-label">Name</label>
                                <input type="text" name="f_name" class="form-control __form-control"
                                    placeholder="Customer name" required value="{{ old('f_name') }}">
                            </div>
                        </div>

                        <div class="col-sm-6">
                            <div class="form-group mb-3">
                                <label class="input-label">{{ translate('messages.email') }}</label>
                                <input type="email" name="email" class="form-control __form-control"
                                    placeholder="ex@example.com" value="{{ old('email') }}" required>
                            </div>
                        </div>

                        <div class="col-sm-6 mb-3">
                            <label class="form-label">Phone Number</label>
                            <input type="tel" class="form-control" style="width:100%;" name="phone" id="phoneInp" placeholder="Ex: 9988776655">
                            <div class="form-text text-danger response__phone"></div>
                        </div>

                        <div class="col-sm-6">
                            <div class="form-group mb-3">
                                <label class="input-label">GST Number</label>
                                <input type="text" name="gst" class="form-control __form-control"
                                    placeholder="Ex: 22AAAAA0000A1Z5" value="{{ old('gst') }}">
                            </div>
                        </div>

                        <div class="col-sm-12">
                            <div class="form-group mb-3">
                                <label class="input-label">Address</label>
                                <textarea name="address" class="form-control __form-control" rows="3"
                                    placeholder="Full address">{{ old('address') }}</textarea>
                            </div>
                        </div>

                        <div class="form-row col-12">
                            <div class="col my-2">
                                <button class="btn btn--primary btn-outline-primary">Save</button>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@push('script_2')
    @include('front-views.partials.tel_input')
@endpush
