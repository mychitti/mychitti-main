<?php

namespace App\Http\Controllers\Vendor;

use App\CentralLogics\Helpers;
use App\Http\Controllers\Controller;
use App\Services\WhatsAppService;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppController extends Controller
{
    // Vendor "Connect WhatsApp" screen (Embedded Signup).
    public function connect(Request $request)
    {
        WhatsAppService::ensureStoreColumns();
        $config = Helpers::get_business_settings('whatsapp_config');
        $storeId = Helpers::get_store_id();
        $store = DB::table('stores')->where('id', $storeId)
            ->select('wa_enabled', 'wa_phone_number_id', 'wa_business_account_id')
            ->first();

        $es = [
            'app_id'      => $config['es_app_id'] ?? null,
            'config_id'   => $config['es_config_id'] ?? null,
            'api_version' => $config['api_version'] ?? 'v21.0',
            'ready'       => !empty($config['es_app_id']) && !empty($config['es_config_id']),
        ];

        return view('vendor-views.whatsapp.connect', compact('es', 'store'));
    }

    // Completes Embedded Signup: exchanges the auth code for a token and saves the vendor's number.
    public function finish(Request $request)
    {
        $request->validate([
            'code'            => 'required|string',
            'phone_number_id' => 'required|string',
            'waba_id'         => 'required|string',
        ]);

        WhatsAppService::ensureStoreColumns();
        $config = Helpers::get_business_settings('whatsapp_config');
        $appId     = $config['es_app_id'] ?? null;
        $appSecret = $config['es_app_secret'] ?? null;
        $version   = $config['api_version'] ?? 'v21.0';

        if (!$appId || !$appSecret) {
            return response()->json(['success' => false, 'message' => 'Embedded Signup not configured by admin.'], 422);
        }

        try {
            // 1) Exchange the short-lived code for a business access token scoped to the vendor's WABA.
            $tokenResp = Http::get("https://graph.facebook.com/{$version}/oauth/access_token", [
                'client_id'     => $appId,
                'client_secret' => $appSecret,
                'code'          => $request->code,
            ]);
            if (!$tokenResp->successful() || !data_get($tokenResp->json(), 'access_token')) {
                Log::warning('WA ES token exchange failed', ['body' => $tokenResp->json()]);
                return response()->json(['success' => false, 'message' => data_get($tokenResp->json(), 'error.message', 'Token exchange failed')], 422);
            }
            $token = data_get($tokenResp->json(), 'access_token');

            // 2) Subscribe our app to the vendor's WABA so webhooks/status flow in.
            Http::withToken($token)->post("https://graph.facebook.com/{$version}/{$request->waba_id}/subscribed_apps");

            // 3) Register the phone number for Cloud API (idempotent; ignore "already registered").
            Http::withToken($token)->post("https://graph.facebook.com/{$version}/{$request->phone_number_id}/register", [
                'messaging_product' => 'whatsapp',
                'pin'               => str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT),
            ]);

            // 4) Persist on the store — WhatsAppService picks this up automatically.
            DB::table('stores')->where('id', Helpers::get_store_id())->update([
                'wa_enabled'             => 1,
                'wa_phone_number_id'     => $request->phone_number_id,
                'wa_token'               => $token,
                'wa_business_account_id' => $request->waba_id,
                'wa_api_version'         => $version,
            ]);

            return response()->json(['success' => true, 'message' => 'WhatsApp connected.']);
        } catch (\Throwable $e) {
            Log::error('WA ES finish error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Connection failed: ' . $e->getMessage()], 500);
        }
    }

    public function disconnect(Request $request)
    {
        WhatsAppService::ensureStoreColumns();
        DB::table('stores')->where('id', Helpers::get_store_id())->update([
            'wa_enabled'             => 0,
            'wa_phone_number_id'     => null,
            'wa_token'               => null,
            'wa_business_account_id' => null,
        ]);
        Toastr::success('WhatsApp disconnected.');
        return back();
    }
}
