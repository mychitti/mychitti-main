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
 

];
