@extends('layouts.admin.app')

@section('title', 'Error Logs')

@push('css_or_js')
@endpush

@section('content')
    <div class="content container-fluid">
        @include('admin-views/partials/_action-nav')


        <!-- Page Header -->
        <div class="page-header d-flex flex-wrap align-items-center justify-content-between">
            <h1 class="page-header-title">
                <span class="page-header-icon">
                    <img src="{{ asset('public/assets/admin/img/module.png') }}" alt="">
                </span>
                <span>
                    {{ translate('messages.App Error Logs') }}
                </span>
            </h1>
        </div>
        <!-- End Page Header -->

        <div class="card">
            <div class="card-header border-0 py-2">
                <div class="d-flex justify-content-between w-100 align-items-center g-2">
                    <div>
                        <button class="btn btn-danger btn-sm d-none" id="bulk-delete-btn">
                            <i class="tio-delete"></i> Delete Selected (<span id="selected-count">0</span>)
                        </button>
                    </div>
                    <div class="">
                        <label class="form-label mb-1">Status</label>
                        <select class="form-control" id="status-filter">
                            <option value="all" {{ $status == 'all' ? 'selected' : '' }}>All</option>
                            <option value="open" {{ $status == 'open' ? 'selected' : '' }}>Open</option>
                            <option value="resolved" {{ $status == 'resolved' ? 'selected' : '' }}>Resolved</option>
                            <option value="reopen" {{ $status == 'reopen' ? 'selected' : '' }}>Reopened</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive datatable-custom">
                    <table id="columnSearchDatatable" class="table table-borderless table-thead-bordered table-align-middle"
                        data-hs-datatables-options='{
                            "isResponsive": false,
                            "isShowPaging": false, 
                            "paging":false,
                        }'>
                        <thead class="thead-light border-0">
                            <tr>
                                <th class="border-0">
                                    <div class="custom-control custom-checkbox">
                                        <input type="checkbox" class="custom-control-input" id="select-all">
                                        <label class="custom-control-label" for="select-all"></label>
                                    </div>
                                </th>
                                <th class="border-0">{{ translate('messages.sl') }}</th>
                                <th class="border-0">{{ translate('messages.message') }}</th>
                                <th class="border-0">{{ translate('messages.stack') }}</th>
                                <th class="border-0">User</th>
                                <th class="border-0">Phone</th>
                                <th class="border-0">{{ translate('messages.api_url') }}</th>
                                <th class="border-0">{{ translate('messages.device') }}</th>
                                <th class="border-0">{{ translate('messages.app_version') }}</th>
                                <th class="border-0">{{ translate('messages.platform') }}</th>
                                <th class="border-0">{{ translate('messages.status') }}</th>
                                <th class="border-0">{{ translate('messages.create_at') }}</th>
                            </tr>
                        </thead>

                        <tbody id="table-div">
                            @foreach ($app_errors as $key => $value)
                                <tr>
                                    <td>
                                        <div class="custom-control custom-checkbox">
                                            <input type="checkbox" class="custom-control-input row-checkbox" id="row-{{ $value->id }}" value="{{ $value->id }}">
                                            <label class="custom-control-label" for="row-{{ $value->id }}"></label>
                                        </div>
                                    </td>
                                    <td class="pl-4">{{ $key + $app_errors->firstItem() }}</td>
                                    <td>
                                        <span class="d-block font-size-sm text-body text-capitalize">
                                            {{ Str::limit(translate($value['message']), 100, '...') }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="d-block font-size-sm text-body" style="max-width:300px;">
                                            {{ Str::limit($value['stack'], 80, '...') }}
                                            @if(strlen($value['stack']) > 80)
                                                <a href="javascript:void(0)" class="text-primary toggle-stack" data-target="stack-{{ $key }}">Details</a>
                                                <pre class="d-none mt-2 p-2 bg-light" id="stack-{{ $key }}" style="white-space:pre-wrap; word-break:break-word; max-height:300px; overflow-y:auto; font-size:12px;">{{ $value['stack'] }}</pre>
                                            @endif
                                        </span>
                                    </td>
                                    <td>
                                        @if($value->f_name)
                                            {{ $value->f_name . ' ' . $value->l_name }}
                                        @elseif($value->user_id)
                                            <span class="text-muted">Deleted User #{{ $value->user_id }}</span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>{{ $value->user_phone ?? '-' }}</td>
                                    <td>
                                        {{ $value['api_url'] }}
                                    </td>
                                    <td>
                                        {{ $value['device'] }}
                                    </td>
                                    <td>
                                        {{ $value['app_version'] }}
                                    </td>
                                    <td>
                                        {{ $value['platform'] }}
                                    </td>

                                    <td>
                                        <select class="form-control form-control-sm change-error-status" data-id="{{ $value->id }}" style="min-width:100px;">
                                            <option value="open" {{ ($value->status ?? 'open') == 'open' ? 'selected' : '' }}>Open</option>
                                            <option value="resolved" {{ ($value->status ?? 'open') == 'resolved' ? 'selected' : '' }}>Resolved</option>
                                            <option value="reopen" {{ ($value->status ?? 'open') == 'reopen' ? 'selected' : '' }}>Reopen</option>
                                        </select>
                                    </td>
                                    <td>{{ $value->created_at }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer page-area pt-0 border-0">
                <!-- Pagination -->
                <div class="d-flex justify-content-center justify-content-sm-end">
                    <!-- Pagination -->
                    {!! $app_errors->links() !!}
                </div>
                <!-- End Pagination -->
                @if (count($app_errors) === 0)
                    <div class="empty--data">
                        <img src="{{ asset('/public/assets/admin/svg/illustrations/sorry.svg') }}" alt="public">
                        <h5>
                            {{ translate('no_data_found') }}
                        </h5>
                    </div>
                @endif
            </div>
        </div> 
    </div>
@endsection

@push('script_2')
    <script>
        $(document).on('click', '.toggle-stack', function() {
            var target = $('#' + $(this).data('target'));
            target.toggleClass('d-none');
            $(this).text(target.hasClass('d-none') ? 'Details' : 'Hide');
        });

        $('#status-filter').on('change', function() {
            var url = "{{ route('admin.logs.action-logs.errors') }}";
            window.location.href = url + '?status=' + $(this).val();
        });

        // Select all / row checkboxes
        $('#select-all').on('change', function() {
            $('.row-checkbox').prop('checked', this.checked);
            updateBulkDeleteBtn();
        });

        $(document).on('change', '.row-checkbox', function() {
            var total = $('.row-checkbox').length;
            var checked = $('.row-checkbox:checked').length;
            $('#select-all').prop('checked', total === checked);
            updateBulkDeleteBtn();
        });

        function updateBulkDeleteBtn() {
            var count = $('.row-checkbox:checked').length;
            $('#selected-count').text(count);
            if (count > 0) {
                $('#bulk-delete-btn').removeClass('d-none');
            } else {
                $('#bulk-delete-btn').addClass('d-none');
            }
        }

        $('#bulk-delete-btn').on('click', function() {
            console.log('fsddd333')
            var ids = [];
            $('.row-checkbox:checked').each(function() {
                ids.push($(this).val());
            });
            if (ids.length === 0) return;

            Swal.fire({
                title: 'Are you sure?',
                text: 'Delete ' + ids.length + ' selected error(s)?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                confirmButtonText: 'Yes, delete!',
            }).then((result) => {
                if (result.value) {
                    $.ajax({
                        url: "{{ route('admin.logs.error-logs.bulk-delete') }}",
                        type: 'POST',
                        data: { ids: ids, _token: '{{ csrf_token() }}' },
                        success: function(res) {
                            toastr.success(res.message);
                            location.reload();
                        },
                        error: function() {
                            toastr.error('Failed to delete');
                        }
                    });
                }else{
                }
            });
        });

        $(document).on('change', '.change-error-status', function() {
            var id = $(this).data('id');
            var status = $(this).val();
            $.ajax({
                url: "{{ url('admin/logs/error-logs') }}/" + id + "/status",
                type: 'POST',
                data: { status: status, _token: '{{ csrf_token() }}' },
                success: function() {
                    toastr.success('Status updated');
                },
                error: function() {
                    toastr.error('Failed to update status');
                }
            });
        });
    </script>
@endpush
