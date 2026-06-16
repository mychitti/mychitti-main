@if (strtolower(\App\CentralLogics\Helpers::get_store_data()->business_type ?? '') == 'hospital')
    <style>
        #header {
            background-color: #0b2545 !important;
            border-bottom: 1px solid rgba(255, 255, 255, 0.12) !important;
        }
        #header .page-header-title,
        #header .quick_action, 
        #header .quick_action2,
        #header .card-title, 
        #header .card-text,
        #header .navbar-nav-wrap-content-left i,
        #header .navbar-nav-wrap-content-right i, 
        #header .navbar-nav-wrap-content-right .tio-notifications {
            color: #ffffff !important;
        }
        #header .navbar-nav-wrap-content-left button i {
            color: #ffffff !important;
        }
        #header .badge-soft-primary {
            background-color: rgba(255, 255, 255, 0.15) !important;
            color: #ffffff !important;
            border: 1px solid rgba(255, 255, 255, 0.2) !important;
        }
        #header .badge-soft-primary:hover {
            background-color: rgba(255, 255, 255, 0.25) !important;
        }
        #header .btn--primary {
            background-color: rgba(255, 255, 255, 0.15) !important;
            border-color: rgba(255, 255, 255, 0.2) !important;
            color: #ffffff !important;
        }
        #header .btn--primary:hover {
            background-color: rgba(255, 255, 255, 0.25) !important;
        }
    </style>
@endif

<style>
.quick_action{
    font-size: 14px; color: black;padding: 9px;
}
.quick_action2{
       font-size: 12px;
    color: black;
    padding: 3px;
    border: 1px solid #e5e5e5;
    font-weight: bold;
}
.desktop_linke{
    display: block;

}
.mobil_linke{
    display: none;
}
@media (max-width: 710px) {
.desktop_linke{
    display: none;

}
.mobil_linke{
          display: inline-block;
}
}
</style>

