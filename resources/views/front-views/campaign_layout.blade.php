<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />

    <!-- ─── PER-PAGE META (edit on each page) ─── -->
    <title>@yield('title', 'My Chitti Campaign')</title>
    <meta name="csrf-token" id="csrf-token" content="{{ csrf_token() }}">
    @php($logo = \App\Models\BusinessSetting::where(['key' => 'icon'])->first()->value)
    <link rel="icon" type="image/x-icon" href="{{ asset('storage/app/public/business/' . $logo ?? '') }}">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">

    <meta content="@yield('meta_keywords')" name="keywords">
    <meta content="@yield('meta_description')" name="description">
    @if (\Illuminate\Support\Str::contains(request()->getHost(), 'staging.mychitti.net'))
        <meta name="robots" content="noindex, nofollow"> <!-- no indexing of staging  -->
    @endif

    <!-- Google tag (gtag.js) -->
    @include('front-views/partials/ads')

    @stack('meta_tags')

    <link rel="canonical" href="{{ url()->current() }}" />

    <!-- Open Graph -->
    <meta property="og:title" content="{{ $campaign?->meta_title ?? $campaign?->name ?? 'Campaign' }}" />
    <meta property="og:description" content="{{ $campaign?->meta_description ?? '' }}" />
    <meta property="og:image" content="{{ $campaign?->og_image ? asset('storage/app/public/'.$campaign->og_image) : '' }}" />
    <meta property="og:url" content="{{ url()->current() }}" />
    <meta property="og:type" content="website" />

    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
        href="https://fonts.googleapis.com/css2?family=Syne:wght@400;500;600;700;800&family=DM+Sans:ital,wght@0,300;0,400;0,500;1,300&display=swap"
        rel="stylesheet" />
    <link href="{{ asset('public/assets/front/css/campaign.css') }}" rel="stylesheet">
    @stack('css_or_js')

</head>

<body>

@include('front-views.partials.campaign._navbar')

    @yield('content')

    @include('front-views.partials.campaign._footer')
    <div id="toast" class="toast"></div>

     {!! Toastr::message() !!}

    @if ($errors->any())
        <script>
            @foreach ($errors->all() as $error)
                toastr.error('{{ translate($error) }}', Error, {
                    CloseButton: true,
                    ProgressBar: true
                });
            @endforeach
        </script>
    @endif

    @stack('script')



    @stack('script_2')

  
   

    <!-- ══════════════════════════════════════
       JS
       ══════════════════════════════════════ -->
    <script>
        function openMobileNav() {
            document.getElementById('mobileNav').classList.add('open');
            document.body.style.overflow = 'hidden';
        }

        function closeMobileNav(e) {
            if (!e || e.target === document.getElementById('mobileNav') || e.currentTarget.classList.contains(
                    'mobile-nav-close')) {
                document.getElementById('mobileNav').classList.remove('open');
                document.body.style.overflow = '';
            }
        }

        /* Mark active nav link based on current path */
        (function() {
            const path = window.location.pathname;
            document.querySelectorAll('.nav-link, .mobile-nav-link').forEach(link => {
                link.classList.remove('active');
                if (link.getAttribute('href') === path ||
                    (path !== '/' && link.getAttribute('href') !== '/' && path.startsWith(link.getAttribute(
                        'href')))) {
                    link.classList.add('active');
                }
            });
        })();
    </script>

</body>

</html>
