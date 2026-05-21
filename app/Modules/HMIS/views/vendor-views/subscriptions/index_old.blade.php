@extends('layouts.vendor.app')

@section('title', 'Subscriptions')

@push('css_or_js')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        .form-row {
            margin-top: 6px;
        }
    </style>
    <style>
        .check-mark {
            font-weight: bold;
            font-size: 1.2rem;
            color: #00aa6d;
        }

        .cross-mark {
            font-weight: bold;
            font-size: 1.2rem;
            color: #d41a1a;
        }
    </style>
    <link rel="stylesheet" href="{{ asset('assets/admin') }}/css/customize_plan.css">
@endpush

@section('content') 
    <div class="content container-fluid">
        <!-- Page Header -->
        <div class="page-header">
            <h1 class="page-header-title"><i class="tio-filter-list"></i>Subscriptions</h1>

        </div>
        @if (\App\CentralLogics\Helpers::get_store_data()->module->id == 6)
            <div id="planDiv" class=" mb-3 mb-lg-5">
                <!-- Body -->
                <div class="">
                    {{-- {{\App\CentralLogics\Helpers::existingVendorPlan()}} --}}

                    @php
                        $plan = \App\CentralLogics\Helpers::existingVendorPlan();
                        if ($plan) {
                            $subscriptionDet = $plan['subscription']; // details on time of plan purchase
                            $planDet = $plan['planDetails']; // current details
                        }

                    @endphp
                    @if ($plan)
                        <h3>Current Plan </h3>

                        <div class="row">
                            <div class="card col-md-4 shadow shadow-sm mx-2">

                                <div class="card-body  text-center" style="line-height: 2rem;">
                                    <h3 class="card-title  text-center"
                                        style=" margin: 0 auto;
                            width: fit-content;
                            margin-bottom: 1.5rem;">
                                        {{ $planDet->title }}</h3>

                                    {{-- <h6 class="card-subtitle mb-2 text-muted">Plan Description</h6> --}}
                                    <ul style="list-style: none; padding:0;">
                                        <li>
                                            @if (in_array('advanced_leads_manage', json_decode($subscriptionDet->permitted_modules, true)))
                                            <span class="check-mark">✓</span>@else<span class="cross-mark">x</span>
                                            @endif Advanced Leads Management
                                        </li>
                                        <li>
                                            @if (in_array('projects_manage', json_decode($subscriptionDet->permitted_modules, true)))
                                            <span class="check-mark">✓</span>@else<span class="cross-mark">x</span>
                                            @endif Projects Management
                                        </li>
                                        <li>
                                            @if (in_array('quotaiton_manage', json_decode($subscriptionDet->permitted_modules, true)))
                                            <span class="check-mark">✓</span>@else<span class="cross-mark">x</span>
                                            @endif Quotation Management
                                        </li>
                                        {{-- <li>
                                                    @if (in_array('salary_manage', json_decode($subscriptionDet->permitted_modules, true)))
                                                    <span class="check-mark">✓</span>@else<span
                                                            class="cross-mark">x</span>
                                                    @endif Salary Manage
                                                </li>
                                                <li>
                                                    @if (in_array('staff_manage', json_decode($subscriptionDet->permitted_modules, true)))
                                                    <span class="check-mark">✓</span>@else<span
                                                            class="cross-mark">x</span>
                                                    @endif Staff Manage
                                                </li>
                                                <li>
                                                    @if (in_array('att_manage', json_decode($subscriptionDet->permitted_modules, true)))
                                                    <span class="check-mark">✓</span>@else<span
                                                            class="cross-mark">x</span>
                                                    @endif Attendance Manage
                                                </li>
                                                <li>
                                                    @if (in_array('leave_manage', json_decode($subscriptionDet->permitted_modules, true)))
                                                    <span class="check-mark">✓</span>@else<span
                                                            class="cross-mark">x</span>
                                                    @endif Leaves Manage
                                                </li> --}}
                                        <li>
                                            @if (in_array('account_manage', json_decode($subscriptionDet->permitted_modules, true)))
                                            <span class="check-mark">✓</span>@else<span class="cross-mark">x</span>
                                            @endif Accounts Management
                                        </li>
                                        <li>
                                            @if (in_array('billing', json_decode($subscriptionDet->permitted_modules, true)))
                                            <span class="check-mark">✓</span>@else<span class="cross-mark">x</span>
                                            @endif HR Management
                                        </li>
                                        <li>
                                            @if (in_array('billing', json_decode($subscriptionDet->permitted_modules, true)))
                                            <span class="check-mark">✓</span>@else<span class="cross-mark">x</span>
                                            @endif Advanced Billing
                                        </li>
                                        <li>
                                            @if (in_array('inventory_manage', json_decode($subscriptionDet->permitted_modules, true)))
                                            <span class="check-mark">✓</span>@else<span class="cross-mark">x</span>
                                            @endif Inventory Management
                                        </li>
                                    </ul>
                                    @if ($subscriptionDet->variation)

                                        <span style="min-width: 150px;     text-align: left;" class="badge badge-light">
                                            {{ \App\CentralLogics\Helpers::format_currency(_discountedPrice(json_decode($subscriptionDet->variation)->price, json_decode($subscriptionDet->variation)?->discount ?? 0, 'percent')) }}
                                            /
                                            {{ json_decode($subscriptionDet->variation)->duration }} <br>

                                        </span><br>
                                    @else
                                        <a href="#" style="pointer-events: none"
                                            class="btn btn--primary btn-outline-primary">{{ \App\CentralLogics\Helpers::format_currency(_discountedPrice($planDet->price, $planDet->discount, 'percent')) }}
                                        </a> <span class="text-secondary">
                                            @if ($planDet->duration_style == 'standard')
                                                {{ $planDet->standard_duration }}
                                            @else
                                                / {{ $planDet->duration_count . ' ' . $planDet->duration_type }}
                                            @endif
                                        </span>
                                    @endif
                                </div>
                            </div>
                            <div class="card col-md-4 shadow shadow-sm mx-2">

                                <div class="card-body " style="line-height: 2rem;">
                                    <h5>Purchased At:</h5>
                                    <p>{{ $subscriptionDet->updated_at }}</p>
                                    <h5>Expiring At: </h5>
                                    <p>{{ $subscriptionDet->plan_expiry }}</p>
                                </div>
                            </div>
                        </div>
                    @endif
                    <div class="d-flex gap-4 my-3 align-items-center">
                        <h3 class="m-0">Upgrade Plan</h3>

                    </div>
                    <p>Our standard plans are great, but we know every business is unique. Whether you need specific
                        features, custom integrations, or flexible pricing, we're here to build a subscription plan that
                        fits your exact needs. Let's create something tailored just for you.</p>
                    <button class="btn btn-primary" data-toggle="modal" data-target="#subscriptionModal">
                        Want a Customized Plan?
                    </button>


                    <!-- Form -->
                    <form id="subscriptionPlanForm"
                        action="{{ env('APP_MODE') != 'demo' ? route('vendor.profile.buy-plan') : 'javascript:' }}"
                        method="post" enctype="multipart/form-data">
                        @csrf
                        @if (!\App\CentralLogics\Helpers::existingVendorPlan())
                            <div class="alert alert-warning alert-dismissible fade show" role="alert">
                                <strong>No Active Plan Yet!</strong> Purchase One.
                                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                        @endif
                        <div class="row">
                            @foreach ($allPlans as $plan)
                                <div class="rounded col-md-3 shadow shadow-sm mx-2 my-2 px-0 p-2">
                                    <div class="d-flex justify-content-between pt-3">
                                        @if ($plan->discount)
                                            <div class="discount_badge">
                                                {{ $plan->discount }}% Off
                                            </div>
                                        @else
                                            <div></div>
                                        @endif
                                        <input type="radio" style="height:20px; width:20px; vertical-align: middle;"
                                            value="{{ $plan->id }}" name="plan_select" id="">
                                    </div>
                                    <div class=" text-center" style="line-height: 2rem;">
                                        <h3 class="card-title  text-center"
                                            style=" margin: 0 auto;
                            width: fit-content;
                            margin-bottom: 1.5rem;">
                                            {{ $plan->title }}</h3>

                                        {{-- <h6 class="card-subtitle mb-2 text-muted">Plan Description</h6> --}}
                                        <ul style="list-style: none; padding:0;">
                                            <li>
                                                @if ($plan->advanced_leads_manage)
                                                <span class="check-mark">✓</span>@else<span class="cross-mark">x</span>
                                                @endif Advanced Leads Management
                                            </li>
                                            <li>
                                                @if ($plan->projects_manage)
                                                <span class="check-mark">✓</span>@else<span class="cross-mark">x</span>
                                                @endif Project Management
                                            </li>
                                            <li>
                                                @if ($plan->quotaiton_manage)
                                                <span class="check-mark">✓</span>@else<span class="cross-mark">x</span>
                                                @endif Quotation Management
                                            </li>
                                            <li>
                                                @if ($plan->hr_manage)
                                                <span class="check-mark">✓</span>@else<span class="cross-mark">x</span>
                                                @endif HR Management
                                            </li>
                                            <li>
                                                @if ($plan->billing)
                                                <span class="check-mark">✓</span>@else<span class="cross-mark">x</span>
                                                @endif Advanced Billing
                                            </li>
                                            {{-- <li>
                                                        @if ($plan->salary_manage)
                                                        <span class="check-mark">✓</span>@else<span
                                                                class="cross-mark">x</span>
                                                        @endif Salary Manage
                                                    </li>
                                                    <li>
                                                        @if ($plan->staff_manage)
                                                        <span class="check-mark">✓</span>@else<span
                                                                class="cross-mark">x</span>
                                                        @endif Staff Manage
                                                    </li>
                                                    <li>
                                                        @if ($plan->att_manage)
                                                        <span class="check-mark">✓</span>@else<span
                                                                class="cross-mark">x</span>
                                                        @endif Attendance Manage
                                                    </li>
                                                    <li>
                                                        @if ($plan->leave_manage)
                                                        <span class="check-mark">✓</span>@else<span
                                                                class="cross-mark">x</span>
                                                        @endif Leaves Manage
                                                    </li> --}}
                                            <li>
                                                @if ($plan->account_manage)
                                                <span class="check-mark">✓</span>@else<span class="cross-mark">x</span>
                                                @endif Accounts Management
                                            </li>
                                            {{-- <li>
                                                        @if ($plan->service_leads)
                                                        <span class="check-mark">✓</span>@else<span
                                                                class="cross-mark">x</span>
                                                        @endif Service Leads
                                                    </li> --}}
                                            <li>
                                                @if ($plan->inventory_manage)
                                                <span class="check-mark">✓</span>@else<span class="cross-mark">x</span>
                                                @endif Inventory Management
                                            </li>
                                            <li>
                                                @if ($plan->task_manage)
                                                <span class="check-mark">✓</span>@else<span class="cross-mark">x</span>
                                                @endif Task Management
                                            </li>
                                        </ul>

                                        @if ($plan->price_variations && is_array(json_decode($plan->price_variations)))
                                            @foreach (json_decode($plan->price_variations) as $key => $value)
                                                <span style="min-width: 150px;text-align: left;" class="badge badge-light">
                                                    <input class="form-check-input" type="radio"
                                                        name="variation{{ $plan->id }}" id="exampleRadios1"
                                                        value="{{ $value->duration }}" {{ $key == 0 ? 'checked' : '' }}>
                                                    <span class="strikethrough">
                                                        {{ \App\CentralLogics\Helpers::format_currency($value->price) }}
                                                    </span>
                                                    {{ \App\CentralLogics\Helpers::format_currency(_discountedPrice($value->price, $value->discount ?? 0, 'percent')) }}/
                                                    {{ $value->duration }} </span><br>
                                            @endforeach
                                        @else
                                            <a href="#" style="pointer-events: none"
                                                class="btn btn--primary btn-outline-primary p-2"><span
                                                    class="strikethrough">{{ \App\CentralLogics\Helpers::format_currency($plan->price) }}
                                                </span> &nbsp;
                                                &nbsp;<span>{{ \App\CentralLogics\Helpers::format_currency(_discountedPrice($plan->price, $plan->discount, 'percent')) }}
                                                </span></a>
                                            <div class="text-secondary">
                                                {{ $plan->duration_count . ' ' . $plan->duration_type }}</div>
                                        @endif

                                    </div>
                                </div>
                            @endforeach
                        </div>


                        <div class="d-flex justify-content-end">
                            <button type="button"
                                onclick="@if (env('APP_MODE') != 'demo') $('#subscriptionPlanForm').submit()  @else call_demo() @endif"
                                class="btn btn--primary">Buy Now</button>
                        </div>
                    </form>
                    <!-- End Form -->

                </div>
                <!-- End Body -->
            </div>
        @endif
    </div>
    <div class="modal fade" id="subscriptionModal" tabindex="-1" role="dialog"
        aria-labelledby="subscriptionModalLabel" aria-hidden="true">

        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable" role="document">
            <div class="modal-content">

                <!-- HEADER -->
                <div class="modal-header">
                    <h5 class="modal-title" id="subscriptionModalLabel">
                        Customized Subscription Plan Request
                    </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>

                <!-- BODY -->
                <div class="modal-body">

                    <div id="formContent">
                        <p class="text-muted mb-4">
                            Tell us about your needs and we'll create a tailored subscription plan for you.
                        </p>

                        <form id="subscriptionForm" method="post"
                            action="{{ route('vendor.request-subscription-plan') }}">
                            @csrf


                            <!-- FEATURES -->
                            <div class="form-group">
                                <label class="required">Required Features</label>

                                <div class="features-grid" id="featuresGrid">
                                    @foreach ($features as $key => $feature)
                                        <div class="feature-option" data-feature-id="{{ $feature->key }}"
                                            onclick="toggleFeature('{{ $feature->key }}', this)">
                                            <div class="checkbox"></div>
                                            <span>{{ translate('messages.' . $feature->key) }}</span>
                                        </div>
                                    @endforeach
                                </div>

                                <input type="hidden" name="features" id="featuresInput">
                                <div class="invalid-feedback d-block" id="featuresError"></div>
                            </div>

                            <!-- ADDITIONAL REQUIREMENTS -->
                            <div class="form-group">
                                <label>Additional Requirements</label>
                                <textarea class="form-control" name="additional_requirements" id="additionalRequirements" rows="3"
                                    placeholder="Tell us about any specific requirements, integrations, or customizations you need..."></textarea>
                                <div class="invalid-feedback" id="additionalRequirementsError"></div>
                            </div>

                            <!-- SUBMIT BUTTON -->
                            <button type="button" class="btn btn-primary btn-block" id="submitBtn"
                                onclick="handleSubmit()">
                                <span id="submitBtnText">Submit Request</span>
                                <span id="submitBtnSpinner" class="spinner-border spinner-border-sm ml-2"
                                    style="display: none;"></span>
                            </button>

                        </form>
                    </div>

                    <!-- SUCCESS CONTENT -->
                    <div id="successContent" class="text-center" style="display: none;">
                        <div class="display-4 text-success mb-3">✓</div>
                        <h4>Request Submitted!</h4>
                        <p>
                            Thank you for your interest. Our team will review your requirements and contact you within
                            1-2 business days.
                        </p>
                        <button class="btn btn-secondary" data-dismiss="modal">Close</button>
                    </div>

                </div>
            </div>
        </div>
    </div>


