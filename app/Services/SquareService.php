<?php

namespace App\Services;

use App\CentralLogics\Helpers;
use App\Model\AddOn;
use App\Model\Branch;
use App\Model\Order;
use App\Model\OrderDetail;
use App\Model\Product;
use App\Model\Reward;
use App\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use RuntimeException;
use Square\Models\AcceptedPaymentMethods;
use Square\Models\CheckoutOptions;
use Square\Models\CreatePaymentLinkRequest;
use Square\Models\Money;
use Square\Models\ObtainTokenRequest;
use Square\Models\Order as SquareOrder;
use Square\Models\OrderFulfillment;
use Square\Models\OrderFulfillmentDeliveryDetails;
use Square\Models\OrderFulfillmentDeliveryDetailsScheduleType;
use Square\Models\OrderFulfillmentPickupDetails;
use Square\Models\OrderFulfillmentPickupDetailsScheduleType;
use Square\Models\OrderFulfillmentRecipient;
use Square\Models\OrderFulfillmentState;
use Square\Models\OrderFulfillmentType;
use Square\Models\OrderLineItem;
use Square\Models\OrderLineItemModifier;
use Square\Models\OrderSource;
use Square\SquareClient;

class SquareService
{
    const LOCAL_REFERENCE_PREFIX = 'sq_';

    protected static $columnCache = [];
    protected static $tableCache = [];

    public function settings($branchId = null, $locationId = null)
    {
        $db = [];
        try {
            $db = 
            Helpers::get_business_settings('square') ?: [];
            $db = is_array($db) ? $db : [];
        } catch (\Exception $exception) {
            $db = [];
        }

        $branch = $this->squareBranch($branchId, $locationId);
        if ($branch) {
            $this->refreshBranchOAuthTokenIfNeeded($branch);
            $branch = Branch::find($branch->id);
        }
        $useBranchSettings = (bool) $branch;
        $branchAccessToken = $branch && $this->hasColumn('branches', 'square_access_token') ? $branch->square_access_token : null;
        $branchLocationId = $branch && $this->hasColumn('branches', 'square_location_id') ? $branch->square_location_id : null;
        $branchMerchantId = $branch && $this->hasColumn('branches', 'square_merchant_id') ? $branch->square_merchant_id : null;
        $branchOAuthRefreshToken = $branch && $this->hasColumn('branches', 'square_oauth_refresh_token') ? $branch->square_oauth_refresh_token : null;

        $accessToken = $useBranchSettings ? $branchAccessToken : $this->first(config('services.square.access_token'), isset($db['access_token']) ? $db['access_token'] : null);
        $squareLocationId = $useBranchSettings ? $branchLocationId : $this->first(config('services.square.location_id'), isset($db['location_id']) ? $db['location_id'] : null);
        $branchStatus = $branch && $this->hasColumn('branches', 'square_status') ? $branch->square_status : null;
        $branchCommissionStatus = $branch && $this->hasColumn('branches', 'square_commission_status') ? $branch->square_commission_status : null;
        $branchCommissionType = $branch && $this->hasColumn('branches', 'square_commission_type') ? $branch->square_commission_type : null;
        $branchCommissionValue = $branch && $this->hasColumn('branches', 'square_commission_value') ? $branch->square_commission_value : null;
        $globalCommissionStatus = $this->first(config('services.square.commission_status'), isset($db['commission_status']) ? $db['commission_status'] : 0);
        $globalCommissionType = $this->first(config('services.square.commission_type'), isset($db['commission_type']) ? $db['commission_type'] : 'percent');
        $globalCommissionValue = $this->first(config('services.square.commission_value'), isset($db['commission_value']) ? $db['commission_value'] : 0);

        return [
            'status' => (int) ($useBranchSettings ? $branchStatus : $this->first(config('services.square.status'), ($accessToken && $squareLocationId) ? 1 : (isset($db['status']) ? $db['status'] : 0))),
            'application_id' => $useBranchSettings ? ($this->hasColumn('branches', 'square_application_id') ? $branch->square_application_id : null) : $this->first(config('services.square.application_id'), isset($db['application_id']) ? $db['application_id'] : null),
            'access_token' => $accessToken,
            'location_id' => $squareLocationId,
            'environment' => $useBranchSettings ? $this->normalizeEnvironment($this->hasColumn('branches', 'square_environment') ? $branch->square_environment : 'sandbox') : $this->normalizeEnvironment($this->first(config('services.square.environment'), isset($db['environment']) ? $db['environment'] : 'sandbox')),
            'webhook_signature_key' => $useBranchSettings ? ($this->hasColumn('branches', 'square_webhook_signature_key') ? $branch->square_webhook_signature_key : null) : $this->first(config('services.square.webhook_signature_key'), isset($db['webhook_signature_key']) ? $db['webhook_signature_key'] : null),
            'webhook_url' => $this->first(config('services.square.webhook_url'), isset($db['webhook_url']) ? $db['webhook_url'] : null),
            'branch_id' => $branch ? $branch->id : null,
            'merchant_id' => $branchMerchantId,
            'oauth_connected' => $useBranchSettings && !empty($branchMerchantId) && !empty($branchOAuthRefreshToken),
            'commission_status' => (int) ($useBranchSettings && $branchCommissionStatus !== null ? $branchCommissionStatus : $globalCommissionStatus),
            'commission_type' => $this->normalizeCommissionType($useBranchSettings ? $this->first($branchCommissionType, $globalCommissionType) : $globalCommissionType),
            'commission_value' => (float) ($useBranchSettings ? $this->first($branchCommissionValue, $globalCommissionValue) : $globalCommissionValue),
        ];
    }

    public function isEnabled($branchId = null, $locationId = null)
    {
        $settings = $this->settings($branchId, $locationId);

        return (int) $settings['status'] === 1
            && !empty($settings['access_token'])
            && !empty($settings['location_id']);
    }

    public function client($branchId = null, $locationId = null)
    {
        $settings = $this->settings($branchId, $locationId);

        if (empty($settings['access_token'])) {
            throw new RuntimeException('Square access token is not configured.');
        }

        return new SquareClient([
            'accessToken' => $settings['access_token'],
            'environment' => $settings['environment'],
        ]);
    }

    public function createCheckoutPaymentLink($amount, $currency, $callback = null, $branchId = null, $customerId = null, array $checkoutContext = [])
    {
        $settings = $this->settings($branchId);
        if (!$this->isEnabled($branchId)) {
            throw new RuntimeException('Square is not configured for this branch.');
        }

        if (!$this->hasTable('square_payment_references')) {
            throw new RuntimeException('Square payment references table is missing. Run php artisan migrate --force.');
        }

        $currency = strtoupper($currency ?: 'USD');
        $locationId = $this->locationIdForBranch($branchId, $settings);

        // Check if a transaction reference / key was provided in checkout context
        $providedReference = $this->first(
            isset($checkoutContext['transaction_reference']) ? $checkoutContext['transaction_reference'] : null,
            isset($checkoutContext['transaction_key']) ? $checkoutContext['transaction_key'] : null,
            isset($checkoutContext['reference']) ? $checkoutContext['reference'] : null
        );

        if ($providedReference) {
            $reference = strpos($providedReference, self::LOCAL_REFERENCE_PREFIX) === 0
                ? $providedReference
                : self::LOCAL_REFERENCE_PREFIX . $providedReference;
        } else {
            // Generate unique transaction key BEFORE calling Square API
            $userIdPart = $customerId ? '_' . $customerId : '';
            $reference = self::LOCAL_REFERENCE_PREFIX . 'ORD_' . time() . '_' . substr(uniqid(), -4) . $userIdPart;
        }

        if (strlen($reference) > 30) {
            $reference = substr($reference, 0, 30);
        }

        // Reuse existing payment reference and link if already created (e.g. on client retry)
        if ($this->hasTable('square_payment_references')) {
            $existingPaymentReference = $this->paymentReference($reference);
            if ($existingPaymentReference) {
                $existingPayload = json_decode($existingPaymentReference->payload, true);
                $existingPaymentLink = isset($existingPayload['payment_link']) ? $existingPayload['payment_link'] : null;
                $existingUrl = $existingPaymentLink ? ($existingPaymentLink['url'] ?? null) : null;

                if ($existingUrl) {
                    Log::info('Reusing existing Square payment link for transaction reference.', [
                        'reference' => $reference,
                        'square_order_id' => $existingPaymentReference->square_order_id,
                        'customer_id' => $customerId,
                        'branch_id' => $branchId,
                    ]);

                    return [
                        'reference' => $reference,
                        'square_order_id' => $existingPaymentReference->square_order_id,
                        'payment_link' => [
                            'url' => $existingUrl,
                            'long_url' => isset($existingPaymentLink['long_url']) ? $existingPaymentLink['long_url'] : $existingUrl,
                            'order_id' => $existingPaymentReference->square_order_id,
                        ],
                    ];
                }
            }
        }

        $restaurantName = $this->restaurantName();
        $checkoutRewards = $this->checkoutRewards($checkoutContext);
        $this->assertCheckoutRewardsAvailable($checkoutRewards);
        $this->assertCustomerHasRewardPoints($customerId, $checkoutRewards);
        $hasRewards = count($checkoutRewards) > 0;
        $lineItems = $this->checkoutLineItems($amount, $currency, $checkoutContext, $restaurantName);

        $source = new OrderSource();
        $source->setName('Website/App');

        $squareOrder = new SquareOrder($locationId);
        $squareOrder->setReferenceId($reference);
        $squareOrder->setSource($source);
        $squareOrder->setLineItems($lineItems);
        $fulfillments = $this->checkoutFulfillments($checkoutContext, $customerId);
        if (count($fulfillments) > 0) {
            $squareOrder->setFulfillments($fulfillments);
        }
        
        $cartToken = urldecode($checkoutContext['cart']);
        $cartItems = base64_decode($cartToken);
      
        $squareOrder->setMetadata(array_filter([
            'local_reference' => $reference,
            'branch_id' => $branchId ? (string) $branchId : null,
            'customer_id' => $customerId ? (string) $customerId : null,
            'source' => 'website_app',
            'order_type' => isset($checkoutContext['order_type']) ? (string) $checkoutContext['order_type'] : null,
            'delivery_date' => isset($checkoutContext['delivery_date']) ? (string) $checkoutContext['delivery_date'] : null,
            'delivery_time' => isset($checkoutContext['delivery_time']) ? (string) $checkoutContext['delivery_time'] : null,
            'coupon_code' => isset($checkoutContext['coupon_code']) ? (string) $checkoutContext['coupon_code'] : null,
            'coupon_discount_amount' => isset($checkoutContext['coupon_discount_amount']) ? (string) $checkoutContext['coupon_discount_amount'] : null,
            'has_cart_items' => $this->checkoutHasItemContext($checkoutContext) ? '1' : '0',
            'has_rewards' => $hasRewards ? '1' : '0',
         //   'cart_items' => $cartItems ?? ''
        ], function ($value) {
            return $value !== null && $value !== '';
        }));

        $checkoutOptions = new CheckoutOptions();
        $checkoutOptions->setRedirectUrl(route('pay-square.success', [
            'callback' => $callback,
            'transaction_reference' => $reference,
        ]));
        $acceptedPaymentMethods = new AcceptedPaymentMethods();
        $acceptedPaymentMethods->setApplePay(true);
        $checkoutOptions->setAcceptedPaymentMethods($acceptedPaymentMethods);
        $appFeeMoney = $this->applicationFeeMoney($amount, $currency, $settings);
        if ($appFeeMoney) {
            $checkoutOptions->setAppFeeMoney($appFeeMoney);
        }

        $body = new CreatePaymentLinkRequest();
        $body->setIdempotencyKey($reference);
        $body->setDescription($restaurantName ? $restaurantName . ' order payment' : 'Order payment');
        $body->setOrder($squareOrder);
        $body->setCheckoutOptions($checkoutOptions);
        $body->setPaymentNote('Local reference ' . $reference);

        $response = $this->client($branchId, $locationId)->getCheckoutApi()->createPaymentLink($body);
        if (!$response->isSuccess() && $hasRewards) {
            Log::warning('Square checkout rejected reward line items; retrying with reward note fallback.', [
                'reference' => $reference,
                'branch_id' => $branchId,
                'errors' => $this->formatSquareErrors($response->getErrors()),
            ]);

            $lineItems = $this->checkoutLineItems($amount, $currency, $checkoutContext, $restaurantName, false);

            $squareOrder->setLineItems($lineItems);
            $body->setIdempotencyKey($reference . '_reward_note');
            $body->setOrder($squareOrder);

            $response = $this->client($branchId, $locationId)->getCheckoutApi()->createPaymentLink($body);
        }

        if (!$response->isSuccess()) {
            throw new RuntimeException($this->formatSquareErrors($response->getErrors()));
        }

        $result = $response->getResult();
        $paymentLink = $result ? $result->getPaymentLink() : null;
        $squareOrderId = $paymentLink ? $paymentLink->getOrderId() : null;

        $paymentLinkUrl = $paymentLink ? $paymentLink->getUrl() : null;
        $paymentLinkLongUrl = $paymentLink ? ($paymentLink->getLongUrl() ?: $paymentLink->getUrl()) : null;

        $this->storePaymentReference($reference, [
            'square_order_id' => $squareOrderId,
            'square_location_id' => $locationId,
            'customer_id' => $customerId,
            'branch_id' => $branchId,
            'amount' => (float) $amount,
            'currency' => $currency,
            'app_fee_amount' => $appFeeMoney ? $this->moneyToAmount($appFeeMoney) : null,
            'app_fee_currency' => $appFeeMoney ? $appFeeMoney->getCurrency() : null,
            'app_fee_type' => $appFeeMoney ? $settings['commission_type'] : null,
            'app_fee_value' => $appFeeMoney ? $settings['commission_value'] : null,
            'callback' => $callback,
            'status' => 'created',
            'payload' => json_encode([
                'payment_link' => [
                    'url' => $paymentLinkUrl,
                    'long_url' => $paymentLinkLongUrl,
                    'order_id' => $squareOrderId,
                ],
                'result' => $result,
            ]),
        ]);

        return [
            'reference' => $reference,
            'square_order_id' => $squareOrderId,
            'payment_link' => [
                'url' => $paymentLink ? $paymentLink->getUrl() : null,
                'long_url' => $paymentLink ? ($paymentLink->getLongUrl() ?: $paymentLink->getUrl()) : null,
                'order_id' => $squareOrderId,
            ],
        ];
    }

