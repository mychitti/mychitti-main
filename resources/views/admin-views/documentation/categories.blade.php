@extends('layouts.admin.app')

@section('title', translate('Documentation Categories'))

@push('css_or_js')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        .doc-help {
            font-size: 12px;
            color: #6c757d;
        }

        .doc-cat-dot {
            width: 14px;
            height: 14px;
            border-radius: 50%;
            display: inline-block;
            vertical-align: middle;
        }
    </style>
@endpush

@section('content')
    <div class="content container-fluid">
        <div class="page-header">
            <div class="row align-items-center g-2">
                <div class="col-md-8 col-12">
                    <h1 class="page-header-title">
                        <span class="page-header-icon"><i class="tio-folder"></i></span>
                        <span>{{ translate('Documentation Categories') }}</span>
                    </h1>
                    <p class="doc-help mb-0">
                        <a href="{{ route('admin.documentation.index') }}">{{ translate('Documentation') }}</a>
                        / {{ translate('Categories') }}
                    </p>
                </div>
                <div class="col-md-4 col-12 text-md-right">
                    <a href="{{ route('admin.documentation.index') }}" class="btn btn-outline-secondary">
                        <i class="tio-back-ui"></i> {{ translate('Back') }}
                    </a>
                </div>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header border-0">
                        <h5 class="card-title mb-0">{{ translate('Add Category') }}</h5>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('admin.documentation.categories.store') }}" method="post">
                            @csrf
                            <div class="form-group">
                                <label class="input-label">{{ translate('Name') }} <span
                                        class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control" required
                                    placeholder="{{ translate('e.g. Database Schema') }}">
                            </div>
                            <div class="form-group">
                                <label class="input-label">{{ translate('Colour') }}</label>
                                <input type="color" name="color" class="form-control" value="#6c5ce7"
                                    style="height:40px; padding:4px;">
                            </div>
                            <div class="form-group">
                                <label class="input-label">{{ translate('Description') }}</label>
                                <textarea name="description" rows="2" class="form-control"></textarea>
                            </div>
                            <button type="submit" class="btn btn--primary w-100">{{ translate('Add Category') }}</button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-lg-8">
                <div class="card">
                    <div class="table-responsive datatable-custom">
                        <table class="table table-borderless table-thead-bordered table-align-middle card-table">
                            <thead class="thead-light">
                                <tr>
                                    <th class="border-0">{{ translate('SL') }}</th>
                                    <th class="border-0">{{ translate('Category') }}</th>
                                    <th class="border-0">{{ translate('Description') }}</th>
                                    <th class="border-0 text-center">{{ translate('Documents') }}</th>
                                    <th class="border-0 text-center">{{ translate('Status') }}</th>
                                    <th class="border-0 text-center">{{ translate('Action') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($categories as $key => $cat)
                                    <tr>
                                        <td>{{ $key + 1 }}</td>
                                        <td>
                                            <span class="doc-cat-dot"
                                                style="background: {{ $cat->color ?? '#6c5ce7' }};"></span>
                                            <strong class="ml-1">{{ $cat->name }}</strong>
                                        </td>
                                        <td class="doc-help">{{ $cat->description ?: '—' }}</td>
                                        <td class="text-center">{{ $cat->documents_count }}</td>
                                        <td class="text-center">
                                            @if ($cat->status)
                                                <span class="badge badge-soft-success">{{ translate('Active') }}</span>
                                            @else
                                                <span class="badge badge-soft-secondary">{{ translate('Hidden') }}</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="btn--container justify-content-center">
                                                <a class="btn action-btn btn--primary btn-outline-primary"
                                                    href="javascript:" data-toggle="modal"
                                                    data-target="#editCat{{ $cat->id }}"
                                                    title="{{ translate('Edit') }}"><i class="tio-edit"></i></a>
                                                <a class="btn action-btn btn--danger btn-outline-danger form-alert"
                                                    href="javascript:" data-id="cat-{{ $cat->id }}"
                                                    data-message="{{ translate('Delete this category?') }}"
                                                    title="{{ translate('Delete') }}"><i
                                                        class="tio-delete-outlined"></i></a>
                                                <form
                                                    action="{{ route('admin.documentation.categories.delete', $cat->id) }}"
                                                    method="post" id="cat-{{ $cat->id }}">
                                                    @csrf @method('delete')
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center py-4">{{ translate('No categories yet.') }}
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @foreach ($categories as $cat)
        <div class="modal fade" id="editCat{{ $cat->id }}" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <form action="{{ route('admin.documentation.categories.update', $cat->id) }}" method="post">
                    @csrf
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">{{ translate('Edit Category') }}</h5>
                            <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                        </div>
                        <div class="modal-body">
                            <div class="form-group">
                                <label class="input-label">{{ translate('Name') }} <span
                                        class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control" required
                                    value="{{ $cat->name }}">
                            </div>
                            <div class="form-group">
                                <label class="input-label">{{ translate('Colour') }}</label>
                                <input type="color" name="color" class="form-control"
                                    value="{{ $cat->color ?: '#6c5ce7' }}" style="height:40px; padding:4px;">
                            </div>
                            <div class="form-group">
                                <label class="input-label">{{ translate('Description') }}</label>
                                <textarea name="description" rows="2" class="form-control">{{ $cat->description }}</textarea>
                            </div>
                            <div class="form-group form-check mb-0">
                                <input type="checkbox" class="form-check-input" name="status" value="1"
                                    id="catStatus{{ $cat->id }}" {{ $cat->status ? 'checked' : '' }}>
                                <label class="form-check-label"
                                    for="catStatus{{ $cat->id }}">{{ translate('Active') }}</label>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary"
                                data-dismiss="modal">{{ translate('Cancel') }}</button>
                            <button type="submit" class="btn btn--primary">{{ translate('Save') }}</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    @endforeach
@endsection
