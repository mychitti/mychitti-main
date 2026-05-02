 @if (auth('vendor')->check())
     <div id="servicesDiv" class="card mb-1">
         <div class="card-header">
             <h2 class="card-title h4">
                 <i class="tio-calendar-note"></i>
                 <span>{{ $store_data->module_id == 6 ? 'Services and ' : '' }}Categories</span>
             </h2>
         </div>

         <!-- Body -->
         <div class="card-body">
             <form class="row" action="{{ route('vendor.category.update-selected') }}" method="post">
                 @csrf
                 @if ($store_data->module_id == 6)
                     <div class=" col-md-6 form-group">
                         <div class="form-group mb-1">
                             <label class="input-label" id="" for="other_verification">Category
                                 1<span>*</span></label>
                             <select name="category_1" data-id="1" required
                                 class="category_select form-control __form-control js-select2-custom js-example-basic-single"
                                 data-placeholder="Category">
                                 <option value=""></option>
                                 @foreach ($module_categories as $cat)
                                     <option {{ $cat->id == $store_data['category_1'] ? 'selected' : '' }}
                                         value="{{ $cat->id }}">{{ $cat->name }}</option>
                                 @endforeach
                             </select>
                         </div>
                     </div>
                     <div class=" col-md-6 form-group subcategory_1">
                         <div class="form-group mb-1">
                             <label class="input-label" id="" for="other_verification">Services
                                 1</label>
                             <select name="services_1[]" multiple="multiple"
                                 class="select_subcategory_1 form-control __form-control js-select2-custom js-example-basic-multiple"
                                 data-placeholder="Subcategory">
                                 <option value=""></option>
                                 @foreach ($items_1 as $sc)
                                     <option
                                         {{ in_array($sc->id, explode(',', $store_data->services_1)) ? 'selected' : '' }}
                                         value="{{ $sc->id }}">{{ $sc->name }}</option>
                                 @endforeach
                             </select>
                         </div>
                     </div>
                     <div class=" col-md-6 form-group">
                         <label class="input-label" id="" for="other_verification">Category 2
                             <span>(optional)</span></label>
                         <select name="category_2" data-id="2"
                             class="category_select form-control __form-control js-select2-custom js-example-basic-single"
                             data-placeholder="Category">
                             <option value=""></option>
                             @foreach ($module_categories as $cat)
                                 <option {{ $cat->id == $store_data['category_2'] ? 'selected' : '' }}
                                     value="{{ $cat->id }}">{{ $cat->name }}</option>
                             @endforeach
                         </select>
                     </div>
                     <div class=" col-md-6 form-group subcategory_2">
                         <div class="form-group mb-1">
                             <label class="input-label" id="" for="other_verification">Services
                                 2</label>
                             <select name="services_2[]" multiple="multiple" id=""
                                 class="category_select select_subcategory_2 form-control __form-control js-select2-custom js-example-basic-multiple"
                                 data-placeholder="Subcategory">
                                 <option value=""></option>
                                 @foreach ($items_2 as $sc)
                                     <option
                                         {{ in_array($sc->id, explode(',', $store_data->services_2)) ? 'selected' : '' }}
                                         value="{{ $sc->id }}">{{ $sc->name }}</option>
                                 @endforeach
                             </select>
                         </div>
                     </div>
                 @else
                     <div class="categroy_set_shop">
                         <div class="form-group shop_categories">
                             <div class="form-group mb-1">
                                 <label class="input-label" id="" for="shop_categories">Categories
                                     (max 20)</label>
                                 <select name="shop_categories[]" multiple="multiple" id="shop_categories"
                                     class=" form-control __form-control  select_2_max_20"
                                     data-placeholder="Categories">
                                     <option value=""></option>
                                     @foreach ($module_categories as $cat)
                                         <option
                                             {{ in_array($cat->id, explode(',', $store_data->shop_categories)) ? 'selected' : '' }}
                                             value="{{ $cat->id }}">{{ $cat->name }}</option>
                                     @endforeach
                                 </select>
                             </div>
                         </div>
                     </div>
                 @endif


                 <div class="d-flex justify-content-end mx-3 w-100">

                     <button class="btn btn-primary ">{{ translate('messages.Save_changes') }}</button>
                 </div>
             </form>
         </div>
         <!-- End Body -->
     </div>
 @endif
