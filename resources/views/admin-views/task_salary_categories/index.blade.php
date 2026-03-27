@extends('layouts.admin.app')

@section('title', translate(' Tasks Salary Category'))

@push('css_or_js')
    <meta name="csrf-token" content="{{ csrf_token() }}">
@endpush

@section('content')
    <div class="content container-fluid"> 
        <div class="page-header flex-wrap d-flex w-100 justify-content-between">
            <h1 class="page-header-title"><i class="tio-filter-list"></i> Tasks Salary Category<span
                    class="badge badge-soft-dark ml-2" id="itemCount">{{ count($categories) }}</span></h1>
            @if (hasPermission('task_salary_category', 'add'))
                <a type="button" data-toggle="modal" data-target="#taskCatAddModal" class="btn btn_sm btn--primary">+ Add New
                    Category</a>
                @include('admin-views/form_modals/add_task_category')
            @endif
        </div>

        <!-- Card -->
        <div class="card">
            <!-- End Header --> 

            <!-- Table -->
            @if (hasPermission('task_salary_category', 'list'))

                <div class="table-responsive datatable-custom">
                    <table id="columnSearchDatatable"
                        class="table table-borderless table-thead-bordered table-nowrap table-align-middle card-table"
                        data-hs-datatables-options='{
                            "order": [],
                            "orderCellsTop": true,
                            "paging":false

                        }'>
                        <thead class="thead-light">
                            <tr>
                                <th class="border-0">{{ translate('sl') }}</th>
                                <th class="border-0">Name</th>
                                <th class="border-0">Amount (Per Task)</th>
                                <th class="text-center border-0">{{ translate('messages.action') }}</th>
                            </tr>
                        </thead>

                        <tbody id="set-rows">
                            @foreach ($categories as $key => $cat)
                                <tr>
                                    <td>{{ $key + $categories->firstItem() }}</td>
                                    <td>{{ $cat->name }}</td>
                                    <td>{{ _price($cat->amount) }}</td>
                                    <td>
                                        <div class="btn--container justify-content-center">
                                            @if (hasPermission('task_salary_category', 'edit'))
                                                <a class="btn action-btn btn--primary btn-outline-primary edit_btn"
                                                    data-name="{{ $cat['name'] }}" data-id="{{ $cat['id'] }}"
                                                    data-amount="{{ $cat['amount'] }}" data-toggle="modal"
                                                    data-target="#editAdModal"
                                                    title="{{ translate('messages.edit_category') }}"><i
                                                        class="tio-edit"></i>
                                                </a>
                                            @endif
                                            @if (hasPermission('task_salary_category', 'delete'))
                                                <a class="btn action-btn btn--danger btn-outline-danger form-alert"
                                                    href="javascript:" data-id="category-{{ $cat['id'] }}"
                                                    data-message="{{ translate('Want to delete this category') }}"
                                                    title="{{ translate('messages.delete_ad') }}"><i
                                                        class="tio-delete-outlined"></i>
                                                </a>
                                                <form
                                                    action="{{ route('admin.task-salary-categories.destroy', $cat->id) }}"
                                                    id="category-{{ $cat['id'] }}" method="POST">
                                                    @csrf @method('DELETE')
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    @if (count($categories))
                        <hr>
                        {!! $categories->links() !!}
                    @else
                        <div class="page-area">
                        </div>
                        <div class="empty--data">
                            <img src="{{ asset('/public/assets/admin/svg/illustrations/sorry.svg') }}" alt="public">
                            <h5>
                                {{ translate('no_data_found') }}
                            </h5>
                        </div>
                    @endif
                </div>
            @endif
            @if (hasPermission('task_salary_category', 'edit'))
                <div class="modal fade" id="editAdModal" data-backdrop="static" data-keyboard="false" tabindex="-1"
                    aria-labelledby="staticBackdropLabel" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="staticBackdropLabel">Edit Ad</h5>
                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                            <div class="modal-body">
                                <form id="edit_form" method="POST"
                                    action="{{ route('admin.task-salary-categories.update', 0) }}">
                                    @csrf
                                    @method('PUT')
                                    <input type="hidden" name="edit_id" class="edit_id">
                                    <label for="">Name</label>
                                    <input type="text" required placeholder="Enter Category Name" name="name"
                                        class="form-control ad_name">
                                    <label for="">Amount</label>
                                    <input type="number" step="0.001" required placeholder="Enter Amount" name="amount"
                                        class="form-control ad_amount">
                                    <button class="btn btn--primary mt-2">Update</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
            <!-- End Table -->
        </div>
        <!-- End Card -->
    </div>

@endsection

@push('script_2')
    <script>
        var routeTemplate = "{{ route('admin.task-salary-categories.update', ['__ID__']) }}";

        $(".edit_btn").on('click', function() {
            var id = $(this).attr('data-id')
            var amount = $(this).attr('data-amount')
            var ad_name = $(this).attr('data-name')
            $('.edit_id').val(id);
            $('.ad_amount').val(amount);
            $('.ad_name').val(ad_name);

            var url = routeTemplate.replace('__ID__', id);
            $('#edit_form').attr('action', url);
        })
    </script>
@endpush