    public function markPaymentReferenceReturned($reference)
    {
        if (!$reference || !$this->hasTable('square_payment_references')) {
            return;
        }

        $paymentReference = $this->paymentReference($reference);
        $status = $paymentReference && $this->isPaidStatus($paymentReference->status)
            ? $paymentReference->status
            : 'returned';

        DB::table('square_payment_references')
            ->where('reference', $reference)
            ->update([
                'status' => $status,
                'updated_at' => now(),
            ]);
    }

    public function confirmCheckoutPaymentReference($reference, $attempts = 1, $sleepMicroseconds = 0)
    {
        $attempts = max(1, (int) $attempts);

        for ($attempt = 1; $attempt <= $attempts; $attempt++) {
            $paymentReference = $this->paymentReference($reference);
            if (!$paymentReference) {
                return false;
            }

            if ($this->isPaidStatus($paymentReference->status)) {
                return true;
            }

            try {
                if (!empty($paymentReference->square_order_id)) {
                    $this->syncSquareOrder(
                        $paymentReference->square_order_id,
                        $paymentReference->square_payment_id,
                        isset($paymentReference->branch_id) ? $paymentReference->branch_id : null,
                        $paymentReference->square_location_id
                    );
                }
            } catch (\Exception $exception) {
                Log::warning('Square checkout order confirmation failed: ' . $exception->getMessage(), [
                    'reference' => $reference,
                    'square_order_id' => $paymentReference->square_order_id,
                    'attempt' => $attempt,
                ]);
            }

            $paymentReference = $this->paymentReference($reference);
            if ($paymentReference && $this->isPaidStatus($paymentReference->status)) {
                return true;
            }

            try {
                if ($paymentReference && $this->syncCompletedPaymentForReference($paymentReference)) {
                    return true;
                }
            } catch (\Exception $exception) {
                Log::warning('Square checkout payment lookup failed: ' . $exception->getMessage(), [
                    'reference' => $reference,
                    'attempt' => $attempt,
                ]);
            }

            if ($attempt < $attempts && $sleepMicroseconds > 0) {
                usleep((int) $sleepMicroseconds);
            }
        }

        return false;
    }

    public function validateCheckoutPayment($reference, $customerId = null, $amount = null, $currency = null, $allowedOrderId = null, $branchId = null)
    {
        if (!$reference || substr($reference, 0, strlen(self::LOCAL_REFERENCE_PREFIX)) !== self::LOCAL_REFERENCE_PREFIX) {
            return ['valid' => false, 'message' => 'Square payment reference is missing or invalid.'];
        }

        if (!$this->hasTable('square_payment_references')) {
            return ['valid' => false, 'message' => 'Square payment table is missing. Run migrations before accepting Square orders.'];
        }

        $paymentReference = $this->paymentReference($reference);
        if (!$paymentReference) {
            return ['valid' => false, 'message' => 'Square payment reference was not found.'];
        }

        if (!empty($paymentReference->local_order_id) && (!$allowedOrderId || (int) $paymentReference->local_order_id !== (int) $allowedOrderId)) {
            return ['valid' => false, 'message' => 'Square payment reference has already been used.'];
        }

        if ($customerId && $paymentReference->customer_id && (string) $paymentReference->customer_id !== (string) $customerId) {
            return ['valid' => false, 'message' => 'Square payment reference does not belong to this customer.'];
        }

        if ($branchId && $paymentReference->branch_id && (string) $paymentReference->branch_id !== (string) $branchId) {
            return ['valid' => false, 'message' => 'Square payment reference does not belong to this branch.'];
        }

        if ($amount !== null && !$this->amountsMatch($paymentReference->amount, $amount)) {
            return ['valid' => false, 'message' => 'Square payment amount does not match the order amount.'];
        }

        if ($currency && $paymentReference->currency && strtoupper($paymentReference->currency) !== strtoupper($currency)) {
            return ['valid' => false, 'message' => 'Square payment currency does not match the order currency.'];
        }

        if (!$this->confirmCheckoutPaymentReference($reference, 6, 750000)) {
            return ['valid' => false, 'message' => 'Square payment is not confirmed as paid.'];
        }

        return ['valid' => true, 'message' => 'Square payment confirmed.'];
    }

    public function checkoutReferenceDetails($reference)
    {
        return $this->paymentReference($reference);
    }

    public function attachLocalOrder(Order $order, $reference = null)
    {
        if (!$order || $order->payment_method !== 'square') {
            return;
        }

        $reference = $reference ?: $order->transaction_reference;
        $paymentReference = $this->paymentReference($reference);
        if ($paymentReference && $paymentReference->local_order_id && (int) $paymentReference->local_order_id !== (int) $order->id) {
            Log::warning('Square payment reference is already attached to another order.', [
                'reference' => $reference,
                'existing_order_id' => $paymentReference->local_order_id,
                'order_id' => $order->id,
            ]);

            return;
        }

        $squareLocationId = $paymentReference && $paymentReference->square_location_id
            ? $paymentReference->square_location_id
            : null;

        if (!$squareLocationId) {
            try {
                $squareLocationId = $this->locationIdForBranch($order->branch_id);
            } catch (\Exception $exception) {
                Log::warning('Unable to attach Square location to local order: ' . $exception->getMessage(), [
                    'order_id' => $order->id,
                ]);
            }
        }

        if ($this->hasColumn('orders', 'square_order_id') && $paymentReference && $paymentReference->square_order_id) {
            $order->square_order_id = $paymentReference->square_order_id;
        }

        if ($this->hasColumn('orders', 'square_payment_id') && $paymentReference && $paymentReference->square_payment_id) {
            $order->square_payment_id = $paymentReference->square_payment_id;
        }

        if ($this->hasColumn('orders', 'square_location_id')) {
            $order->square_location_id = $squareLocationId;
        }

        if ($this->hasColumn('orders', 'square_application_fee_amount') && $paymentReference && isset($paymentReference->app_fee_amount)) {
            $order->square_application_fee_amount = $paymentReference->app_fee_amount;
        }

        if ($this->hasColumn('orders', 'square_application_fee_currency') && $paymentReference && isset($paymentReference->app_fee_currency)) {
            $order->square_application_fee_currency = $paymentReference->app_fee_currency;
        }

        if ($this->hasColumn('orders', 'square_source')) {
            $order->square_source = $paymentReference ? 'website_app' : 'local_pos';
        }

        $order->save();

        if ($paymentReference && $this->hasTable('square_payment_references')) {
            $status = $this->isPaidStatus($paymentReference->status) ? $paymentReference->status : 'local_order_attached';

            DB::table('square_payment_references')
                ->where('reference', $reference)
                ->update([
                    'local_order_id' => $order->id,
                    'status' => $status,
                    'updated_at' => now(),
                ]);
        }
    }

    // public function handleWebhookPayload(array $payload)
    // {
    //     $orderId = $this->extractSquareOrderId($payload);
    //     $paymentId = $this->extractSquarePaymentId($payload);

    //     if (!$orderId && $paymentId) {
    //         Log::info('Square webhook received without an order id.', [
    //             'type' => isset($payload['type']) ? $payload['type'] : null,
    //             'payment_id' => $paymentId,
    //         ]);

    //         return null;
    //     }

    //     if (!$orderId) {
    //         Log::info('Square webhook ignored because it has no order id.', [
    //             'type' => isset($payload['type']) ? $payload['type'] : null,
    //         ]);

    //         return null;
    //     }

    //     return $this->syncSquareOrder($orderId, $paymentId);
    // }
public function handleWebhookPayload(array $payload)
{
    $orderId = $this->extractSquareOrderId($payload);
    $paymentId = $this->extractSquarePaymentId($payload);

    if (!$orderId) {
        return null;
    }

    // Verify this is our order BEFORE processing
    try {
        $response = $this->client()->getOrdersApi()->retrieveOrder($orderId);
        if ($response->isSuccess()) {
            $squareOrder = $response->getResult()->getOrder();
            $reference = $this->localReferenceFromSquareOrder($squareOrder);
            
            // ONLY process if it has sq_ prefix
            if (!$reference || strpos($reference, self::LOCAL_REFERENCE_PREFIX) !== 0) {
                Log::warning('Square webhook blocked: Not from AppDash', [
                    'order_id' => $orderId,
                    'reference' => $reference,
                ]);
                return null;
            }
        }
    } catch (\Exception $e) {
        Log::error('Square webhook verification failed: ' . $e->getMessage(), [
            'order_id' => $orderId,
        ]);
        return null;
    }

    return $this->syncSquareOrder($orderId, $paymentId);
}

    // public function syncSquareOrder($squareOrderId, $paymentId = null, $branchId = null, $locationId = null)
    // {
    //     $response = $this->client($branchId, $locationId)->getOrdersApi()->retrieveOrder($squareOrderId);

    //     if (!$response->isSuccess()) {
    //         throw new RuntimeException($this->formatSquareErrors($response->getErrors()));
    //     }

    //     $squareOrder = $response->getResult()->getOrder();
    //     $paymentId = $paymentId ?: $this->paymentIdFromOrder($squareOrder);
    //     $reference = $this->localReferenceFromSquareOrder($squareOrder);

    //     if ($reference) {
    //         $branchId = $branchId ?: $this->branchIdFromSquareOrder($squareOrder);
    //         $isPaid = $this->isSquareOrderPaid($squareOrder, $paymentId, $branchId, $squareOrder->getLocationId());

    //         $this->storePaymentReference($reference, [
    //             'square_order_id' => $squareOrder->getId(),
    //             'square_payment_id' => $paymentId,
    //             'square_location_id' => $squareOrder->getLocationId(),
    //             'branch_id' => $branchId,
    //             'status' => $isPaid ? 'paid' : 'updated',
    //             'payload' => json_encode($squareOrder),
    //         ]);

    //         $localOrder = Order::where('transaction_reference', $reference)->first();
    //         if ($localOrder) {
    //             $this->attachLocalOrder($localOrder, $reference);
    //         }

    //         return $localOrder;
    //     }

    //     return $this->importSquarePosOrder($squareOrder, $paymentId);
    // }

public function syncSquareOrder($squareOrderId, $paymentId = null, $branchId = null, $locationId = null)
{
    $response = $this->client($branchId, $locationId)->getOrdersApi()->retrieveOrder($squareOrderId);

    if (!$response->isSuccess()) {
        throw new RuntimeException($this->formatSquareErrors($response->getErrors()));
    }

    $squareOrder = $response->getResult()->getOrder();
    $paymentId = $paymentId ?: $this->paymentIdFromOrder($squareOrder);
    $reference = $this->localReferenceFromSquareOrder($squareOrder);

    // CRITICAL FIX: ONLY process orders with sq_ prefix
    if ($reference && strpos($reference, self::LOCAL_REFERENCE_PREFIX) === 0) {
        $branchId = $branchId ?: $this->branchIdFromSquareOrder($squareOrder);

        $isPaid = $this->isSquareOrderPaid(
            $squareOrder,
            $paymentId,
            $branchId,
            $squareOrder->getLocationId()
        );

        $this->storePaymentReference($reference, [
            'square_order_id' => $squareOrder->getId(),
            'square_payment_id' => $paymentId,
            'square_location_id' => $squareOrder->getLocationId(),
            'branch_id' => $branchId,
            'status' => $isPaid ? 'paid' : 'updated',
            'payload' => json_encode($squareOrder),
        ]);

        $localOrder = Order::where('transaction_reference', $reference)->first();

        if ($localOrder) {
            $this->attachLocalOrder($localOrder, $reference);
        }

        return $localOrder;
    }

    // IGNORE all POS/in-store orders
    Log::warning('Blocked Square POS order import', [
        'square_order_id' => $squareOrder->getId(),
        'reference' => $reference,
        'location_id' => $squareOrder->getLocationId(),
    ]);

    return null; // CRITICAL: Return null, don't import
}

    // protected function importSquarePosOrder(SquareOrder $squareOrder, $paymentId = null)
    // {
    //     $branch = $this->branchForLocation($squareOrder->getLocationId());
    //     if (!$branch) {
    //         Log::warning('Square POS order ignored because its location is not mapped to a branch.', [
    //             'square_order_id' => $squareOrder->getId(),
    //             'square_location_id' => $squareOrder->getLocationId(),
    //         ]);

    //         throw new RuntimeException('Square location is not mapped to a branch: ' . $squareOrder->getLocationId());
    //     }

    //     $amount = $this->moneyToAmount($squareOrder->getTotalMoney());
    //     $taxAmount = $this->moneyToAmount($squareOrder->getTotalTaxMoney());
    //     $lastException = null;

    //     for ($attempt = 1; $attempt <= 3; $attempt++) {
    //         try {
    //             $result = DB::transaction(function () use ($squareOrder, $paymentId, $branch, $amount, $taxAmount) {
    //                 $reference = substr($paymentId ?: $squareOrder->getId(), 0, 30);
    //                 $localOrder = null;

    //                 if ($this->hasColumn('orders', 'square_order_id')) {
    //                     $localOrder = Order::where('square_order_id', $squareOrder->getId())
    //                         ->lockForUpdate()
    //                         ->first();
    //                 }

