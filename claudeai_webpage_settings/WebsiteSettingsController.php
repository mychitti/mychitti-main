<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Store;
use App\Models\StoreConfigNew;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class WebsiteSettingsController extends Controller
{
    /**
     * Display the website settings page
     */
    public function index()
    {
        $store = auth()->user()->stores()->first();
        $storeConfig = StoreConfigNew::where('store_id', $store->id)->first();
        
        return view('vendor-views.settings.website-settings', compact('store', 'storeConfig'));
    }

    /**
     * Update domain configuration
     */
    public function updateDomain(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'custom_domain' => 'required|string|max:255|alpha_dash|unique:store_config_news,custom_domain,' . auth()->user()->stores()->first()->id . ',store_id',
            'personal_domain' => 'nullable|string|max:255',
            'website_active' => 'nullable|boolean'
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput()
                ->with(['error' => 'Domain validation failed']);
        }

        $store = auth()->user()->stores()->first();
        $storeConfig = StoreConfigNew::firstOrCreate(['store_id' => $store->id]);

        // Generate slug-friendly domain
        $customDomain = Str::slug($request->custom_domain);

        // Check if domain is available
        $exists = StoreConfigNew::where('custom_domain', $customDomain)
            ->where('store_id', '!=', $store->id)
            ->exists();

        if ($exists) {
            return redirect()->back()
                ->with(['error' => 'This subdomain is already taken. Please choose another.'])
                ->withInput();
        }

        $storeConfig->custom_domain = $customDomain;
        $storeConfig->personal_domain = $request->personal_domain;
        $storeConfig->website_active = $request->has('website_active') ? 1 : 0;
        $storeConfig->domain_status = 'active';
        $storeConfig->save();

        // Update store slug for consistency
        $store->slug = $customDomain;
        $store->save();

        return redirect()->back()->with(['success' => 'Domain settings updated successfully!']);
    }

    /**
     * Update layout settings
     */
    public function updateLayout(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'inventory_items_position' => 'required|in:above,below'
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->with(['error' => 'Invalid layout settings']);
        }

        $store = auth()->user()->stores()->first();
        $storeConfig = StoreConfigNew::firstOrCreate(['store_id' => $store->id]);

        $storeConfig->inventory_items_position = $request->inventory_items_position;
        $storeConfig->save();

        return redirect()->back()->with(['success' => 'Layout settings updated successfully!']);
    }

    /**
     * Update webpage contact information
     */
    public function webpageUpdate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'website_name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'address' => 'required|string',
            'phone' => 'required|array',
            'phone.*' => 'required|string',
            'gst_number' => 'nullable|string|max:255'
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput()
                ->with(['error' => 'Please check all fields']);
        }

        $store = auth()->user()->stores()->first();
        $storeConfig = StoreConfigNew::firstOrCreate(['store_id' => $store->id]);

        $storeConfig->webpage_name = $request->website_name;
        $storeConfig->webpage_email = $request->email;
        $storeConfig->webpage_address = $request->address;
        $storeConfig->webpage_phones = json_encode($request->phone);
        $storeConfig->gst_number = $request->gst_number;
        $storeConfig->save();

        return redirect()->back()->with(['success' => 'Contact information updated successfully!']);
    }

    /**
     * Update location (map coordinates)
     */
    public function updateLocation(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric'
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->with(['error' => 'Invalid location coordinates']);
        }

        $store = auth()->user()->stores()->first();
        $storeConfig = StoreConfigNew::firstOrCreate(['store_id' => $store->id]);

        $storeConfig->webpage_latitude = $request->latitude;
        $storeConfig->webpage_longitude = $request->longitude;
        $storeConfig->save();

        // Also update main store location
        $store->latitude = $request->latitude;
        $store->longitude = $request->longitude;
        $store->save();

        return redirect()->back()->with(['success' => 'Location updated successfully!']);
    }

    /**
     * Update branding (logo and cover photo)
     */
    public function updateBranding(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'logo' => 'nullable|image|mimes:jpg,jpeg,png,gif|max:2048',
            'cover_photo' => 'nullable|image|mimes:jpg,jpeg,png,gif|max:2048'
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->with(['error' => 'Invalid image file']);
        }

        $store = auth()->user()->stores()->first();

        if ($request->hasFile('logo')) {
            // Delete old logo if exists
            if ($store->logo) {
                $oldPath = storage_path('app/public/store/' . $store->logo);
                if (file_exists($oldPath)) {
                    unlink($oldPath);
                }
            }

            $logoName = time() . '_logo.' . $request->logo->extension();
            $request->logo->storeAs('public/store', $logoName);
            $store->logo = $logoName;
        }

        if ($request->hasFile('cover_photo')) {
            // Delete old cover photo if exists
            if ($store->cover_photo) {
                $oldPath = storage_path('app/public/store/' . $store->cover_photo);
                if (file_exists($oldPath)) {
                    unlink($oldPath);
                }
            }

            $coverName = time() . '_cover.' . $request->cover_photo->extension();
            $request->cover_photo->storeAs('public/store', $coverName);
            $store->cover_photo = $coverName;
        }

        $store->save();

        return redirect()->back()->with(['success' => 'Branding updated successfully!']);
    }

    /**
     * Check if subdomain is available
     */
    public function checkDomainAvailability(Request $request)
    {
        $domain = Str::slug($request->domain);
        $store = auth()->user()->stores()->first();

        $exists = StoreConfigNew::where('custom_domain', $domain)
            ->where('store_id', '!=', $store->id)
            ->exists();

        return response()->json([
            'available' => !$exists,
            'domain' => $domain . '.mychitti.net'
        ]);
    }

    /**
     * Get DNS configuration instructions
     */
    public function getDnsInstructions()
    {
        $store = auth()->user()->stores()->first();
        $storeConfig = StoreConfigNew::where('store_id', $store->id)->first();

        if (!$storeConfig || !$storeConfig->personal_domain) {
            return response()->json(['error' => 'No custom domain configured'], 404);
        }

        $instructions = [
            'domain' => $storeConfig->personal_domain,
            'dns_records' => [
                [
                    'type' => 'CNAME',
                    'name' => '@',
                    'value' => 'mychitti.net',
                    'ttl' => '3600'
                ],
                [
                    'type' => 'CNAME',
                    'name' => 'www',
                    'value' => 'mychitti.net',
                    'ttl' => '3600'
                ]
            ]
        ];

        return response()->json($instructions);
    }
}