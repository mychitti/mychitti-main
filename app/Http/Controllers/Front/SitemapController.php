<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class SitemapController extends Controller
{
    public function generate()
    {
        // Fetch URLs dynamically (Example: Fetching from a 'posts' table)
        $items = DB::table('items')->where('status', 1)->get();
        $categories = DB::table('categories')->where('status', 1)->get();
        $stores = DB::table('stores')->where('status', 1)->get();

        // Start XML structure
        $xml = '<?xml version="1.0" encoding="UTF-8"?>';
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

        // Add static URLs
        $xml .= '<url><loc>' . url('/') . '</loc></url>';
        $xml .= '<url><loc>' . url('/about-us') . '</loc></url>';
        $xml .= '<url><loc>' . url('/contact') . '</loc></url>';
        $xml .= '<url><loc>' . url('/list-your-business') . '</loc></url>';
        $xml .= '<url><loc>' . url('/blog') . '</loc></url>';
        $xml .= '<url><loc>' . url('/cart') . '</loc></url>';

        // Add dynamic URLs from the database
        foreach ($items as $item) {
            $xml .= '<url><loc>' . url('/item/' . $item->slug) . '</loc></url>';
        }
        foreach ($categories as $cat) {
            $xml .= '<url><loc>' . url('/category/' . $cat->slug) . '</loc></url>';
        }
        foreach ($stores as $store) {
            $xml .= '<url><loc>' . url('/store/' . $store->slug) . '</loc></url>';
        }
         // Add static URLs
         $xml .= '<url><loc>' . url('/dashboard') . '</loc></url>';
         $xml .= '<url><loc>' . url('/terms-and-conditions') . '</loc></url>';
         $xml .= '<url><loc>' . url('/privacy-policy') . '</loc></url>';
         $xml .= '<url><loc>' . url('/cancellation-policy') . '</loc></url>';
         $xml .= '<url><loc>' . url('/refund-policy') . '</loc></url>';
         $xml .= '<url><loc>' . url('/shipping-policy') . '</loc></url>';

        $xml .= '</urlset>';

        $sitemapPath = base_path('sitemap.xml');
 
        // Delete the old sitemap if it exists
        if (file_exists($sitemapPath)) {
            unlink($sitemapPath);
        }

        // Save the sitemap to the public folder
        file_put_contents($sitemapPath, $xml);

        return response()->json(['message' => 'Sitemap updated successfully!']);
    }
}