    //                 if (!$localOrder) {
    //                     $localOrder = Order::where('transaction_reference', $reference)
    //                         ->where('payment_method', 'square')
    //                         ->lockForUpdate()
    //                         ->first();
    //                 }

    //                 $isNew = false;
    //                 if (!$localOrder) {
    //                     $localOrder = new Order();
    //                     $localOrder->id = $this->nextOrderIdForUpdate();
    //                     $localOrder->created_at = $this->squareDate($squareOrder->getCreatedAt()) ?: Helpers::order_now();
    //                     $isNew = true;
    //                 }

    //                 $localOrder->user_id = $localOrder->user_id ?: null;
    //                 $localOrder->order_amount = $amount;
    //                 $localOrder->coupon_discount_amount = $localOrder->coupon_discount_amount ?: 0;
    //                 $localOrder->coupon_discount_title = $localOrder->coupon_discount_title ?: null;
    //                 $localOrder->payment_status = $this->isSquareOrderPaid($squareOrder, $paymentId, $branch ? $branch->id : null, $squareOrder->getLocationId()) ? 'paid' : 'unpaid';
    //                 $localOrder->order_status = $this->mapSquareOrderStatus($squareOrder);
    //                 $localOrder->total_tax_amount = $taxAmount;
    //                 $localOrder->payment_method = 'square';
    //                 $localOrder->transaction_reference = $reference;
    //                 $localOrder->delivery_address_id = $localOrder->delivery_address_id ?: null;
    //                 $localOrder->order_type = 'pos';
    //                 $localOrder->branch_id = $branch->id;
    //                 $localOrder->checked = 1;
    //                 $orderTimestamp = Helpers::order_now();
    //                 $localOrder->delivery_date = $orderTimestamp->format('Y-m-d');
    //                 $localOrder->delivery_time = $orderTimestamp->format('H:i:s');
    //                 $localOrder->order_note = 'Imported from Square POS order ' . $squareOrder->getId();
    //                 $localOrder->updated_at = $orderTimestamp;

    //                 if ($this->hasColumn('orders', 'square_order_id')) {
    //                     $localOrder->square_order_id = $squareOrder->getId();
    //                 }

    //                 if ($this->hasColumn('orders', 'square_payment_id')) {
    //                     $localOrder->square_payment_id = $paymentId ?: $this->paymentIdFromOrder($squareOrder);
    //                 }

    //                 if ($this->hasColumn('orders', 'square_location_id')) {
    //                     $localOrder->square_location_id = $squareOrder->getLocationId();
    //                 }

    //                 if ($this->hasColumn('orders', 'square_source')) {
    //                     $localOrder->square_source = 'square_pos';
    //                 }

    //                 $localOrder->save();

    //                 if ($isNew) {
    //                     $this->importSquareLineItems($localOrder, $squareOrder);
    //                 }

    //                 return [$localOrder, $isNew];
    //             });

    //             if ($result[1]) {
    //                 $this->notifyKitchen($result[0]);
    //             }

    //             return $result[0];
    //         } catch (\Illuminate\Database\QueryException $exception) {
    //             $lastException = $exception;
    //             if (!$this->isDuplicateKeyException($exception)) {
    //                 throw $exception;
    //             }

    //             $existing = $this->existingSquareImportedOrder($squareOrder, $paymentId);
    //             if ($existing) {
    //                 return $existing;
    //             }

    //             usleep(100000 * $attempt);
    //         }
    //     }

    //     if ($lastException) {
    //         throw $lastException;
    //     }

    //     return null;
    // }

protected function importSquarePosOrder(SquareOrder $squareOrder, $paymentId = null)
{
    // First check if this is a POS order we should import
    $reference = $this->localReferenceFromSquareOrder($squareOrder);
    if ($reference && substr($reference, 0, strlen(self::LOCAL_REFERENCE_PREFIX)) === self::LOCAL_REFERENCE_PREFIX) {
        Log::warning('Attempted to import POS order that has our reference.', [
            'square_order_id' => $squareOrder->getId(),
            'reference' => $reference,
        ]);
        return null;
    }

    // CRITICAL: Skip POS orders by default
    Log::warning('Square POS order import is disabled to prevent unrelated orders.', [
        'square_order_id' => $squareOrder->getId(),
        'square_location_id' => $squareOrder->getLocationId(),
    ]);
    
    return null; // ✅ POS import DISABLED
}

