<?php

namespace App\Traits;

use App\CentralLogics\Helpers;
use App\Models\BusinessSetting;
use GuzzleHttp\Client;
trait NotificationTrait
{
    public static function sendPushNotificationToTopicOld($data, $topic, $type,$web_push_link = null): bool|string
    {
        $key = BusinessSetting::where(['key' => 'push_notification_key'])->first()->value;

        $url = "https://fcm.googleapis.com/fcm/send";
        $header = array(
            "authorization: key=" . $key . "",
            "content-type: application/json"
        );
        if(isset($data['module_id'])){
            $module_id = $data['module_id'];
        }else{
            $module_id = '';
        }
        if(isset($data['order_type'])){
            $order_type = $data['order_type'];
        }else{
            $order_type = '';
        }
        if(isset($data['zone_id'])){
            $zone_id = $data['zone_id'];
        }else{
            $zone_id = '';
        }

        $click_action = "";
        if($web_push_link){
            $click_action = ',
            "click_action": "'.$web_push_link.'"';
        }

        if (isset($data['order_id'])) {
            $postdata = '{
                "to" : "/topics/' . $topic . '",
                "mutable_content": true,
                "data" : {
                    "title":"' . $data['title'] . '",
                    "body" : "' . $data['description'] . '",
                    "image" : "' . $data['image'] . '",
                    "order_id":"' . $data['order_id'] . '",
                    "module_id":"' . $module_id . '",
                    "order_type":"' . $order_type . '",
                    "zone_id":"' . $zone_id . '",
                    "is_read": 0,
                    "type":"' . $type . '"
                },
                "notification" : {
                    "title":"' . $data['title'] . '",
                    "body" : "' . $data['description'] . '",
                    "image" : "' . $data['image'] . '",
                    "order_id":"' . $data['order_id'] . '",
                    "title_loc_key":"' . $data['order_id'] . '",
                    "body_loc_key":"' . $type . '",
                    "type":"' . $type . '",
                    "is_read": 0,
                    "icon" : "new",
                    "sound": "notification.wav",
                    "android_channel_id": "MyChitti"
                    '.$click_action.'
                  }
            }';
        } else {
            $postdata = '{
                "to" : "/topics/' . $topic . '",
                "mutable_content": true,
                "data" : {
                    "title":"' . $data['title'] . '",
                    "body" : "' . $data['description'] . '",
                    "image" : "' . $data['image'] . '",
                    "is_read": 0,
                    "type":"' . $type . '"
                },
                "notification" : {
                    "title":"' . $data['title'] . '",
                    "body" : "' . $data['description'] . '",
                    "image" : "' . $data['image'] . '",
                    "body_loc_key":"' . $type . '",
                    "type":"' . $type . '",
                    "is_read": 0,
                    "icon" : "new",
                    "sound": "notification.wav",
                    "android_channel_id": "MyChitti"
                    '.$click_action.'
                  }
            }';
        }


        $ch = curl_init();
        $timeout = 120;
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);

        // Get URL content
        $result = curl_exec($ch);
        // close handle to release resources
        curl_close($ch);

        return $result;
    }


    /**
     * Send push notification directly to a batch of device tokens.
     * Uses the legacy FCM API which supports registration_ids (up to 500 per call).
     * More reliable than topic-based delivery for admin broadcasts.
     *
     * @param array  $tokens  Array of FCM device tokens
     * @param mixed  $data    Notification data (array or Eloquent model)
     * @param string $type    Notification type
     * @return array ['sent' => int, 'failed' => int, 'errors' => array]
     */
    /**
     * Send push notification to a batch of device tokens using FCM v1 API.
     * The legacy registration_ids API was shut down in 2024; FCM v1 requires
     * one message per token but supports async concurrent sending.
     *
     * @param array  $tokens  Array of FCM device tokens
     * @param mixed  $data    Notification data (array or Eloquent model)
     * @param string $type    Notification type
     * @return array ['sent' => int, 'failed' => int, 'errors' => array]
     */
    public static function sendPushNotificationToTokensBatch(array $tokens, $data, string $type): array
    {
        if (empty($tokens)) {
            return ['sent' => 0, 'failed' => 0, 'errors' => ['Empty token list']];
        }

        try {
            $accessToken = _getAccessToken();
        } catch (\Exception $e) {
            \Log::error('FCM batch: failed to get access token: ' . $e->getMessage());
            return ['sent' => 0, 'failed' => count($tokens), 'errors' => ['Access token error: ' . $e->getMessage()]];
        }

        if (!$accessToken) {
            return ['sent' => 0, 'failed' => count($tokens), 'errors' => ['Could not obtain FCM access token']];
        }

        $projectId   = config('fcm.project_id', 'fcm-3-e0206');
        $url         = "https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send";
        $title       = is_array($data) ? ($data['title'] ?? '') : $data['title'];
        $description = is_array($data) ? ($data['description'] ?? '') : $data['description'];
        $image       = is_array($data) ? ($data['image'] ?? '') : ($data['image'] ?? '');

        $sent   = 0;
        $failed = 0;
        $errors = [];
        $client = new Client(['timeout' => 30, 'http_errors' => false]);

        $headers = [
            'Authorization' => 'Bearer ' . $accessToken,
            'Content-Type'  => 'application/json',
        ];

        // FCM v1 has no multicast — send concurrently in chunks of 100 async requests
        foreach (array_chunk($tokens, 100) as $chunk) {
            $promises = [];

            foreach ($chunk as $token) {
                $promises[] = $client->postAsync($url, [
                    'headers' => $headers,
                    'json'    => [
                        'message' => [
                            'token' => $token,
                            'data'  => [
                                'title'   => (string) $title,
                                'body'    => (string) $description,
                                'image'   => (string) ($image ?? ''),
                                'type'    => $type,
                                'is_read' => '0',
                            ],
                            'notification' => [
                                'title' => (string) $title,
                                'body'  => (string) $description,
                                'image' => (string) ($image ?? ''),
                            ],
                            'android' => [
                                'notification' => [
                                    'sound'      => 'notification.wav',
                                    'channel_id' => 'MyChitti',
                                ],
                            ],
                        ],
                    ],
                ]);
            }

            $settled = \GuzzleHttp\Promise\Utils::settle($promises)->wait();

            foreach ($settled as $result) {
                if ($result['state'] === 'fulfilled') {
                    $statusCode = $result['value']->getStatusCode();
                    $body       = json_decode($result['value']->getBody()->getContents(), true);

                    if ($statusCode === 200) {
                        $sent++;
                    } else {
                        $failed++;
                        $errMsg = $body['error']['message'] ?? ('HTTP ' . $statusCode);
                        $errors[] = $errMsg;
                        \Log::warning('FCM v1 token send failed: ' . $errMsg);
                    }
                } else {
                    $failed++;
                    $errors[] = $result['reason']->getMessage();
                    \Log::error('FCM v1 async error: ' . $result['reason']->getMessage());
                }
            }
        }

        if ($sent > 0 || $failed > 0) {
            \Log::info("FCM batch complete: {$sent} sent, {$failed} failed out of " . count($tokens) . " tokens.");
        }

        return ['sent' => $sent, 'failed' => $failed, 'errors' => array_unique($errors)];
    }

    public static function sendPushNotificationToTopic($data, $topic, $type, $web_push_link = null): array
    {
        try {
            $accessToken = _getAccessToken();
            $projectId = 'fcm-3-e0206'; // Add this to your config

            $client = new Client();
            $url = "https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send";

            // Prepare the message payload
            $message = [
                'message' => [
                    'topic' => $topic,
                    'data' => [
                        'title' => (string) $data['title'],
                        'body' => (string) $data['description'],
                        'image' => $data['image'],
                        'type' => (string) $type,
                        'is_read' => '0'
                    ],
                    'notification' => [
                        'title' => (string) $data['title'],
                        'body' => (string) $data['description'],
                        'image' => $data['image']
                    ],
                    'android' => [
                        'notification' => [
                            'icon' => 'new',
                            'sound' => 'notification.wav',
                            'channel_id' => 'MyChitti'
                        ]
                    ]
                ]
            ];

            // Add optional fields if they exist
            if (isset($data['order_id'])) {
                $message['message']['data']['order_id'] = $data['order_id'];
                $message['message']['notification']['title_loc_key'] = $data['order_id'];
            }
            if (isset($data['module_id'])) {
                $message['message']['data']['module_id'] = $data['module_id'];
            }
            if (isset($data['order_type'])) {
                $message['message']['data']['order_type'] = $data['order_type'];
            }
            if (isset($data['zone_id'])) {
                $message['message']['data']['zone_id'] = (string) $data['zone_id'];
            }
            if ($web_push_link) {
                $message['message']['webpush'] = [
                    'fcm_options' => [
                        'link' => $web_push_link
                    ]
                ];
            }

            $response = $client->post($url, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $accessToken,
                    'Content-Type' => 'application/json',
                ],
                'json' => $message
            ]);

            return [
                'success' => true,
                'response' => json_decode($response->getBody()->getContents(), true)
            ];
        } catch (\Exception $e) {
            \Log::error('FCM Notification Error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    public static function sendPushNotificationToDevice($fcm_token, $data, $web_push_link = null): bool|string
    {
        $key = BusinessSetting::where(['key' => 'push_notification_key'])->first()->value;
        $url = "https://fcm.googleapis.com/fcm/send";
        $header = array(
            "authorization: key=" . $key . "",
            "content-type: application/json"
        );

        if(isset($data['message'])){
            $message = $data['message'];
        }else{
            $message = '';
        }
        if(isset($data['conversation_id'])){
            $conversation_id = $data['conversation_id'];
        }else{
            $conversation_id = '';
        }
        if(isset($data['sender_type'])){
            $sender_type = $data['sender_type'];
        }else{
            $sender_type = '';
        }
        if(isset($data['module_id'])){
            $module_id = $data['module_id'];
        }else{
            $module_id = '';
        }
        if(isset($data['order_type'])){
            $order_type = $data['order_type'];
        }else{
            $order_type = '';
        }

        $click_action = "";
        if($web_push_link){
            $click_action = ',
            "click_action": "'.$web_push_link.'"';
        }

        $postdata = '{
            "to" : "' . $fcm_token . '",
            "mutable_content": true,
            "data" : {
                "title":"' . $data['title'] . '",
                "body" : "' . $data['description'] . '",
                "image" : "' . $data['image'] . '",
                "order_id":"' . $data['order_id'] . '",
                "type":"' . $data['type'] . '",
                "conversation_id":"' . $conversation_id . '",
                "sender_type":"' . $sender_type . '",
                "module_id":"' . $module_id . '",
                "order_type":"' . $order_type . '",
                "is_read": 0
            },
            "notification" : {
                "title" :"' . $data['title'] . '",
                "body" : "' . $data['description'] . '",
                "image" : "' . $data['image'] . '",
                "order_id":"' . $data['order_id'] . '",
                "title_loc_key":"' . $data['order_id'] . '",
                "body_loc_key":"' . $data['type'] . '",
                "type":"' . $data['type'] . '",
                "is_read": 0,
                "icon" : "new",
                "sound": "notification.wav",
                "android_channel_id": "MyChitti"
                '.$click_action.'
            }
        }';
        $ch = curl_init();
        $timeout = 120;
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);

        // Get URL content
        $result = curl_exec($ch);
        // close handle to release resources
        curl_close($ch);

        return $result;
    }
}
