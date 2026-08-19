<?php

namespace App\Http\Controllers;

use App\Services\SquareService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Square\Utils\WebhooksHelper;

class SquareWebhookController extends Controller
{
    public function handle(Request $request, SquareService $square)
    {
        $rawBody = $request->getContent();
        $payload = json_decode($rawBody, true);

        if (!is_array($payload)) {
            return response()->json(['message' => 'Invalid payload.'], 400);
        }

        $settings = $square->settings(null, $this->locationIdFromPayload($payload));

        if (!empty($settings['webhook_signature_key'])) {
            $signature = $request->header('x-square-hmacsha256-signature');
            $notificationUrl = $settings['webhook_url'] ?: route('square.webhook');

            if (!$signature || !WebhooksHelper::isValidWebhookEventSignature($rawBody, $signature, $settings['webhook_signature_key'], $notificationUrl)) {
                Log::warning('Rejected Square webhook with invalid signature.');
                return response()->json(['message' => 'Invalid signature.'], 403);
            }
        } else {
            Log::warning('Square webhook signature key is not configured; webhook processing is disabled.');
            return response()->json(['message' => 'Square webhook signature key is not configured.'], 503);
        }

        try {
            $order = $square->handleWebhookPayload($payload);

            return response()->json([
                'status' => 'ok',
                'order_id' => $order ? $order->id : null,
            ]);
        } catch (\Exception $exception) {
            Log::error('Square webhook processing failed: ' . $exception->getMessage(), [
                'type' => isset($payload['type']) ? $payload['type'] : null,
            ]);

            return response()->json(['message' => 'Webhook processing failed.'], 500);
        }
    }

    private function locationIdFromPayload(array $payload)
    {
        $object = isset($payload['data']['object']) && is_array($payload['data']['object']) ? $payload['data']['object'] : [];

        if (!empty($object['payment']['location_id'])) {
            return $object['payment']['location_id'];
        }

        if (!empty($object['order']['location_id'])) {
            return $object['order']['location_id'];
        }

        if (!empty($object['order_created']['location_id'])) {
            return $object['order_created']['location_id'];
        }

        if (!empty($object['order_updated']['location_id'])) {
            return $object['order_updated']['location_id'];
        }

        return null;
    }
}
