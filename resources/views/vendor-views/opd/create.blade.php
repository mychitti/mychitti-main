@extends('layouts.vendor.app')
@section('title', 'Register OPD Visit')

@section('content')
<div class="content container-fluid">
    <div class="page-header d-flex justify-content-between align-items-center">
        <h1 class="page-header-title mb-0">
            <span class="page-header-icon"><i class="tio-document-text" style="font-size:22px;"></i></span>
            Register OPD Visit
        </h1>
        <a href="{{ route('vendor.opd.index') }}" class="btn btn-sm btn-outline-secondary">
            <i class="tio-arrow-backward"></i> Register
        </a>
    </div>

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <form action="{{ route('vendor.opd.store') }}" method="POST">
                @csrf
                @include('vendor-views.opd._form', ['visit' => null])
                <div class="d-flex gap-2 mt-3">
                    <button type="submit" class="btn btn--primary">Save Visit</button>
                    <a href="{{ route('vendor.opd.index') }}" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
