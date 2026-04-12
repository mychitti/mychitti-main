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
    public static function sendPushNotificationToTokensBatch(array $tokens, $data, string $type): array
    {
        $key = BusinessSetting::where('key', 'push_notification_key')->value('value');
        if (!$key || empty($tokens)) {
            return ['sent' => 0, 'failed' => 0, 'errors' => ['No server key or empty token list']];
        }

        $title       = is_array($data) ? ($data['title'] ?? '') : $data['title'];
        $description = is_array($data) ? ($data['description'] ?? '') : $data['description'];
        $image       = is_array($data) ? ($data['image'] ?? '') : ($data['image'] ?? '');

        $sent   = 0;
        $failed = 0;
        $errors = [];

        $client = new Client();

        foreach (array_chunk($tokens, 500) as $chunk) {
            $payload = [
                'registration_ids' => $chunk,
                'mutable_content'  => true,
                'data'             => [
                    'title'   => (string) $title,
                    'body'    => (string) $description,
                    'image'   => (string) ($image ?? ''),
                    'type'    => $type,
                    'is_read' => '0',
                ],
                'notification'     => [
                    'title'              => (string) $title,
                    'body'               => (string) $description,
                    'image'              => (string) ($image ?? ''),
                    'sound'              => 'notification.wav',
                    'android_channel_id' => 'MyChitti',
                ],
            ];

            try {
                $response = $client->post('https://fcm.googleapis.com/fcm/send', [
                    'headers' => [
                        'Authorization' => 'key=' . $key,
                        'Content-Type'  => 'application/json',
                    ],
                    'json'        => $payload,
                    'http_errors' => false,
                    'timeout'     => 30,
                ]);

                $result = json_decode($response->getBody()->getContents(), true);
                $sent  += $result['success'] ?? 0;
                $failed += $result['failure'] ?? 0;

                if (!empty($result['results'])) {
                    foreach ($result['results'] as $r) {
                        if (!empty($r['error'])) {
                            $errors[] = $r['error'];
                        }
                    }
                }
            } catch (\Exception $e) {
                \Log::error('FCM batch send error: ' . $e->getMessage());
                $failed += count($chunk);
                $errors[] = $e->getMessage();
            }
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
