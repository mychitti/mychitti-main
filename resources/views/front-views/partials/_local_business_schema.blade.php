{{-- LocalBusiness JSON-LD for a vendor/store page. Expects $store (Store model) + shared $canonical. --}}
@php
    $lb = $store ?? null;
@endphp
@if ($lb)
    @php
        $lbCity = null;
        if (!empty($lb->zone_id)) {
            $lbZoneName = \Illuminate\Support\Facades\DB::table('zones')->where('id', $lb->zone_id)->value('name');
            $lbCity = $lbZoneName ? trim(explode(',', $lbZoneName)[0]) : null;
        }

        $lbOgTitle = $lb->name . ($lbCity ? ' in ' . $lbCity : '') . ' | My Chitti';
        $lbOgDesc = $lb->name . ($lbCity ? ' — verified local business in ' . $lbCity : '')
            . '. View services, ratings and contact details on My Chitti.';
        $lbOgImage = !empty($lb->logo)
            ? asset('storage/app/public/store/' . $lb->logo)
            : asset('storage/app/public/business/' . (\App\Models\BusinessSetting::where('key', 'logo')->value('value')));
    @endphp

    <meta property="og:type" content="website" />
    <meta property="og:site_name" content="My Chitti" />
    <meta property="og:title" content="{{ $lbOgTitle }}" />
    <meta property="og:description" content="{{ $lbOgDesc }}" />
    <meta property="og:url" content="{{ $canonical ?? url()->current() }}" />
    <meta property="og:image" content="{{ $lbOgImage }}" />

    @php

        $lbSchema = array_filter([
            '@context'   => 'https://schema.org',
            '@type'      => 'LocalBusiness',
            'name'       => $lb->name,
            'url'        => $canonical ?? url()->current(),
            'image'      => !empty($lb->logo) ? asset('storage/app/public/store/' . $lb->logo) : null,
            'telephone'  => $lb->phone ?? null,
            'email'      => $lb->email ?? null,
            'address'    => !empty($lb->address) ? array_filter([
                '@type'           => 'PostalAddress',
                'streetAddress'   => $lb->address,
                'addressLocality' => $lbCity,
                'addressRegion'   => 'Andhra Pradesh',
                'addressCountry'  => 'IN',
            ]) : null,
            'geo'        => (!empty($lb->latitude) && !empty($lb->longitude)) ? [
                '@type'     => 'GeoCoordinates',
                'latitude'  => (string) $lb->latitude,
                'longitude' => (string) $lb->longitude,
            ] : null, 
            'aggregateRating' => ((int) ($lb->rating_count ?? 0) > 0) ? [
                '@type'       => 'AggregateRating',
                'ratingValue' => round((float) $lb->average_rating, 1),
                'reviewCount' => (int) $lb->rating_count,
                'bestRating'  => 5,
                'worstRating' => 1,
            ] : null,
        ], fn($v) => $v !== null);
    @endphp
    <script type="application/ld+json">
    {!! json_encode($lbSchema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
    </script>

    {{-- Breadcrumb kept to Home -> Store: there's no standalone /{city} page to link a city crumb to. --}}
    <script type="application/ld+json">
    {!! json_encode([
        '@context' => 'https://schema.org',
        '@type' => 'BreadcrumbList',
        'itemListElement' => [
            ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => url('/')],
            ['@type' => 'ListItem', 'position' => 2, 'name' => $lb->name, 'item' => $canonical ?? url()->current()],
        ],
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
    </script>
@endif
