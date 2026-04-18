@extends('front-views.layout')

@section('title', $store->name)

@section('content')
    <!-- FULL STORE CONTENT FOR TEMPLATE 16 -->
    {{-- Existing content or placeholder --}}
    
    <!-- STORE REVIEW SECTION -->
    <section class="reviews-section mt-5 py-5 bg-light">
        <div class="container">
            <div class="row justify-content-center"> 
                <div class="col-lg-8">
                    <div class="text-center mb-5">
                        <h2 class="display-5 fw-bold mb-3">Share Your Experience</h2>
                        <p class="lead text-muted mb-4">Help others by sharing your review of this store</p>
                        <button class="btn btn-primary btn-lg px-5 py-3 shadow-lg" data-bs-toggle="modal" data-bs-target="#storeReviewModal{{ $store['id'] }}">
                            <i class="fas fa-star me-2"></i> <strong>Write Review</strong>
                        </button>
                    </div>
                    @include('front-views.partials._store-review-form', ['store' => $store])
                </div>
            </div>
        </div>
    </section>

@include('front-views.partials._appointment_booking')
@include('front-views.partials._hospital_booking_modal')
@endsection
