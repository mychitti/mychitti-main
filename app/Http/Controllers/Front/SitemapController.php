<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class SitemapController extends Controller
{
    public function generate()
    {
        $categories = DB::table('categories')->where('status', 1)->get();

        $host = request()->getHost();
        $baseUrl = $host === 'staging.mychitti.net'
            ? 'https://staging.mychitti.net'
            : 'https://mychitti.net';

        $today = date('Y-m-d');

        // Zone slug map: zone_id → url-safe city slug. Must use the SAME rule as
        // store_details()/_storeCity() (_zoneCitySlug — first comma-segment of the zone name),
        // otherwise these sitemap URLs don't match the real canonical URLs and just 301 away.
        $zones = DB::table('zones')->pluck('name', 'id')->map(fn($name) => _zoneCitySlug($name));

        $stores = DB::table('stores')->where('status', 1)->where('show_in_mychitti', 1)->get();
        $items  = DB::table('items')->where('status', 1)->get();

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        // Homepage - lastmod = latest item/store update
        $latestUpdate = collect([$items->max('updated_at'), $stores->max('updated_at'), $categories->max('updated_at')])->filter()->max() ?? $today;
        $xml .= $this->urlTag($baseUrl . '/', $latestUpdate, 'daily', '1.0');

        // Static pages
        $xml .= $this->urlTag($baseUrl . '/about-us', '2025-01-01', 'monthly', '0.4');
        $xml .= $this->urlTag($baseUrl . '/contact', '2025-01-01', 'monthly', '0.4');
        // /list-your-business is gone from here on purpose: it now 301s to MC Vendor Hub, and a
        // sitemap is a list of pages worth indexing, not of redirects. Submitting one asks Google
        // to keep crawling a URL whose whole job is to send it somewhere else.
        $xml .= $this->urlTag($baseUrl . '/blog', $today, 'weekly', '0.7');

        // Category pages — module 6 (services) is gated to real supply: only emit a URL for a
        // (category, city) combo that actually has a store offering it there (via item_store),
        // preferring the AI-generated /{city}/services/{category} page when published, else the
        // general listing. This avoids submitting thousands of empty "category in city" pages.
        $serviceCategories = $categories->where('module_id', 6)->keyBy('id');
        if ($serviceCategories->isNotEmpty()) {
            $serviceCombos = DB::table('item_store as ist')
                ->join('items as i', 'i.id', '=', 'ist.item_id')
                ->join('stores as s', 's.id', '=', 'ist.store_id')
                ->where('s.status', 1)->where('i.status', 1)
                ->whereIn('i.category_id', $serviceCategories->keys()->all())
                ->groupBy('i.category_id', 's.zone_id')
                ->select('i.category_id', 's.zone_id')
                ->get();

            // Category-level (item_id = 0) published pages only — item-level rows are handled below.
            $publishedCategorySeo = DB::table('service_zone_seo')
                ->where('status', 'published')->where('item_id', 0)
                ->get(['category_id', 'zone_id', 'slug'])
                ->keyBy(fn($r) => $r->category_id . ':' . $r->zone_id);

            foreach ($serviceCombos as $combo) {
                $citySlug = $zones[$combo->zone_id] ?? null;
                $cat = $serviceCategories[$combo->category_id] ?? null;
                if (!$citySlug || !$cat) continue;

                $lastmod = $cat->updated_at ?? $today;
                $seoKey  = $combo->category_id . ':' . $combo->zone_id;

                if (isset($publishedCategorySeo[$seoKey])) {
                    $xml .= $this->urlTag($baseUrl . '/' . $publishedCategorySeo[$seoKey]->slug, $lastmod, 'weekly', '0.9');
                } else {
                    $xml .= $this->urlTag($baseUrl . '/category/' . $cat->slug . '/' . $citySlug, $lastmod, 'weekly', '0.7');
                }
            }

            // Item-level published pages — specific high-supply services with their own page.
            $publishedItemSeo = DB::table('service_zone_seo')
                ->where('status', 'published')->where('item_id', '>', 0)
                ->get(['slug', 'updated_at']);
            foreach ($publishedItemSeo as $row) {
                $xml .= $this->urlTag($baseUrl . '/' . $row->slug, $row->updated_at ?? $today, 'weekly', '0.85');
            }
        }

        // Other modules — items belong to exactly one store (no cross-vendor pivot), so a
        // category page per zone-with-any-store is the existing, simpler mechanism.
        $usedZoneIds = $stores->pluck('zone_id')->unique()->filter();
        foreach ($categories->where('module_id', '!=', 6) as $cat) {
            $lastmod = $cat->updated_at ?? $today;
            foreach ($usedZoneIds as $zid) {
                $citySlug = $zones[$zid] ?? null;
                if (!$citySlug) continue;
                $xml .= $this->urlTag($baseUrl . '/category/' . $cat->slug . '/' . $citySlug, $lastmod, 'weekly', '0.8');
            }
        }

        // Store IDs that actually have at least one gallery image
        $galleryStoreIds = DB::table('store_galleries')->distinct()->pluck('store_id')->flip();

        // Store pages + gallery — one city per store based on zone
        foreach ($stores as $store) {
            $lastmod   = $store->updated_at ?? $today;
            $citySlug  = $store->zone_id ? ($zones[$store->zone_id] ?? null) : null;
            if ($citySlug) {
                $xml .= $this->urlTag($baseUrl . '/' . $citySlug . '/store/' . $store->slug, $lastmod, 'weekly', '0.7');
            }
            // Only list the gallery page when the store has images
            if (isset($galleryStoreIds[$store->id])) {
                $xml .= $this->urlTag($baseUrl . '/gallery/' . $store->slug, $lastmod, 'monthly', '0.5');
            }
        }

        // Item pages — city from the store the item belongs to
        $storeZones = $stores->pluck('zone_id', 'id');
        foreach ($items as $item) {
            $lastmod  = $item->updated_at ?? $today;
            $citySlug = isset($item->store_id, $storeZones[$item->store_id])
                ? ($zones[$storeZones[$item->store_id]] ?? null)
                : null;
            if ($citySlug) {
                $xml .= $this->urlTag($baseUrl . '/' . $citySlug . '/' . $item->slug, $lastmod, 'weekly', '0.6');
            }
        }

        // Policy pages - lastmod from data_settings
        $policyPages = [
            'terms_and_conditions' => '/terms-and-conditions',
            'privacy_policy' => '/privacy-policy',
            'cancellation_policy' => '/cancellation-policy',
            'refund_policy' => '/refund-policy',
            'shipping_policy' => '/shipping-policy',
        ]; 
        $policySettings = DB::table('data_settings')
            ->where('type', 'admin_landing_page')
            ->whereIn('key', array_keys($policyPages)) 
            ->pluck('updated_at', 'key');

        foreach ($policyPages as $key => $path) { 
            $lastmod = $policySettings[$key] ?? '2025-01-01';
            $xml .= $this->urlTag($baseUrl . $path, $lastmod, 'yearly', '0.3');
        }

        $xml .= '</urlset>';

        file_put_contents(public_path('sitemap.xml'), $xml);

        return response()->json(['message' => 'Sitemap updated successfully!']);
    }

    private function urlTag($loc, $lastmod, $changefreq, $priority)
    {
        $lastmod = date('Y-m-d', strtotime($lastmod));
        return "<url>\n" .
            "  <loc>{$loc}</loc>\n" .
            "  <lastmod>{$lastmod}</lastmod>\n" .
            "  <changefreq>{$changefreq}</changefreq>\n" .
            "  <priority>{$priority}</priority>\n" .
            "</url>\n";
    }

    public function show()
    {
        $path = public_path('sitemap.xml');
        if (!file_exists($path)) {
            abort(404);
        }
        return response(file_get_contents($path), 200)
            ->header('Content-Type', 'application/xml');
    }
}
