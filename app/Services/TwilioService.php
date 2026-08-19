<?php

namespace App\Services;

use RuntimeException;
use Twilio\Rest\Client;

class TwilioService
{
    protected $client;
    protected $from;

    public function __construct()
    {
        $accountSid = config('services.twilio.sid');
        $authToken = config('services.twilio.auth_token');
        $apiKey = config('services.twilio.api_key');
        $apiSecret = config('services.twilio.api_secret');

        if ($apiKey && $apiSecret && $accountSid) {
            $this->client = new Client($apiKey, $apiSecret, $accountSid);
        } elseif ($accountSid && $authToken) {
            $this->client = new Client($accountSid, $authToken);
        } else {
            throw new RuntimeException('Twilio credentials are not configured.');
        }

        $this->from = config('services.twilio.from_number');

        if (!$this->from) {
            throw new RuntimeException('Twilio from number is not configured.');
        }
    }

    public function makeCall($to, array $options)
    {
        return $this->client->calls->create(
            $this->normalizeNumber($to),
            $this->from,
            $options
        );
    }

    public function sendSMS($to, $message, array $mediaUrls = [])
    {
        $payload = [
            'from' => $this->from,
            'body' => $message,
        ];

        if (!empty($mediaUrls)) {
            $payload['mediaUrl'] = $mediaUrls;
        }

        return $this->client->messages->create(
            $this->normalizeNumber($to),
            $payload
        );
    }

    public function sendWhatsApp($to, $message)
    {
        $from = config('services.twilio.whatsapp_from');
        if (!$from) {
            throw new RuntimeException('Twilio WhatsApp from number is not configured.');
        }

        if (strpos($from, 'whatsapp:') !== 0) {
            $from = 'whatsapp:' . $from;
        }

        $to = trim($to);
        if (strpos($to, 'whatsapp:') !== 0) {
            $to = 'whatsapp:' . $to;
        }

        return $this->client->messages->create(
            $to,
            [
                'from' => $from,
                'body' => $message,
            ]
        );
    }

    protected function normalizeNumber($number)
    {
        return preg_replace('/\s+/', '', trim($number));
    }
}
