@extends('layouts.vendor.app')

@section('title', translate('messages.Branches'))

@push('css_or_js')
@endpush

@section('content')
    <div class="content container-fluid">
        <!-- Page Header -->
        <div class="page-header d-flex justify-content-between flex-wrap w-100">
            <h1 class="page-header-title">
                <span class="page-header-icon">
                    <img src="{{ asset('public/assets/admin/img/category.png') }}" class="w--20" alt="">
                </span>
                <span>
                    {{ translate('Branches') }}
                </span>
            </h1>
            @if (hasPermission('pos_branch', 'add'))
                <button type="button" class="btn btn--primary" data-toggle="modal" data-target="#addBranchModal">+ Add
                    Branch</button>
                <div class="modal fade" id="addBranchModal" tabindex="-1" aria-labelledby="exampleModalLabel"
                    aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="exampleModalLabel">Add Branch</h5>
                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                            @php
                                [$allowedSub, $errorSub] = canAddBranch(
                                    \App\CentralLogics\Helpers::get_store_id(),
                                    'sub',
                                );
                                [$allowedMain, $errorMain] = canAddBranch(
                                    \App\CentralLogics\Helpers::get_store_id(),
                                    'main',
                                );
                            @endphp
                            @if (!$allowedSub && !$allowedMain)
                                <div class="modal-body">
                                    <p>{{ $errorSub }}</p>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                                </div>
                            @else
                                <form action="{{ route('vendor.pos.branch.store') }}" method="post"
                                    enctype="multipart/form-data">
                                    <div class="modal-body">
                                        @csrf
                                        <label for="">Name</label>
                                        <input type="text" name="name" placeholder="Name" class="form-control">
                                        <label for="">Address</label>
                                        <input type="text" name="address" placeholder="Address" class="form-control">
                                        <label for="">GST Number</label>
                                        <input type="text" name="gst_number" placeholder="GST Number"
                                            class="form-control">
                                        <label for="">GST Document</label>
                                        <input type="file" name="gst_doc" class="form-control">
                                        <div class="pos--payment-options mt-3 mb-3">
                                            <ul>

                                                {{--  --}}
                                                @if ($allowedMain)
                                                    <li>
                                                        <label>
                                                            <input type="radio" name="type" value="Main" hidden
                                                                checked>
                                                            <span>Main</span>
                                                        </label>
                                                    </li>
                                                @else
                                                    {{ $errorSub }}
                                                @endif
                                                @if ($allowedSub)
                                                    <li>
                                                        <label>
                                                            <input {{ !$allowedMain ? 'checked' : '' }} type="radio"
                                                                name="type" value="sub" hidden>
                                                            <span>Sub</span>
                                                        </label>
                                                    </li>
                                                @else
                                                    {{ $errorMain }}
                                                @endif
                                            </ul>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                                        <button type="submit" class="btn btn-primary">Save changes</button>
                                    </div>
                                </form>
                            @endif
                        </div>
                    </div>
                </div>
            @endif
        </div>
        <!-- End Page Header -->
        @if (hasPermission('pos_branch', 'list'))
            <div class="card mt-3">
                <div class="card-header py-2 border-0 ">
                    <div class="search--button-wrapper">
                        <h5 class="card-title">{{ translate('messages.Branches') }}<span class="badge badge-soft-dark ml-2"
                                id="itemCount">{{ $branches->total() }}</span></h5>
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
                                    <th class="border-0">ID</th>
                                    <th class="border-0">Name</th>
                                    <th class="border-0 ">Address</th>
                                    <th class="border-0 ">GST Number</th>
                                    <th class="border-0 ">Type</th>
                                    <th class="border-0 ">Created At</th>
                                    <th class="border-0 ">Action</th>
                                </tr>
                            </thead>

                            <tbody id="table-div">
                                @foreach ($branches as $key => $value)
                                    <tr>
                                        <td>{{ $key + $branches->firstItem() }}</td>
                                        <td>{{ $value->id }}</td>
                                        <td>{{ ucfirst($value->name) }}</td>
                                        <td style="white-space: normal; word-break: break-word; max-width:180px;">
                                            {{ $value->address }}
                                        </td>
                                        <td>
                                            {{ $value->gst_number }}
                                        </td>
                                        <td>
                                            @if ($value->type == 'main')
                                                <span class="badge badge-soft-success">Main</span>
                                            @else
                                                <span class="badge badge-soft-warning">Sub</span>
                                            @endif
                                        </td>
                                        <td>{{ $value->created_at }}</td>
                                        <td>
                                            <div class="btn--container justify-content-start">
                                                @if (hasPermission('pos_branch', 'edit'))
                                                    <a type="button" data-toggle="modal" data-target="#editBranchModal"
                                                        data-id = "{{ $value->id }}"
                                                        data-gst = "{{ $value->gst_number }}"
                                                        data-address ="{{ $value->address }}"
                                                        data-doc ="{{ asset('storage/app/public/store/branch/docs') . '/' . $value->gst_doc }}"
                                                        data-filename ="{{  $value->gst_doc }}"
                                                        data-name = "{{ $value->name }}" data-type="{{ $value->type }}"
                                                        class="btn action-btn btn--primary btn-outline-primary edit_btn"
                                                        title="{{ translate('messages.edit branch') }}"><i
                                                            class="tio-edit"></i>
                                                    </a>
                                                @endif
                                                @if (hasPermission('pos_branch', 'delete'))
                                                    <a class="btn action-btn btn--danger btn-outline-danger form-alert"
                                                        href="javascript:" data-id="category-{{ $value->id }}"
                                                        data-message="{{ translate('Want to delete this branch') }}"
                                                        title="{{ translate('messages.delete branch') }}"><i
                                                            class="tio-delete-outlined"></i>
                                                    </a>
                                                    <form action="{{ route('vendor.pos.branch.delete', [$value->id]) }}"
                                                        method="get" id="category-{{ $value->id }}">
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
                @if (count($branches) !== 0)
                    <hr>
                @endif
                <div class="page-area">
                    {!! $branches->links() !!}
                </div>
                @if (count($branches) === 0)
                    <div class="empty--data">
                        <img src="{{ asset('/public/assets/admin/svg/illustrations/sorry.svg') }}" alt="public">
                        <h5>
                            {{ translate('no_data_found') }}
                        </h5>
                    </div>
                @endif
            </div>
            <div class="modal fade" id="editBranchModal" tabindex="-1" aria-labelledby="exampleModalLabel"
                aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="editBranchModalTitle">Edit Branch</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <form action="{{ route('vendor.pos.branch.update') }}" method="post"
                            enctype="multipart/form-data">
                            <div class="modal-body">
                                @csrf
                                <input type="hidden" name="edit_id" class="edit_id">
                                <label for="">Name</label>
                                <input type="text" name="name" placeholder="Name" 
                                    class="form-control edit_name">
                                <label for="">Address</label>
                                <input type="text" name="address" placeholder="Address"
                                    class="form-control edit_address">
                                <label for="">GST Number</label>
                                <input type="text" name="gst_number" placeholder="GST Number" 
                                    class="form-control edit_gst">
                                <label for="">GST Document <i><a href="" target="_blank" class="edit_doc">View Current</a></i></label>
                                <input type="file" name="gst_doc" class="form-control ">
                                <div class="pos--payment-options mt-3 mb-3">
                                    <ul>
                                        <li>
                                            <label>
                                                <input type="radio" name="type" value="Main" hidden checked
                                                    class="edit_type">
                                                <span>Main</span>
                                            </label>
                                        </li>
                                        <li>
                                            <label>
                                                <input type="radio" name="type" value="sub" hidden
                                                    class="edit_type">
                                                <span>Sub</span>
                                            </label>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                                <button type="submit" class="btn btn-primary">Update</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endif

    </div>


