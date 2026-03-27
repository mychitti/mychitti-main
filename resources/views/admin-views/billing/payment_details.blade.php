@extends('layouts.admin.app')

@section('title', 'Bill Details')

@push('css_or_js')
    <meta name="csrf-token" content="{{ csrf_token() }}">
@endpush

@section('content')


    <div class="content container-fluid">
        <!-- Page Header -->
        <div class="page-header">
            <h1 class="page-header-title"><i class="tio-filter-list"></i>Bill Details</h1>
            <div class="page-header-select-wrapper">
            </div>
        </div>
        <!-- End Page Header --> 

        <div class="row g-2">
            <div class="background-pattern" style="background-color: #faffee;">
                <div class="container py-5">
                    <div class="row align-items-center" style="max-width: 1000px;    margin: 0 auto;">

                        <div class=""> 
                            <div class="highlight-box">
                                <h3 class="font-weight-bold">
                                    Total Amount : {{ \App\CentralLogics\Helpers::format_currency($invoice->total_amount) }}
                                </h3>
                                <ul class="list-unstyled mt-3 mb-4">
                                    @foreach (json_decode($invoice->other_details) as $key => $value)
                                        <li>
                                            <h4>{{ $value->name }}</h4>
                                            <h4 class="text-muted">Price :
                                                {{ \App\CentralLogics\Helpers::format_currency($value->price) }}</h4>
                                        </li>
                                    @endforeach
                                    <li>Invoice Date : {{ $invoice->created_at }} </li>
                                </ul>
                                @if ( $invoice->payment_status == 'Paid')
                                    <a  class="btn btn-primary btn-block" style="background-color: #5da100 !important;"><i class="tio-checkmark-circle"></i> Paid</a>
                                @else
                                    <a href="{{ route('admin.billing.make-payment', [$invoice->id]) }}"
                                        class="btn btn-block btn-outline-dark text-dark" style="background-color: #ffffff !important;">
                                        <img width="70px" src="{{asset('storage/app/public/payment_modules/gateway_image/razorpay-logo.png')}}" >
                                        Pay
                                        With
                                        Razorpay</a>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
@push('script_2')
@endpush
