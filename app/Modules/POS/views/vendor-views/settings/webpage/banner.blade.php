 <div class="row">
     <div class="row gx-2 gx-lg-3 col-md-6 ">
         <div class="col-sm-12 col-lg-12 mb-3 mb-lg-2">
             <form action="{{ route('vendor.banner.store') }}" method="POST" enctype="multipart/form-data">
                 @csrf
                 <div class="card mb-3">
                     <div class="card-body">
                         <div class="row g-3">
                             <div class="col-12 d-flex justify-content-end">

                                 <div class="blinkings">
                                     <strong class="mr-2">{{ translate('instructions') }}</strong>
                                     <div>
                                         <i class="tio-info-outined"></i>
                                     </div>
                                     <div class="business-notes">
                                         <h6><img src="{{ asset('/public/assets/admin/img/notes.png') }}"
                                                 alt="">
                                             {{ translate('Note') }}</h6>
                                         <div>
                                             {{ translate('messages.Customer_will_see_there_banners_in_your_store_details_page_in_website_and_user_apps.') }}
                                         </div>
                                     </div>
                                 </div>
                             </div>
                             <div class="col-sm-12">
                                 <div class="form-group">

                                     <label for="title" class="form-label">{{ translate('Banner_title') }}</label>
                                     <input id="title" type="text" name="title" class="form-control"
                                         placeholder="{{ translate('messages.title_here...') }}" required>
                                 </div>
                                 <div class="form-group">

                                     <label for="default_link"
                                         class="form-label">{{ translate('Redirection_URL_/_Link') }}</label>
                                     <input id="default_link" type="url" name="default_link" class="form-control"
                                         placeholder="{{ translate('messages.Enter_URL') }}">
                                 </div>
                             </div>
                             <div class="col-sm-12">
                                 <h3 class="form-label d-block mb-2">
                                     {{ translate('Upload_Banner') }}
                                 </h3>
                                 <label class="upload-img-3 m-0 d-block">
                                     <div class="img">
                                         <img src="{{ asset('/public/assets/admin/img/upload-4.png') }}" id="viewer"
                                             class="vertical-img mw-100 vertical" alt="">
                                     </div>
                                     <input type="file" name="image" hidden>
                                 </label>
                                 <h3 class="form-label d-block mt-2">
                                     {{ translate('Banner_Image_Ratio_3:1') }}
                                 </h3>
                                 <p>{{ translate('image_format_:_jpg_,_png_,_jpeg_|_maximum_size:_2_MB') }}</p>
                             </div>
                             <div class="col-sm-6">
                             </div>
                         </div>
                         <div class="btn--container justify-content-end mt-3">
                             <button type="reset" id="reset_btn"
                                 class="btn btn--reset">{{ translate('Reset') }}</button>
                             <button type="submit" class="btn btn--primary mb-2">{{ translate('Submit') }}</button>
                         </div>
                     </div>
                 </div>
             </form>

         </div>

         <div class="col-sm-12 col-lg-12 mb-3 mb-lg-2">
             <div class="card">
                 <div class="card-header py-2 border-0">
                     <div class="search--button-wrapper">
                         <h5 class="card-title">
                             {{ translate('messages.banner_list') }}<span class="badge badge-soft-dark ml-2"
                                 id="itemCount">{{ $banners->count() }}</span>
                         </h5>
                         <form id="search-form" class="search-form">
                             <!-- Search -->
                             <div class="input-group input--group">
                                 <input id="datatableSearch" type="search" name="search" class="form-control"
                                     placeholder="{{ translate('messages.search_by_title') }}"
                                     aria-label="{{ translate('messages.search_here') }}"
                                     value="{{ request()->search }}">
                                 <button type="submit" class="btn btn--secondary"><i class="tio-search"></i></button>
                             </div>
                             <!-- End Search -->
                         </form>
                     </div>
                 </div>
                 <!-- Table -->
                 <div class="table-responsive datatable-custom">
                     <table id="columnSearchDatatable"
                         class="table table-borderless table-thead-bordered table-nowrap table-align-middle card-table"
                         data-hs-datatables-options='{
                                "order": [],
                                "orderCellsTop": true,
                                "search": "#datatableSearch",
                                "entries": "#datatableEntries",
                                "isResponsive": false,
                                "isShowPaging": false,
                                "paging": false
                               }'>
                         <thead class="thead-light">
                             <tr>
                                 <th class="border-0">{{ translate('messages.SL') }}</th>
                                 <th class="border-0">{{ translate('messages.title') }}</th>
                                 <th class="border-0">{{ translate('messages.banner_Image') }}</th>
                                 <th class="border-0">{{ translate('messages.redirection_Link') }}</th>
                                 <th class="border-0 text-center">{{ translate('messages.status') }}</th>
                                 <th class="border-0 text-center">{{ translate('messages.action') }}</th>
                             </tr>
                         </thead>

                         <tbody id="set-rows">
                             @foreach ($banners as $key => $banner)
                                 <tr>
                                     <td>{{ $key + $banners->firstItem() }}</td>
                                     <td>
                                         <h5 class="text-hover-primary mb-0">
                                             {{ Str::limit($banner['title'], 25, '...') }}
                                         </h5>
                                     </td>
                                     <td>
                                         <span class="media align-items-center">
                                             <img class="img--ratio-3 w-auto h--50px rounded mr-2 onerror-image"
                                                 src="{{ \App\CentralLogics\Helpers::onerror_image_helper($banner['image'], asset('storage/app/public/banner/') . '/' . $banner['image'], asset('/public/assets/admin/img/900x400/img1.jpg'), 'banner/') }}"
                                                 data-onerror-image="{{ asset('/public/assets/admin/img/900x400/img1.jpg') }}"
                                                 alt="{{ $banner->name }} image">
                                         </span>
                                     </td>
                                     <td><a href="{{ $banner->default_link }}">
                                             {{ Str::limit($banner['default_link'], 60, '...') }}</a></td>
                                     <td>
                                         <div class="d-flex justify-content-center">
                                             <label class="toggle-switch toggle-switch-sm"
                                                 for="statusCheckbox{{ $banner->id }}">
                                                 <input type="checkbox"
                                                     data-url="{{ route('vendor.banner.status_update', [$banner['id'], $banner->status ? 0 : 1]) }}"
                                                     class="toggle-switch-input redirect-url"
                                                     id="statusCheckbox{{ $banner->id }}"
                                                     {{ $banner->status ? 'checked' : '' }}>
                                                 <span class="toggle-switch-label">
                                                     <span class="toggle-switch-indicator"></span>
                                                 </span>
                                             </label>
                                         </div>
                                     </td>
                                     <td>
                                         <div class="btn--container justify-content-center">
                                             <a class="btn action-btn btn--primary btn-outline-primary"
                                                 href="{{ route('vendor.banner.edit', [$banner['id']]) }}"
                                                 title="{{ translate('messages.edit_banner') }}"><i
                                                     class="tio-edit"></i>
                                             </a>
                                             <a class="btn action-btn btn--danger btn-outline-danger form-alert"
                                                 href="javascript:" data-id="banner-{{ $banner['id'] }}"
                                                 data-message="{{ translate('Want to delete this banner ?') }}"
                                                 title="{{ translate('messages.delete_banner') }}"><i
                                                     class="tio-delete-outlined"></i>
                                             </a>
                                             <form action="{{ route('vendor.banner.delete', [$banner['id']]) }}"
                                                 method="post" id="banner-{{ $banner['id'] }}">
                                                 @csrf @method('delete')
                                             </form>
                                         </div>
                                     </td>
                                 </tr>
                             @endforeach
                         </tbody>
                     </table>

                     @if (count($banners) !== 0)
                         <hr>
                     @endif
                     <div class="page-area">
                         {!! $banners->links() !!}
                     </div>
                     @if (count($banners) === 0)
                         <div class="empty--data">
                             <img src="{{ asset('/public/assets/admin/svg/illustrations/sorry.svg') }}"
                                 alt="public">
                             <h5>
                                 {{ translate('no_data_found') }}
                             </h5>
                         </div>
                     @endif
                 </div>
             </div>
         </div>
         <!-- End Table -->
     </div>

     <div class="row gx-2 gx-lg-3 col-md-6">

         <div class="col-sm-12 col-lg-12 mb-3 mb-lg-2">
             <div class="row g-3">
                 <div class="col-12">
                     <div class="card">
                         <div class="card-body">
                             <form enctype="multipart/form-data"
                                 action="{{ route('vendor.banner.store-offer-banner') }}" method="post"
                                 id="banner_form">
                                 @csrf

                                 <div class="row">
                                     <div class="col-md-8">
                                         <div class="col-12 p-1">
                                             <div class="form-group mb-0">
                                                 <label class="input-label"
                                                     for="default_title">{{ translate('messages.Banner_Title') }}
                                                 </label>
                                                 <input type="text" name="title" id="default_title"
                                                     class="form-control"
                                                     placeholder="{{ translate('messages.Ex:Dhamaka_Offer') }}">
                                             </div>
                                         </div>
                                         <div class="col-12 p-1">
                                             <div class="form-group mb-0">
                                                 <label class="input-label"
                                                     for="url">{{ translate('messages.URL') }}
                                                 </label>
                                                 <input type="text" name="url" id="url"
                                                     class="form-control"
                                                     placeholder="{{ translate('messages.Ex:https://example.com') }}">
                                             </div>
                                         </div>


                                     </div>

                                     <div class="col-md-4">
                                         <label for="">Image</label>
                                         <div class="flex-grow-1 mx-auto">
                                             <label class="d-inline-block m-0 position-relative">
                                                 <img class="img--136 border" id="viewer2"
                                                     src="{{ asset('public/assets/admin/img/upload-img.png') }}"
                                                     alt="thumbnail" />
                                                 <div class="icon-file-group">
                                                     <div class="icon-file"><input type="file" name="banner"
                                                             id="customFileEg12" class="custom-file-input d-none"
                                                             accept=".jpg, .png, .jpeg, .gif, .bmp, .tif, .tiff|image/*">
                                                         <i class="tio-edit"></i>
                                                     </div>
                                                 </div>
                                             </label>
                                         </div>
                                     </div>
                                     <div class="row g-0 px-3 col-12">
                                         <div class="col-md-6 p-1">
                                             <div class="form-group">
                                                 <label class="input-label"
                                                     for="exampleFormControlInput1">{{ translate('messages.start_date') }}<span
                                                         class="form-label-secondary text-danger"
                                                         data-toggle="tooltip" data-placement="right"
                                                         data-original-title="{{ translate('messages.Required.') }}">
                                                         *
                                                     </span></label>
                                                 <input type="date" name="start_date" class="form-control"
                                                     id="date_from" required>
                                             </div>
                                         </div>
                                         <div class="col-md-6 p-1">
                                             <div class="form-group">
                                                 <label class="input-label"
                                                     for="exampleFormControlInput1">{{ translate('messages.end_date') }}<span
                                                         class="form-label-secondary text-danger"
                                                         data-toggle="tooltip" data-placement="right"
                                                         data-original-title="{{ translate('messages.Required.') }}">
                                                         *
                                                     </span></label>
                                                 <input type="date" name="end_date" class="form-control"
                                                     id="date_to" required>
                                             </div>
                                         </div>
                                     </div>

                                 </div>
                                 <div class="col-12 d-flex justify-content-end">
                                     <button type="submit"
                                         class="btn btn--primary">{{ translate('messages.submit') }}</button>
                                 </div>
                             </form>
                         </div>
                     </div>
                 </div>
                 <div class="col-12">
                     <div class="card">
                         <div class="card-body">
                             <div class="">
                                 <div class="card-header py-2 border-0">
                                     <h5 class="card-title">
                                         {{ translate('messages.offer_banner_list') }}<span
                                             class="badge badge-soft-dark ml-2"
                                             id="itemCount">{{ count($banners) }}</span>
                                     </h5>

                                 </div>
                             </div>
                             <!-- Table -->
                             <div class="table-responsive datatable-custom">
                                 <table id="columnSearchDatatable"
                                     class="table table-borderless table-thead-bordered table-nowrap table-align-middle card-table"
                                     data-hs-datatables-options='{
                                    "order": [],
                                    "orderCellsTop": true,
                                    "search": "#datatableSearch",
                                    "entries": "#datatableEntries",
                                    "isResponsive": false,
                                    "isShowPaging": false,
                                    "paging": false
                                }'>
                                     <thead class="thead-light">
                                         <tr>
                                             <th class="border-0">{{ translate('messages.SL') }}</th>
                                             <th class="border-0">Image</th>

                                             <th class="border-0 ">Created At
                                             </th>
                                             <th class="border-0 ">Action
                                             </th>
                                         </tr>
                                     </thead>

                                     <tbody id="set-rows">
                                         @foreach ($banners as $key => $banner)
                                             <tr>
                                                 <td>{{ $key + $banners->firstItem() }}</td>
                                                 <td>
                                                     <span class="media align-items-center">
                                                         <img class="img--176 border w-auto h--50px rounded mr-2 onerror-image"
                                                             src="{{ \App\CentralLogics\Helpers::onerror_image_helper($banner['image'], asset('storage/app/public/banners/') . '/' . $banner['image'], asset('public/assets/admin/img/900x400/img1.jpg'), 'banners/') }}"
                                                             data-onerror-image="{{ asset('/public/assets/admin/img/900x400/img1.jpg') }}"
                                                             alt="{{ $banner->name }} image">
                                                         <div class="media-body">
                                                             <h5 title="{{ $banner['title'] }}"
                                                                 class="text-hover-primary mb-0">
                                                                 {{ Str::limit($banner['title'], 25, '...') }}
                                                             </h5>
                                                         </div>
                                                     </span>
                                                     <span class="d-block font-size-sm text-body">

                                                     </span>
                                                 </td>
                                                 <td>{{ $banner['created_at'] }}</td>

                                                 <td>
                                                     <div class="btn--container justify-content-center">

                                                         <a class="btn action-btn btn--danger btn-outline-danger form-alert"
                                                             href="javascript:" data-id="banner-{{ $banner['id'] }}"
                                                             data-message="{{ translate('Want to delete this banner ?') }}"><i
                                                                 class="tio-delete-outlined"></i>
                                                         </a>
                                                         <form
                                                             action="{{ route('vendor.banner.delete-offer-banner', [$banner['id']]) }}"
                                                             method="post" id="banner-{{ $banner['id'] }}">
                                                             @csrf @method('get')
                                                         </form>
                                                     </div>
                                                 </td>
                                             </tr>
                                         @endforeach
                                     </tbody>
                                 </table>

                             </div>
                             @if (count($banners) !== 0)
                                 <hr>
                             @endif
                             <div class="page-area">
                                 {!! $banners->links() !!}
                             </div>
                             @if (count($banners) === 0)
                                 <div class="empty--data">
                                     <img src="{{ asset('/public/assets/admin/svg/illustrations/sorry.svg') }}"
                                         alt="public">
                                     <h5>
                                         {{ translate('no_data_found') }}
                                     </h5>
                                 </div>
                             @endif
                         </div>
                     </div>
                 </div>
             </div>
         </div>
     </div>
 </div>
