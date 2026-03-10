<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class SitemapController extends Controller
{
    public function generate()
    {
        // Fetch URLs dynamically (Example: Fetching from a 'posts' table)
        $items = DB::table('items')->where('status', 1)->get();
        $categories = DB::table('categories')->where('status', 1)->get();
        $stores = DB::table('stores')->where('status', 1)->get();

        // Use staging domain if on staging, otherwise production
        $host = request()->getHost();
        $baseUrl = $host === 'staging.mychitti.net'
            ? 'https://staging.mychitti.net'
            : 'https://mychitti.net'; 

        // Start XML structure
        $xml = '<?xml version="1.0" encoding="UTF-8"?>';
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

        // Add static URLs
        $xml .= '<url><loc>' . $baseUrl . '/' . '</loc></url>';
        $xml .= '<url><loc>' . $baseUrl . '/about-us</loc></url>';
        $xml .= '<url><loc>' . $baseUrl . '/contact</loc></url>';
        $xml .= '<url><loc>' . $baseUrl . '/list-your-business</loc></url>';
        $xml .= '<url><loc>' . $baseUrl . '/blog</loc></url>';
        $xml .= '<url><loc>' . $baseUrl . '/cart</loc></url>';

        // Add dynamic URLs from the database
        foreach ($items as $item) {
            $xml .= '<url><loc>' . $baseUrl . '/tirupati/' . $item->slug . '</loc></url>';
        }
        foreach ($items as $item) {
            $xml .= '<url><loc>' . $baseUrl . '/hyderabad/' . $item->slug . '</loc></url>';
        } 
        foreach ($categories as $cat) {
            $xml .= '<url><loc>' . $baseUrl . '/category/' . $cat->slug . '/tirupati</loc></url>';
        }
        foreach ($categories as $cat) {
            $xml .= '<url><loc>' . $baseUrl . '/category/' . $cat->slug . '/hyderabad</loc></url>';
        }
        foreach ($stores as $store) {
            $xml .= '<url><loc>' . $baseUrl . '/tirupati/store/' . $store->slug . '</loc></url>';
        }
        foreach ($stores as $store) {
            $xml .= '<url><loc>' . $baseUrl . '/hyderabad/store/' . $store->slug . '</loc></url>';
        }
         // Add static URLs
         $xml .= '<url><loc>' . $baseUrl . '/dashboard</loc></url>';
         $xml .= '<url><loc>' . $baseUrl . '/terms-and-conditions</loc></url>';
         $xml .= '<url><loc>' . $baseUrl . '/privacy-policy</loc></url>';
         $xml .= '<url><loc>' . $baseUrl . '/cancellation-policy</loc></url>';
        $xml .= '<url><loc>' . $baseUrl . '/refund-policy</loc></url>';
         $xml .= '<url><loc>' . $baseUrl . '/shipping-policy</loc></url>';

        $xml .= '</urlset>'; 

        // Save directly to public/ directory
        file_put_contents(public_path('sitemap.xml'), $xml);

        return response()->json(['message' => 'Sitemap updated successfully!']);
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
