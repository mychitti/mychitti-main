@extends('layouts.vendor.app')

@section('title', translate('messages.POS Items'))

@push('css_or_js')
    <link rel="stylesheet" href="{{ asset('public/assets/admin/css/select2custom.css') }}">
@endpush


@section('content')
    <div class="content container-fluid">

        @if (hasPermission('pos_items', 'add'))
            <!-- Page Header -->
            <div class="page-header">
                <h1 class="page-header-title">
                    <span class="page-header-icon">
                        <img src="{{ asset('public/assets/admin/img/category.png') }}" class="w--20" alt="">
                    </span>
                    <span>
                        {{ translate('POS_Items') }}
                    </span>
                </h1>
            </div>
            <!-- End Page Header -->

            <div class="card">
                <div class="card-body">
                    @include('vendor-views/forms/pos_item_add')

                </div>
            </div>
        @endif
        @if (hasPermission('pos_items', 'list'))
            <div class="card mt-3">
                <div class="card-header py-2 border-0">
                    <div class="search--button-wrapper">
                        <h5 class="card-title">{{ translate('messages.POS_items_list') }}<span
                                class="badge badge-soft-dark ml-2" id="itemCount">{{ count($posItems) }}</span></h5>
                        <a href="{{ route('vendor.pos.items', ['action' => 'export']) }}"
                                class="btn btn-outline-primary">{{ translate('messages.export') }}</a>
                        <form action="" class="d-flex">
                            <div class="search_inp">
                                <div class="input-group">
                                    <input type="text" placeholder="Search Item Name" value="{{ request()->search  ?? ''}}" name="search" class=" form-control">
                                    <button type="submit" class="btn btn-white bg-light border outline-0 search-clear-btn">
                                        <i class="tio-search"></i>
                                    </button>
                                </div>
                            </div>
                        </form>

                        <form action="" class="d-flex">
                            <select class="form-control mx-1 js-select2-custom" name="branch"
                                onchange="this.form.submit()">
                                <option {{ request()->branch == 'all' ? 'selected' : '' }} value="all">All Branches
                                </option>
                                @foreach ($branches as $key => $branch)
                                    <option {{ request()->branch == $branch->id ? 'selected' : '' }}
                                        value="{{ $branch->id }}">{{ ucfirst($branch->name) }}</option>
                                @endforeach
                            </select>
                        </form>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive datatable-custom">
                        <table id="columnSearchDatatable"
                            class="table table-borderless table-thead-bordered table-align-middle"
                            data-hs-datatables-options='{
                            "isResponsive": false,
                            "isShowPaging": false,
                            "paging":false,
                        }'>
                            <thead class="thead-light">
                                <tr>
                                    <th class="border-0">{{ translate('sl') }}</th>
                                    {{-- <th class="border-0">Type</th> --}}
                                    <th class="border-0 ">Name</th>
                                    <th class="border-0 ">Added Stock</th>
                                    <th class="border-0 ">Current Stock</th>
                                    <th class="border-0 ">Price</th>
                                    <th class="border-0 ">Branch</th>
                                    <th class="border-0 ">Action</th>
                                </tr>
                            </thead>

                            <tbody id="table-div">
                                @foreach ($posItems as $key => $item)
                                    <tr>
                                        <td>{{ $key + 1 }}</td>
                                        {{-- <td>{{ $item->item_type }}</td> --}}
                                        <td>
                                            {{ ucfirst($item->name) }}
                                        </td>
                                        <td>
                                            {{ $item->qty }}
                                        </td>
                                        <td>
                                            {{ $item->qty_left }}
                                        </td>
                                        <td>
                                            {{ _price($item->price) }}
                                        </td>
                                        <td>
                                            {{ ucfirst($item->branch_name) }}
                                            @if ($item->branch_type == 'main')
                                                <span class=" text-success mb-0">(Main)</span>
                                            @else
                                                <span class=" text-warning mb-0">(Sub)</span>
                                            @endif
                                        </td>

                                        <td>
                                            <div class="btn--container justify-content-start">
                                                @if (hasPermission('pos_items', 'delete'))
                                                    <a class="btn action-btn btn--danger btn-outline-danger form-alert"
                                                        href="javascript:" data-id="category-{{ $key }}"
                                                        data-message="{{ translate('Want to remove this item from pos') }}"
                                                        title="{{ translate('messages.remove item') }}"><i
                                                            class="tio-delete-outlined"></i>
                                                    </a>
                                                    <form
                                                        action="{{ route('vendor.pos.item.remove', [$item->id, $item->branch_id]) }}"
                                                        method="get" id="category-{{ $key }}">
                                                        @csrf @method('get')
                                                    </form>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                @if (count($posItems) !== 0)
                    <hr>
                @endif
                <div class="page-area">
                </div>
                @if (count($posItems) === 0)
                    <div class="empty--data">
                        <img src="{{ asset('/public/assets/admin/svg/illustrations/sorry.svg') }}" alt="public">
                        <h5>
                            {{ translate('no_data_found') }}
                        </h5>
                    </div>
                @endif
            </div>
        @endif
    </div>

@endsection

@push('script_2')
    @include('vendor-views/js/pos_items_js')
@endpush
