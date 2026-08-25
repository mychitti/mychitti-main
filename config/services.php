<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials. 
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
    ],
 
    'postmark' => [ 
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],
    'google' => [
        'client_id'     => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect'      => env('GOOGLE_DEFAULT_REDIRECT'),

    ],

    'google_redirects' => [
        'user'   => env('GOOGLE_USER_REDIRECT'),
        'vendor' => env('GOOGLE_VENDOR_REDIRECT'),
    ],
    'anthropic' => [
        'key' => env('ANTHROPIC_API_KEY'),
    ],
    'openai' => [
        'key' => env('OPENAI_API_KEY'), 
    ],
    'gemini' => [
        'key' => env('GEMINI_API_KEY'),
    ],
    'custom_ai' => [
        'url'   => env('CUSTOM_AI_URL'),          // e.g. http://localhost:11434/v1/chat/completions
        'key'   => env('CUSTOM_AI_KEY'),           // leave empty for Ollama
        'model' => env('CUSTOM_AI_MODEL', 'llama3'),
    ],
    'cloudflare' => [
        'api_token' => env('CLOUDFLARE_API_TOKEN'),
        'zone_id'   => env('CLOUDFLARE_ZONE_ID'),
    ],  
    'ai_service' => [
        'url' => env('AI_SERVICE_URL'),
        'key' => env('AI_SERVICE_KEY'),
    ],
    'ai_server' => [
        'url'   => env('AI_SERVER_URL'),
        'token' => env('AI_SERVER_TOKEN'),
    ],

    // Meta WhatsApp Cloud API — platform-wide default credentials. The admin can also
    // store these in business_settings (key: whatsapp_config), and individual vendors
    // can override them per store. Resolution order is handled by
    // App\Services\WhatsAppService: per-vendor → global (DB) → this env fallback.
    'whatsapp' => [
        'api_version'          => env('WHATSAPP_API_VERSION', 'v21.0'),
        'phone_number_id'      => env('WHATSAPP_PHONE_NUMBER_ID'),
        'token'                => env('WHATSAPP_TOKEN'),
        'business_account_id'  => env('WHATSAPP_BUSINESS_ACCOUNT_ID'),
        'default_country_code' => env('WHATSAPP_DEFAULT_COUNTRY_CODE', '91'),

        // The daily hospital summary goes out on the PLATFORM number, so it needs one template
        // approved once on the platform WABA rather than one per hospital. Body variables, in
        // order: {{1}} hospital name, {{2}} date, {{3}} the figures as one line.
        'daily_report_template' => env('WHATSAPP_DAILY_REPORT_TEMPLATE', 'daily_report'),
        'daily_report_language' => env('WHATSAPP_DAILY_REPORT_LANGUAGE', 'en_US'),
    ],

    // Vendor-panel "generate with AI" buttons (Phase 4): Review Auto-Reply, Lead follow-up,
    // AI Draft Items (invoice), Write with AI (About Us). HIDDEN by default — turn them on with
    // VENDOR_AI_TOOLS_ENABLED=true in .env, then `php artisan config:clear`. See PROJECT_NOTES.md.
    'vendor_ai_tools' => [
        'enabled' => env('VENDOR_AI_TOOLS_ENABLED', false),
    ],

];
