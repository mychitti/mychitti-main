@extends('front-views.layout')

@section('title','Edit Address')

@push('css_or_js')
<meta name="csrf-token" content="{{ csrf_token() }}">
<style>
    #map {
        height:300px;
        width: 100%;
    }
    input.no-arrows::-webkit-outer-spin-button,
    input.no-arrows::-webkit-inner-spin-button { -webkit-appearance: none; margin: 0; }
    input.no-arrows { -moz-appearance: textfield; }
</style>

<script>

</script>
@endpush

@section('content')

<!-- Contact Start -->
<div class="container-fluid contact py-5">
    <div class="container py-5">
        <div class="p-5 bg-light rounded">
            <form class="formSubmit addressForm" action="{{route('add-new-address')}}" method="post">
                @csrf
                <div class="row ">
                    <div class="col-md-12 row">
                        <div class="row">
                            <div class="rounded p-2 d-flex flex-column position-relative align-items-center" style="height: fit-content;">
                                <div id="map2"></div>
                                <!-- <p id="info"></p> -->
                            </div>

                            <div class="col-12 row">
                                <input type="hidden" name="latitude" id="user_latitude">
                                <input type="hidden" name="longitude" id="user_longitude" >
                                <label for="Delivery-1" style="cursor:pointer" class="col-2 form-check text-start my-3 border rounded border-primary py-3 mx-1 px-1">
                                    <input type="radio"  style="cursor:pointer" class="form-check-input bg-primary border-0 mx-1" id="Delivery-1" name="address_type" value="home">
                                    Home
                                </label> 
                                <label for="Delivery-2" style="cursor:pointer" class="col-2 form-check text-start my-3 border rounded border-primary py-3  mx-1 px-1">
                                    <input type="radio" class="form-check-input bg-primary border-0 mx-1" id="Delivery-2" name="address_type" value="office">
                                    Work
                                </label>
                                <label for="Delivery-3" style="cursor:pointer" class="col-2 form-check text-start my-3 border rounded border-primary py-3  mx-1 px-1">
                                    <input type="radio"  class="form-check-input bg-primary border-0 mx-1" id="Delivery-3" name="address_type" value="others">
                                    Other
                                </label> 

                            </div>
                            <div class="col-md-12 col-lg-12">
                                <div class="form-item w-100">
                                    <label class="form-label my-3">Address<sup>*</sup></label>
                                    <input type="text" style="pointer-events:none;" name="address" id="user_address" class="form-control">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-item w-100">
                                    <label class="form-label my-3">Contact Person Name<sup>*</sup></label>
                                    <input type="text" name="contact_person_name"  required  class="form-control">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-item w-100">
                                    <label class="form-label my-3">Contact Person Mobile</label>
                                    <input oninput="this.value = this.value.slice(0, 10)" onwheel="this.blur()" onkeydown="if(event.key==='ArrowUp'||event.key==='ArrowDown')event.preventDefault()" name="contact_person_number" type="number" class="form-control no-arrows">
                                </div>
                            </div>
                            <div class="col-md-12 col-lg-12">
                                <div class="form-item w-100">
                                    <label class="form-label my-3">Street Number</label>
                                    <input  name="road" type="text" placeholder="(Optional)" class="form-control">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-item w-100">
                                    <label class="form-label my-3">House</label>
                                    <input  name="house" type="text" placeholder="(Optional)" class="form-control">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-item w-100">
                                    <label class="form-label my-3">Floor</label>
                                    <input  name="floor" type="text" placeholder="(Optional)" class="form-control">
                                </div>
                            </div>
                        </div>

                        <div class="mt-4">
                            <button type="submit" class="btn border-secondary text-primary">Save</button>
                        </div>

                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- Contact End -->

@endsection

@push('script_2')

@endpush