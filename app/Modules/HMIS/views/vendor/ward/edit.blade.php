@extends('layouts.vendor.app')
@section('title', 'Edit Ward')

@section('content')
<div class="content container-fluid">
    <div class="page-header">
        <h1 class="page-header-title mb-0">
            <span class="page-header-icon"><i class="tio-hospital" style="font-size:22px;"></i></span>
            Edit Ward — {{ $ward->ward_name }}
        </h1>
    </div>

    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="card">
                <div class="card-body">
                    <form action="{{ route('vendor.ward.update', $ward->id) }}" method="POST">
                        @csrf
                        @method('PUT')
                        @include('hmis::vendor.ward._form', ['ward' => $ward])
                        <div class="form-group mt-3">
                            <div class="custom-control custom-switch">
                                <input type="checkbox" class="custom-control-input" id="is_active"
                                    name="is_active" {{ $ward->is_active ? 'checked' : '' }}>
                                <label class="custom-control-label" for="is_active">Active</label>
                            </div>
                        </div>
                        <div class="d-flex gap-2 mt-3">
                            <button type="submit" class="btn btn--primary">Update Ward</button>
                            <a href="{{ route('vendor.ward.index') }}" class="btn btn-outline-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