@endsection

@push('script_2')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        $(".edit_btn").on('click', function() {
            $(".edit_name").val($(this).attr('data-name'))
            $(".edit_gst").val($(this).attr('data-gst'))
            $(".edit_address").val($(this).attr('data-address'))
            $(".edit_id").val($(this).attr('data-id'))
            var doc = $(this).attr('data-doc');
            var filename = $(this).attr('data-filename');
            if(filename ){ $(".edit_doc").show(); $(".edit_doc").attr('href', doc);  }
            else{ $(".edit_doc").hide() }
            $(".edit_type[value='" + $(this).attr('data-type') + "']").prop('checked', true);
        })
        $(document).on('change', '.item_type', function() {
            if ($(this).val() === 'inv_item') {
                $('#itemDiv').show();
                $('#serviceDiv').hide();
            } else {
                $('#itemDiv').hide();
                $('#serviceDiv').show();
            }
        });
        $('#branches').off('change').on('change', function() {
            let selectedIds = $(this).val() || []; // Always unique IDs
            let container = $('#branch-prices');

            // Store previous prices
            let existingPrices = {};
            container.find('input').each(function() {
                existingPrices[$(this).data('branch-id')] = $(this).val();
            });

            // Clear container
            container.empty();

            // Append only selected branch price inputs
            selectedIds.forEach(function(branchId) {
                let branchName = $('#branches option[value="' + branchId + '"]').text();
                let priceValue = existingPrices[branchId] || '';

                container.append(`
            <div class="branch-price-item" data-branch-id="${branchId}">
                <label>${branchName} Price:</label>
                <input type="number" 
                       name="prices[${branchId}]" 
                       data-branch-id="${branchId}"
                       placeholder="Enter price" 
                       step="0.001" 
                       value="${priceValue}"
                       class="form-control">
            </div>
        `);
            });
        });
        $(document).ready(function() {
            $('#branches').select2({
                tags: true, // Enable new tag creation
                tokenSeparators: [','], // Allow splitting on commas/spaces
                placeholder: "Select or type branches"
            });
        });


        $(document).ready(function() {

            $('.js-example-tags').select2({
                tags: true,
                tokenSeparators: [','],
                placeholder: "Start Typing..",
                allowClear: true,
            });
        });
    </script>

    <script>
        $(document).on('change', '.warnig-charge-btn', function(e) {

            $('.warnig-charge-btn').prop('checked', false);
            swal({
                //text: message,
                title: 'Please Add Lead Charges First',
                type: 'warning',
                confirmButtonColor: '#FC6A57',
                confirmButtonText: 'Add Now',
            }).then(function(isConfirm) {
                console.log(isConfirm)
                if (isConfirm.value) {
                    window.location.href = '{{ route('admin.service.lead-charge') }}';
                } else {


                }
            })

        })
    </script>

    <script src="{{ asset('public/assets/admin') }}/js/view-pages/category-index.js"></script>
    <script>
        "use strict";
        $('.location-reload-to-category').on('click', function() {
            const url = $(this).data('url');
            let nurl = new URL(url);
            nurl.searchParams.delete('search');
            location.href = nurl;
        });

        $("#customFileEg1").change(function() {
            readURL(this);
            $('#viewer').show(1000)
        });
        $('#reset_btn').click(function() {
            $('#exampleFormControlSelect1').val(null).trigger('change');
            $('#viewer').attr('src', "{{ asset('public/assets/admin/img/upload-img.png') }}");
        })
    </script>
@endpush
