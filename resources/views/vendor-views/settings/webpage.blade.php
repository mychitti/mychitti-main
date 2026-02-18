 @extends('layouts.vendor.app')

 @section('title', 'Webpage Settings')

 @push('css_or_js')
     <meta name="csrf-token" content="{{ csrf_token() }}">
     <style>
         .settings-wrapper {
             font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
             min-height: 100vh;
             background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
             padding: 60px 20px;
             position: relative;
             overflow: hidden;
         }

         .settings-wrapper::before {
             content: '';
             position: absolute;
             top: -50%;
             right: -50%;
             width: 100%;
             height: 100%;
             background: radial-gradient(circle, rgba(99, 102, 241, 0.1) 0%, transparent 70%);
             animation: pulse 15s ease-in-out infinite;
         }

         @keyframes pulse {

             0%,
             100% {
                 transform: scale(1);
                 opacity: 0.5;
             }

             50% {
                 transform: scale(1.1);
                 opacity: 0.8;
             }
         }

         .settings-card {
             position: relative;
             max-width: 850px;
             margin: 0 auto;
             background: rgba(255, 255, 255, 0.98);
             backdrop-filter: blur(20px);
             border-radius: 24px;
             padding: 48px;
             box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5),
                 0 0 0 1px rgba(255, 255, 255, 0.1);
             border: 1px solid rgba(255, 255, 255, 0.2);
         }

         .settings-header {
             margin-bottom: 40px;
             padding-bottom: 24px;
             border-bottom: 2px solid #e5e7eb;
         }

         .settings-title {
             font-size: 32px;
             font-weight: 700;
             background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 50%, #d946ef 100%);
             -webkit-background-clip: text;
             -webkit-text-fill-color: transparent;
             background-clip: text;
             margin: 0 0 8px 0;
             letter-spacing: -0.5px;
         }

         .settings-subtitle {
             font-size: 15px;
             color: #64748b;
             margin: 0;
         }

         . {
             margin-bottom: 28px;
             position: relative;
         }

         .settings-label {
             display: flex;
             align-items: center;
             gap: 8px;
             font-size: 13px;
             font-weight: 600;
             color: #475569;
             margin-top: 10px;
             text-transform: uppercase;
             letter-spacing: 0.5px;
         }

         .settings-icon {
             font-size: 16px;
         }

         {{-- .form-control,
         .settings-textarea {
             width: 100%;
             padding: 14px 18px;
             font-size: 15px;
             line-height: 1.6;
             color: #0f172a;
             background: #f8fafc;
             border: 2px solid #e2e8f0;
             border-radius: 12px;
             transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
             font-family: inherit;
         } --}} .form-control:hover,
         .settings-textarea:hover {
             border-color: #cbd5e1;
             background: #ffffff;
         }

         .form-control:focus,
         .settings-textarea:focus {
             outline: none;
             border-color: #6366f1;
             background: #ffffff;
             box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1),
                 0 4px 6px -1px rgba(0, 0, 0, 0.1);
             transform: translateY(-1px);
         }

         .form-control::placeholder,
         .settings-textarea::placeholder {
             color: #94a3b8;
         }

         .settings-textarea {
             resize: vertical;
             min-height: 110px;
         }

         .settings-phone-section {
             margin-bottom: 28px;
             background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
             padding: 24px;
             border-radius: 16px;
             border: 1px solid #e2e8f0;
         }

         .settings-phone-header {
             display: flex;
             justify-content: space-between;
             align-items: center;
             margin-bottom: 16px;
         }

         .settings-phone-header .settings-label {
             margin-bottom: 0;
         }

         .settings-phone-item {
             display: flex;
             gap: 12px;
             margin-bottom: 12px;
             animation: slideIn 0.4s cubic-bezier(0.4, 0, 0.2, 1);
         }

         @keyframes slideIn {
             from {
                 opacity: 0;
                 transform: translateX(-20px);
             }

             to {
                 opacity: 1;
                 transform: translateX(0);
             }
         }

         .settings-phone-item .form-control {
             flex: 1;
             background: #ffffff;
         }

         .settings-btn {
             display: inline-flex;
             align-items: center;
             justify-content: center;
             gap: 8px;
             padding: 11px 20px;
             font-size: 14px;
             font-weight: 600;
             line-height: 1.5;
             text-align: center;
             border: none;
             border-radius: 10px;
             cursor: pointer;
             transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
             position: relative;
             overflow: hidden;
         }

         .settings-btn::before {
             content: '';
             position: absolute;
             top: 50%;
             left: 50%;
             width: 0;
             height: 0;
             border-radius: 50%;
             background: rgba(255, 255, 255, 0.3);
             transform: translate(-50%, -50%);
             transition: width 0.6s, height 0.6s;
         }

         .settings-btn:hover::before {
             width: 300px;
             height: 300px;
         }

         .settings-btn span {
             position: relative;
             z-index: 1;
         }

         .settings-btn-primary {
             color: #ffffff;
             background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
             box-shadow: 0 4px 12px rgba(99, 102, 241, 0.4);
         }

         .settings-btn-primary:hover {
             transform: translateY(-2px);
             box-shadow: 0 6px 20px rgba(99, 102, 241, 0.5);
         }

         .settings-btn-primary:active {
             transform: translateY(0);
         }

         .settings-btn-danger {
             color: #ffffff;
             background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
             padding: 13px 20px;
             box-shadow: 0 4px 12px rgba(239, 68, 68, 0.4);
         }

         .settings-btn-danger:hover {
             transform: translateY(-2px);
             box-shadow: 0 6px 20px rgba(239, 68, 68, 0.5);
         }

         .settings-btn-danger:active {
             transform: translateY(0);
         }

         .settings-btn-success {
             width: 100%;
             padding: 16px 24px;
             font-size: 16px;
             color: #ffffff;
             background: linear-gradient(135deg, #10b981 0%, #059669 100%);
             margin-top: 16px;
             box-shadow: 0 10px 25px rgba(16, 185, 129, 0.3);
         }

         .settings-btn-success:hover {
             transform: translateY(-3px);
             box-shadow: 0 15px 35px rgba(16, 185, 129, 0.4);
         }

         .settings-btn-success:active {
             transform: translateY(-1px);
         }

         @media (max-width: 640px) {
             .settings-wrapper {
                 padding: 30px 15px;
             }

             .settings-card {
                 padding: 32px 24px;
             }

             .settings-title {
                 font-size: 26px;
             }

             .settings-phone-item {
                 flex-direction: column;
             }

             .settings-btn-danger {
                 width: 100%;
             }
         }
     </style>
 @endpush

 @section('content')
     <div class="content container-fluid">
         <!-- Page Header -->
         <div class="page-header">
             <h1 class="page-header-title"><i class="tio-filter-list"></i>Webpage Settings</h1>
         </div>
     </div>
     @include('layouts/vendor/partials/webpage/_nav')
     <div class="row g-0  p-3 d-flex ">
         @if ($tab == 'basic-info')
             @include('vendor-views.settings.webpage.basic_info')
         @elseif($tab == 'banners')
             @include('vendor-views.settings.webpage.banner')
         @elseif($tab == 'offer-banners')
             @include('vendor-views.settings.webpage.offer_banner')
         @elseif($tab == 'gallery')
             @include('vendor-views.settings.webpage.gallery')
         @elseif($tab == 'tnc')
             @include('vendor-views.settings.webpage.tnc')
         @elseif($tab == 'privacy-policy')
             @include('vendor-views.settings.webpage.privacy_policy')
         @elseif($tab == 'about-us')
             @include('vendor-views.settings.webpage.about_us')
         @elseif($tab == 'mychitti-services')
             @include('vendor-views.settings.webpage.mychitti_services')
         @elseif($tab == 'my-services')
             @include('vendor-views.settings.webpage.my_services')
         @elseif($tab == 'webpage-templates')
             @include('vendor-views.settings.webpage.templates')
         @elseif($tab == 'domain-setup')
             @include('vendor-views.settings.webpage.domain_setup')
         @elseif($tab == 'third-party')
             @include('vendor-views.settings.webpage.third_party')
         @endif
     </div>
 @endsection

 @push('script_2')
     @if ($tab == 'basic-info')
         @include('vendor-views.settings.webpage_js.basic_info_js')
     @elseif($tab == 'banners')
         @include('vendor-views.settings.webpage_js.banner_js')
     @elseif($tab == 'about-us')
         @include('vendor-views.settings.webpage_js.about_us_js')
     @elseif($tab == 'tnc')
         @include('vendor-views.settings.webpage_js.tnc_js')
     @elseif($tab == 'gallery')
         @include('vendor-views.settings.webpage_js.gallery_js')
     @elseif($tab == 'webpage-templates')
         @include('vendor-views.settings.webpage_js.templates_js')
     @elseif($tab == 'mychitti-services')
         @include('vendor-views.settings.webpage_js.mychitti_services_js')
     @elseif($tab == 'my-services')
         @include('vendor-views.settings.webpage_js.my_services_js')
     @elseif($tab == 'privacy-policy')
         @include('vendor-views.settings.webpage_js.privacy_policy_js')
     @elseif($tab == 'domain-setup')
         @include('vendor-views.settings.webpage_js.domain_setup_js')
     @elseif($tab == 'third-party')
         @include('vendor-views.settings.webpage_js.third_party_js')
     @endif
 @endpush
