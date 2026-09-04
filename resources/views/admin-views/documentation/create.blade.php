@extends('layouts.admin.app')

@section('title', translate('New Document'))

@push('css_or_js')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @include('admin-views.documentation._editor')
@endpush

@section('content')
    <div class="content container-fluid">
        <div class="page-header">
            <h1 class="page-header-title">
                <span class="page-header-icon"><i class="tio-book"></i></span>
                <span>{{ translate('New Document') }}</span>
            </h1>
            <p class="doc-help mb-0">
                <a href="{{ route('admin.documentation.index') }}">{{ translate('Documentation') }}</a>
                / {{ translate('New Document') }}
            </p>
        </div>

        <form action="{{ route('admin.documentation.store') }}" method="post" enctype="multipart/form-data"
            id="doc-form">
            @csrf
            @include('admin-views.documentation._form', ['doc' => null])
        </form>
    </div>
@endsection
