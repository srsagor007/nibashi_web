<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class SmsService
{
    protected string $domain;

    protected ?string $api_token;

    protected ?string $sid;

    public function __construct()
    {
        $this->domain = env('SMS_API_DOMAIN', 'https://smsplus.sslwireless.com');
        $this->api_token = env('SMS_API_TOKEN', null);
        $this->sid = env('SMS_SID', null);
    }

    public function sendSms(string $receiver, string $message)
    {
        $url = "{$this->domain}/api/v3/send-sms";

        if (config('app.env') !== 'production') {
            // For local development, use a mock URL or skip sending
            return true;
        }

        if (empty($this->api_token) || empty($this->sid)) {
            return false; // API token or SID is not set
        }

        $payload = [
            'api_token' => $this->api_token,
            'sid' => $this->sid,
            'msisdn' => $receiver,
            'sms' => $message,
            'csms_id' => uniqid(),
        ];

        $ch = curl_init();

        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($payload),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/x-www-form-urlencoded',
            ],
        ]);

        $response = curl_exec($ch);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            return false;
        }

        $result = json_decode($response, true);

        return isset($result['status']) && $result['status'] === 'SUCCESS';
    }

    // try {
    //     $response = Http::asForm()->post($url, $payload);
    //     return $response;

    //     if ($response->successful() && ($response['status'] ?? '') === 'SUCCESS') {
    //         return true;
    //     }
    // } catch (\Exception $e) {
    //     return false;
    // }
}
