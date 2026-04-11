@extends('layouts.admin.app')

@section('title', translate('Blog List'))

@push('css_or_js')
    <meta name="csrf-token" content="{{ csrf_token() }}">
@endpush

@section('content')
    <div class="content container-fluid">
        <!-- Page Header -->
        <div class="page-header">
            <div class="row align-items-center g-2">
                <div class="col-md-9 col-12">
                    <h1 class="page-header-title">
                        <span class="page-header-icon">
                            <img src="{{ asset('public/assets/admin/img/items.png') }}" class="w--22" alt="">
                        </span>
                        <span>
                            {{ ($type ?? 'common') === 'mc_vendor' ? 'mcvendorhub.com' : 'mychitti.net' }} — Blog Posts
                            <span class="badge badge-soft-dark ml-2" id="foodCount">{{ $blogs->total() }}</span>
                        </span>
                    </h1>
                </div>
            </div>

        </div>
        <!-- End Page Header -->
        <div class="card">
            <!-- Header -->
            <div class="card-header py-2 border-0">
                <div class="search--button-wrapper justify-content-end">

                    <div>
                        <a href="{{ route('admin.blog.add-new') }}?type={{ $type ?? 'common' }}" class="btn btn--primary font-regular">Add Blog Post</a>
                    </div>


                </div>
                <!-- End Row -->
            </div>
            <!-- End Header -->

            <!-- Table -->
            <div class="table-responsive datatable-custom" id="table-div">
                <table id="datatable"
                    class="table table-borderless table-thead-bordered table-nowrap table-align-middle card-table"
                    data-hs-datatables-options='{
                        "columnDefs": [{
                            "targets": [],
                            "width": "5%",
                            "orderable": false
                        }],
                        "order": [],
                        "info": {
                        "totalQty": "#datatableWithPaginationInfoTotalQty"
                        },

                        "entries": "#datatableEntries",

                        "isResponsive": false,
                        "isShowPaging": false,
                        "paging":false
                    }'>
                    <thead class="thead-light">
                        <tr>
                            <th class="border-0">sl</th>
                            <th class="border-0">Image</th>
                            <th class="border-0">Title</th>
                            <th class="border-0">Type</th>
                            <th class="border-0">Category</th>
                            <th class="border-0 text-center">{{ translate('messages.action') }}</th>
                        </tr>
                    </thead>

                    <tbody id="set-rows">
                        @foreach ($blogs as $key => $item)
                            <tr>
                                <td>{{ $key + $blogs->firstItem() }}</td>
                                <td>
                                    <img class="avatar avatar-lg mr-3 onerror-image"
                                        src="{{ \App\CentralLogics\Helpers::onerror_image_helper(
                                            $item->image ?? '',
                                            asset('storage/app/public/blog') . '/' . $item->image ?? '',
                                            asset('public/assets/admin/img/160x160/img2.jpg'),
                                            'blog/',
                                        ) }}"
                                        data-onerror-image="{{ asset('public/assets/admin/img/160x160/img2.jpg') }}"
                                        alt="{{ $item->title }} image">


                                </td>
                                <td>
                                    <div title="{{ $item->title }}" class="media-body">
                                        <h5 class="text-hover-primary mb-0">{{ Str::limit($item->title, 20, '...') }}</h5>
                                    </div>
                                </td>
                                <td>
                                    @if ($item->type == 'mc_vendor')
                                        <span class="badge badge-soft-info">{{ translate('messages.mc_vendor') }}</span>
                                    @else
                                        <span class="badge badge-soft-success">{{ translate('messages.common') }}</span>
                                    @endif
                                </td> 
                                <td>
                                    {{ ucfirst($item->cat_name) }}
                                </td>


                                <td>
                                    <div class="btn--container justify-content-center">
                                        <a class="btn action-btn btn--primary btn-outline-primary"
                                            href="{{ route('admin.blog.edit', [$item->id]) }}"
                                            title="{{ translate('messages.edit_post') }}"><i class="tio-edit"></i>
                                        </a>
                                        <a class="btn  action-btn btn--danger btn-outline-danger form-alert"
                                            href="javascript:" data-id="food-{{ $item->id }}"
                                            data-message="{{ translate('messages.Want_to_delete_this_post') }}"
                                            title="{{ translate('messages.delete_post') }}"><i
                                                class="tio-delete-outlined"></i>
                                        </a>
                                        <form action="{{ route('admin.blog.delete', [$item->id]) }}" method="post"
                                            id="food-{{ $item->id }}">
                                            @csrf @method('delete')
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if (count($blogs) !== 0)
                <hr>
            @endif
            <div class="page-area">
                <tfoot class="border-top">
                    {!! $blogs->withQueryString()->links() !!}
            </div>
            @if (count($blogs) === 0)
                <div class="empty--data">
                    <img src="{{ asset('/public/assets/admin/svg/illustrations/sorry.svg') }}" alt="public">
                    <h5>
                        {{ translate('no_data_found') }}
                    </h5>
                </div>
            @endif
            <!-- End Table -->
        </div>
        <!-- End Card -->
    </div>

