@extends('layouts.vendor.app')

@section('title','Selected Categories')

@push('css_or_js')

@endpush

@section('content')
<div class="content container-fluid">
    <!-- Page Header -->
    <div class="page-header">
        <h1 class="page-header-title">
            <span class="page-header-icon">
                <img src="{{asset('public/assets/admin/img/categories.png')}}" class="w--20" alt="">
            </span>
            <span>
                {{translate('messages.category_list')}} 
            </span>
        </h1>
    </div>
    <!-- End Page Header -->
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header py-2 border-0">

                </div>
                <div class="card-body p-0">
                    <form action="{{route('vendor.category.update-selected')}}" method="post">
                        @csrf
                        <div class="form-group">
                            <div class="form-group mb-4">
                                <label class="input-label" id=""
                                    for="other_verification">Category 1<span>*</span></label>
                                <select name="category_1" data-id="1" required
                                    class="category_select form-control __form-control js-select2-custom js-example-basic-single"
                                    data-placeholder="Category">
                                    <option value=""></option>
                                    @foreach($module_categories as $cat)
                                    <option {{$cat->id == $store_data['category_1'] ? 'selected': ''}} value="{{$cat->id}}">{{$cat->name}}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="form-group subcategory_1">
                            <div class="form-group mb-4">
                                <label class="input-label" id=""
                                    for="other_verification">Services 1</label>
                                <select name="services_1[]" multiple="multiple" required
                                    class="select_subcategory_1 form-control __form-control js-select2-custom js-example-basic-multiple"
                                    data-placeholder="Subcategory">
                                    <option value=""></option>
                                    @foreach($items_1 as $sc)
                                    <option {{in_array($sc->id, \App\CentralLogics\Helpers::store_item_ids($store_data->id)) ? 'selected': ''}} value="{{$sc->id}}">{{$sc->name}}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="input-label" id=""
                                for="other_verification">Category 2 <span>(optional)</span></label>
                            <select name="category_2" data-id="2" required
                                class="category_select form-control __form-control js-select2-custom js-example-basic-single"
                                data-placeholder="Category">
                                <option value=""></option>
                                @foreach($module_categories as $cat)
                                <option {{$cat->id == $store_data['category_2'] ? 'selected': ''}} value="{{$cat->id}}">{{$cat->name}}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group subcategory_2">
                            <div class="form-group mb-4">
                                <label class="input-label" id=""
                                    for="other_verification">Services 2</label>
                                <select name="services_2[]" multiple="multiple" id="" required
                                    class="category_select select_subcategory_2 form-control __form-control js-select2-custom js-example-basic-multiple"
                                    data-placeholder="Subcategory">
                                    <option value=""></option>
                                    @foreach($items_2 as $sc)
                                    <option {{in_array($sc->id, \App\CentralLogics\Helpers::store_item_ids($store_data->id)) ? 'selected': ''}} value="{{$sc->id}}">{{$sc->name}}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <button class="btn btn-primary">Update</button>
                    </form>
                </div>
             
            </div>
        </div>
    </div>
</div>

@endsection

@push('script_2')
    <script>
          $('.category_select').on('change', function (){
        var cat_id = $(this).val()
        var dataid =  $(this).attr('data-id');

       //fetchsubcategory
        $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });
            $.get({
                url: "{{route('fetch-subcategory')}}", 
                data: {
                    cat_id: cat_id
                },
                success: function (data) {
                    console.log(data)
                    if(data){
                        if(data.categories.length){
                            var html = '';
                            data.categories.forEach(element => {
                                html += '<option value="'+element.id+'">'+element.name+'</option>';
                            });
                            $(".subcategory_"+ dataid).show()
                            $(".select_subcategory_"+ dataid).html(html)
                        }else{
                            $(".subcategory_"+ dataid).hide()
                            $(".select_subcategory_"+ dataid).html('')
                        }
                    }
                },
            });
    })
    </script>
@endpush