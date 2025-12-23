@extends('layouts.vendor.app')

@section('title', 'Select Services')

@push('css_or_js')
    <meta name="csrf-token" content="{{ csrf_token() }}">
@endpush

@section('content')
    <div class="content container-fluid">
        <!-- Page Header -->
        <div class="page-header">
            <h1 class="page-header-title"><i class="tio-filter-list"></i> Services  <span class="badge badge-soft-dark ml-2"
                    id="itemCount">{{ count($product) }}</span></h1>
          
        </div>
        <!-- End Page Header -->


       
        <!-- Card -->
        <div class="card">
            <!-- Header -->
            <div class="card-header py-2">
                <div class="search--button-wrapper">
                    <h5 class="card-title">Service List</h5>
                    <form action="" id="search-form" class="search-form">
                        <!-- Search -->
                        <div class="input-group input--group">
                        <a href="{{route('vendor.item.service_select')}}" type="submit" class="btn btn--secondary" style="    border-radius: 4px !important;
    margin-right: 6px !important;display: flex;
    align-items: center;
    justify-content: center;"><i class="tio-refresh"></i></a>
                            <input id="datatableSearch_" type="search" name="search" value="{{$search_term}}" class="form-control"
                                placeholder="Search Service" aria-label="{{ translate('messages.search') }}" required>
                            <button type="submit" class="btn btn--secondary"><i class="tio-search"></i></button>

                        </div>
                        <!-- End Search -->
                    </form>

                    <!-- End Unfold -->
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
                        <th class="border-0">Service</th>
                        <th class="border-0">Category</th>
                        <th class="border-0">Description</th>
                        <th class="text-uppercase border-0">Available</th>
                    </tr>
                </thead>

                <tbody id="set-rows">
                    @foreach ($product as $lead)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>
                                <!--href="{{route('vendor.item.view',[$lead->id])}}"-->
                                <a class="media align-items-center" href="javascript:;" style="cursor:default;">
                                    <img class="avatar avatar-lg mr-3 onerror-image" src="{{\App\CentralLogics\Helpers::onerror_image_helper($lead->image, asset('storage/app/public/product/').'/'.$lead->image, asset('public/assets/admin/img/160x160/img2.jpg'), 'product/') }}"
                                         data-onerror-image="{{asset('public/assets/admin/img/160x160/img2.jpg')}}" alt="{{$lead->name}} image">
                                    <div class="media-body">
                                        <h5 class="text-hover-primary mb-0">{{Str::limit($lead->name,20,'...')}}</h5>
                                    </div>
                                </a>
                            </td>
                          
                            <td>
                                <span class="d-block font-size-sm text-body">
                                    
                                       @php
                                        $depNm = _getWhere('categories', ['id' => $lead->category_id]);
                                        if (isset($depNm[0])) {
                                            echo $depNm[0]->name;
                                    } @endphp
                                    
                                </span>

                            </td>
                            <td>
                                <div>
                                  {{Str::limit($lead->description,50,'...')}}
                                </div>
                            </td>
                    
                           

                            <td>
                                @php $stores = _getWhere('items', ['id'=> $lead->id])[0]->store_ids;
                                $storeArr = explode(',', $stores); @endphp
                                <label class="toggle-switch toggle-switch-sm" for="stausCheckbox{{$lead->id}}">
                                    <input type="checkbox"
                                           class="toggle-switch-input serviceCheckBox"
                                           data-id = "{{$lead->id}}"
                                           id="stausCheckbox{{$lead->id}}" {{(in_array( \App\CentralLogics\Helpers::get_store_id() ,$storeArr)) ? 'checked' : ''}}>
                                    <span class="toggle-switch-label mx-auto">
                                            <span class="toggle-switch-indicator"></span>
                                        </span>
                                </label>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
                @if (count($product))
                    <hr>
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
                 @if (count($product))
                <button id="serviceSaveBtn" style="width: fit-content;" class="btn btn--primary">Save Changes</button>
                    <hr>
                @endif
            <!-- End Table -->
        </div>
        <!-- End Card -->
    </div>

@endsection

@push('script_2')

<script>
    $(document).on('click','#serviceSaveBtn', function (){
        var selectedServicesChecks = $(".serviceCheckBox:checked");
        var selectedIds = [];
        selectedServicesChecks.each(function() {
            selectedIds.push($(this).data('id'));
        });
         $.ajaxSetup({
             headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });
            $.post({
                url: '{{route('vendor.item.service_save')}}',
                data: {
                    services: selectedIds
                },
                beforeSend: function () {
                    $('#loading').show()
                },
                success: function (data) {
                  if(data){
                       toastr.success('Changes saved successfully');
                  }
                },
                complete: function () {
                    $('#loading').hide()
                }
            });
    } )
</script>
    <script>
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
@endpush