@endsection

@push('script_2')
    <script>
        let selectedFeatures = [];

        function toggleFeature(featureId, element) {
            if (selectedFeatures.includes(featureId)) {
                selectedFeatures = selectedFeatures.filter(f => f !== featureId);
                element.classList.remove('selected');
            } else {
                selectedFeatures.push(featureId);
                element.classList.add('selected');
            }

            // Update hidden input
            document.getElementById('featuresInput').value = JSON.stringify(selectedFeatures);

            // Clear error if features are selected
            if (selectedFeatures.length > 0) {
                document.getElementById('featuresInput').classList.remove('is-invalid');
                document.getElementById('featuresError').textContent = '';
            }
        }

        function clearErrors() {
            document.querySelectorAll('.form-control, .form-select').forEach(el => {
                el.classList.remove('is-invalid');
            });
            document.querySelectorAll('.invalid-feedback').forEach(el => {
                el.textContent = '';
            });
        }

        function showError(fieldName, message) {
            const field = document.getElementById(fieldName);
            const errorDiv = document.getElementById(fieldName + 'Error');

            if (field) {
                field.classList.add('is-invalid');
            }
            if (errorDiv) {
                errorDiv.textContent = message;
            }
        }
        async function handleSubmit() {
            clearErrors();
            console.log('fsdf')
            const submitBtn = document.getElementById('submitBtn');
            const submitBtnText = document.getElementById('submitBtnText');
            const submitBtnSpinner = document.getElementById('submitBtnSpinner');

            // Disable button and show spinner
            submitBtn.disabled = true;
            submitBtnSpinner.style.display = 'inline-block';
            submitBtnText.textContent = 'Submitting...'
            //const form = document.getElementById('subscriptionForm');
            //form.submit();



            const formData = new FormData(document.getElementById('subscriptionForm'));
            //  formData.append('features', JSON.stringify(selectedFeatures));
            selectedFeatures.forEach(feature => {
                formData.append('features[]', feature);
            });
            try {
                const response = await fetch('{{ route('vendor.request-subscription-plan') }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                    body: formData
                });

                const data = await response.json();


                // Handle validation errors 
                if (data.errors && data.errors.length > 0) {
                    for (var i = 0; i < data.errors.length; i++) {
                        toastr.error(data.errors[i].message, {
                            CloseButton: true,
                            ProgressBar: true
                        });
                    }
                } else if (data.status) {
                    document.getElementById('formContent').style.display = 'none';
                    document.getElementById('successContent').style.display = 'block';
                } else {
                    for (var i = 0; i < data.errors.length; i++) {
                    toastr.error(data.errors[i].message, {
                            CloseButton: true,
                            ProgressBar: true
                        });
                    }
                }

            } catch (error) {
                console.error('Error:', error);
                alert('An error occurred. Please try again.');
            } finally {
                // Re-enable button and hide spinner
                submitBtn.disabled = false;
                submitBtnSpinner.style.display = 'none';
                submitBtnText.textContent = 'Submit Request';
            }

        };

        function resetForm() {
            document.getElementById('subscriptionForm').reset();
            selectedFeatures = [];
            document.querySelectorAll('.feature-option').forEach(option => {
                option.classList.remove('selected');
            });
            document.getElementById('featuresInput').value = '';
            clearErrors();

            document.getElementById('formContent').style.display = 'block';
            document.getElementById('successContent').style.display = 'none';
        }

        // Reset form when modal is closed
        document.getElementById('subscriptionModal').addEventListener('hidden.bs.modal', function() {
            resetForm();
        });
    </script>

    @if (request('flag') && request('flag') == 'success')
        <script>
            $(document).ready(function() {
                toastr.success('Plan purchased successfully!', 'Success');

                // Remove 'flag' and 'token' from the URL
                const url = new URL(window.location);
                url.searchParams.delete('flag'); // Remove flag
                url.searchParams.delete('token'); // Remove token

                // Update the URL without reloading the page
                window.history.replaceState({}, '', url);
            });
        </script>
    @endif
@endpush
