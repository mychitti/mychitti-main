 @extends('layouts.admin.app')

 @section('title', 'Theme Settings')

 @push('css_or_js')
     <meta name="csrf-token" content="{{ csrf_token() }}">
     <style>
         .theme-customizer-body {
             font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
             padding: 2rem;
             min-height: 100vh;
             transition: all 0.3s ease;
         }



         .theme-customizer-title {
             font-size: 2.5rem;
             margin-bottom: 0.5rem;
         }

         .theme-customizer-subtitle {
             opacity: 0.7;
             margin-bottom: 2rem;
         }

         .theme-customizer-card {
             background: white;
             border-radius: 12px;
             box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
             padding: 2rem;
             margin-bottom: 1.5rem;
         }

         .theme-customizer-color-control {
             margin-bottom: 1.5rem;
         }

         .theme-customizer-color-control:last-of-type {
             margin-bottom: 0;
         }

         .theme-customizer-label {
             display: block;
             font-weight: 500;
             margin-bottom: 0.5rem;
             color: #374151;
             font-size: 0.875rem;
         }

         .theme-customizer-input-group {
             display: flex;
             gap: 1rem;
             align-items: center;
         }

         .theme-customizer-color-picker {
             height: 48px;
             width: 80px;
             border: 2px solid #d1d5db;
             border-radius: 8px;
             cursor: pointer;
         }

         .theme-customizer-text-input {
             flex: 1;
             padding: 0.75rem 1rem;
             border: 1px solid #d1d5db;
             border-radius: 8px;
             font-family: 'Courier New', monospace;
             font-size: 0.875rem;
         }

         .theme-customizer-apply-btn {
             width: 100%;
             margin-top: 1.5rem;
             padding: 0.875rem 1.5rem;
             color: white;
             border: none;
             border-radius: 8px;
             font-weight: 600;
             font-size: 1rem;
             cursor: pointer;
             transition: opacity 0.2s;
         }

         .theme-customizer-apply-btn:hover {
             opacity: 0.9;
         }

         .theme-customizer-preview-title {
             font-size: 1.25rem;
             margin-bottom: 1rem;
             color: #1f2937;
         }

         .theme-customizer-preview-btn {
             padding: 0.75rem 1.5rem;
             color: white;
             border: none;
             border-radius: 8px;
             font-weight: 500;
             margin-bottom: 1rem;
             cursor: pointer;
         }

         .theme-customizer-preview-text {
             color: #374151;
             line-height: 1.6;
         }
     </style>
 @endpush

 @section('content')
     <div class="content container-fluid">
         <!-- Page Header -->
         <div class="page-header">
             <h1 class="page-header-title mr-3">
                 <span class="page-header-icon">
                     <img src="{{ asset('public/assets/admin/img/business.png') }}" class="w--26" alt="">
                 </span>
                 <span>
                     {{ translate('messages.business_settings') }}
                 </span>
             </h1>
             @include('admin-views.business-settings.partials.nav-menu')
         </div>
         <!-- End Page Header -->



         <div class="card  ">
             <!-- Header -->
             <div class="card-header py-2">
                 <div class="search--button-wrapper d-flex justify-content-between">
                     <h5 class="card-title">Theme Color Settings</h5>
                     <!-- End Unfold -->
                 </div>
             </div>
             @php($primary_color = \App\Models\BusinessSetting::where('key', 'primary_color')->first())
             @php($secondary_color = \App\Models\BusinessSetting::where('key', 'secondary_color')->first())
             @php($primary_btn_hover = \App\Models\BusinessSetting::where('key', 'primary_btn_hover')->first())

             <form action="{{ route('admin.business-settings.update-setup2') }}" method="post"
                 enctype="multipart/form-data">
                 @csrf

                 <div class="theme-customizer-body" id="themeCustomizerBody">
                     <div class="theme-customizer-container">
                         <p class="theme-customizer-subtitle">Customize color scheme</p>
                         <div class="row">
                             <div class="col-md-6">
                                 <div class="theme-customizer-color-control">
                                     <label class="theme-customizer-label">Primary Color</label>
                                     <div class="theme-customizer-input-group">
                                         <input type="color" class="theme-customizer-color-picker"
                                             id="themeCustomizerPrimaryColor" value="{{ $primary_color ? $primary_color->value : '#754BFF' }}">
                                         <input type="text" class="theme-customizer-text-input"  value="{{ $primary_color ? $primary_color->value : '#754BFF' }}"
                                             id="themeCustomizerPrimaryText" name="primary_color">
                                     </div>
                                 </div>
                                 <div class="theme-customizer-color-control">
                                     <label class="theme-customizer-label">Primary Button Hover</label>
                                     <div class="theme-customizer-input-group">
                                         <input type="color" class="theme-customizer-color-picker"
                                             id="themeCustomizerPrimaryHoverColor" value="{{ $primary_btn_hover ? $primary_btn_hover->value : '#6e44fa' }}">
                                         <input type="text" class="theme-customizer-text-input"  value="{{ $primary_btn_hover ? $primary_btn_hover->value : '#6e44fa' }}"
                                             id="themeCustomizerPrimaryHoverText" name="primary_btn_hover">
                                     </div>
                                 </div>

                                 <div class="theme-customizer-color-control">
                                     <label class="theme-customizer-label">Secondary Color</label>
                                     <div class="theme-customizer-input-group">
                                         <input type="color" class="theme-customizer-color-picker"
                                             id="themeCustomizerBgColor"  value="{{ $secondary_color ? $secondary_color->value : '#A099FF' }}">
                                         <input type="text" class="theme-customizer-text-input"
                                             id="themeCustomizerSecondary" value="{{ $secondary_color ? $secondary_color->value : '#A099FF' }}" name="secondary_color">
                                     </div>
                                 </div>

                                 <button class="theme-customizer-apply-btn" id="themeCustomizerApplyBtn">Apply
                                     Theme</button>
                             </div>

                             <div class="theme-customizer-card col-md-6">
                                 <h2 class="theme-customizer-preview-title">Preview</h2>
                                 <button class="theme-customizer-preview-btn" id="themeCustomizerPreviewBtn">Primary
                                     Button</button>
                                 <button class="theme-customizer-preview-btn" id="themeCustomizerPreviewSecBtn">Secondary
                                     Button</button>
                                 <button class=" theme-customizer-preview-btn" id="themeCustomizerPreviewOutlineBtn">Outline
                                     Button</button>
                                 <p class="theme-customizer-preview-text">Lorem ipsum, dolor sit amet consectetur
                                     adipisicing elit. Enim, ipsam voluptas! Inventore commodi ratione totam quia omnis
                                     veniam iste debitis illum quae similique suscipit eligendi, deleniti cupiditate, ab
                                     nulla placeat!</p>
                             </div>
                         </div>

                     </div>
                 </div>

             </form>
         </div>
     </div>

 @endsection

 @push('script_2')
     <script>
         $(document).ready(function() {
             const $primaryColor = $('#themeCustomizerPrimaryColor');
             const $primaryText = $('#themeCustomizerPrimaryText');
             const $primaryHoverText  = $('#themeCustomizerPrimaryHoverText');
             const $primaryHoverColor  = $('#themeCustomizerPrimaryHoverColor');
             const $bgText = $('#themeCustomizerSecondary');
             const $bgColor = $('#themeCustomizerBgColor');
             const $textColor = $('#themeCustomizerTextColor');
             const $textText = $('#themeCustomizerTextText');
             const $applyBtn = $('#themeCustomizerApplyBtn');
             const $previewBtn = $('#themeCustomizerPreviewBtn');
             const $secondary = $('#themeCustomizerPreviewSecBtn');
             const $outline = $('#themeCustomizerPreviewOutlineBtn');

             function updateTheme() {
                 $applyBtn.css('background-color', $primaryColor.val())
                  .hover(
                         function() { // mouse enter
                             $(this).css({
                                 background: $primaryHoverText.val(),
                             });
                         },
                         function() { // mouse leave
                             $(this).css({
                                 background: $primaryColor.val(),
                             });
                         }
                     );

                 $previewBtn.css('background-color', $primaryColor.val())
                 .hover(
                         function() { // mouse enter
                             $(this).css({
                                 background: $primaryHoverText.val(),
                             });
                         },
                         function() { // mouse leave
                             $(this).css({
                                 background: $primaryColor.val(),
                             });
                         }
                     );

                 $secondary.css('background-color', $bgText.val());

                 $outline
                     .css({
                         border: '1px solid ' + $primaryColor.val(),
                         color: $primaryColor.val()
                     })
                     .hover(
                         function() { // mouse enter
                             $(this).css({
                                 background: $primaryColor.val(),
                                 color: '#fff'
                             });
                         },
                         function() { // mouse leave
                             $(this).css({
                                 background: 'transparent',
                                 color: $primaryColor.val()
                             });
                         }
                     );

             }

             // Primary color sync
             $primaryColor.on('input', function() {
                 $primaryText.val($(this).val());
                 updateTheme();
             });

             $primaryText.on('input', function() {
                 $primaryColor.val($(this).val());
                 updateTheme();
             });
             $primaryHoverColor.on('input', function() {
                 $primaryHoverText.val($(this).val());
                 updateTheme();
             });

             $primaryHoverText.on('input', function() {
                 $primaryHoverColor.val($(this).val());
                 updateTheme();
             });

             // Background color sync
             $bgColor.on('input', function() {
                 $bgText.val($(this).val());
                 updateTheme();
             });

             $bgText.on('input', function() {
                 $bgColor.val($(this).val());
                 updateTheme();
             });

             // Text color sync
             $textColor.on('input', function() {
                 $textText.val($(this).val());
                 updateTheme();
             });

             $textText.on('input', function() {
                 $textColor.val($(this).val());
                 updateTheme();
             });

             // Initialize theme
             updateTheme();
         });
     </script>
 @endpush
