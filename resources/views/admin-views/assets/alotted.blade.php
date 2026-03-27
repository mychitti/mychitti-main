    @extends('layouts.admin.app')

    @section('title', translate('Company Assets (Properties)'))

    @push('css_or_js')
        <meta name="csrf-token" content="{{ csrf_token() }}">
    @endpush

    @section('content')
        <div class="content container-fluid">
            <div class="page-header d-flex w-100 justify-content-between">
                <h1 class="page-header-title"><i class="tio-filter-list"></i> Company Assets (Properties)<span
                        class="badge badge-soft-dark ml-2" id="itemCount">{{ count($assets) }}</span></h1>

            </div>

         @if (hasPermission('assets_company_assets', 'list'))
            <!-- Card -->
            <div class="card">
                <!-- End Header -->
                <!-- Table -->
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
                                <th class="border-0">brand</th>
                                <th class="border-0">Model Number</th>
                                <th class="border-0">Alotted Qty</th>
                                <th class="border-0">Status</th>
                                <th class="text-center border-0">{{ translate('messages.action') }}</th>
                            </tr>
                        </thead>

                        <tbody id="set-rows">
                            @foreach ($assets as $key => $asset)
                                <tr>
                                    <td>{{ $key + $assets->firstItem() }}</td>
                                    <td>
                                        <img class="avatar avatar-lg mr-3 onerror-image"
                                            src="{{ \App\CentralLogics\Helpers::onerror_image_helper($asset->inventoryItem?->image, asset('storage/app/public/inventory-item/') . '/' . $asset->inventoryItem?->image, asset('public/assets/admin/img/160x160/img2.jpg'), 'inventory-item/') }}"
                                            data-onerror-image="{{ asset('public/assets/admin/img/160x160/img2.jpg') }}"
                                            alt="{{ $asset->inventoryItem?->name }} image">
                                        {{ $asset->inventoryItem?->item_name }}
                                    </td>
                                    <td>{{ $asset->inventoryItem?->brand }}</td>
                                    <td>{{ $asset->inventoryItem?->model_number }}</td>
                                    <td><span class="badge badge-soft-warning">{{ $asset->alotted_qty }}</span></td>
                                    <td>
                                    @if($asset->returned)
                                    <span class="badge badge-soft-danger">Returned</span>
                                    @else
                                    <span class="badge badge-soft-success">Alotted</span>
                                    @endif
                                    </td>
                                    @if (!$asset->returned)
                                        <td>
                                            <div class="btn--container justify-content-center">
                                                <a style="width:fit-content; padding:3px 10px !important;"
                                                    class="btn action-btn btn--danger btn-outline-danger"
                                                    data-toggle="modal"
                                                    data-target="#returnItemModal{{ $asset->id }}">Return
                                                </a>
                                            </div>
                                        </td>
                                    @else
                                        <td></td>
                                    @endif
                                </tr>
                                @if (!$asset->returned)
                                    <div class="modal fade" id="returnItemModal{{ $asset->id }}" tabindex="-1"
                                        aria-labelledby="exampleModalLabel" aria-hidden="true">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title" id="exampleModalLabel">Return Item</h5>
                                                    <button type="button" class="close" data-dismiss="modal"
                                                        aria-label="Close">
                                                        <span aria-hidden="true">&times;</span>
                                                    </button>
                                                </div>
                                                <div class="modal-body">
                                                    <form action="{{ route('admin.asset.return') }}" method="post"
                                                        enctype="multipart/form-data">
                                                        @csrf
                                                        <input type="hidden" name="al_id" value="{{ $asset->id }}">
                                                        <label for="">Condition</label>
                                                        <textarea name="condition" id="" placeholder="Start typing..." class="form-control"></textarea>
                                                        <label for="">File Upload (Optional)</label>
                                                        <input type="file" name="file" class="form-control">
                                                        <button class="btn btn--primary mt-2">Proceed</button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            @endforeach
                        </tbody>
                    </table>
                    @if (count($assets))
                        <hr>
                        {!! $assets->links() !!}
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
                <!-- End Table -->
            </div>
            @endif
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
