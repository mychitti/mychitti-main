@extends('layouts.admin.app')

@section('title', translate('Store SEO'))

@section('content')
    <div class="content container-fluid">
        <div class="page-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <h1 class="page-header-title">
                    <span class="page-header-icon"><i class="tio-search"></i></span>
                    <span>{{ translate('Store SEO') }}
                        <span class="badge badge-soft-dark ml-2">{{ $counts['total'] }}</span>
                    </span>
                </h1>
                <p class="mb-0 text-muted">
                    {{ translate('Meta title / description for every store\'s page — MyChitti-listed and MC Vendorhub-only alike.') }}
                </p>
            </div>
            @if ($counts['missing'] > 0)
                <form method="POST" action="{{ route('admin.mcvendorhub.seo.generate-missing') }}"
                    onsubmit="return confirm('{{ translate('Queue AI meta generation for every store missing meta?') }}');">
                    @csrf
                    @foreach (request()->only(['search', 'zone_id', 'status']) as $key => $val)
                        <input type="hidden" name="{{ $key }}" value="{{ $val }}">
                    @endforeach
                    <button type="submit" class="btn btn--primary btn-sm">
                        <i class="tio-magic-wand"></i> {{ translate('Generate all missing') }} ({{ $counts['missing'] }})
                    </button>
                </form>
            @endif
        </div>

        <div class="row mb-3">
            <div class="col-md-4 mb-2">
                <div class="card text-center p-3">
                    <div class="h3 mb-0">{{ $counts['total'] }}</div>
                    <div class="text-muted small">{{ translate('Total stores') }}</div>
                </div>
            </div>
            <div class="col-md-4 mb-2">
                <div class="card text-center p-3">
                    <div class="h3 mb-0 text-success">{{ $counts['filled'] }}</div>
                    <div class="text-muted small">{{ translate('Meta filled') }}</div>
                </div>
            </div>
            <div class="col-md-4 mb-2">
                <div class="card text-center p-3">
                    <div class="h3 mb-0 text-warning">{{ $counts['missing'] }}</div>
                    <div class="text-muted small">{{ translate('Missing meta') }}</div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header py-2 border-0">
                <form class="row g-2 w-100 align-items-end">
                    <div class="col-md-4">
                        <div class="input-group input--group">
                            <input type="search" name="search" value="{{ request('search') }}" class="form-control"
                                placeholder="{{ translate('Search store name or address') }}">
                            <button type="submit" class="btn btn--secondary"><i class="tio-search"></i></button>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <select name="zone_id" class="form-control">
                            <option value="">{{ translate('All cities') }}</option>
                            @foreach ($zones as $z)
                                <option value="{{ $z->id }}" {{ request('zone_id') == $z->id ? 'selected' : '' }}>
                                    {{ $z->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select name="status" class="form-control" onchange="this.form.submit()">
                            <option value="">{{ translate('All stores') }}</option>
                            <option value="missing" {{ request('status') === 'missing' ? 'selected' : '' }}>{{ translate('Missing meta') }}</option>
                            <option value="filled" {{ request('status') === 'filled' ? 'selected' : '' }}>{{ translate('Meta filled') }}</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <a href="{{ route('admin.mcvendorhub.seo') }}"
                            class="btn btn-outline-secondary w-100">{{ translate('Reset') }}</a>
                    </div>
                </form>
            </div>

            <div class="table-responsive datatable-custom">
                <table class="table table-borderless table-thead-bordered table-align-middle card-table">
                    <thead class="thead-light">
                        <tr>
                            <th>{{ translate('Store') }}</th>
                            <th>{{ translate('Meta title') }}</th>
                            <th>{{ translate('Meta description') }}</th>
                            <th class="text-center">{{ translate('Status') }}</th>
                            <th class="text-center">{{ translate('Action') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($stores as $store)
                            @php($hasMeta = $store->meta_title && $store->meta_description)
                            <tr>
                                <td>
                                    <a href="{{ route('admin.store.view', $store->id) }}" class="font-weight-bold">{{ $store->name }}</a>
                                    <div class="text-muted small">#{{ $store->id }}</div>
                                </td>
                                <td style="max-width: 260px;">
                                    <span class="text-muted small">{{ $store->meta_title ?: '—' }}</span>
                                </td>
                                <td style="max-width: 340px;">
                                    <span class="text-muted small">{{ $store->meta_description ?: '—' }}</span>
                                </td>
                                <td class="text-center">
                                    @if ($hasMeta)
                                        <span class="badge badge-soft-success">{{ translate('Filled') }}</span>
                                    @else
                                        <span class="badge badge-soft-warning">{{ translate('Missing') }}</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <form method="POST" action="{{ route('admin.mcvendorhub.seo.generate', $store->id) }}"
                                        onsubmit="return confirm('{{ $hasMeta ? translate('This will overwrite the existing meta. Continue?') : translate('Generate meta for this store?') }}');">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-outline-primary">
                                            <i class="tio-repeat"></i> {{ $hasMeta ? translate('Regenerate') : translate('Generate') }}
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-5">
                                    <img class="w--120px mb-3"
                                        src="{{ asset('/public/assets/admin/img/empty-box.png') }}" alt="">
                                    <h5 class="mb-0">{{ translate('No stores match this filter') }}</h5>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($stores->hasPages())
                <div class="card-footer">
                    {!! $stores->links() !!}
                </div>
            @endif
        </div>
    </div>
@endsection
