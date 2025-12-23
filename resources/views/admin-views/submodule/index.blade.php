@extends('layouts.admin.app')

@section('title', 'Modules Billing')

@push('css_or_js')
    <meta name="csrf-token" content="{{ csrf_token() }}">
@endpush

@section('content')

    <div class="content container-fluid">
        <!-- Page Header -->
        <div class="page-header">
            <h1 class="page-header-title"><i class="tio-filter-list"></i>Modules Billing</h1>
            <div class="page-header-select-wrapper">

            </div>
        </div>
        <!-- End Page Header -->


        <div class="row g-2">
            <form class="w-100" action="{{ route('admin.modules-billing.update-info') }}" method="post">
                @csrf
                <input type="hidden" id="lead_id" name="lead_id" value="">
                <div class="col-md-12">
                    <div class="card h-100">
                        <!-- <h4 class="m-3 mb-0">Client Details</h4> -->
                        <div class="card-body row">
                            <table class="table table-borderless">
                                <thead>
                                    <tr>
                                        <th scope="col">#</th>
                                        <th scope="col">Name</th>
                                        <th scope="col">Price (per month)</th>
                                        <th scope="col">Free Trial Days</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($submodules as $key => $m)
                                        <tr>
                                            <th scope="row">{{ $key + 1 }}</th>
                                            <td> <label for="">{{ $m->name }}</label></td>
                                            <td> <input class="form-control" value="{{ $m->price_per_month }}"
                                                    name="price_{{ $m->id }}" placeholder="Price"></td>
                                            <td> <input class="form-control" value="{{ $m->free_trial_days }}"
                                                    name="trial_days_{{ $m->id }}" placeholder="Trial Days"></td>
                                        </tr>
                                    @endforeach

                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="form-row">
                    <div class="col my-2">
                        <button class="btn  btn--primary btn-outline-primary">Update</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('script_2')
@endpush