    protected function importSquareLineItems(Order $localOrder, SquareOrder $squareOrder)
    {
        $lineItems = $squareOrder->getLineItems() ?: [];
        foreach ($lineItems as $lineItem) {
            $quantity = (int) $lineItem->getQuantity();
            $quantity = $quantity > 0 ? $quantity : 1;
            $totalAmount = $this->moneyToAmount($lineItem->getTotalMoney());
            $price = $quantity > 0 ? $totalAmount / $quantity : $totalAmount;

            DB::table('order_details')->insert([
                'order_id' => $localOrder->id,
                'product_id' => null,
                'product_details' => json_encode($this->squareProductDetails($lineItem)),
                'quantity' => $quantity,
                'price' => $price,
                'tax_amount' => $this->moneyToAmount($lineItem->getTotalTaxMoney()),
                'discount_on_product' => $this->moneyToAmount($lineItem->getTotalDiscountMoney()),
                'discount_type' => 'discount_on_product',
                'variant' => json_encode([]),
                'variation' => json_encode([]),
                'add_on_ids' => json_encode([]),
                'add_on_qtys' => json_encode([]),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    protected function squareProductDetails(OrderLineItem $lineItem)
    {
        return [
            'name' => $lineItem->getName() ?: 'Square item',
            'description' => $lineItem->getVariationName(),
            'image' => null,
            'add_ons' => [],
            'variations' => [],
            'attributes' => [],
            'category_ids' => [],
            'choice_options' => [],
            'square_catalog_object_id' => $lineItem->getCatalogObjectId(),
            'square_metadata' => $lineItem->getMetadata() ?: [],
        ];
    }

    protected function notifyKitchen(Order $order)
    {
        try {
            $sent = Helpers::send_push_notif_to_topic([
                'title' => 'You have a new Square POS order.',
                'description' => $order->id,
                'order_id' => $order->id,
                'image' => '',
            ], "kitchen-{$order->branch_id}", 'general');
            if (!$sent) {
                Log::warning('Square POS kitchen notification was not delivered.', [
                    'order_id' => $order->id,
                    'branch_id' => $order->branch_id,
                    'topic' => "kitchen-{$order->branch_id}",
                ]);
            }
        } catch (\Exception $exception) {
            Log::warning('Square POS kitchen notification failed: ' . $exception->getMessage(), [
                'order_id' => $order->id,
                'branch_id' => $order->branch_id,
                'topic' => "kitchen-{$order->branch_id}",
            ]);
        }
    }

    public function locationIdForBranch($branchId = null, array $settings = null)
    {
        if ($branchId && $this->hasColumn('branches', 'square_location_id')) {
            $branch = Branch::find($branchId);
            if ($branch && !empty($branch->square_location_id)) {
                return $branch->square_location_id;
            }
        }

        $settings = $settings ?: $this->settings($branchId);
        if (empty($settings['location_id'])) {
            throw new RuntimeException('Square location ID is not configured.');
        }

        return $settings['location_id'];
    }

    protected function branchForLocation($locationId)
    {
        if ($locationId && $this->hasColumn('branches', 'square_location_id')) {
            $branch = Branch::where('square_location_id', $locationId)->first();
            if ($branch) {
                return $branch;
            }
        }

        $settings = $this->settings();
        if ($locationId && $settings['location_id'] === $locationId) {
            return Branch::where('id', 1)->first() ?: Branch::first();
        }

        return null;
    }

    protected function squareBranch($branchId = null, $locationId = null)
    {
        if ($branchId) {
            return Branch::find($branchId);
        }

        if ($locationId && $this->hasColumn('branches', 'square_location_id')) {
            return Branch::where('square_location_id', $locationId)->first();
        }

        return null;
    }

    protected function refreshBranchOAuthTokenIfNeeded(Branch $branch)
    {
        if (!$this->hasColumn('branches', 'square_oauth_refresh_token') || empty($branch->square_oauth_refresh_token)) {
            return;
        }

        if ($this->hasColumn('branches', 'square_oauth_token_expires_at') && $branch->square_oauth_token_expires_at) {
            try {
                if (Carbon::parse($branch->square_oauth_token_expires_at)->greaterThan(now()->addDays(3))) {
                    return;
                }
            } catch (\Exception $exception) {
                // If the saved expiry cannot be parsed, try refreshing with the stored refresh token.
            }
        }

        $clientId = $this->first(
            config('services.square.application_id'),
            $this->hasColumn('branches', 'square_application_id') ? $branch->square_application_id : null
        );
        $clientSecret = config('services.square.application_secret');

        if (!$clientId || !$clientSecret) {
            Log::warning('Square OAuth token refresh skipped because OAuth app credentials are missing.', [
                'branch_id' => $branch->id,
            ]);
            return;
        }

        try {
            $body = new ObtainTokenRequest($clientId, 'refresh_token');
            $body->setClientSecret($clientSecret);
            $body->setRefreshToken($branch->square_oauth_refresh_token);

            $environment = $this->normalizeEnvironment($this->hasColumn('branches', 'square_environment') ? $branch->square_environment : config('services.square.environment', 'sandbox'));
            $response = (new SquareClient([
                'accessToken' => '',
                'environment' => $environment,
            ]))->getOAuthApi()->obtainToken($body);

            if (!$response->isSuccess()) {
                Log::warning('Square OAuth token refresh failed: ' . $this->formatSquareErrors($response->getErrors()), [
                    'branch_id' => $branch->id,
                ]);
                return;
            }

            $token = $response->getResult();
            if (!$token || !$token->getAccessToken()) {
                Log::warning('Square OAuth token refresh returned no access token.', [
                    'branch_id' => $branch->id,
                ]);
                return;
            }

            $fields = [
                'square_access_token' => $token->getAccessToken(),
                'square_oauth_refresh_token' => $token->getRefreshToken(),
                'square_oauth_token_expires_at' => $this->squareDate($token->getExpiresAt()),
                'square_oauth_refresh_token_expires_at' => $this->squareDate($token->getRefreshTokenExpiresAt()),
            ];

            foreach ($fields as $column => $value) {
                if ($this->hasColumn('branches', $column) && $value) {
                    $branch->{$column} = $value;
                }
            }

            $branch->save();
        } catch (\Exception $exception) {
            Log::warning('Square OAuth token refresh failed: ' . $exception->getMessage(), [
                'branch_id' => $branch->id,
            ]);
        }
    }

    protected function branchIdFromSquareOrder(SquareOrder $squareOrder)
    {
        $metadata = $squareOrder->getMetadata() ?: [];
        if (isset($metadata['branch_id']) && $metadata['branch_id']) {
            return $metadata['branch_id'];
        }

        $branch = $this->branchForLocation($squareOrder->getLocationId());
        return $branch ? $branch->id : null;
    }

    protected function checkoutLineItems($amount, $currency, array $checkoutContext, $restaurantName, $includeRewardLineItems = true)
    {
        try {
            $cart = $this->normalizeCheckoutCart(isset($checkoutContext['cart']) ? $checkoutContext['cart'] : null);

            if (count($cart) > 0) {
                $lineItems = $this->lineItemsFromCart($cart, $amount, $currency, $checkoutContext, $includeRewardLineItems);
                if (count($lineItems) > 0) {
                    return $lineItems;
                }
            }

            $productIds = $this->productIdsFromContext(isset($checkoutContext['product_ids']) ? $checkoutContext['product_ids'] : null);
            if (count($productIds) > 0) {
                $lineItems = $this->lineItemsFromProductIds($productIds, $amount, $currency, $checkoutContext, $includeRewardLineItems);
                if (count($lineItems) > 0) {
                    return $lineItems;
                }
            }
        } catch (\Exception $exception) {
            Log::warning('Square checkout item mapping failed; using generic payment line item. ' . $exception->getMessage());
        }

        $lineItems = [$this->genericCheckoutLineItem($amount, $currency, $restaurantName)];
        $lineItems = $this->appendCheckoutCouponNoteToLineItems($lineItems, $checkoutContext);
        $lineItems = $this->appendCheckoutRewardNoteToLineItems($lineItems, $checkoutContext);

        return $includeRewardLineItems
            ? $this->appendCheckoutRewardLineItems($lineItems, $checkoutContext, $currency)
            : $lineItems;
    }

    protected function checkoutHasItemContext(array $checkoutContext)
    {
        return count($this->normalizeCheckoutCart(isset($checkoutContext['cart']) ? $checkoutContext['cart'] : null)) > 0
            || count($this->productIdsFromContext(isset($checkoutContext['product_ids']) ? $checkoutContext['product_ids'] : null)) > 0
            || count($this->checkoutRewards($checkoutContext)) > 0;
    }

    protected function checkoutFulfillments(array $checkoutContext, $customerId = null)
    {
        $orderType = strtolower($this->textValue(isset($checkoutContext['order_type']) ? $checkoutContext['order_type'] : 'pickup'));
        $fulfillment = new OrderFulfillment();
        $fulfillment->setState(OrderFulfillmentState::PROPOSED);
        $fulfillment->setMetadata(array_filter([
            'local_customer_id' => $customerId ? (string) $customerId : null,
            'order_type' => $orderType ?: 'pickup',
            'delivery_date' => isset($checkoutContext['delivery_date']) ? (string) $checkoutContext['delivery_date'] : null,
            'delivery_time' => isset($checkoutContext['delivery_time']) ? (string) $checkoutContext['delivery_time'] : null,
        ], function ($value) {
            return $value !== null && $value !== '';
        }));

        if ($orderType === 'delivery') {
            $delivery = new OrderFulfillmentDeliveryDetails();
            $delivery->setScheduleType($this->isScheduledCheckout($checkoutContext) ? OrderFulfillmentDeliveryDetailsScheduleType::SCHEDULED : OrderFulfillmentDeliveryDetailsScheduleType::ASAP);
            $delivery->setPlacedAt(Carbon::now()->toIso8601String());

            $deliverAt = $this->checkoutScheduleTimestamp($checkoutContext);
            if ($deliverAt) {
                $delivery->setDeliverAt($deliverAt);
            }

            $note = $this->checkoutFulfillmentNote($checkoutContext);
            if ($note) {
                $delivery->setNote($this->limitText($note, 500));
            }

            $recipient = $this->checkoutRecipient($checkoutContext, $customerId);
            if ($recipient) {
                $delivery->setRecipient($recipient);
            }

            $fulfillment->setType(OrderFulfillmentType::DELIVERY);
            $fulfillment->setDeliveryDetails($delivery);

            return [$fulfillment];
        }

        $pickup = new OrderFulfillmentPickupDetails();
        $pickup->setScheduleType($this->isScheduledCheckout($checkoutContext) ? OrderFulfillmentPickupDetailsScheduleType::SCHEDULED : OrderFulfillmentPickupDetailsScheduleType::ASAP);
        $pickup->setPlacedAt(Carbon::now()->toIso8601String());

        $pickupAt = $this->checkoutScheduleTimestamp($checkoutContext);
        if ($pickupAt) {
            $pickup->setPickupAt($pickupAt);
        }

        $note = $this->checkoutFulfillmentNote($checkoutContext);
        if ($note) {
            $pickup->setNote($this->limitText($note, 500));
        }

        $recipient = $this->checkoutRecipient($checkoutContext, $customerId);
        if ($recipient) {
            $pickup->setRecipient($recipient);
        }

        $fulfillment->setType(OrderFulfillmentType::PICKUP);
        $fulfillment->setPickupDetails($pickup);

        return [$fulfillment];
    }

    protected function checkoutRecipient(array $checkoutContext, $customerId = null)
    {
        $customerData = isset($checkoutContext['customer']) && is_array($checkoutContext['customer']) ? $checkoutContext['customer'] : [];
        $user = null;
        if ($customerId && is_numeric($customerId)) {
            try {
                $user = User::find($customerId);
            } catch (\Exception $exception) {
                $user = null;
            }
        }

        $name = $this->first(
            isset($customerData['name']) ? $customerData['name'] : null,
            isset($customerData['f_name']) || isset($customerData['l_name']) ? trim(($customerData['f_name'] ?? '') . ' ' . ($customerData['l_name'] ?? '')) : null,
            $user ? trim($user->f_name . ' ' . $user->l_name) : null,
            'AppDash Customer'
        );
        $phone = $this->first(isset($customerData['phone']) ? $customerData['phone'] : null, $user ? $user->phone : null);
        $email = $this->first(isset($customerData['email']) ? $customerData['email'] : null, $user ? $user->email : null);
        if (!$phone) {
            return null;
        }

        $recipient = new OrderFulfillmentRecipient();
        $recipient->setDisplayName($this->limitText($name, 255));
        $recipient->setPhoneNumber($this->limitText($phone, 30));
        if ($email) {
            $recipient->setEmailAddress($this->limitText($email, 255));
        }

        return $recipient;
    }

    protected function checkoutFulfillmentNote(array $checkoutContext)
    {  
            $parts = [];

        // Only send customer order note to kitchen
         if (!empty($checkoutContext['order_note'])) {
                $parts[] = $this->textValue($checkoutContext['order_note']);
             }

            return implode("\n", array_filter($parts));
    }

    protected function checkoutCouponNote(array $checkoutContext)
    {
        $code = $this->first(
            isset($checkoutContext['coupon_code']) ? $checkoutContext['coupon_code'] : null,
            isset($checkoutContext['couponCode']) ? $checkoutContext['couponCode'] : null
        );
        $title = $this->first(
            isset($checkoutContext['coupon_discount_title']) ? $checkoutContext['coupon_discount_title'] : null,
            isset($checkoutContext['couponDiscountTitle']) ? $checkoutContext['couponDiscountTitle'] : null
        );
        $amount = $this->first(
            isset($checkoutContext['coupon_discount_amount']) ? $checkoutContext['coupon_discount_amount'] : null,
            isset($checkoutContext['couponDiscountAmount']) ? $checkoutContext['couponDiscountAmount'] : null
        );

        if (!$code && !$title && (float) $amount <= 0) {
            return null;
        }

        $parts = [];
        if ($code) {
            $parts[] = 'Coupon: ' . $this->textValue($code);
        } elseif ($title) {
            $parts[] = 'Coupon: ' . $this->textValue($title);
        }

        if ((float) $amount > 0) {
            $parts[] = 'Coupon discount: -' . $this->textValue($amount);
        }

        return implode("\n", $parts);
    }

 protected function checkoutRewardSummaryNote(array $checkoutContext)
{
    $rewards = $this->checkoutRewards($checkoutContext);
    if (count($rewards) < 1) {
        return null;
    }

    $rewardIds = array_values(array_unique(array_filter(array_map(function ($reward) {
        return isset($reward['id']) ? (int) $reward['id'] : (isset($reward['reward_id']) ? (int) $reward['reward_id'] : null);
    }, $rewards), function ($id) {
        return (int) $id > 0;
    })));

    $rewardModels = collect();
    if (count($rewardIds) > 0 && $this->hasTable('rewards')) {
        try {
            $rewardModels = Reward::whereIn('id', $rewardIds)->get()->keyBy('id');
        } catch (\Exception $exception) {
            $rewardModels = collect();
        }
    }

    $rewardLines = [];
    foreach ($rewards as $rewardItem) {
        $rewardId = isset($rewardItem['id']) ? (int) $rewardItem['id'] : (isset($rewardItem['reward_id']) ? (int) $rewardItem['reward_id'] : null);
        $reward = $rewardId ? $rewardModels->get($rewardId) : null;
        $points = $this->rewardPoints($rewardItem, $reward);
        
        // Format: Reward Name (points pts)
        $line = $this->rewardItemName($rewardItem, $reward);
        if ($points !== null) {
            $line .= ' (' . $this->textValue($points) . ' pts)';
        }
        
        $rewardLines[] = $line;
    }

    // Combine: Rewards redeemed: Reward1, Reward2
    return 'Rewards redeemed: ' . implode(', ', $rewardLines);
}

    protected function isScheduledCheckout(array $checkoutContext)
    {
        $time = isset($checkoutContext['delivery_time']) ? strtolower($this->textValue($checkoutContext['delivery_time'])) : null;
        return !empty($checkoutContext['delivery_date'])
            && $time
            && $time !== 'now';
    }

    protected function checkoutScheduleTimestamp(array $checkoutContext)
    {
        if (!$this->isScheduledCheckout($checkoutContext)) {
            return null;
        }

        try {
            $date = $this->textValue($checkoutContext['delivery_date']);
            $time = $this->textValue($checkoutContext['delivery_time']);
            $time = preg_replace('/\s*-\s*.+$/', '', $time);
            $timezone = Helpers::get_business_settings('time_zone') ?: config('app.timezone');
            return Carbon::parse(trim($date . ' ' . $time), $timezone)->toIso8601String();
        } catch (\Exception $exception) {
            return null;
        }
    }

    protected function normalizeCheckoutCart($cart)
    {
        if ($cart === null || $cart === '') {
            return [];
        }

        if ($cart instanceof \Illuminate\Support\Collection) {
            $cart = $cart->toArray();
        } elseif ($cart instanceof \Traversable) {
            $cart = iterator_to_array($cart);
        }

        if (is_string($cart)) {
            
            $token = urldecode($cart);
            $json = base64_decode($token);
            $json = json_decode($json, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $cart = $json;
            } else {
                $base64 = base64_decode($cart, true);
                if ($base64 !== false) {
                    $json = json_decode($base64, true);
                    if (json_last_error() === JSON_ERROR_NONE) {
                        $cart = $json;
                    }
                }
            }
        }

        if (!is_array($cart)) {
            return [];
        }

        foreach (['cart', 'items', 'cart_items', 'cartItems', 'products', 'data', 'details', 'order_details', 'orderDetails'] as $itemsKey) {
            if (isset($cart[$itemsKey]) && is_array($cart[$itemsKey])) {
                $cart = $cart[$itemsKey];
                break;
            }
        }

        if (isset($cart['product_id']) || isset($cart['id']) || isset($cart['reward_id']) || isset($cart['rewardId'])) {
            $cart = [$cart];
        }

        $items = [];
        foreach ($cart as $item) {
            if (is_array($item)) {
                $items[] = $item;
            }
        }

        return $items;
    }

    protected function normalizeCheckoutRewards($rewards)
    {
        if ($rewards === null || $rewards === '') {
            return [];
        }

        if ($rewards instanceof \Illuminate\Support\Collection) {
            $rewards = $rewards->toArray();
        } elseif ($rewards instanceof \Traversable) {
            $rewards = iterator_to_array($rewards);
        }

        if (is_string($rewards)) {
            $decoded = rawurldecode($rewards);
            $json = json_decode($decoded, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($json)) {
                $rewards = $json;
            } else {
                $base64 = base64_decode($rewards, true);
                if ($base64 !== false) {
                    $json = json_decode($base64, true);
                    if (json_last_error() === JSON_ERROR_NONE) {
                        $rewards = $json;
                    }
                }

                if (!is_array($rewards)) {
                    $ids = array_values(array_filter(preg_split('/[,\|]+/', $decoded), function ($item) {
                        return preg_match('/^\d+$/', trim((string) $item));
                    }));

                    if (count($ids) > 0) {
                        $rewards = array_map(function ($id) {
                            return ['id' => (int) $id];
                        }, $ids);
                    }
                }
            }
        }

        if (!is_array($rewards)) {
            return [];
        }

        foreach (['reward', 'reward_item', 'rewardItem', 'rewards', 'items', 'reward_ids', 'rewardIds', 'reward_items', 'rewardItems', 'order_rewards', 'orderRewards', 'redeemed_rewards', 'redeemedRewards', 'selected_rewards', 'selectedRewards'] as $itemsKey) {
            if (isset($rewards[$itemsKey]) && is_array($rewards[$itemsKey])) {
                $rewards = $rewards[$itemsKey];
                break;
            }
        }

        if (isset($rewards['id']) || isset($rewards['reward_id']) || isset($rewards['rewardId'])) {
            $rewards = [$rewards];
        }

        $items = [];
        foreach ($rewards as $reward) {
            if (is_array($reward)) {
                if (!isset($reward['id']) && isset($reward['rewardId'])) {
                    $reward['id'] = $reward['rewardId'];
                }
                $items[] = $reward;
            } elseif (is_numeric($reward)) {
                $items[] = ['id' => (int) $reward];
            }
        }

        return $items;
    }

    // protected function lineItemsFromCart(array $cart, $amount, $currency, array $checkoutContext, $includeRewardLineItems = true)
    // {
    //     $tipMinor = $this->checkoutTipMinor($checkoutContext, $cart, $currency);
    //     $itemAmount = $this->checkoutAmountWithoutTip($amount, $currency, $tipMinor);
    //     $descriptors = [];
    //     foreach ($cart as $index => $cartItem) {
    //         if ($this->isRewardCartItem($cartItem)) {
    //             continue;
    //         }

    //         $productId = isset($cartItem['product_id']) ? $cartItem['product_id'] : (isset($cartItem['id']) ? $cartItem['id'] : null);
    //         $product = $productId ? Product::find($productId) : null;
    //         $quantity = $this->cartQuantity($cartItem);

    //         $descriptors[] = [
    //             'name' => $this->cartItemName($cartItem, $product),
    //             'quantity' => $quantity,
    //             'weight_minor' => $this->cartItemWeightMinor($cartItem, $product, $currency, $quantity),
    //             'note' => $this->cartItemNote($cartItem, $checkoutContext),
    //             'variation_name' => $this->cartVariationName($cartItem),
    //             'modifiers' => $this->cartItemSquareModifiers($cartItem),
    //             'product_id' => $productId,
    //             'cart_index' => $index,
    //         ];
    //     }

    //     $lineItems = $this->allocatedCheckoutLineItems($descriptors, $itemAmount, $currency);
    //     $lineItems = $this->appendCheckoutCouponNoteToLineItems($lineItems, $checkoutContext);
    //     // Reward notes removed from kitchen ticket
    //     // $lineItems = $this->appendCheckoutRewardNoteToLineItems($lineItems, $checkoutContext);
    //     // if ($includeRewardLineItems) {
    //     //     $lineItems = $this->appendCheckoutRewardLineItems($lineItems, $checkoutContext, $currency);
    //     // }
    //     return $this->appendCheckoutTipLineItem($lineItems, $amount, $currency, $tipMinor);
    // }
    
    protected function lineItemsFromCart(array $cart, $amount, $currency, array $checkoutContext, $includeRewardLineItems = true)
{
    $tipMinor = $this->checkoutTipMinor($checkoutContext, $cart, $currency);
    $itemAmount = $this->checkoutAmountWithoutTip($amount, $currency, $tipMinor);
    $descriptors = [];
    foreach ($cart as $index => $cartItem) {
        if ($this->isRewardCartItem($cartItem)) {
            continue;
        }

        $productId = isset($cartItem['product_id']) ? $cartItem['product_id'] : (isset($cartItem['id']) ? $cartItem['id'] : null);
        $product = $productId ? Product::find($productId) : null;
        $quantity = $this->cartQuantity($cartItem);

        $descriptors[] = [
            'name' => $this->cartItemName($cartItem, $product),
            'quantity' => $quantity,
            'weight_minor' => $this->cartItemWeightMinor($cartItem, $product, $currency, $quantity),
            'note' => $this->cartItemNote($cartItem, $checkoutContext),
            'variation_name' => $this->cartVariationName($cartItem),
            'modifiers' => $this->cartItemSquareModifiers($cartItem),
            'product_id' => $productId,
            'cart_index' => $index,
        ];
    }

    $lineItems = $this->allocatedCheckoutLineItems($descriptors, $itemAmount, $currency);
    $lineItems = $this->appendCheckoutCouponNoteToLineItems($lineItems, $checkoutContext);
    
    // Show rewards on receipt in single line format
    $lineItems = $this->appendCheckoutRewardNoteToLineItems($lineItems, $checkoutContext);
    if ($includeRewardLineItems) {
        $lineItems = $this->appendCheckoutRewardLineItems($lineItems, $checkoutContext, $currency);
    }
    
    return $this->appendCheckoutTipLineItem($lineItems, $amount, $currency, $tipMinor);
}

    // protected function lineItemsFromProductIds(array $productIds, $amount, $currency, array $checkoutContext, $includeRewardLineItems = true)
    // {
    //     $tipMinor = $this->checkoutTipMinor($checkoutContext, [], $currency);
    //     $itemAmount = $this->checkoutAmountWithoutTip($amount, $currency, $tipMinor);
    //     $grouped = [];
    //     foreach ($productIds as $productId) {
    //         $productId = (int) $productId;
    //         if ($productId > 0) {
    //             $grouped[$productId] = isset($grouped[$productId]) ? $grouped[$productId] + 1 : 1;
    //         }
    //     }

    //     if (count($grouped) < 1) {
    //         return [];
    //     }

    //     $products = Product::whereIn('id', array_keys($grouped))->get()->keyBy('id');
    //     $descriptors = [];
    //     foreach ($grouped as $productId => $quantity) {
    //         $product = $products->get($productId);
    //         $descriptors[] = [
    //             'name' => $product ? $this->textValue($product->name) : 'Product #' . $productId,
    //             'quantity' => $quantity,
    //             'weight_minor' => $product ? max(1, $this->amountToMinorUnits($product->price * $quantity, $currency)) : $quantity,
    //             'note' => 'Full order details are created in AppDash after payment.',
    //             'variation_name' => null,
    //             'modifiers' => [],
    //             'product_id' => $productId,
    //             'cart_index' => null,
    //         ];
    //     }

    //     $lineItems = $this->allocatedCheckoutLineItems($descriptors, $itemAmount, $currency);
    //     $lineItems = $this->appendCheckoutCouponNoteToLineItems($lineItems, $checkoutContext);
    //     // Reward notes removed from kitchen ticket
    //     // $lineItems = $this->appendCheckoutRewardNoteToLineItems($lineItems, $checkoutContext);
    //     // if ($includeRewardLineItems) {
    //     //     $lineItems = $this->appendCheckoutRewardLineItems($lineItems, $checkoutContext, $currency);
    //     // }
    //     return $this->appendCheckoutTipLineItem($lineItems, $amount, $currency, $tipMinor);
    // }

    protected function lineItemsFromProductIds(array $productIds, $amount, $currency, array $checkoutContext, $includeRewardLineItems = true)
{
    $tipMinor = $this->checkoutTipMinor($checkoutContext, [], $currency);
    $itemAmount = $this->checkoutAmountWithoutTip($amount, $currency, $tipMinor);
    $grouped = [];
    foreach ($productIds as $productId) {
        $productId = (int) $productId;
        if ($productId > 0) {
            $grouped[$productId] = isset($grouped[$productId]) ? $grouped[$productId] + 1 : 1;
        }
    }

    if (count($grouped) < 1) {
        return [];
    }

    $products = Product::whereIn('id', array_keys($grouped))->get()->keyBy('id');
    $descriptors = [];
    foreach ($grouped as $productId => $quantity) {
        $product = $products->get($productId);
        $descriptors[] = [
            'name' => $product ? $this->textValue($product->name) : 'Product #' . $productId,
            'quantity' => $quantity,
            'weight_minor' => $product ? max(1, $this->amountToMinorUnits($product->price * $quantity, $currency)) : $quantity,
            'note' => 'Full order details are created in AppDash after payment.',
            'variation_name' => null,
            'modifiers' => [],
            'product_id' => $productId,
            'cart_index' => null,
        ];
    }

    $lineItems = $this->allocatedCheckoutLineItems($descriptors, $itemAmount, $currency);
    $lineItems = $this->appendCheckoutCouponNoteToLineItems($lineItems, $checkoutContext);
    
    // Show rewards on receipt in single line format
    $lineItems = $this->appendCheckoutRewardNoteToLineItems($lineItems, $checkoutContext);
    if ($includeRewardLineItems) {
        $lineItems = $this->appendCheckoutRewardLineItems($lineItems, $checkoutContext, $currency);
    }
    
    return $this->appendCheckoutTipLineItem($lineItems, $amount, $currency, $tipMinor);
}
    
    protected function allocatedCheckoutLineItems(array $descriptors, $amount, $currency)
    {
        $descriptors = array_values(array_filter($descriptors, function ($descriptor) {
            return !empty($descriptor['name']) && (int) ($descriptor['quantity'] ?? 0) > 0;
        }));

        if (count($descriptors) < 1) {
            return [];
        }

        $totalMinor = $this->amountToMinorUnits($amount, $currency);
        if ($totalMinor <= 0) {
            return [];
        }

        $totalWeight = 0;
        foreach ($descriptors as $descriptor) {
            $totalWeight += max(1, (int) ($descriptor['weight_minor'] ?? 1));
        }

        $allocations = [];
        $allocatedTotal = 0;
        foreach ($descriptors as $index => $descriptor) {
            $quantity = max(1, (int) ($descriptor['quantity'] ?? 1));
            $weight = max(1, (int) ($descriptor['weight_minor'] ?? 1));
            $rawAllocation = $totalMinor * ($weight / max(1, $totalWeight));
            $allocatedMinor = max($quantity, (int) floor($rawAllocation));
            $allocations[$index] = [
                'amount' => $allocatedMinor,
                'remainder' => $rawAllocation - floor($rawAllocation),
            ];
            $allocatedTotal += $allocatedMinor;
        }

        if ($allocatedTotal > $totalMinor) {
            return [];
        }

        $remainingMinor = $totalMinor - $allocatedTotal;
        $allocationOrder = array_keys($allocations);
        usort($allocationOrder, function ($left, $right) use ($allocations) {
            return $allocations[$right]['remainder'] <=> $allocations[$left]['remainder'];
        });
        if (count($allocationOrder) < 1) {
            return [];
        }

        $cursor = 0;
        while ($remainingMinor > 0) {
            $index = $allocationOrder[$cursor % count($allocationOrder)];
            $allocations[$index]['amount']++;
            $remainingMinor--;
            $cursor++;
        }

        $lineItems = [];
        $uid = 1;
        foreach ($descriptors as $index => $descriptor) {
            $quantity = max(1, (int) ($descriptor['quantity'] ?? 1));
            $allocatedMinor = (int) ($allocations[$index]['amount'] ?? 0);
            if ($allocatedMinor < $quantity) {
                return [];
            }

            $baseUnitMinor = (int) floor($allocatedMinor / $quantity);
            $extraUnits = $allocatedMinor % $quantity;
            $standardUnits = $quantity - $extraUnits;

            if ($extraUnits > 0) {
                $lineItems[] = $this->allocatedCheckoutLineItem($descriptor, $extraUnits, $baseUnitMinor + 1, $currency, $uid++);
            }

            if ($standardUnits > 0) {
                $lineItems[] = $this->allocatedCheckoutLineItem($descriptor, $standardUnits, max(1, $baseUnitMinor), $currency, $uid++);
            }
        }

        return $lineItems;
    }

    protected function allocatedCheckoutLineItem(array $descriptor, $quantity, $unitMinor, $currency, $uid)
    {
        $lineItem = new OrderLineItem((string) max(1, (int) $quantity));
        $lineItem->setUid('item_' . $uid);
        $lineItem->setName($this->limitText($descriptor['name'], 255));
        $lineItem->setBasePriceMoney($this->moneyFromMinorUnits(max(1, (int) $unitMinor), $currency));

        if (!empty($descriptor['variation_name'])) {
            $lineItem->setVariationName($this->limitText($descriptor['variation_name'], 255));
        }

        $modifiers = $this->squareModifiersFromDescriptor(isset($descriptor['modifiers']) ? $descriptor['modifiers'] : [], $currency, $lineItem->getUid());
        if (count($modifiers) > 0) {
            $lineItem->setModifiers($modifiers);
        }

        if (!empty($descriptor['note'])) {
            $lineItem->setNote($this->limitText($descriptor['note'], 3900));
        }

        $lineMetadata = array_filter([
            'local_product_id' => isset($descriptor['product_id']) ? (string) $descriptor['product_id'] : null,
            'local_cart_index' => isset($descriptor['cart_index']) ? (string) $descriptor['cart_index'] : null,
        ], function ($value) {
            return $value !== null && $value !== '';
        });
        if (count($lineMetadata) > 0) {
            $lineItem->setMetadata($lineMetadata);
        }

        return $lineItem;
    }

    protected function genericCheckoutLineItem($amount, $currency, $restaurantName)
    {
        $lineItem = new OrderLineItem('1');
        $lineItem->setName($restaurantName ? $restaurantName . ' order payment' : 'Order payment');
        $lineItem->setBasePriceMoney($this->money($amount, $currency));

        return $lineItem;
    }

    protected function checkoutTipMinor(array $checkoutContext, array $cart, $currency)
    {
        $tip = $this->first(
            isset($checkoutContext['tip_price']) ? $checkoutContext['tip_price'] : null,
            isset($checkoutContext['tip_amount']) ? $checkoutContext['tip_amount'] : null,
            isset($checkoutContext['tip']) ? $checkoutContext['tip'] : null
        );

        if (($tip === null || $tip === '') && isset($cart[0]) && is_array($cart[0])) {
            $tip = $this->first(
                isset($cart[0]['tip_price']) ? $cart[0]['tip_price'] : null,
                isset($cart[0]['tip_amount']) ? $cart[0]['tip_amount'] : null,
                isset($cart[0]['tip']) ? $cart[0]['tip'] : null
            );
        }

        $tip = max(0, (float) $tip);
        return $tip > 0 ? $this->amountToMinorUnits($tip, $currency) : 0;
    }

    protected function checkoutAmountWithoutTip($amount, $currency, $tipMinor)
    {
        $totalMinor = $this->amountToMinorUnits($amount, $currency);
        if ($tipMinor <= 0 || $tipMinor >= $totalMinor) {
            return $amount;
        }

        $itemMinor = $totalMinor - $tipMinor;
        return $this->usesMinorUnits($currency) ? $itemMinor / 100 : $itemMinor;
    }

  protected function appendCheckoutTipLineItem(array $lineItems, $amount, $currency, $tipMinor)
{
    // Do not add Tip as a line item to the Square Kitchen Order.
    return $lineItems;
}

    protected function appendCheckoutRewardLineItems(array $lineItems, array $checkoutContext, $currency)
    {
        $rewards = $this->checkoutRewards($checkoutContext);
        if (count($rewards) < 1) {
            return $lineItems;
        }

        $rewardIds = array_values(array_unique(array_filter(array_map(function ($reward) {
            return isset($reward['id']) ? (int) $reward['id'] : (isset($reward['reward_id']) ? (int) $reward['reward_id'] : null);
        }, $rewards), function ($id) {
            return (int) $id > 0;
        })));

        $rewardModels = collect();
        if (count($rewardIds) > 0 && $this->hasTable('rewards')) {
            try {
                $rewardModels = Reward::whereIn('id', $rewardIds)->get()->keyBy('id');
            } catch (\Exception $exception) {
                $rewardModels = collect();
            }
        }

        $start = count($lineItems) + 1;
        foreach ($rewards as $index => $rewardItem) {
            $rewardId = isset($rewardItem['id']) ? (int) $rewardItem['id'] : (isset($rewardItem['reward_id']) ? (int) $rewardItem['reward_id'] : null);
            $reward = $rewardId ? $rewardModels->get($rewardId) : null;
            $quantity = $this->rewardQuantity($rewardItem);
            $points = $this->rewardPoints($rewardItem, $reward);

            $lineItem = new OrderLineItem((string) $quantity);
            $lineItem->setUid('reward_' . ($start + $index));
            $lineItem->setName($this->limitText('Reward: ' . $this->rewardItemName($rewardItem, $reward), 255));
            $lineItem->setBasePriceMoney($this->moneyFromMinorUnits(0, $currency));

            $modifiers = $this->squareModifiersFromDescriptor($this->rewardItemSquareModifiers($rewardItem, $reward), $currency, $lineItem->getUid());
            if (count($modifiers) > 0) {
                $lineItem->setModifiers($modifiers);
            }

            $note = $this->rewardItemNote($rewardItem, $reward, $points);
            if ($note) {
                $lineItem->setNote($this->limitText($note, 3900));
            }

            $metadata = array_filter([
                'local_reward_id' => $rewardId ? (string) $rewardId : null,
                'local_reward_index' => (string) $index,
                'reward_points' => $points !== null ? (string) $points : null,
                'source' => 'appdash_reward',
            ], function ($value) {
                return $value !== null && $value !== '';
            });
            if (count($metadata) > 0) {
                $lineItem->setMetadata($metadata);
            }

            $lineItems[] = $lineItem;
        }

        return $lineItems;
    }

    protected function assertCheckoutRewardsAvailable(array $rewards)
    {
        if (count($rewards) < 1) {
            return;
        }

        $rewardIds = [];
        foreach ($rewards as $reward) {
            $rewardId = isset($reward['id']) ? $reward['id'] : (isset($reward['reward_id']) ? $reward['reward_id'] : null);
            if (!$rewardId || !is_numeric($rewardId)) {
                throw new RuntimeException('One or more selected rewards are invalid.');
            }
            $rewardIds[] = (int) $rewardId;
        }

        if (!$this->hasTable('rewards')) {
            throw new RuntimeException('Rewards table is missing.');
        }

        $query = Reward::whereIn('id', array_values(array_unique($rewardIds)));
        if ($this->hasColumn('rewards', 'status')) {
            $query->where('status', 1);
        }

        if ($query->count() !== count(array_unique($rewardIds))) {
            throw new RuntimeException('One or more selected rewards are unavailable.');
        }
    }

    protected function assertCustomerHasRewardPoints($customerId, array $rewards)
    {
        if (count($rewards) < 1) {
            return;
        }

        if (!$customerId || !is_numeric($customerId)) {
            throw new RuntimeException('Rewards require a logged-in customer account.');
        }

        $user = User::find($customerId);
        if (!$user || $user->login_medium === 'guest') {
            throw new RuntimeException('Rewards require a registered customer account.');
        }

        $rewardIds = array_values(array_unique(array_filter(array_map(function ($reward) {
            return isset($reward['id']) ? (int) $reward['id'] : (isset($reward['reward_id']) ? (int) $reward['reward_id'] : null);
        }, $rewards), function ($id) {
            return (int) $id > 0;
        })));

        $rewardModels = collect();
        if (count($rewardIds) > 0 && $this->hasTable('rewards')) {
            $rewardModels = Reward::whereIn('id', $rewardIds)->get()->keyBy('id');
        }

        $requiredPoints = 0;
        foreach ($rewards as $reward) {
            $rewardId = isset($reward['id']) ? (int) $reward['id'] : (isset($reward['reward_id']) ? (int) $reward['reward_id'] : null);
            $rewardModel = $rewardId ? $rewardModels->get($rewardId) : null;
            $requiredPoints += ((float) $this->rewardPoints($reward, $rewardModel)) * $this->rewardQuantity($reward);
        }

        if ($requiredPoints > 0 && (float) $user->point < $requiredPoints) {
            throw new RuntimeException('Insufficient reward points.');
        }
    }

    // protected function appendCheckoutRewardNoteToLineItems(array $lineItems, array $checkoutContext)
    // {
    //     $rewardNote = $this->checkoutRewardSummaryNote($checkoutContext);
    //     if (!$rewardNote || count($lineItems) < 1) {
    //         return $lineItems;
    //     }

    //     foreach ($lineItems as $lineItem) {
    //         if (!$lineItem instanceof OrderLineItem) {
    //             continue;
    //         }

    //         $existingNote = method_exists($lineItem, 'getNote') ? $lineItem->getNote() : null;
    //         $combinedNote = trim(($existingNote ? $existingNote . "\n" : '') . $rewardNote);
    //         $lineItem->setNote($this->limitText($combinedNote, 3900));
    //         break;
    //     }

    //     return $lineItems;
    // }
    
    protected function appendCheckoutRewardNoteToLineItems(array $lineItems, array $checkoutContext)
{
    $rewardNote = $this->checkoutRewardSummaryNote($checkoutContext);
    if (!$rewardNote || count($lineItems) < 1) {
        return $lineItems;
    }

    foreach ($lineItems as $lineItem) {
        if (!$lineItem instanceof OrderLineItem) {
            continue;
        }

        $existingNote = method_exists($lineItem, 'getNote') ? $lineItem->getNote() : null;
        $combinedNote = trim(($existingNote ? $existingNote . "\n" : '') . $rewardNote);
        $lineItem->setNote($this->limitText($combinedNote, 3900));
        break;
    }

    return $lineItems;
}

    protected function appendCheckoutCouponNoteToLineItems(array $lineItems, array $checkoutContext)
    {
        $couponNote = $this->checkoutCouponNote($checkoutContext);
        if (!$couponNote || count($lineItems) < 1) {
            return $lineItems;
        }

        foreach ($lineItems as $lineItem) {
            if (!$lineItem instanceof OrderLineItem) {
                continue;
            }

            $existingNote = method_exists($lineItem, 'getNote') ? $lineItem->getNote() : null;
            $combinedNote = trim(($existingNote ? $existingNote . "\n" : '') . $couponNote);
            $lineItem->setNote($this->limitText($combinedNote, 3900));
            break;
        }

        return $lineItems;
    }

    protected function checkoutRewards(array $checkoutContext)
    {
        $rewards = [];
        foreach (['reward', 'reward_item', 'rewardItem', 'rewards', 'reward_ids', 'rewardIds', 'reward_items', 'rewardItems', 'order_rewards', 'orderRewards', 'redeemed_rewards', 'redeemedRewards', 'selected_rewards', 'selectedRewards'] as $key) {
            if (!array_key_exists($key, $checkoutContext)) {
                continue;
            }

            $rewards = array_merge($rewards, $this->normalizeCheckoutRewards($checkoutContext[$key]));
        }

        $cartRewards = $this->rewardsFromCheckoutCart($checkoutContext);

        return $this->uniqueCheckoutRewards(array_values(array_merge($rewards, $cartRewards)));
    }

    protected function uniqueCheckoutRewards(array $rewards)
    {
        $unique = [];
        foreach ($rewards as $index => $reward) {
            if (!is_array($reward)) {
                continue;
            }

            $id = isset($reward['id']) ? (int) $reward['id'] : (isset($reward['reward_id']) ? (int) $reward['reward_id'] : 0);
            $name = $this->first(
                isset($reward['name']) ? $reward['name'] : null,
                isset($reward['title']) ? $reward['title'] : null,
                isset($reward['reward_title']) ? $reward['reward_title'] : null
            );
            $key = $id > 0 ? 'id_' . $id : 'idx_' . $index . '_' . md5(json_encode($reward));
            $quantity = $this->rewardQuantity($reward);

            if (!isset($unique[$key])) {
                $reward['qty'] = $quantity;
                unset($reward['quantity']);
                $unique[$key] = $reward;
                continue;
            }

            $unique[$key]['qty'] = $this->rewardQuantity($unique[$key]) + $quantity;

            if ($name && empty($unique[$key]['name']) && empty($unique[$key]['title'])) {
                $unique[$key]['name'] = $name;
            }
        }

        return array_values($unique);
    }

    protected function rewardsFromCheckoutCart(array $checkoutContext)
    {
        $cart = $this->normalizeCheckoutCart(isset($checkoutContext['cart']) ? $checkoutContext['cart'] : null);
        if (count($cart) < 1) {
            return [];
        }

        $rewards = [];
        foreach ($cart as $cartItem) {
            if (!is_array($cartItem)) {
                continue;
            }

            $nestedReward = isset($cartItem['reward']) && is_array($cartItem['reward']) ? $cartItem['reward'] : [];
            if (!$this->isRewardCartItem($cartItem)) {
                continue;
            }

            $rewards[] = array_filter(array_merge($nestedReward, [
                'id' => $this->first(
                    isset($cartItem['reward_id']) ? $cartItem['reward_id'] : null,
                    isset($cartItem['rewardId']) ? $cartItem['rewardId'] : null,
                    isset($nestedReward['id']) ? $nestedReward['id'] : null,
                    isset($nestedReward['reward_id']) ? $nestedReward['reward_id'] : null
                ),
                'name' => $this->first(
                    isset($cartItem['reward_title']) ? $cartItem['reward_title'] : null,
                    isset($cartItem['title']) ? $cartItem['title'] : null,
                    isset($cartItem['name']) ? $cartItem['name'] : null,
                    isset($cartItem['product_name']) ? $cartItem['product_name'] : null,
                    isset($nestedReward['name']) ? $nestedReward['name'] : null,
                    isset($nestedReward['title']) ? $nestedReward['title'] : null
                ),
                'qty' => $this->cartQuantity($cartItem),
                'reward_point' => $this->first(
                    isset($cartItem['reward_point']) ? $cartItem['reward_point'] : null,
                    isset($cartItem['points']) ? $cartItem['points'] : null,
                    isset($cartItem['points_required']) ? $cartItem['points_required'] : null,
                    isset($nestedReward['reward_point']) ? $nestedReward['reward_point'] : null,
                    isset($nestedReward['points']) ? $nestedReward['points'] : null
                ),
                'instruction' => $this->first(
                    isset($cartItem['instruction']) ? $cartItem['instruction'] : null,
                    isset($cartItem['instructions']) ? $cartItem['instructions'] : null,
                    isset($nestedReward['instruction']) ? $nestedReward['instruction'] : null
                ),
                'variation' => $this->cartVariationDescription($cartItem),
                'addon_description' => $this->cartAddOnDescription($cartItem),
                'details_note' => $this->cartItemNote($cartItem, $checkoutContext),
            ]), function ($value) {
                return $value !== null && $value !== '';
            });
        }

        return $rewards;
    }

    protected function isRewardCartItem($cartItem)
    {
        if (!is_array($cartItem)) {
            return false;
        }

        $nestedReward = isset($cartItem['reward']) && is_array($cartItem['reward']) ? $cartItem['reward'] : [];

        return !empty($cartItem['is_reward'])
            || !empty($cartItem['reward_id'])
            || !empty($cartItem['rewardId'])
            || count($nestedReward) > 0;
    }

    protected function rewardQuantity(array $rewardItem)
    {
        $quantity = isset($rewardItem['qty']) ? (int) $rewardItem['qty'] : (isset($rewardItem['quantity']) ? (int) $rewardItem['quantity'] : 1);

        return $quantity > 0 ? $quantity : 1;
    }

    protected function rewardPoints(array $rewardItem, Reward $reward = null)
    {
        $points = $this->first(
            isset($rewardItem['reward_point']) ? $rewardItem['reward_point'] : null,
            isset($rewardItem['points']) ? $rewardItem['points'] : null,
            $reward ? $reward->reward_point : null
        );

        return $points === null ? null : (float) $points;
    }

    protected function rewardItemName(array $rewardItem, Reward $reward = null)
    {
        $title = $this->first(
            $reward ? $reward->title : null,
            isset($rewardItem['title']) ? $rewardItem['title'] : null,
            isset($rewardItem['name']) ? $rewardItem['name'] : null,
            isset($rewardItem['reward_title']) ? $rewardItem['reward_title'] : null
        );
        $branchText = $this->first(
            $reward ? $reward->branch_txt : null,
            isset($rewardItem['branch_txt']) ? $rewardItem['branch_txt'] : null,
            isset($rewardItem['branch_text']) ? $rewardItem['branch_text'] : null
        );

        if ($title && $branchText && strtolower($this->textValue($title)) !== strtolower($this->textValue($branchText))) {
            $name = $this->textValue($title . ' - ' . $branchText);
        } else {
            $name = $this->textValue($title ?: ($branchText ?: 'Reward item'));
        }

        $variation = $this->first(
            isset($rewardItem['variation']) ? $rewardItem['variation'] : null,
            isset($rewardItem['variation_name']) ? $rewardItem['variation_name'] : null,
            isset($rewardItem['size']) ? $rewardItem['size'] : null,
            isset($rewardItem['selected_size']) ? $rewardItem['selected_size'] : null,
            isset($rewardItem['size_name']) ? $rewardItem['size_name'] : null
        );
        $variation = $this->textValue($variation);
        if ($variation && stripos($name, $variation) === false) {
            $name .= ' (' . $variation . ')';
        }

        return $name;
    }

    protected function rewardItemNote(array $rewardItem, Reward $reward = null, $points = null)
    {
        $parts = ['Reward redemption item'];

        $branchText = $this->first(
            $reward ? $reward->branch_txt : null,
            isset($rewardItem['branch_txt']) ? $rewardItem['branch_txt'] : null,
            isset($rewardItem['branch_text']) ? $rewardItem['branch_text'] : null
        );
        if ($branchText) {
            $parts[] = 'Reward detail: ' . $this->textValue($branchText);
        }

        $instruction = $this->first(
            $reward ? $reward->instruction : null,
            isset($rewardItem['instruction']) ? $rewardItem['instruction'] : null,
            isset($rewardItem['instructions']) ? $rewardItem['instructions'] : null
        );
        if ($instruction) {
            $parts[] = 'Instruction: ' . $this->textValue($instruction);
        }

        if (!empty($rewardItem['details_note'])) {
            $parts[] = $this->textValue($rewardItem['details_note']);
        } elseif (!empty($rewardItem['variation'])) {
            $parts[] = strpos($rewardItem['variation'], ':') !== false
                ? $this->textValue($rewardItem['variation'])
                : 'Variation: ' . $this->textValue($rewardItem['variation']);
        }

        if (!empty($rewardItem['addon_description'])) {
            $parts[] = 'Add-ons: ' . $this->textValue($rewardItem['addon_description']);
        }

        if ($points !== null) {
            $parts[] = 'Redeemed points: ' . $this->textValue($points);
        }

        return implode("\n", array_filter($parts));
    }

    protected function productIdsFromContext($value)
    {
        if ($value === null || $value === '') {
            return [];
        }

        if (is_array($value)) {
            $ids = $value;
        } else {
            $raw = rawurldecode((string) $value);
            $decoded = json_decode($raw, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $ids = $decoded;
            } else {
                $ids = preg_split('/[,\|]+/', $raw);
            }
        }

        return array_values(array_filter(array_map(function ($id) {
            return is_array($id) ? null : (int) $id;
        }, $ids), function ($id) {
            return (int) $id > 0;
        }));
    }

    protected function cartQuantity(array $cartItem)
    {
        $quantity = isset($cartItem['quantity']) ? (int) $cartItem['quantity'] : 1;

        return $quantity > 0 ? $quantity : 1;
    }

    protected function cartItemName(array $cartItem, Product $product = null)
    {
        $name = $product ? $product->name : null;
        $name = $name ?: (isset($cartItem['name']) ? $cartItem['name'] : null);
        $name = $name ?: (isset($cartItem['product_name']) ? $cartItem['product_name'] : null);
        $name = $this->textValue($name ?: 'AppDash item');

        return $name;
    }

    protected function cartItemWeightMinor(array $cartItem, Product $product = null, $currency, $quantity)
    {
        $unitPrice = isset($cartItem['price']) ? (float) $cartItem['price'] : ($product ? (float) $product->price : 0);
        $addOnTotal = $this->cartAddOnTotal($cartItem);
        $lineAmount = max(0, ($unitPrice * $quantity) + $addOnTotal);

        if ($lineAmount <= 0) {
            return max(1, $quantity);
        }

        return max(1, $this->amountToMinorUnits($lineAmount, $currency));
    }

    protected function cartAddOnTotal(array $cartItem)
    {
        $ids = $this->arrayValue(isset($cartItem['add_on_ids']) ? $cartItem['add_on_ids'] : (isset($cartItem['add_ons']) ? $cartItem['add_ons'] : []));
        $qtys = $this->arrayValue(isset($cartItem['add_on_qtys']) ? $cartItem['add_on_qtys'] : []);
        if (count($ids) < 1) {
            return 0;
        }

        $addons = AddOn::whereIn('id', $ids)->get()->keyBy('id');
        $total = 0;
        foreach ($ids as $index => $id) {
            $addon = $addons->get($id);
            $quantity = isset($qtys[$index]) ? (int) $qtys[$index] : 1;
            $quantity = $quantity > 0 ? $quantity : 1;
            if ($addon) {
                $total += ((float) $addon->price) * $quantity;
            }
        }

        return $total;
    }

    protected function cartItemNote(array $cartItem, array $checkoutContext)
    {
        $parts = [];

        $addons = $this->cartAddOnDescription($cartItem);
        if ($addons) {
            $parts[] = 'Add-ons: ' . $addons;
        }

        $instruction = isset($cartItem['instruction']) ? $cartItem['instruction'] : (isset($cartItem['instructions']) ? $cartItem['instructions'] : null);
        if ($instruction) {
            $parts[] = 'Instruction: ' . $this->textValue($instruction);
        }

        return $this->compactKitchenLineItemNote($parts);
    }

    protected function compactKitchenLineItemNote(array $parts)
    {
        $lines = [];
        foreach ($parts as $part) {
            foreach (preg_split('/\r\n|\r|\n/', (string) $part) as $line) {
                $line = trim($line);
                if ($line === '') {
                    continue;
                }

                if (preg_match('/^(Type|Pickup\/Delivery)\s*:/i', $line)) {
                    continue;
                }

                $lines[] = $line;
            }
        }

        return implode("\n", array_values(array_unique($lines)));
    }

    protected function cartItemSquareModifiers(array $cartItem)
    {
        $modifiers = [];

        $variation = $this->cartVariationDescription($cartItem);
        if ($variation) {
            $modifiers[] = [
                'name' => $this->squareModifierDisplayName($variation),
                'source' => 'variation',
            ];
        }

        foreach ($this->cartAddOnSquareModifiers($cartItem) as $modifier) {
            $modifiers[] = $modifier;
        }

        return $this->uniqueSquareModifierDescriptors($modifiers);
    }

    protected function cartAddOnSquareModifiers(array $cartItem)
    {
        $ids = $this->arrayValue(isset($cartItem['add_on_ids']) ? $cartItem['add_on_ids'] : (isset($cartItem['add_ons']) ? $cartItem['add_ons'] : []));
        $qtys = $this->arrayValue(isset($cartItem['add_on_qtys']) ? $cartItem['add_on_qtys'] : []);
        if (count($ids) < 1) {
            return [];
        }

        $addons = AddOn::whereIn('id', $ids)->get()->keyBy('id');
        $modifiers = [];
        foreach ($ids as $index => $id) {
            $addon = $addons->get($id);
            $quantity = isset($qtys[$index]) ? (int) $qtys[$index] : 1;
            $quantity = $quantity > 0 ? $quantity : 1;
            $modifiers[] = [
                'name' => $addon ? $this->textValue($addon->name) : 'Add-on #' . $id,
                'quantity' => $quantity,
                'source' => 'addon',
                'source_id' => $id,
            ];
        }

        return $modifiers;
    }

    protected function rewardItemSquareModifiers(array $rewardItem, Reward $reward = null)
    {
        $modifiers = [];
        $variation = $this->first(
            isset($rewardItem['variation']) ? $rewardItem['variation'] : null,
            isset($rewardItem['variation_name']) ? $rewardItem['variation_name'] : null,
            isset($rewardItem['selected_variation']) ? $rewardItem['selected_variation'] : null,
            isset($rewardItem['size']) ? $rewardItem['size'] : null,
            isset($rewardItem['selected_size']) ? $rewardItem['selected_size'] : null
        );
        $variation = $this->describeVariationValue($variation);
        if ($variation) {
            $modifiers[] = [
                'name' => $this->squareModifierDisplayName($variation),
                'source' => 'reward_variation',
            ];
        }

        return $this->uniqueSquareModifierDescriptors($modifiers);
    }

    protected function squareModifiersFromDescriptor(array $descriptors, $currency, $lineUid = null)
    {
        $modifiers = [];
        foreach ($this->uniqueSquareModifierDescriptors($descriptors) as $index => $descriptor) {
            if (empty($descriptor['name'])) {
                continue;
            }

            $modifier = new OrderLineItemModifier();
            $modifier->setUid($this->squareModifierUid($lineUid, $index));
            $modifier->setName($this->limitText($descriptor['name'], 255));
            $modifier->setQuantity((string) max(1, (int) ($descriptor['quantity'] ?? 1)));
            $modifier->setBasePriceMoney($this->moneyFromMinorUnits(0, $currency));

            $metadata = array_filter([
                'source' => isset($descriptor['source']) ? $this->limitText($descriptor['source'], 60) : null,
                'source_id' => isset($descriptor['source_id']) ? (string) $descriptor['source_id'] : null,
            ], function ($value) {
                return $value !== null && $value !== '';
            });
            if (count($metadata) > 0) {
                $modifier->setMetadata($metadata);
            }

            $modifiers[] = $modifier;
        }

        return $modifiers;
    }

    protected function squareModifierUid($lineUid, $index)
    {
        $base = preg_replace('/[^a-zA-Z0-9_-]/', '_', (string) ($lineUid ?: 'item'));
        return $this->limitText($base . '_mod_' . ((int) $index + 1), 60);
    }

    protected function uniqueSquareModifierDescriptors(array $modifiers)
    {
        $unique = [];
        foreach ($modifiers as $modifier) {
            if (!is_array($modifier) || empty($modifier['name'])) {
                continue;
            }

            $name = $this->squareModifierDisplayName($modifier['name']);
            if (!$name) {
                continue;
            }

            $key = strtolower($name);
            if (!isset($unique[$key])) {
                $modifier['name'] = $name;
                $unique[$key] = $modifier;
                continue;
            }

            $unique[$key]['quantity'] = max(
                (int) ($unique[$key]['quantity'] ?? 1),
                (int) ($modifier['quantity'] ?? 1)
            );
        }

        return array_values($unique);
    }

    protected function squareModifierDisplayName($value)
    {
        $value = $this->textValue($value);
        $value = preg_replace('/^(size|variation|variant|selected size|selected variation)\s*:\s*/i', '', $value);

        return trim($value);
    }

    protected function checkoutScheduleNote(array $checkoutContext)
    {
        return '';
    }

    protected function cartVariationName(array $cartItem)
    {
        $description = $this->cartVariationDescription($cartItem);

        return $description ?: null;
    }

    protected function cartVariationDescription(array $cartItem)
    {
        foreach ($this->cartVariationCandidates($cartItem) as $candidate) {
            $description = $this->describeVariationValue($candidate['value']);
            if ($description) {
                if ($this->shouldPrefixVariationLabel($candidate['key'], $candidate['value'], $description)) {
                    return $this->variationLabel($candidate['key']) . ': ' . $description;
                }

                return $description;
            }
        }

        return null;
    }

    protected function cartVariationCandidates(array $cartItem)
    {
        $keys = [
            'variation',
            'variant',
            'variations',
            'variation_name',
            'variationName',
            'variant_name',
            'variantName',
            'selected_variation',
            'selectedVariation',
            'selected_variant',
            'selectedVariant',
            'size',
            'selected_size',
            'selectedSize',
            'size_name',
            'sizeName',
            'item_size',
            'itemSize',
            'drink_size',
            'drinkSize',
            'selected_size_name',
            'selectedSizeName',
            'option',
            'option_name',
            'optionName',
            'options',
            'selected_option',
            'selectedOption',
            'selected_options',
            'selectedOptions',
            'choice_options',
            'choiceOptions',
            'option_values',
            'optionValues',
            'attribute',
            'attributes',
            'selected_attributes',
            'selectedAttributes',
            'variation_type',
            'variationType',
            'variation_title',
            'variationTitle',
            'variant_type',
            'variantType',
            'variant_title',
            'variantTitle',
            'variant_data',
            'variantData',
            'variations_text',
            'variationsText',
        ];

        $candidates = [];
        foreach ($keys as $key) {
            if (array_key_exists($key, $cartItem) && $this->hasMeaningfulValue($cartItem[$key])) {
                $candidates[] = [
                    'key' => $key,
                    'value' => $cartItem[$key],
                ];
            }
        }

        return $candidates;
    }

    protected function shouldPrefixVariationLabel($key, $value, $description)
    {
        if (!$key || strpos($description, ':') !== false) {
            return false;
        }

        if (is_array($value)) {
            return false;
        }

        return in_array(strtolower((string) $key), [
            'variation',
            'variant',
            'variation_name',
            'variationName',
            'variant_name',
            'variantName',
            'selected_variation',
            'selectedVariation',
            'selected_variant',
            'selectedVariant',
            'size',
            'selected_size',
            'selectedSize',
            'size_name',
            'sizeName',
            'item_size',
            'itemSize',
            'drink_size',
            'drinkSize',
            'selected_size_name',
            'selectedSizeName',
        ], true);
    }

    protected function hasMeaningfulValue($value)
    {
        if ($value === null || $value === '') {
            return false;
        }

        if (is_string($value)) {
            $trimmed = trim($value);
            if ($trimmed === '' || $trimmed === '[]' || $trimmed === '{}') {
                return false;
            }
        }

        if (is_array($value)) {
            return count(array_filter($value, function ($item) {
                return $this->hasMeaningfulValue($item);
            })) > 0;
        }

        return true;
    }

    protected function describeVariationValue($value)
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_string($value)) {
            $decoded = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $this->describeVariationValue($decoded);
            }

            return $this->textValue($value);
        }

        if (!is_array($value)) {
            return $this->textValue($value);
        }

        if (isset($value[0]) && is_array($value[0]) && count($value) === 1) {
            return $this->describeVariationValue($value[0]);
        }

        $parts = [];
        foreach ($value as $key => $item) {
            if (in_array((string) $key, ['price', 'id', 'product_id'], true) || !$this->hasMeaningfulValue($item)) {
                continue;
            }

            if (is_array($item)) {
                $nested = $this->describeVariationValue($item);
                if ($nested) {
                    $parts[] = is_string($key) && !is_numeric($key) ? $this->variationLabel($key) . ': ' . $nested : $nested;
                }
                continue;
            }

            $label = is_string($key) && !is_numeric($key) ? $this->variationLabel($key) : null;
            $text = $this->textValue($item);
            $parts[] = $label ? $label . ': ' . $text : $text;
        }

        return implode(', ', array_filter(array_unique($parts)));
    }

    protected function variationLabel($key)
    {
        $key = strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', (string) $key));
        if (in_array($key, ['type', 'size', 'selected_size', 'size_name', 'item_size', 'drink_size', 'selected_size_name'], true)) {
            return 'Size';
        }

        return ucwords(str_replace('_', ' ', $key));
    }

    protected function cartAddOnDescription(array $cartItem)
    {
        $ids = $this->arrayValue(isset($cartItem['add_on_ids']) ? $cartItem['add_on_ids'] : (isset($cartItem['add_ons']) ? $cartItem['add_ons'] : []));
        $qtys = $this->arrayValue(isset($cartItem['add_on_qtys']) ? $cartItem['add_on_qtys'] : []);
        if (count($ids) < 1) {
            return null;
        }

        $addons = AddOn::whereIn('id', $ids)->get()->keyBy('id');
        $parts = [];
        foreach ($ids as $index => $id) {
            $addon = $addons->get($id);
            $quantity = isset($qtys[$index]) ? (int) $qtys[$index] : 1;
            $quantity = $quantity > 0 ? $quantity : 1;
            $parts[] = ($quantity > 1 ? $quantity . ' x ' : '') . ($addon ? $this->textValue($addon->name) : 'Add-on #' . $id);
        }

        return implode(', ', $parts);
    }

    protected function arrayValue($value)
    {
        if (is_array($value)) {
            return array_values($value);
        }

        if ($value === null || $value === '') {
            return [];
        }

        $decoded = json_decode(rawurldecode((string) $value), true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return array_values($decoded);
        }

        return array_values(array_filter(preg_split('/[,\|]+/', (string) $value), function ($item) {
            return $item !== '';
        }));
    }

    protected function describeValue($value, array $skipKeys = [])
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_string($value)) {
            $decoded = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $value = $decoded;
            } else {
                return $this->textValue($value);
            }
        }

        if (!is_array($value)) {
            return $this->textValue($value);
        }

        $parts = [];
        foreach ($value as $key => $item) {
            if (in_array((string) $key, $skipKeys, true)) {
                continue;
            }

            if (is_array($item)) {
                $nested = $this->describeValue($item, $skipKeys);
                if ($nested) {
                    $parts[] = is_string($key) ? $key . ': ' . $nested : $nested;
                }
            } elseif ($item !== null && $item !== '') {
                $parts[] = is_string($key) ? $key . ': ' . $this->textValue($item) : $this->textValue($item);
            }
        }

        return implode(', ', array_filter($parts));
    }

    protected function textValue($value)
    {
        if (is_array($value) || is_object($value)) {
            $value = json_encode($value);
        }

        return trim(strip_tags(html_entity_decode((string) $value, ENT_QUOTES, 'UTF-8')));
    }

    protected function limitText($value, $limit)
    {
        $value = $this->textValue($value);
        if (function_exists('mb_strlen') && function_exists('mb_substr')) {
            return mb_strlen($value) > $limit ? mb_substr($value, 0, $limit) : $value;
        }

        return strlen($value) > $limit ? substr($value, 0, $limit) : $value;
    }

    protected function money($amount, $currency)
    {
        $money = new Money();
        $money->setAmount($this->amountToMinorUnits($amount, $currency));
        $money->setCurrency(strtoupper($currency ?: 'USD'));

        return $money;
    }

    protected function moneyFromMinorUnits($minorAmount, $currency)
    {
        $money = new Money();
        $money->setAmount((int) $minorAmount);
        $money->setCurrency(strtoupper($currency ?: 'USD'));

        return $money;
    }

    protected function moneyToAmount(?Money $money)
    {
        if (!$money || $money->getAmount() === null) {
            return 0;
        }

        $currency = $money->getCurrency() ?: 'USD';
        return $this->usesMinorUnits($currency) ? $money->getAmount() / 100 : $money->getAmount();
    }

    protected function applicationFeeMoney($amount, $currency, array $settings)
    {
        if ((int) ($settings['commission_status'] ?? 0) !== 1) {
            return null;
        }

        if (empty($settings['oauth_connected'])) {
            throw new RuntimeException('Square commission requires this branch to be connected with Square OAuth. Connect the branch Square account before enabling commission.');
        }

        $amount = (float) $amount;
        $value = max(0, (float) ($settings['commission_value'] ?? 0));
        if ($amount <= 0 || $value <= 0) {
            return null;
        }

        $type = $this->normalizeCommissionType($settings['commission_type'] ?? 'percent');
        if ($type === 'percent' && $value > 90) {
            throw new RuntimeException('Square commission percent cannot be greater than 90.');
        }

        $feeAmount = $type === 'fixed' ? $value : ($amount * $value / 100);
        $feeAmount = round($feeAmount, 2);

        if ($feeAmount <= 0) {
            return null;
        }

        $maxFeeAmount = $this->maximumApplicationFee($amount);
        if ($feeAmount > $maxFeeAmount) {
            throw new RuntimeException('Square commission exceeds the allowed application fee limit.');
        }

        return $this->money($feeAmount, $currency);
    }

    protected function maximumApplicationFee($amount)
    {
        $amount = (float) $amount;

        return $amount < 5 ? round($amount * 0.6, 2) : round($amount * 0.9, 2);
    }

    protected function amountToMinorUnits($amount, $currency)
    {
        $amount = (float) $amount;

        return $this->usesMinorUnits($currency) ? (int) round($amount * 100) : (int) round($amount);
    }

    protected function usesMinorUnits($currency)
    {
        $currency = strtoupper($currency ?: 'USD');
        $zeroDecimalCurrencies = ['BIF', 'CLP', 'DJF', 'GNF', 'JPY', 'KMF', 'KRW', 'MGA', 'PYG', 'RWF', 'UGX', 'VND', 'VUV', 'XAF', 'XOF', 'XPF'];

        return !in_array($currency, $zeroDecimalCurrencies);
    }

    protected function isSquareOrderPaid(SquareOrder $squareOrder, $paymentId = null, $branchId = null, $locationId = null)
    {
        if ($squareOrder->getState() === 'COMPLETED') {
            return true;
        }

        if (count($squareOrder->getTenders() ?: []) > 0) {
            return true;
        }

        return $paymentId ? $this->paymentIsCompleted($paymentId, $squareOrder->getId(), $branchId, $locationId ?: $squareOrder->getLocationId()) : false;
    }

    protected function paymentIsCompleted($paymentId, $expectedOrderId = null, $branchId = null, $locationId = null)
    {
        try {
            $response = $this->client($branchId, $locationId)->getPaymentsApi()->getPayment($paymentId);
            if (!$response->isSuccess()) {
                return false;
            }

            $payment = $response->getResult()->getPayment();
            if (!$payment) {
                return false;
            }

            if ($expectedOrderId && $payment->getOrderId() && $payment->getOrderId() !== $expectedOrderId) {
                return false;
            }

            return $payment->getStatus() === 'COMPLETED';
        } catch (\Exception $exception) {
            Log::warning('Square payment status check failed: ' . $exception->getMessage(), [
                'payment_id' => $paymentId,
            ]);

            return false;
        }
    }

    protected function syncCompletedPaymentForReference($paymentReference)
    {
        if (!$paymentReference || empty($paymentReference->reference) || empty($paymentReference->square_location_id)) {
            return false;
        }

        $currency = $paymentReference->currency ?: 'USD';
        $total = $this->amountToMinorUnits($paymentReference->amount, $currency);
        $branchId = isset($paymentReference->branch_id) ? $paymentReference->branch_id : null;
        $locationId = $paymentReference->square_location_id;
        $beginTime = Carbon::now()->subHours(2)->toIso8601String();

        $response = $this->client($branchId, $locationId)
            ->getPaymentsApi()
            ->listPayments($beginTime, null, 'DESC', null, $locationId, $total, null, null, 100);

        if (!$response->isSuccess()) {
            throw new RuntimeException($this->formatSquareErrors($response->getErrors()));
        }

        $result = $response->getResult();
        $payments = $result ? ($result->getPayments() ?: []) : [];

        foreach ($payments as $payment) {
            if (!$this->paymentMatchesReference($payment, $paymentReference)) {
                continue;
            }

            if ($payment->getStatus() !== 'COMPLETED') {
                continue;
            }

            $this->storePaymentReference($paymentReference->reference, [
                'square_order_id' => $payment->getOrderId() ?: $paymentReference->square_order_id,
                'square_payment_id' => $payment->getId(),
                'square_location_id' => $payment->getLocationId() ?: $locationId,
                'branch_id' => $branchId,
                'status' => 'paid',
                'payload' => json_encode([
                    'source' => 'payments_api',
                    'payment' => $payment,
                ]),
            ]);

            if ($payment->getOrderId()) {
                try {
                    $this->syncSquareOrder($payment->getOrderId(), $payment->getId(), $branchId, $payment->getLocationId() ?: $locationId);
                } catch (\Exception $exception) {
                    Log::warning('Square order sync after payment lookup failed: ' . $exception->getMessage(), [
                        'reference' => $paymentReference->reference,
                        'square_order_id' => $payment->getOrderId(),
                        'square_payment_id' => $payment->getId(),
                    ]);
                }
            }

            return true;
        }

        return false;
    }

    protected function paymentMatchesReference($payment, $paymentReference)
    {
        $reference = $paymentReference->reference;

        if (!empty($paymentReference->square_order_id) && $payment->getOrderId() === $paymentReference->square_order_id) {
            return true;
        }

        if (method_exists($payment, 'getReferenceId') && $payment->getReferenceId() === $reference) {
            return true;
        }

        if (method_exists($payment, 'getNote') && $payment->getNote() && strpos($payment->getNote(), $reference) !== false) {
            return true;
        }

        return false;
    }

    protected function paymentIdFromOrder(SquareOrder $squareOrder)
    {
        foreach ($squareOrder->getTenders() ?: [] as $tender) {
            if ($tender->getPaymentId()) {
                return $tender->getPaymentId();
            }
        }

        return null;
    }

    protected function mapSquareOrderStatus(SquareOrder $squareOrder)
    {
        switch ($squareOrder->getState()) {
            case 'CANCELED':
                return 'canceled';
            case 'COMPLETED':
                return 'delivered';
            default:
                return 'confirmed';
        }
    }

    protected function localReferenceFromSquareOrder(SquareOrder $squareOrder)
    {
        $reference = $squareOrder->getReferenceId();
        if ($reference && substr($reference, 0, strlen(self::LOCAL_REFERENCE_PREFIX)) === self::LOCAL_REFERENCE_PREFIX) {
            return $reference;
        }

        $metadata = $squareOrder->getMetadata() ?: [];
        $metadataReference = isset($metadata['local_reference']) ? $metadata['local_reference'] : null;
        if ($metadataReference && substr($metadataReference, 0, strlen(self::LOCAL_REFERENCE_PREFIX)) === self::LOCAL_REFERENCE_PREFIX) {
            return $metadataReference;
        }

        return null;
    }

    protected function extractSquareOrderId(array $payload)
    {
        $object = isset($payload['data']['object']) && is_array($payload['data']['object']) ? $payload['data']['object'] : [];

        if (isset($object['order_created']['order_id'])) {
            return $object['order_created']['order_id'];
        }

        if (isset($object['order_updated']['order_id'])) {
            return $object['order_updated']['order_id'];
        }

        if (isset($object['order']['id'])) {
            return $object['order']['id'];
        }

        if (isset($object['payment']['order_id'])) {
            return $object['payment']['order_id'];
        }

        return null;
    }

    protected function extractSquarePaymentId(array $payload)
    {
        $object = isset($payload['data']['object']) && is_array($payload['data']['object']) ? $payload['data']['object'] : [];

        if (isset($object['payment']['id'])) {
            return $object['payment']['id'];
        }

        return null;
    }

    protected function storePaymentReference($reference, array $data)
    {
        if (!$reference || !$this->hasTable('square_payment_references')) {
            return;
        }

        $payload = array_merge($data, [
            'updated_at' => now(),
        ]);
        $payload = $this->filterColumns('square_payment_references', $payload);

        DB::table('square_payment_references')->updateOrInsert(
            ['reference' => $reference],
            array_merge($payload, ['created_at' => now()])
        );
    }

    protected function paymentReference($reference)
    {
        if (!$reference || !$this->hasTable('square_payment_references')) {
            return null;
        }

        return DB::table('square_payment_references')->where('reference', $reference)->first();
    }

    protected function isPaidStatus($status)
    {
        return strtolower((string) $status) === 'paid';
    }

    protected function amountsMatch($expected, $actual)
    {
        return abs((float) $expected - (float) $actual) <= 0.01;
    }

    protected function nextOrderId()
    {
        return max((int) Order::max('id'), 100000) + 1;
    }

    protected function nextOrderIdForUpdate()
    {
        return max((int) DB::table('orders')->lockForUpdate()->max('id'), 100000) + 1;
    }

    protected function existingSquareImportedOrder(SquareOrder $squareOrder, $paymentId = null)
    {
        if ($this->hasColumn('orders', 'square_order_id')) {
            $order = Order::where('square_order_id', $squareOrder->getId())->first();
            if ($order) {
                return $order;
            }
        }

        $reference = substr($paymentId ?: $squareOrder->getId(), 0, 30);

        return Order::where('transaction_reference', $reference)
            ->where('payment_method', 'square')
            ->first();
    }

    protected function isDuplicateKeyException(\Illuminate\Database\QueryException $exception)
    {
        return (string) $exception->getCode() === '23000'
            && strpos($exception->getMessage(), 'Duplicate entry') !== false;
    }

    protected function squareDate($date)
    {
        if (!$date) {
            return null;
        }

        try {
           return Carbon::parse($date)->setTimezone(Helpers::order_now()->getTimezone());
        } catch (\Exception $exception) {
            return null;
        }
    }

    protected function restaurantName()
    {
        try {
            $name = Helpers::get_business_settings('restaurant_name');
            return is_string($name) ? $name : null;
        } catch (\Exception $exception) {
            return null;
        }
    }

    // ADD THIS METHOD HERE - After restaurantName()
