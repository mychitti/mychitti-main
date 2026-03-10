<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class SitemapController extends Controller
{
    public function generate()
    {
        $items = DB::table('items')->where('status', 1)->get();
        $categories = DB::table('categories')->where('status', 1)->get();
        $stores = DB::table('stores')->where('status', 1)->get();

        $host = request()->getHost();
        $baseUrl = $host === 'staging.mychitti.net'
            ? 'https://staging.mychitti.net'
            : 'https://mychitti.net';

        $today = date('Y-m-d');

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        // Homepage - lastmod = latest item/store update
        $latestUpdate = collect([$items->max('updated_at'), $stores->max('updated_at'), $categories->max('updated_at')])->filter()->max() ?? $today;
        $xml .= $this->urlTag($baseUrl . '/', $latestUpdate, 'daily', '1.0');

        // Static pages
        $xml .= $this->urlTag($baseUrl . '/about-us', '2025-01-01', 'monthly', '0.4');
        $xml .= $this->urlTag($baseUrl . '/contact', '2025-01-01', 'monthly', '0.4');
        $xml .= $this->urlTag($baseUrl . '/list-your-business', '2025-01-01', 'monthly', '0.6');
        $xml .= $this->urlTag($baseUrl . '/blog', $today, 'weekly', '0.7');

        // Category pages
        foreach ($categories as $cat) {
            $lastmod = $cat->updated_at ?? $today;
            $xml .= $this->urlTag($baseUrl . '/category/' . $cat->slug . '/tirupati', $lastmod, 'weekly', '0.8');
            $xml .= $this->urlTag($baseUrl . '/category/' . $cat->slug . '/hyderabad', $lastmod, 'weekly', '0.8');
        }

        // Store pages
        foreach ($stores as $store) {
            $lastmod = $store->updated_at ?? $today;
            $xml .= $this->urlTag($baseUrl . '/tirupati/store/' . $store->slug, $lastmod, 'weekly', '0.7');
            $xml .= $this->urlTag($baseUrl . '/hyderabad/store/' . $store->slug, $lastmod, 'weekly', '0.7');
        }

        // Item pages
        foreach ($items as $item) {
            $lastmod = $item->updated_at ?? $today;
            $xml .= $this->urlTag($baseUrl . '/tirupati/' . $item->slug, $lastmod, 'weekly', '0.6');
            $xml .= $this->urlTag($baseUrl . '/hyderabad/' . $item->slug, $lastmod, 'weekly', '0.6');
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
