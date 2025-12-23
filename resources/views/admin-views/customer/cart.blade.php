@extends('layouts.admin.app')

@section('title', translate('Customer Cart'))

@push('css_or_js')
    <meta name="csrf-token" content="{{ csrf_token() }}">
@endpush

@section('content')
    <div class="content container-fluid">
        <!-- Page Header -->
        <div class="page-header">
            <h1 class="page-header-title mr-3">
                <span class="page-header-icon">
                    <img src="{{asset('/public/assets/admin/img/people.png')}}" class="w--26" alt="">
                </span>
                <span>
                     {{ translate('messages.cart') }} <span class="badge badge-soft-dark ml-2" id="count">{{ count($carts)}}</span>
                </span>
            </h1>
        </div>
        <!-- End Page Header -->

        <!-- Card -->
        <div class="card">
        

            <div class="card-body p-0">
                <!-- Table -->
                <div class="table-responsive datatable-custom">
                    <table id="datatable"
                        class="table table-hover table-borderless table-thead-bordered table-nowrap table-align-middle card-table" >
                        <thead class="thead-light">
                            <tr>
                                <th class="border-0">
                                    {{ translate('sl') }}
                                </th>
                                <th class="table-column-pl-0 border-0">Customer Name</th>
                                <th class="border-0">Item Name</th>
                                <th class="border-0">Price</th>
                                <th class="border-0">Quantity</th>
                                <th class="border-0">Added On</th>
                            </tr>
                        </thead>

                        <tbody id="set-rows">
                            @foreach ($carts as $key => $cart)

                                <tr class="">
                                    <td class="">
                                        {{ $key + 1 }}
                                    </td>
                                    <td class="table-column-pl-0">
                                        <a href="{{ route('admin.users.customer.view', [$cart->user_id]) }}" class="text--hover">
                                           <b>{{ $cart->f_name . ' ' . $cart->l_name }}</b> 
                                        </a>
                                    </td>
                                    <td>
                                       {{ $cart->name}}
                                       
                                    </td>
                                    <td>
                                       {{ _price($cart->price) }}
                                       
                                    </td>
                                    <td>
                                        <label class="badge">
                                            {{ $cart->quantity }}
                                        </label>
                                    </td>
                                    <td> 
                                         {{ $cart->created_at }}
                                    
                                    </td>
                                   
                                    
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <!-- End Table -->
            </div>

            @if(count($carts) !== 0)
            <hr>
            @endif
            <div class="page-area">
                {!! $carts->links() !!}
            </div>
            @if(count($carts) === 0)
            <div class="empty--data">
                <img src="{{asset('/public/assets/admin/svg/illustrations/sorry.svg')}}" alt="public">
                <h5>
                    {{translate('no_data_found')}}
                </h5>
            </div>
            @endif

        </div>
        <!-- End Card -->
    </div>
@endsection

@push('script_2')
    <script src="{{asset('public/assets/admin')}}/js/view-pages/customer-list.js"></script>
    <script>
        "use strict";

        $('.status_change_alert').on('click', function (event) {
            let url = $(this).data('url');
            let message = $(this).data('message');
            status_change_alert(url, message, event)
        })

        function status_change_alert(url, message, e) {
            e.preventDefault();
            Swal.fire({
                title: '{{ translate('messages.Are you sure?') }}',
                text: message,
                type: 'warning',
                showCancelButton: true,
                cancelButtonColor: 'default',
                confirmButtonColor: '#FC6A57',
                cancelButtonText: '{{ translate('messages.no') }}',
                confirmButtonText: '{{ translate('messages.Yes') }}',
                reverseButtons: true
            }).then((result) => {
                if (result.value) {
                    location.href = url;
                }
            })
        }

    </script>
@endpush
