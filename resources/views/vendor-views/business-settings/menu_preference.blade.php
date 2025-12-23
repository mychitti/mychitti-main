@extends('layouts.vendor.app')

@section('title', 'Menu Preference')

@push('css_or_js')
    @include('vendor-views/ck_editor_form')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        .hover-shadow:hover {
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.15);
        }

        .transition {
            transition: 0.3s ease-in-out;
        }

        .cursor-pointer {
            cursor: pointer;
        }

        .menu_card.active {
            background: color-mix(in srgb, var(--primary) 10%, white);

            border: 2px solid var(--primary) !important;
        }

        .select2-container--default .select2-selection--multiple {
            height: fit-content !important;
        }
    </style>
@endpush

@section('content')
    <div class="content">
        <div class="page-header">
            <div class="row align-items-center py-2">
                <div class="col-sm mb-2 mb-sm-0">
                    <div class="d-flex align-items-center">
                        <h1>Menu Preference</h1>
                    </div>

                </div>
            </div>
        </div>
        <div class=" row px-4">
            <div class="col-md-8 card shadow-sm border-0 rounded-3 overflow-hidden">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0 text-white">Customize Your Sidebar Menu</h5>
                </div>

                <div class="card-body">
                    <p class="text-muted mb-4">Select the menu items you want to show in your vendor panel. Unchecked
                        items
                        will
                        be hidden.</p>

                    <form action="{{ route('vendor.menu_preference_save') }}" method="POST">
                        @csrf

                        <div class="row g-3">
                            @php
                                $groupedMenus = $full_menu->groupBy(function ($item) {
                                    return $item->group ?? 'other';
                                });
                            @endphp


                            @foreach ($groupedMenus as $groupName => $menus)
                                <div class="col-12 mb-4">
                                    <div class="border rounded p-3">

                                        {{-- Group Title --}}
                                        @if ($groupName !== 'other')
                                            <h6 class="mb-3 text-uppercase font-weight-bold">
                                                {{ str_replace('_', ' ', $groupName) }}
                                            </h6>
                                        @endif

                                        <div class="row">
                                            @foreach ($menus as $key => $value)
                                                <div class="col-md-3 col-sm-6 mb-2">
                                                    @php
                                                        $store_business_type = \App\CentralLogics\Helpers::get_store_data()
                                                            ->business_type;
                                                    @endphp

                                                    <label
                                                        @if ($value->business_type != 'all' && $value->business_type != strtolower($store_business_type)) disabled
                                data-toggle="popover"
                                title="Module Unavailable"
                                data-content="{{ ucfirst($value->name) }} module is only available for business type {{ strtoupper($value->business_type) }}"
                            @elseif($value->under_development)
                                disabled
                                data-toggle="popover"
                                title="Under Development"
                                data-content="Module under development. We will release it soon." @endif
                                                        class="w-100 d-flex align-items-center p-3 border rounded shadow-sm hover-shadow transition cursor-pointer
                            menu_card menu_card_{{ $value->slug }} {{ in_array($value->slug, $selectedMenus) ? 'active' : '' }}"
                                                        style="min-height: 60px;">
                                                        <input @if (
                                                            ($value->business_type != 'all' && $value->business_type != strtolower($store_business_type)) ||
                                                                $value->under_development) disabled @endif
                                                            type="checkbox" name="menu[]" value="{{ $value->slug }}"
                                                            class="mr-2 menu_check"
                                                            {{ in_array($value->slug, $selectedMenus) ? 'checked' : '' }}>
                                                        <span>{{ $value->name }}</span>
                                                    </label>
                                                </div>
                                            @endforeach
                                        </div>

                                    </div>
                                </div>
                            @endforeach

                        </div>


                        <div class="mt-4 d-flex justify-content-end">
                            <button type="submit" class="btn btn-primary px-4 py-2">Save Sidebar Menu</button>
                        </div>
                    </form>
                </div>
            </div>
            <div class=" col-md-4 card shadow-sm border-0 rounded-3 overflow-hidden">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0 text-white">Quick Action Bar</h5>
                </div>

                <div class="card-body">
                    <p class="text-muted mb-4">Select the menu items you want to show in your header. </p>

                    <form action="{{ route('vendor.business-settings.quick_actions_save') }}" method="POST">
                        @csrf

                        <select name="menu[]" id="" class="form-control js-select2-custom_limit"
                            multiple="multiple">
                            @foreach ($full_quick_actions as $key => $value)
                                <option {{ in_array($value->slug, $selectedQuickActions) ? 'selected' : '' }}
                                    value="{{ $value->slug }}">{{ $value->name }}</option>
                            @endforeach
                        </select>

                        {{-- <div class="row g-3">
                            @foreach ($full_menu as $key => $value)
                                <div class="col-md-3 col-sm-6 ">
                                    <label
                                        class="w-100 d-flex align-items-center p-3 border rounded shadow-sm hover-shadow transition cursor-pointer menu_card menu_card_{{ $value->slug }} {{ in_array($value->slug, $selectedMenus) ? 'active' : '' }}"
                                        style="min-height: 60px;">
                                        <input type="checkbox" data-key="{{ $value->slug }}" name="menu[]"
                                            value="{{ $value->slug }}" class=" mr-2 menu_check "
                                            {{ in_array($value->slug, $selectedMenus) ? 'checked' : '' }}>
                                        <span>{{ $value->name }}</span>
                                    </label>
                                </div>
                            @endforeach
                        </div> --}}

                        <div class="mt-4 d-flex justify-content-end">
                            <button type="submit" class="btn btn-primary px-4 py-2">Save Quick Actions</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script_2')
    <script>
        $(".menu_check").on('change', function() {
            var key = $(this).attr('data-key');
            if ($(this).prop('checked') == true) {
                $(".menu_card_" + key).addClass("active");
            } else {
                $(".menu_card_" + key).removeClass("active");
            }
        })
        $(document).ready(function() {
            $('.js-select2-custom_limit').select2({
                placeholder: "Select menu items",
                maximumSelectionLength: 5
            });
        });
    </script>
@endpush
