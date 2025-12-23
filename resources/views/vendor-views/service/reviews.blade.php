@extends('layouts.vendor.app')

@section('title', 'Reviews')

@push('css_or_js')
    <meta name="csrf-token" content="{{ csrf_token() }}">
@endpush

@section('content')
    <div class="content container-fluid">
        <!-- Page Header -->
        <div class="page-header">
            <h1 class="page-header-title"><i class="tio-filter-list"></i>Reviews<span class="badge badge-soft-dark ml-2"
                    id="itemCount">{{ count($reviews) }}</span></h1>
            <div class="page-header-select-wrapper">


            </div>
        </div>
        <!-- End Page Header -->

        <!-- Card -->
        <div class="card">
            <!-- Header -->
            <div class="card-header py-2">
                <div class="search--button-wrapper">
                    <h5 class="card-title">List</h5>

                </div>
            </div>
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
                            <th class="border-0">User Info</th>
                            <th class="border-0">Rating</th>
                            <th class="border-0">Review</th>

                            <th class="border-0">Created at</th>
                            <th class="border-0">Action</th>
                        </tr>
                    </thead>

                    <tbody id="set-rows">
                        @foreach ($reviews as $key => $rev)

                            <tr>
                                <td>{{ $key + 1 }}</td>
                                <td>
                                    <div>
                                        <a href="" style="cursor:default;" class="table-rest-info" alt="view store">
                                            <img src="{{ \App\CentralLogics\Helpers::onerror_image_helper($rev->profile_image, asset('storage/app/public/profile/') . '/' . $rev->profile_image, asset('public/assets/admin/img/160x160/img1.jpg'), 'profile/') }}"
                                                class="img-fluid rounded-circle " style="width: 80px; height: 80px;"
                                                alt="">

                                            <div class="info">
                                                <div class="text--title">
                                                    {{ $rev->f_name . ' ' . $rev->l_name }}
                                                </div>
                                            </div>
                                        </a>
                                    </div>
                                </td>

                                <td>
                                    <div class="d-flex mb-3">
                                        @for ($i = 1; $i < 6; $i++)
                                            <i class="tio-star {{ $rev->rating >= $i ? 'text-warning' : '' }}"></i>
                                        @endfor
                                    </div>
                                </td>
                                <td>
                                    <span class="d-block font-size-sm text-body">
                                        {{ $rev->comment }}
                                    </span>
                                    @if ($rev->attachment)
                                        @php $attachments = json_decode($rev->attachment); @endphp
                                        @if (!empty($attachments))
                                            @foreach ($attachments as $img)
                                                <img class="rounded" style="width: 30px;height:30px;"
                                                    src="{{ \App\CentralLogics\Helpers::onerror_image_helper($img, asset('storage/app/public/') . '/' . $img, asset('public/assets/admin/img/160x160/img1.jpg'), '/') }}"
                                                    alt="">
                                            @endforeach
                                        @endif
                                    @endif

                                </td>
                                <td>
                                    {{ _formatted_datetime($rev->created_at) }}
                                </td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-outline-primary" data-toggle="modal"
                                        data-target="#exampleModalw{{ $key }}">
                                        <i class="tio-visible-outlined"></i>
                                    </button>
                                </td>
                            </tr>

                            <div class="modal fade" id="exampleModalw{{ $key }}" tabindex="-1" role="dialog"
                                aria-labelledby="exampleModalLabel" aria-hidden="true">
                                <div class="modal-dialog" role="document">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="exampleModalLabel">Review</h5>
                                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                <span aria-hidden="true">&times;</span>
                                            </button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="d-flex mb-3">
                                                @for ($i = 1; $i < 6; $i++)
                                                    <i class="tio-star {{ $rev->rating >= $i ? 'text-warning' : '' }}"></i>
                                                @endfor
                                            </div>
                                            <span class="d-block font-size-sm text-body">
                                                {{ $rev->comment }}
                                            </span>
                                            @if ($rev->attachment)
                                                @php $attachments = json_decode($rev->attachment); @endphp
                                                @if (!empty($attachments))
                                                    @foreach ($attachments as $img)
                                                        <a target="_blank"
                                                            href="{{ \App\CentralLogics\Helpers::onerror_image_helper($img, asset('storage/app/public/') . '/' . $img, asset('public/assets/admin/img/160x160/img1.jpg'), '/') }}">
                                                            <img class="rounded"
                                                                style="width: 100px;height:100px;    cursor: zoom-in;"
                                                                src="{{ \App\CentralLogics\Helpers::onerror_image_helper($img, asset('storage/app/public/') . '/' . $img, asset('public/assets/admin/img/160x160/img1.jpg'), '/') }}"
                                                                alt=""></a>
                                                    @endforeach
                                                @endif
                                            @endif
                                            @if ($rev->reply)
                                            <br>
                                            <b>Replied :</b><br>
                                            {{$rev->reply}}
                                            @else
                                                <p style="text-align:end; margin-top:5px;">

                                                    <button class="btn btn-primary" type="button" data-toggle="collapse"
                                                        data-target="#collapseExample" aria-expanded="false"
                                                        aria-controls="collapseExample">
                                                        Reply
                                                    </button>
                                                </p>
                                                <div class="collapse" id="collapseExample">
                                                    <div class="">
                                                        <form method="post" action="{{ route('vendor.submit-reply') }}">
                                                            @csrf
                                                            <input type="hidden" value="{{ $rev->rev_id }}"
                                                                name="rev_id">
                                                            <label for="revie" title="">Reply</label>
                                                            <textarea name="reply" class="form-control mb-2" id="revie"placeholder="Start typing..."></textarea>
                                                            <button type="submit" class="btn btn-primary">Submit</button>
                                                        </form>
                                                    </div>
                                                </div>
                                            @endif
                                        </div>

                                    </div>
                                </div>
                            </div>

                        @endforeach
                    </tbody>
                </table>

                <hr>

                <div class="page-area">
                </div>
                @if (!count($reviews))
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
        <!-- End Card -->
    </div>