<div id="headerMain" class="d-none">
    <header id="header"
            class="navbar navbar-expand-lg navbar-fixed navbar-height navbar-flush navbar-container navbar-bordered">
        <div class="navbar-nav-wrap">
            <div class="navbar-nav-wrap-content-left  d-xl-none">
                <!-- Navbar Vertical Toggle -->
                <button type="button" class="js-navbar-vertical-aside-toggle-invoker close mr-3">
                    <i class="fa fa-bars navbar-vertical-aside-toggle-short-align" style="font-size: 21px;color: black;"  data-toggle="tooltip" data-placement="right" title="Collapse"></i>
                    <i class="fa fa-bars navbar-vertical-aside-toggle-full-align" style="font-size: 21px;color: black;"
                       data-template='<div class="tooltip d-none d-sm-block" role="tooltip"><div class="arrow"></div><div class="tooltip-inner"></div></div>'
                       data-toggle="tooltip" data-placement="right" title="Expand"></i>
                </button>
                
                   <a class="" data-img="{{asset('storage/store/') . '/' . \App\CentralLogics\Helpers::get_store_data()->logo}}" href="{{ route('vendor.dashboard') }}" aria-label="Front">
                    <img class="onerror-image" style="    height: 40px;
    margin-left: 10px;"
                        data-onerror-image="{{ asset('public/assets/admin/img/160x160/img2.jpg') }}"
                        src="{{ \App\CentralLogics\Helpers::onerror_image_helper(\App\CentralLogics\Helpers::get_store_data()->logo, asset('storage/store/') . '/' . \App\CentralLogics\Helpers::get_store_data()->logo, asset('public/assets/admin/img/160x160/img2.jpg'), 'store/') }}"
                        alt="Logo">
                </a>
                <!-- End Navbar Vertical Toggle -->
            </div>
            @if(auth('vendor')->check())
            @if(Route::currentRouteName() == 'vendor.dashboard')
                 <h1 class="page-header-title d-none d-md-block">{{ translate('messages.welcome') }},
                {{ auth('vendor')->user()->f_name }}.</h1>
              
            @else
            <div class="desktop_linke">
                <div class="d-flex gap-2  ">
                    @if (count(_quickActions()))
                        @foreach (_quickActions() as $key => $value)
                            @if ($value->route == 'vendor.customer.list' && hasPermission('client_manage', 'add'))
                                <button type="button" class="badge badge-soft-primary quick_action add-customer-btn " 
                                    data-toggle="modal" data-target="#addCustomerModal">
                                    {{ $value->name }}
                                </button>
                            @elseif($value->group == NULL || hasPermission($value->group, 'add'))
                                <a href="{{ route($value->route, ['add']) }}" class="badge badge-soft-primary quick_action"
                                >{{ $value->name }}</a>
                            @endif
                        @endforeach
                        <a href="{{ route('vendor.menu_preference') }}"><i
                                class="tio-edit"></i></a>
                    @elseif(!_isHospital())
                        <a class="btn btn--primary"
                            href="{{ route('vendor.menu_preference') }}">Add Quick Actions</a>
                    @endif
                </div>
            </div>
            
            @endif
            @endif
              
            <div class="navbar-nav-wrap-content-right">
                <!-- Navbar --> 
                <ul class="navbar-nav align-items-center flex-row">
                @if(\App\CentralLogics\Helpers::employee_module_permission_check('store_availability'))
              
                   <li class="d-flex align-items-center mr-2 flex-column">
                  
                   <label class="switch toggle-switch-lg m-0"> 
                        <input type="checkbox" class="toggle-switch-input restaurant-open-status"
                            {{\App\CentralLogics\Helpers::get_store_data()->active ?'checked':''}}>
                        <span class="toggle-switch-label">
                            <span class="toggle-switch-indicator"></span>
                        </span>
                    </label>
                   @if(\App\CentralLogics\Helpers::get_store_data()->active)
                   <span class="text-success" style="    font-size: 10px;">Open</span> 
                   @else
                   <span class="text-danger" style="    font-size: 10px;">Closed</span>
                    @endif
                   </li>
                  
                   @endif
                    @if(\App\CentralLogics\Helpers::employee_module_permission_check('notifications'))
                        <li class="nav-item max-sm-m-0">
                        <i  style="font-size: 1.5em;" class="tio-notifications"><a href="{{ route('vendor.notifications') }}" class="position-relative"><span class="badge badge-danger notif_count_badge" style="position: absolute;
                            right: -9px;
                            top: -10px;
                            border-radius: 50%;
                            padding: 3px 8px;">@php print_r(_unreadNotificationCount()) @endphp</i>
                            <div class="hs-unfold">
                                <div>
                                    @php($local = session()->has('vendor_local')?session('vendor_local'):null)
                                    @php($lang = \App\Models\BusinessSetting::where('key', 'system_language')->first())
                                    @if ($lang)
                                    <!-- <div
                                        class="topbar-text dropdown disable-autohide text-capitalize d-flex">
                                        <a class="topbar-link dropdown-toggle d-flex align-items-center title-color"
                                        href="#" data-toggle="dropdown">
                                                @foreach(json_decode($lang['value'],true) as $data)
                                                    @if($data['code']==$local)
                                                        <i class="tio-globe"></i> {{$data['code']}}

                                                    @elseif(!$local &&  $data['default'] == true)
                                                        <i class="tio-globe"></i> {{$data['code']}}
                                                    @endif
                                                @endforeach
                                        </a>
                                        <ul class="dropdown-menu lang-menu">
                                            @foreach(json_decode($lang['value'],true) as $key =>$data)
                                                @if($data['status']==1)
                                                    <li>
                                                        <a class="dropdown-item py-1"
                                                            href="{{route('vendor.lang',[$data['code']])}}">
                                                            <span class="text-capitalize">{{$data['code']}}</span>
                                                        </a>
                                                    </li>
                                                @endif
                                            @endforeach
                                        </ul>
                                    </div> -->
                                    @endif
                                </div>
                            </div>
                        </li>
                    @endif
                    @if(\App\CentralLogics\Helpers::get_store_data()->module_id == 5)
                    <li class="nav-item d-none d-sm-inline-block mr-4">
                        <!-- Notification -->
                        <div class="hs-unfold">
                            <a class="js-hs-unfold-invoker btn btn-icon btn-soft-secondary rounded-circle"
                               href="{{route('vendor.message.list')}}">
                                <i class="tio-messages-outlined"></i>
                                @php($message=\App\Models\Conversation::whereUser(\App\CentralLogics\Helpers::get_loggedin_user()->id)->where('unread_message_count','>','0')->count())
                                @if($message!=0)
                                    <span class="btn-status btn-sm-status btn-status-danger"></span>
                                @endif
                            </a>x
                        </div>
                        <!-- End Notification -->
                    </li>
                    @endif

                    <li class="nav-item">
                        <!-- Account -->
                        <div class="hs-unfold">
                            <a class="js-hs-unfold-invoker navbar-dropdown-account-wrapper" href="javascript:;"
                               data-hs-unfold-options='{
                                     "target": "#accountNavbarDropdown",
                                     "type": "css-animation"
                                   }'>
                                <div class="cmn--media right-dropdown-icon d-flex align-items-center">
                                    <div class="media-body pl-0 pr-2">
                                        <span class="card-title h5 text-right">
                                            {{\App\CentralLogics\Helpers::get_loggedin_user()->f_name}}
                                            {{\App\CentralLogics\Helpers::get_loggedin_user()->l_name}}
                                        </span>
                                        <span class="card-text">{{\App\CentralLogics\Helpers::get_loggedin_user()->email}}</span>
                                    </div>
                                    {{-- {{\App\CentralLogics\Helpers::get_loggedin_user()}} --}}
                                    <div class="avatar avatar-sm avatar-circle">
                                        <img class="avatar-img  onerror-image"  data-onerror-image="{{asset('public/assets/admin/img/160x160/img1.jpg')}}"
                                        src="{{\App\CentralLogics\Helpers::onerror_image_helper(\App\CentralLogics\Helpers::get_loggedin_user()->image, asset('storage/app/public/vendor/').'/'.\App\CentralLogics\Helpers::get_loggedin_user()->image, asset('public/assets/admin/img/160x160/img1.jpg'), 'vendor/') }}"
                                            alt="Image Description">
                                        <span class="avatar-status avatar-sm-status avatar-status-success"></span>
                                    </div>
                                </div>
                            </a>

                            <div id="accountNavbarDropdown"
                                 class="hs-unfold-content dropdown-unfold dropdown-menu dropdown-menu-right navbar-dropdown-menu navbar-dropdown-account min--240">
                                <div class="dropdown-item-text">
                                @if(auth('vendor_employee')->check())
                                <a href="{{route('vendor.profile.view')}}" class="media align-items-center">
                                        <div class="avatar avatar-sm avatar-circle mr-2">
                                            <img class="avatar-img  onerror-image"  data-onerror-image="{{asset('public/assets/admin/img/160x160/img1.jpg')}}"
                                            src="{{\App\CentralLogics\Helpers::onerror_image_helper(\App\CentralLogics\Helpers::get_loggedin_user()->image, asset('storage/app/public/vendor/').'/'.\App\CentralLogics\Helpers::get_loggedin_user()->image, asset('public/assets/admin/img/160x160/img1.jpg'), 'vendor/') }}"
                                                 alt="Owner image">
                                        </div>
                                        <div class="media-body">
                                            <span class=" h5">{{\App\CentralLogics\Helpers::get_loggedin_user()->f_name}}</span>
                                            <span class="">{{\App\CentralLogics\Helpers::get_loggedin_user()->email}}</span>
                                        </div>
                                    </a>
                                @else
                                    <div class="media align-items-center">
                                        <div class="avatar avatar-sm avatar-circle mr-2">
                                            <img class="avatar-img  onerror-image"  data-onerror-image="{{asset('public/assets/admin/img/160x160/img1.jpg')}}"
                                            src="{{\App\CentralLogics\Helpers::onerror_image_helper(\App\CentralLogics\Helpers::get_loggedin_user()->image, asset('storage/app/public/vendor/').'/'.\App\CentralLogics\Helpers::get_loggedin_user()->image, asset('public/assets/admin/img/160x160/img1.jpg'), 'vendor/') }}"
                                                 alt="Owner image">
                                        </div>
                                        <div class="media-body">
                                            <span class=" h5 ">{{\App\CentralLogics\Helpers::get_loggedin_user()->f_name}}</span>
                                            <span class="">{{\App\CentralLogics\Helpers::get_loggedin_user()->email}}</span>
                                        </div>
                                    </div>
                                </div>
                                @endif

                                <div class="dropdown-divider"></div>

                                {{-- <a class="dropdown-item" href="{{route('vendor.profile.view')}}">
                                    <span class="text-truncate pr-2" title="Settings">{{translate('messages.settings')}}</span>
                                </a> --}}


                                <a class="dropdown-item log-out" >
                                    <span class="text-truncate pr-2 log-out" title="Sign out">{{translate('messages.sign_out')}}</span>
                                </a>
                            </div>
                        </div>
                        <!-- End Account -->
                    </li>
                </ul>
                <!-- End Navbar -->
            </div>
          
            <!-- End Secondary Content -->
        </div>
    </header>
