<div class="card-header border-0 text-center">
    <h4>Price</h4>
    <input name="product_id" value="{{$product['id']}}" class="initial-hidden">
</div>
<div class="card-body">
    <div class="form-group">
        <div class="mb-4">
            <div class="variant_combination" id="variant_combination">
                @include('vendor-views.product.partials._edit-price',['combinations'=>json_decode($product['variations'],true),'stock'=>config('module.'.$product->module->module_type)['stock']])
            </div>
            <div id="quantity">
                <label class="form-label" for="main_price">{{translate('messages.main_price')}}</label>
                <input type="number" min='0' class="form-control" name="main_price" value="{{$product->price}}" id="main_price" >
            </div>
        </div>
    </div>
</div>