@endsection

@push('script_2')
    <script>
        function cancelLead(serviceId) {
            Swal.fire({
                title: 'Are you sure?',
                text: 'You want to cancel this lead?',
                type: 'warning',
                showCancelButton: true,
                cancelButtonColor: 'default',
                confirmButtonColor: '#FC6A57',
                cancelButtonText: 'No',
                confirmButtonText: 'Yes',
                reverseButtons: true
            }).then((result) => {
                if (result.value) {
                    $.ajaxSetup({
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        }
                    });
                    $.post({
                        url: '{{ route('vendor.service.cancel') }}',
                        data: {
                            service_id: serviceId
                        },
                        beforeSend: function() {
                            $('#loading').show();
                        },
                        success: function(data) {
                            if (data.status) {
                                toastr.success(data.message);
                            } else {
                                toastr.error(data.message);
                            }
                            setTimeout(() => {

                                window.location.reload()
                            }, 1000)
                        },
                        complete: function() {
                            $('#loading').hide();
                        },
                    });
                }
            })
        }

        function status_change_alert(url, message, e) {
            e.preventDefault();
            Swal.fire({
                title: 'Are you sure?',
                text: message,
                type: 'warning',
                showCancelButton: true,
                cancelButtonColor: 'default',
                confirmButtonColor: '#FC6A57',
                cancelButtonText: 'No',
                confirmButtonText: 'Yes',
                reverseButtons: true
            }).then((result) => {
                if (result.value) {
                    location.href = url;
                }
            })
        }
        $(document).on('ready', function() {
            // INITIALIZATION OF DATATABLES
            // =======================================================
            var datatable = $.HSCore.components.HSDatatables.init($('#columnSearchDatatable'));

            $('#column1_search').on('keyup', function() {
                datatable
                    .columns(1)
                    .search(this.value)
                    .draw();
            });

            $('#column2_search').on('keyup', function() {
                datatable
                    .columns(2)
                    .search(this.value)
                    .draw();
            });

            $('#column3_search').on('keyup', function() {
                datatable
                    .columns(3)
                    .search(this.value)
                    .draw();
            });

            $('#column4_search').on('keyup', function() {
                datatable
                    .columns(4)
                    .search(this.value)
                    .draw();
            });


            // INITIALIZATION OF SELECT2
            // =======================================================
            $('.js-select2-custom').each(function() {
                var select2 = $.HSCore.components.HSSelect2.init($(this));
            });
        });
    </script>

    <script>
        $('#search-form').on('submit', function() {
            var formData = new FormData(this);
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });
            $.post({
                url: '{{ route('admin.store.search') }}',
                data: formData,
                cache: false,
                contentType: false,
                processData: false,
                beforeSend: function() {
                    $('#loading').show();
                },
                success: function(data) {
                    $('#set-rows').html(data.view);
                    $('#itemCount').html(data.total);
                    $('.page-area').hide();
                },
                complete: function() {
                    $('#loading').hide();
                },
            });
        });
    </script>
@endpush