</div>
<div id="headerFluid" class="d-none"></div>
<div id="headerDouble" class="d-none"></div>
<?php
$wallet = \App\Models\StoreWallet::where('vendor_id',\App\CentralLogics\Helpers::get_vendor_id())->first();
$Payable_Balance = $wallet?->collected_cash  > 0 ? 1: 0;

$cash_in_hand_overflow=  \App\Models\BusinessSetting::where('key' ,'cash_in_hand_overflow_store')->first()?->value;
$cash_in_hand_overflow_store_amount =  \App\Models\BusinessSetting::where('key' ,'cash_in_hand_overflow_store_amount')->first()?->value;
$val= (string) ($cash_in_hand_overflow_store_amount - (($cash_in_hand_overflow_store_amount * 10)/100));
?>

@if ($Payable_Balance == 1 &&  $cash_in_hand_overflow &&  $wallet?->balance < 0 &&  $val <=  abs($wallet?->collected_cash)  )
    <div class="alert __alert-2 alert-warning m-0 py-1 px-2" role="alert">
        <img class="rounded mr-1"  width="25" src="{{ asset('/public/assets/admin/img/header_warning.png') }}" alt="">
        <div class="cont">
            <h4 class="m-0">{{ translate('Attention_Please') }} </h4>
            {{ translate('The_Cash_in_Hand_amount_is_about_to_exceed_the_limit._Please_pay_the_due_amount._If_the_limit_exceeds,_your_account_will_be_suspended.') }}
        </div>
    </div>
