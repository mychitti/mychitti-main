 @php $ads = _getGoogleAds(); @endphp

 @foreach ($ads as $key => $value)
     <script async src="https://www.googletagmanager.com/gtag/js?id={{$value->ad_id}}"></script>
     <script>
         window.dataLayer = window.dataLayer || [];

         function gtag() {
             dataLayer.push(arguments);
         }
         gtag('js', new Date());

         gtag('config', '{{$value->ad_id}}');
     </script>
 @endforeach
