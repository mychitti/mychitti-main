@extends('layouts.admin.app')

@section('title', 'Store Modules')

@push('css_or_js')
    <meta name="csrf-token" content="{{ csrf_token() }}">
@endpush

@section('content')

    <div class="content container-fluid">
        <!-- Page Header -->
        <div class="page-header">
            <h1 class="page-header-title"><i class="tio-filter-list"></i>Store Modules</h1>
            <div class="page-header-select-wrapper">
                <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#storemoduleModal">+ Add Free
                    Trial</button>
            </div>
        </div>
        <!-- End Page Header -->


        <div class="row g-2">
            <div class="col-md-12">
                <div class=" h-100">
                    <div class="accordion w-100" id="accordionExample">
                        @foreach ($storeIds as $key => $m)
                            <div class="card p-0">
                                <div class="card-header" id="headingOne">
                                    <button class="btn btn-link btn-block text-left d-flex justify-content-between w-100"
                                        type="button" data-toggle="collapse" data-target="#collapseOne{{ $key }}"
                                        aria-expanded="true" aria-controls="collapseOne">

                                        <div> <b>{{ $key + 1 }} &nbsp; &nbsp; &nbsp; </b> {{ $m->store?->name }}
                                            ({{ count($storemodules[$m->store_id]) }} Modules ) </div>
                                        <div class="d-flex">
                                            <a href="{{ route('admin.modules-billing.history', [$m->store_id]) }}"
                                                class="mx-2 text-underline">Free Trial Renew History</a>
                                            </a>
                                            <a class="btn action-btn btn--warning btn-outline-warning"><i
                                                    class="tio-visible"></i></a>
                                            </a>
                                        </div>
                                    </button>
                                </div>

                                <div id="collapseOne{{ $key }}" class="collapse {{ !$key ? ' show ' : '' }}"
                                    aria-labelledby="headingOne" data-parent="#accordionExample">
                                    <div class="card-body p-0">
                                        <table class="table">
                                            <thead>
                                                <tr>
                                                    <th scope="col"></th>
                                                    <th scope="col">#</th>
                                                    <th scope="col">Service Name</th>
                                                    <th scope="col">Start Date</th>
                                                    <th scope="col">Status</th>
                                                    <th scope="col">Price</th>
                                                    <th scope="col">Type</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($storemodules[$m->store_id] as $key2 => $value)
                                                    <tr>
                                                        <th></th>
                                                        <th scope="row">{{ $key2 + 1 }}</th>
                                                        <td>{{ $value->submodule?->name }}</td>
                                                        <td>{{ $value->start_date }}</td>
                                                        <td>
                                                            @if ($value->end_date)
                                                                <label class="badge badge-soft-danger">
                                                                    Ended
                                                                </label> <br> at {{ $value->end_date }}
                                                            @else
                                                                <label class="badge badge-soft-info">
                                                                    Active
                                                                </label>
                                                            @endif
                                                        </td>
                                                        <td>{{ $value->submodule?->price_per_month }} /mo</td>
                                                        <td>
                                                            @if ($value->free_trial_extended_until)
                                                                <label class="badge badge-soft-danger">
                                                                    Free Trial
                                                                </label>
                                                            @else
                                                                <label class="badge badge-soft-success">
                                                                    Billing 
                                                                </label>
                                                            @endif
                                                        </td>

                                                    </tr>
                                                @endforeach


                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        @endforeach

                    </div>


                    <div class="d-flex justify-content-center rewre">
                        {{ $storeIds->links() }}
                    </div>
                </div>
            </div>
        </div>

        <div class="modal fade" id="storemoduleModal" tabindex="-1" aria-labelledby="storemoduleModal" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="exampleModalLabel">Add Free Trial</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <form action="{{ route('admin.modules-billing.renew-free-trial') }}" method="post"
                        enctype="multipart/form-data">
                        <div class="modal-body">
                            @csrf
                            <div class="form-group">
                                <label class="input-label" for="exampleFormControlInput1">Stores</label>
                                <select name="stores[]" class="form-control js-select2-custom" multiple="multiple"
                                    data-placeholder="{{ translate('messages.select_stores') }}">
                                    @foreach ($stores as $store)
                                        <option value="{{ $store->id }}">{{ $store->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="input-label" for="exampleFormControlInput1">Service</label>
                                <select name="submodules[]" class="form-control js-select2-custom" multiple="multiple"
                                    data-placeholder="{{ translate('messages.select_modules') }}">
                                    @foreach ($submodules as $subm)
                                        <option value="{{ $subm->id }}">{{ $subm->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="input-label" for="exampleFormControlInput1">Free Trial Days</label>
                                <input type="number" class="form-control" name="free_trial_days" placeholder="Ex: 15">
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                            <button type="submit" class="btn btn-primary">Save changes</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script_2')
@endpush
