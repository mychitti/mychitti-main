@extends('layouts.admin.app')

@section('title', 'Wallet')

@push('css_or_js')
    <link rel="stylesheet" href="{{ asset('/public/assets/admin/css/intlTelInput.css') }}" />
@endpush



@section('content')
    <div class="content container-fluid">
        <!-- Page Header -->
        <div class="page-header">
            <h1 class="page-header-title">
                <span class="page-header-icon">
                    <img src="{{ asset('public/assets/admin/img/store.png') }}" class="w--26" alt="">
                </span>
                <span>
                    Wallet
                </span>
            </h1>
        </div>
        <form action="{{ route('admin.store.wallet.recharge') }}" class="formSubmitVerify" method="post">
            @csrf
            <div class="row g-2">
                <div class="col-md-4">
                    <label class="form-check-label" for="flexRadioDefault2">Store</label>
                    <select data-placeholder="Select Store" required name="store_id" id="search_store_id"
                        class="form-control js-select2-custom ">
                        <option value=""></option>

                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-check-label" for="flexRadioDefault2">Amount</label>
                    <input type="number" name="amount" id="" placeholder="Ex: 1200" class="form-control">
                </div>
                <div class="col-md-4 my-3 d-flex gap-2 align-items-center">
                    <input type="radio" value="1" name="billing" class="billing_status" id="billing" checked>
                    <label for="billing" class="mb-0">Billing</label>
                    <input type="radio" value="0" name="billing" class="billing_status" id="retail">
                    <label for="retail" class="mb-0">Retail</label>
                </div>
                <div class="col-12">
                    <div class="w-100 d-flex justify-content-end">
                        <button class="btn btn-primary">Add To Wallet</button>
                    </div>
                </div>
            </div>
        </form>

    </div>

@endsection

@push('script_2')
    <script>
        $(document).ready(function() {

            let url =
                "{{ route('admin.store.get-matches') }}";

            $('#search_store_id').select2({
                placeholder: 'Search for a store',
                minimumInputLength: 3,
                ajax: {
                    url: url,
                    dataType: 'json',
                    delay: 250,
                    data: function(params) {
                        return {
                            q: params.term
                        };
                    },
                    processResults: function(data) {
                        let results = data.map(store => ({
                            id: store.id,
                            text: store.name + ' (' + store.phone + ')'
                        }));

                        return {
                            results: results
                        };
                    },
                    cache: true
                }
            });
        });
    </script>
@endpush