@endsection

@push('script_2')
    <script>
        "use strict";
        $(document).on('ready', function() {
            // INITIALIZATION OF DATATABLES
            // =======================================================
            let datatable = $.HSCore.components.HSDatatables.init($('#datatable'), {
                select: {
                    style: 'multi',
                    classMap: {
                        checkAll: '#datatableCheckAll',
                        counter: '#datatableCounter',
                        counterInfo: '#datatableCounterInfo'
                    }
                },
                language: {
                    zeroRecords: '<div class="text-center p-4">' +
                        '<img class="w-7rem mb-3" src="{{ asset('public/assets/admin/svg/illustrations/sorry.svg') }}" alt="Image Description">' +

                        '</div>'
                }
            });

            $('#datatableSearch').on('mouseup', function(e) {
                let $input = $(this),
                    oldValue = $input.val();

                if (oldValue == "") return;

                setTimeout(function() {
                    let newValue = $input.val();

                    if (newValue == "") {
                        // Gotcha
                        datatable.search('').draw();
                    }
                }, 1);
            });

            $('#toggleColumn_index').change(function(e) {
                datatable.columns(0).visible(e.target.checked)
            })
            $('#toggleColumn_name').change(function(e) {
                datatable.columns(1).visible(e.target.checked)
            })

            $('#toggleColumn_type').change(function(e) {
                datatable.columns(2).visible(e.target.checked)
            })

            $('#toggleColumn_vendor').change(function(e) {
                datatable.columns(3).visible(e.target.checked)
            })

            $('#toggleColumn_status').change(function(e) {
                datatable.columns(5).visible(e.target.checked)
            })
            $('#toggleColumn_price').change(function(e) {
                datatable.columns(4).visible(e.target.checked)
            })
            $('#toggleColumn_action').change(function(e) {
                datatable.columns(6).visible(e.target.checked)
            })

            // INITIALIZATION OF SELECT2
            // =======================================================
            $('.js-select2-custom').each(function() {
                let select2 = $.HSCore.components.HSSelect2.init($(this));
            });
        });

        $('#store').select2({
            ajax: {
                url: '{{ url('/') }}/store/get-stores',
                data: function(params) {
                    return {
                        q: params.term, // search term
                        all: true,
                        module_id: {{ Config::get('module.current_module_id') }},
                        page: params.page
                    };
                },
                processResults: function(data) {
                    return {
                        results: data
                    };
                },
                __port: function(params, success, failure) {
                    let $request = $.ajax(params);

                    $request.then(success);
                    $request.fail(failure);

                    return $request;
                }
            }
        });

        $('#category_id').select2({
            ajax: {
                url: '{{ route('admin.category.get-all') }}',
                data: function(params) {
                    return {
                        q: params.term, // search term
                        all: true,
                        module_id: {{ Config::get('module.current_module_id') }},
                        page: params.page
                    };
                },
                processResults: function(data) {
                    return {
                        results: data
                    };
                },
                __port: function(params, success, failure) {
                    let $request = $.ajax(params);

                    $request.then(success);
                    $request.fail(failure);

                    return $request;
                }
            }
        });


        $('#condition_id').select2({
            ajax: {
                url: '{{ url('/') }}common-condition/get-all',
                data: function(params) {
                    return {
                        q: params.term, // search term
                        page: params.page,
                        all: true,
                    };
                },
                processResults: function(data) {
                    return {
                        results: data
                    };
                },
                __port: function(params, success, failure) {
                    let $request = $.ajax(params);

                    $request.then(success);
                    $request.fail(failure);

                    return $request;
                }
            }
        });
    </script>
@endpush