@endif

@if ($Payable_Balance == 1 &&  $cash_in_hand_overflow &&  $wallet?->balance < 0 &&  $cash_in_hand_overflow_store_amount < $wallet?->collected_cash)
    <div class="alert __alert-2 alert-warning m-0 py-1 px-2" role="alert">
        <img class="mr-1"  width="25" src="{{ asset('/public/assets/admin/img/header_warning.png') }}" alt="">
        <div class="cont">
            <h4 class="m-0">{{ translate('Attention_Please') }} </h4>{{ translate('The_Cash_in_Hand_amount_limit_is_exceeded._Your_account_is_now_suspended._Please_pay_the_due_amount_to_receive_new_order_requests_again.') }}<a href="{{ route('vendor.wallet.index') }}" class="alert-link"> &nbsp; {{ translate('Pay_the_due') }}</a>
        </div>
    </div>
@endif

<script>
   document.addEventListener('DOMContentLoaded', function () {
            $(document).on('click', '.log-out', function () {
            Swal.fire({
            title: '{{ translate('Do you want to logout?') }}',
            showDenyButton: true,
            showCancelButton: true,
            confirmButtonColor: '#FC6A57',
            cancelButtonColor: '#363636',
            confirmButtonText: `{{ translate('yes')}}`,
            cancelButtonText: `{{ translate('Do_not_Logout')}}`,
            }).then((result) => {
            if (result.value) {
            location.href='{{route('vendor_logout')}}';
            } else{
            Swal.fire('{{ translate('messages.canceled') }}', '', 'info')
            }
        })
});
});

      
</script>
