@extends('layouts.admin.app')

@section('title', translate('MC Vendorhub Sales & Marketing'))

@section('content')
    <div class="content container-fluid">
        <div class="page-header">
            <h1 class="page-header-title">
                <span class="page-header-icon"><i class="tio-comment-text-outlined"></i></span>
                <span>{{ translate('MC Vendorhub Sales & Marketing') }}
                    <span class="badge badge-soft-dark ml-2">{{ $contacts->total() }}</span>
                </span>
            </h1>
            <p class="mb-0 text-muted">
                {{ translate('Enquiries raised on the MC Vendorhub site. MyChitti enquiries stay in their own Sales & Marketing section.') }}
            </p>
        </div>

        <div class="card mb-3">
            <div class="card-header py-2 border-0">
                <form class="row g-2 w-100 align-items-end">
                    <div class="col-md-8">
                        <div class="input-group input--group">
                            <input type="search" name="search" value="{{ $search }}" class="form-control"
                                placeholder="{{ translate('Search name, subject, email or phone') }}">
                            <button type="submit" class="btn btn--secondary"><i class="tio-search"></i></button>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <a href="{{ route('admin.mcvendorhub.enquiries') }}"
                            class="btn btn-outline-secondary w-100">{{ translate('Reset') }}</a>
                    </div>
                </form>
            </div>

            <div class="table-responsive datatable-custom">
                <table class="table table-borderless table-thead-bordered table-align-middle card-table">
                    <thead class="thead-light">
                        <tr>
                            <th>{{ translate('SL') }}</th>
                            <th>{{ translate('Name') }}</th>
                            <th>{{ translate('Business') }}</th>
                            <th>{{ translate('Contact') }}</th>
                            <th>{{ translate('Subject') }}</th>
                            <th>{{ translate('Received') }}</th>
                            <th>{{ translate('Status') }}</th>
                            <th class="text-center">{{ translate('Action') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($contacts as $key => $contact)
                            <tr>
                                <td>{{ $contacts->firstItem() + $key }}</td>
                                <td>
                                    <a
                                        href="{{ route('admin.mcvendorhub.enquiries.view', $contact->id) }}">{{ $contact->name }}</a>
                                </td>
                                <td>{{ $contact->business_name ?: '-' }}</td>
                                <td>
                                    <div>{{ $contact->phone ?: '-' }}</div>
                                    <div class="font-size-sm text-muted">{{ $contact->email ?: '-' }}</div>
                                </td>
                                <td>{{ \Illuminate\Support\Str::limit($contact->subject, 50) ?: '-' }}</td>
                                <td>{{ $contact->created_at?->format('d M Y') }}</td>
                                <td>
                                    @if ($contact->seen)
                                        <span class="badge badge-soft-success">{{ translate('Seen') }}</span>
                                    @else
                                        <span class="badge badge-soft-warning">{{ translate('New') }}</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <a href="{{ route('admin.mcvendorhub.enquiries.view', $contact->id) }}"
                                        class="btn btn-sm btn--primary btn-outline-primary action-btn"><i
                                            class="tio-visible"></i></a>
                                    <form action="{{ route('admin.mcvendorhub.enquiries.delete', $contact->id) }}"
                                        method="post" class="d-inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn--danger btn-outline-danger action-btn"
                                            onclick="return confirm('{{ translate('Delete this enquiry?') }}')"><i
                                                class="tio-delete-outlined"></i></button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-5">
                                    <img class="w--120px mb-3"
                                        src="{{ asset('/public/assets/admin/img/empty-box.png') }}" alt="">
                                    <h5 class="mb-0">{{ translate('No MC Vendorhub enquiries yet') }}</h5>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($contacts->hasPages())
                <div class="card-footer">
                    {!! $contacts->links() !!}
                </div>
            @endif
        </div>

        <div class="card">
            <div class="card-header py-2 border-0">
                <h5 class="card-title mb-0">
                    {{ translate('Vendor Requirements') }}
                    <span class="badge badge-soft-dark ml-2">{{ count($requirements) }}</span>
                </h5>
            </div>
            <div class="table-responsive datatable-custom">
                <table class="table table-borderless table-thead-bordered table-align-middle card-table">
                    <thead class="thead-light">
                        <tr>
                            <th>{{ translate('SL') }}</th>
                            <th>{{ translate('Store') }}</th>
                            <th>{{ translate('Type') }}</th>
                            <th>{{ translate('Description') }}</th>
                            <th>{{ translate('Received') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($requirements as $key => $req)
                            <tr>
                                <td>{{ $key + 1 }}</td>
                                <td>{{ $req->store?->name ?? '-' }}</td>
                                <td>{{ $req->requirement_type ?? '-' }}</td>
                                <td>{{ \Illuminate\Support\Str::limit($req->description, 80) ?: '-' }}</td>
                                <td>{{ $req->created_at?->format('d M Y') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-4 text-muted">
                                    {{ translate('No requirements submitted by MC Vendorhub vendors.') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
