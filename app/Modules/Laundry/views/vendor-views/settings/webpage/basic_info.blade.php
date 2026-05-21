 <div class="col-md-6 ">
             <form method="post" action="{{ route('vendor.settings.webpage-update') }}" id="settingsForm"
                 class=" card shadow p-2">
                 @csrf
                 <h3>Contact Settings</h3>
                 <div class="row ">
                     <!-- Website Name -->
                     <div class=" col-md-6">
                         <label class="settings-label">
                             <span class="settings-icon">🌐</span>
                             <span>Website Name</span>
                         </label>
                         <input type="text" id="websiteName" value="{{ $storeConfig?->webpage_name ?? $store->name }}"
                             name="website_name" class="form-control" placeholder="Enter your website name">
                     </div>

                     <!-- Email -->
                     <div class=" col-md-6">
                         <label class="settings-label">
                             <span class="settings-icon">✉️</span>
                             <span>Email Address</span>
                         </label>
                         <input type="email" id="email" value="{{ $storeConfig?->webpage_email ?? $store->email }}"
                             name="email" class="form-control" placeholder="contact@mail.com">
                     </div>

                     <!-- Address -->
                     <div class="col-md-12">
                         <label class="settings-label">
                             <span class="settings-icon">📍</span>
                             <span>Physical Address</span>
                         </label>
                         <textarea id="address" class="form-control" name="address" placeholder="Enter your complete address">{{ $storeConfig?->webpage_address ?? $store->address }}</textarea>
                     </div>
                 </div>

                 <!-- Phone Numbers -->
                 <div class="settings-phone-section mt-3">
                     <div class="settings-phone-header">
                         <label class="settings-label">
                             <span class="settings-icon">📞</span>
                             <span>Phone Numbers</span>
                         </label>
                         <button type="button" class="btn btn--primary" onclick="addPhoneNumber()">
                             <span>+</span>
                             <span>Add Phone</span>
                         </button>
                     </div>
                     <div id="phoneContainer" class="row">
                         @php
                             $phones = $storeConfig?->webpage_phones;
                             if ($phones) {
                                 $phones = json_decode($phones, true);
                             } else {
                                 $phones = [];
                             }
                         @endphp
                         @if (!empty($phones))
                             @foreach ($phones as $key => $value)
                                 <div class="settings-phone-item col-md-6">
                                     <input type="text" name="phone[]" value="{{ $value }}"
                                         class="form-control intl_input phone-input" placeholder="Phone Number">
                                 </div>
                             @endforeach
                         @else
                             <div class="settings-phone-item col-md-6">
                                 <input type="text" name="phone[]" value="{{ $store->phone }}"
                                     class="form-control intl_input phone-input" placeholder="Phone Number">
                             </div>
                         @endif
                     </div>
                 </div>
                 <div class="border p-2">
                     <div class="row">
                         <div class="col-md-6">
                             <label for="inventory_position"><strong>Inventory Items Display Position</strong></label>
                             <select name="inventory_items_position" id="inventory_position" class="form-control mt-2">
                                 <option {{ $storeConfig?->inventory_items_position == 'above' ? 'selected' : '' }}
                                     value="above">Above Service Section</option>
                                 <option {{ $storeConfig?->inventory_items_position == 'below' ? 'selected' : '' }}
                                     value="below">Below Service Section</option>
                             </select>
                         </div>
                         <div class="col-md-6 ">
                             <small class="badge badge-soft-primary" style="margin-top: -5px;">
                                 <i class="tio-info-outined"></i> Select where the inventory items should be displayed in
                                 relation to the service section
                                 on the Webpage. If you choose <strong>Above</strong>, inventory items will appear
                                 before the services. If you choose <strong>Below</strong>, they will appear after the
                                 service
                                 section.
                             </small>
                         </div>
                     </div>
                 </div>
                 <div class="mt-3">
                     <input type="hidden" name="latitude" id="latitude"
                         value="{{ $storeConfig?->webpage_latitude ?? $store->latitude }}">
                     <input type="hidden" name="longitude" id="longitude"
                         value="{{ $storeConfig?->webpage_longitude ?? $store->longitude }}">
                     {{-- <div class="invisible" style="height: 0px;">
                         <label class="form-label" for="latitude">{{ translate('messages.latitude') }}<span
                                 class="form-label-secondary" data-toggle="tooltip" data-placement="right"
                                 data-original-title="{{ translate('messages.store_lat_lng_warning') }}"><img
                                     src="{{ asset('/public/assets/admin/img/info-circle.svg') }}"
                                     alt="{{ translate('messages.store_lat_lng_warning') }}"></span></label>
                         <input type="text" id="latitude" name="latitude" class="form-control"
                             placeholder="{{ translate('messages.Ex:') }} -94.22213"
                             value="{{ $storeConfig?->webpage_latitude ?? $store->latitude }}" required readonly>
                     </div>
                     <div class="invisible" style="height: 0px;">
                         <label class="form-label" for="longitude">{{ translate('messages.longitude') }}<span
                                 class="form-label-secondary" data-toggle="tooltip" data-placement="right"
                                 data-original-title="{{ translate('messages.store_lat_lng_warning') }}"><img
                                     src="{{ asset('/public/assets/admin/img/info-circle.svg') }}"
                                     alt="{{ translate('messages.store_lat_lng_warning') }}"></span></label>
                         <input type="text" name="longitude" class="form-control"
                             placeholder="{{ translate('messages.Ex:') }} 103.344322" id="longitude"
                             value="{{ $storeConfig?->webpage_longitude ?? $store->longitude }}" required readonly>
                     </div> --}}
                     {{-- <input id="pac-input" class="controls rounded" data-toggle="tooltip" data-placement="right"
                         data-original-title="{{ translate('messages.search_your_location_here') }}" type="text"
                         placeholder="{{ translate('messages.search_here') }}" /> --}}
                     <h3>Map</h3>
                     <input type="text" id="searchInput" class="form-control">

                     <div id="map"></div>
                 </div>

                 <!-- Save Button -->
                 <div class="w-100 d-flex justify-content-end mt-3">
                     <button type="submit" class="btn btn--primary">
                         <span>💾</span>
                         <span>Save Contact Settings</span>
                     </button>
                 </div>
             </form>
         </div>
         <div class="col-md-6 ">
             <div class="accordion mb-2" id="accordionExample">
                 <div class="card">
                     <div class="card-header" id="headingOne">
                         <h2 class="mb-0">
                             <button class="btn btn-link btn-block text-left" type="button" data-toggle="collapse"
                                 data-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne">
                                 <span class="card-header-icon">
                                     <img class="w--22" src="{{ asset('public/assets/admin/img/store.png') }}"
                                         alt="">
                                 </span> {{ translate('messages.store_meta_data') }}
                             </button>
                         </h2>
                     </div>

                     <div id="collapseOne" class="collapse show" aria-labelledby="headingOne"
                         data-parent="#accordionExample">
                         <div class="card-body p-1">
                             @php($language = \App\Models\BusinessSetting::where('key', 'language')->first())
                             @php($language = $language->value ?? null)
                             @php($defaultLang = 'en')
                             <form action="{{ route('vendor.business-settings.update-meta-data', [$store['id']]) }}"
                                 method="post" enctype="multipart/form-data" class="col-12">
                                 @csrf
                                 <div class="row g-2">
                                     <div class="col-lg-6">

                                         <div id="default-form">
                                             <div class="form-group">
                                                 <label class="input-label"
                                                     for="meta_title">{{ translate('messages.meta_title') }}
                                                 </label>
                                                 <input type="text" id="meta_title" name="meta_title"
                                                     class="form-control" value="{{ $store->meta_title }}"
                                                     placeholder="{{ translate('messages.meta_title') }}">
                                             </div>
                                             <input type="hidden" name="lang[]" value="default">
                                             <div class="form-group mb-0">
                                                 <label class="input-label"
                                                     for="meta_description">{{ translate('messages.meta_description') }}
                                                 </label>
                                                 <textarea type="text" id="meta_description" name="meta_description"
                                                     placeholder="{{ translate('messages.meta_description') }}" class="form-control min-h-90px ckeditor">{{ $store->meta_description }}</textarea>
                                             </div>
                                         </div>

                                     </div>
                                     <div class="col-lg-6">
                                         <div class="">
                                             <div class="card-header">
                                                 <h5 class="card-title">
                                                     <span class="card-header-icon mr-1"><i
                                                             class="tio-dashboard"></i></span>
                                                     <span>{{ translate('store_meta_image') }}</span>
                                                 </h5>
                                             </div>
                                             <div class="card-body">
                                                 <div class="d-flex flex-wrap flex-sm-nowrap __gap-12px">
                                                     <label class="__custom-upload-img mr-lg-5">
                                                         <label class="form-label">
                                                             {{ translate('meta_image') }} <span
                                                                 class="text--primary">({{ translate('1:1') }})</span>
                                                         </label>
                                                         <div class="text-center">
                                                             <img class="img--110 min-height-170px min-width-170px onerror-image"
                                                                 id="viewer"
                                                                 data-onerror-image="{{ asset('public/assets/admin/img/upload.png') }}"
                                                                 src="{{ \App\CentralLogics\Helpers::onerror_image_helper($store->meta_image, asset('storage/app/public/store') . '/' . $store->meta_image, asset('public/assets/admin/img/upload.png'), 'store/') }}"
                                                                 alt="{{ translate('meta_image') }}" />
                                                         </div>
                                                         <input type="file" name="meta_image" id="customFileEg1"
                                                             class="custom-file-input"
                                                             accept=".jpg, .png, .jpeg, .gif, .bmp, .tif, .tiff|image/*">
                                                     </label>
                                                 </div>
                                             </div>
                                         </div>
                                     </div>
                                     <div class="col-12">
                                         <div class="justify-content-end btn--container">
                                             <button type="submit"
                                                 class="btn btn--primary">{{ translate('save_changes') }}</button>
                                         </div>
                                     </div>
                                 </div>
                             </form>
                         </div>
                     </div>

                 </div>


             </div>
             <div class="accordion" id="accordionExample2">

                 <div class="card">
                     <div class="card-header" id="headingTwo">
                         <h2 class="mb-0">
                             <button class="btn btn-link btn-block text-left collapsed" type="button"
                                 data-toggle="collapse" data-target="#collapseTwo" aria-expanded="false"
                                 aria-controls="collapseTwo">
                                 <span class="card-header-icon">
                                     <img class="w--22" src="{{ asset('public/assets/admin/img/store.png') }}"
                                         alt="">
                                 </span> {{ translate('messages.Social Media Links') }}
                             </button>
                         </h2>
                     </div>
                     <div id="collapseTwo" class="collapse show" aria-labelledby="headingTwo"
                         data-parent="#accordionExample">
                         <div class="card-body p-1">
                             <form action="{{ route('vendor.business-settings.update-social-media', [$store['id']]) }}"
                                 method="post" enctype="multipart/form-data" class="col-12">
                                 @csrf
                                 <div class="row g-2">

                                     <div class="col-lg-12">
                                         <div class="">
                                             <div class="">
                                                 <div id="default-form">
                                                     <div class="form-group mb-0">

                                                         <div class="row">
                                                             <div class="input-group mb-3 col-md-6 ">
                                                                 <span class="input-group-text">Instagram</span>
                                                                 <input type="text" class="form-control"
                                                                     value="{{ $store->insta_url }}" name="insta_url"
                                                                     id="insta_inp">
                                                             </div>
                                                             <div class="input-group mb-3 col-md-6">
                                                                 <span class="input-group-text">Pinterest</span>
                                                                 <input type="text" class="form-control"
                                                                     value="{{ $store->pinterest_url }}"
                                                                     name="pinterest_url" id="pinterest_inp">
                                                             </div>
                                                             <div class="input-group mb-3 col-md-6">
                                                                 <span class="input-group-text">Facebook</span>
                                                                 <input type="text" class="form-control"
                                                                     value="{{ $store->fb_url }}" name="fb_url"
                                                                     id="fb_inp">
                                                             </div>
                                                             <div class="input-group mb-3 col-md-6">
                                                                 <span class="input-group-text">Twitter</span>
                                                                 <input type="text" class="form-control"
                                                                     value="{{ $store->twitter_url }}" name="twitter_url"
                                                                     id="twitter_inp">
                                                             </div>
                                                             <div class="input-group mb-3 col-md-6">
                                                                 <span class="input-group-text">LinkedIn</span>
                                                                 <input type="text" class="form-control"
                                                                     value="{{ $store->linkedin_url }}"
                                                                     name="linkedin_url" id="linkedin_inp">
                                                             </div>
                                                         </div>
                                                     </div>
                                                 </div>
                                             </div>
                                         </div>
                                     </div>
                                     <div class="col-12">
                                         <div class="justify-content-end btn--container">
                                             <button type="submit"
                                                 class="btn btn--primary">{{ translate('save_changes') }}</button>
                                         </div>
                                     </div>
                                 </div>
                             </form>
                         </div>
                     </div>
                 </div>
             </div>
         </div>