protected function shouldImportPosOrders()
{
    try {
        $settings = Helpers::get_business_settings('square') ?: [];
        return isset($settings['import_pos_orders']) && (int)$settings['import_pos_orders'] === 1;
    } catch (\Exception $e) {
        return false; // Default: OFF
    }
}
    protected function formatSquareErrors($errors)
    {
        if (!$errors) {
            return 'Square API request failed.';
        }

        $messages = [];
        foreach ($errors as $error) {
            $messages[] = method_exists($error, 'getDetail') ? $error->getDetail() : (string) $error;
        }

        return implode(' ', $messages);
    }

    protected function first()
    {
        foreach (func_get_args() as $value) {
            if (is_string($value)) {
                $value = trim($value);
            }

            if ($value !== null && $value !== '') {
                return $value;
            }
        }

        return null;
    }

    protected function normalizeEnvironment($environment)
    {
        $environment = strtolower($environment ?: 'sandbox');

        return $environment === 'production' ? 'production' : 'sandbox';
    }

    protected function normalizeCommissionType($type)
    {
        return strtolower((string) $type) === 'fixed' ? 'fixed' : 'percent';
    }

    protected function filterColumns($table, array $payload)
    {
        $filtered = [];
        foreach ($payload as $column => $value) {
            if ($this->hasColumn($table, $column)) {
                $filtered[$column] = $value;
            }
        }

        return $filtered;
    }

    protected function hasTable($table)
    {
        if (!array_key_exists($table, self::$tableCache)) {
            try {
                self::$tableCache[$table] = Schema::hasTable($table);
            } catch (\Exception $exception) {
                self::$tableCache[$table] = false;
            }
        }

        return self::$tableCache[$table];
    }

    protected function hasColumn($table, $column)
    {
        $key = $table . '.' . $column;
        if (!array_key_exists($key, self::$columnCache)) {
            try {
                self::$columnCache[$key] = Schema::hasColumn($table, $column);
            } catch (\Exception $exception) {
                self::$columnCache[$key] = false;
            }
        }

        return self::$columnCache[$key];
    }
}
