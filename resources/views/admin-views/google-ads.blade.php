@extends('layouts.admin.app')

@section('title', \App\Models\BusinessSetting::where(['key' => 'business_name'])->first()->value ??
    translate('messages.dashboard'))

    @push('css_or_js')
        <meta name="csrf-token" content="{{ csrf_token() }}">
    @endpush

@section('content')
    <div class="content container-fluid">
        {{-- @if (auth('admin')->user()->role_id == 1) --}}
        <!-- Page Header -->
        <div class="page-header">
            <div class="row align-items-center py-2">
                <div class="col-sm mb-2 mb-sm-0">
                    <div class="d-flex align-items-center justify-content-between">
                        <div class='d-flex'>
                            <i class="tio-launch-outlined" style="font-size:26px;"></i>
                            <div class="">
                                <h1 class="page-header-title mb-0">Google Ads</h1>
                            </div>
                        </div>
                        <button type="button" data-toggle="modal" data-target="#adAddModal" class="btn btn--primary mt-2">+
                            Add New Google Ad</button>
                    </div>
                </div>
                <div class="modal fade" id="adAddModal" tabindex="-1" aria-labelledby="exampleModalLabel"
                    aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="exampleModalLabel">Add New Google Ad</h5>
                                <button type="button" class="close" data-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <form action="{{ route('admin.common-dashboard.google-ads.save') }}">
                                    @csrf
                                    <label for="ad_name">Name</label>
                                    <input type="text" id="ad_name" required placeholder="Enter Ad Name" name="name"
                                        class="form-control">
                                    <label for="ad_id">Ad ID</label>
                                    <input type="text" id="ad_id" required placeholder="Ex: AW-11382138873" name="ad_id"
                                        class="form-control">
                                    <button class="btn btn--primary">Save</button>
                                </form>
                            </div>

                        </div>
                    </div>
                </div>

            </div>
        </div>
        <!-- End Page Header -->

        <!-- Stats -->
        <div class="card mb-3">
            <div class="card-body p-0">
                <div class="table-responsive datatable-custom">
                    <table id="columnSearchDatatable" class="table table-borderless table-thead-bordered table-align-middle"
                        data-hs-datatables-options='{
                            "isResponsive": false,
                            "isShowPaging": false,
                            "paging":false,
                        }'>
                        <thead class="thead-light">
                            <tr>
                                <th class="border-0">{{ translate('messages.SL') }}</th>
                                <th class="border-0">{{ translate('messages.id') }}</th>
                                <th class="border-0">{{ translate('messages.name') }}</th>
                                <th class="border-0">{{ translate('messages.Ad Id') }}</th>
                                <th class="border-0 text-center">{{ translate('messages.action') }}</th>
                            </tr>
                        </thead>

                        <tbody id="table-div">
                            @foreach ($ads as $key => $ad)
                                <tr>
                                    <td>{{ $key + $ads->firstItem() }}</td>
                                    <td>{{ $ad->id }}</td>
                                    <td>
                                        <span class="d-block font-size-sm text-body">
                                            {{ Str::limit($ad['name'], 20, '...') }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="d-block font-size-sm text-body">
                                            {{ $ad->ad_id }}
                                        </span>
                                    </td>

                                    <td>
                                        <div class="btn--container justify-content-center">
                                            <a class="btn action-btn btn--primary btn-outline-primary edit_btn" data-name="{{$ad['name']}}" data-adid="{{$ad['ad_id']}}" data-id="{{$ad['id']}}"
                                                data-toggle="modal" data-target="#editAdModal"
                                                title="{{ translate('messages.edit_ad') }}"><i class="tio-edit"></i>
                                            </a>
                                            <a class="btn action-btn btn--danger btn-outline-danger form-alert"
                                                href="javascript:" data-id="category-{{ $ad['id'] }}"
                                                data-message="{{ translate('Want to delete this ad') }}"
                                                title="{{ translate('messages.delete_ad') }}"><i
                                                    class="tio-delete-outlined"></i>
                                            </a>
                                            <form action="{{ route('admin.common-dashboard.google-ads.delete', [$ad['id']]) }}"
                                                method="get" id="category-{{ $ad['id'] }}">
                                                @csrf @method('get')
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
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
                            <form action="{{ route('admin.common-dashboard.google-ads.update') }}">
                                @csrf
                                <input type="hidden" name="id" id="ad_id_hid">
                                <label for="ad_name2">Name</label>
                                <input type="text" id="ad_name2" required placeholder="Enter Ad Name" name="name"
                                    class="form-control ad_name">
                                <label for="ad_id2">Ad ID</label>
                                <input type="text" id="ad_id2" required placeholder="Ex: AW-11382138873" name="ad_id"
                                    class="form-control ad_id">
                                <button class="btn btn--primary mt-2">Update</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            @if (count($ads) !== 0)
                <hr>
            @endif
            <div class="page-area">
                {!! $ads->links() !!}
            </div>
            @if (count($ads) === 0)
                <div class="empty--data">
                    <img src="{{ asset('/public/assets/admin/svg/illustrations/sorry.svg') }}" alt="public">
                    <h5>
                        {{ translate('no_data_found') }}
                    </h5>
                </div>
            @endif
        </div>
        <!-- End Stats -->
    </div>
@endsection

@push('script')

@endpush


@push('script_2')
<script>
$(".edit_btn").on('click', function(){
    var ad_id = $(this).attr('data-adid')
    var id = $(this).attr('data-id')
    var ad_name = $(this).attr('data-name')
    console.log(ad_id)
    $("#ad_id_hid" ).val(id)
    $(".ad_id" ).val(ad_id)
    $(".ad_name").val(ad_name)
})
</script>
@endpush
