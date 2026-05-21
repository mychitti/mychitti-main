@extends('layouts.vendor.app')
@section('title', 'Add Ward')

@section('content')
<div class="content container-fluid">
    <div class="page-header">
        <h1 class="page-header-title mb-0">
            <span class="page-header-icon"><i class="tio-hospital" style="font-size:22px;"></i></span>
            Add Ward
        </h1>
    </div>

    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="card">
                <div class="card-body">
                    <form action="{{ route('vendor.ward.store') }}" method="POST">
                        @csrf
                        @include('hmis::vendor.ward._form')
                        <div class="d-flex gap-2 mt-3">
                            <button type="submit" class="btn btn--primary">Save Ward</button>
                            <a href="{{ route('vendor.ward.index') }}" class="btn btn-outline-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
