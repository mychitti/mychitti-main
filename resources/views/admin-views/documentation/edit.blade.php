@extends('layouts.admin.app')

@section('title', translate('Edit Document'))

@push('css_or_js')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @include('admin-views.documentation._editor')
@endpush

@section('content')
    <div class="content container-fluid">
        <div class="page-header">
            <h1 class="page-header-title">
                <span class="page-header-icon"><i class="tio-edit"></i></span>
                <span>{{ translate('Edit') }}: {{ $doc->title }}</span>
            </h1>
            <p class="doc-help mb-0">
                <a href="{{ route('admin.documentation.index') }}">{{ translate('Documentation') }}</a>
                / <a href="{{ route('admin.documentation.show', $doc->id) }}">{{ Str::limit($doc->title, 40) }}</a>
                / {{ translate('Edit') }}
            </p>
        </div>

        <form action="{{ route('admin.documentation.update', $doc->id) }}" method="post"
            enctype="multipart/form-data" id="doc-form">
            @csrf
            @include('admin-views.documentation._form', ['doc' => $doc])
        </form>
    </div>
@endsection
