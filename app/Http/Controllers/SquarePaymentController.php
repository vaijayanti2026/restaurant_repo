<?php

namespace App\Http\Controllers;

use App\CentralLogics\Helpers;
use App\Model\Branch;
use App\Services\SquareService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class SquarePaymentController extends Controller
{
    public function payment_process_3d(Request $request, SquareService $square)
    {
        $order_amount = $this->inputOrSession($request, 'order_amount', ['orderAmount', 'amount']);
        $callback = $this->inputOrSession($request, 'callback', ['callback_url', 'callbackUrl', 'redirect_url', 'redirectUrl']);
        $branchId = $this->inputOrSession($request, 'branch_id', ['branchId', 'branch', 'store_id', 'storeId', 'location_branch_id', 'locationBranchId', 'location_id', 'locationId', 'square_location_id', 'squareLocationId']);
        $branchId = $this->normalizeBranchId($branchId);
        $customerId = $this->inputOrSession($request, 'customer_id', ['customerId', 'user_id', 'userId']);
        $currency_code = strtoupper(Helpers::get_business_settings('currency') ?? 'USD');
        $orderContext = $this->checkoutContext($request);
        if (!$branchId) {
            $branchId = $this->branchIdFromSessionAliases();
        }

        if (!$branchId) {
            $branchId = $this->branchIdFromCart(isset($orderContext['cart']) ? $orderContext['cart'] : $this->cartFromSession());
        }

        if (!$order_amount || !$branchId || !$customerId) {
            Log::warning('Square payment link creation blocked because checkout context is incomplete.', [
                'has_order_amount' => (bool) $order_amount,
                'has_callback' => (bool) $callback,
                'branch_id' => $branchId,
                'customer_id' => $customerId,
                'has_cart' => !empty($orderContext['cart']),
                'has_rewards' => !empty($orderContext['rewards']),
                'request_keys' => array_keys($request->all()),
            ]);

            return response()->json([
                'error' => 'Payment session is incomplete. Please return to cart and try again.',
            ], 422);
        }

        $branch = Branch::find($branchId);
        if (!$branch || (int) $branch->status !== 1) {
            Log::warning('Square payment link creation blocked because branch is closed or unavailable.', [
                'branch_id' => $branchId,
                'customer_id' => $customerId,
            ]);

            return response()->json([
                'error' => 'This branch is currently closed. Please select another location.',
            ], 422);
        }

        try {
            $result = $square->createCheckoutPaymentLink(
                $order_amount,
                $currency_code,
                $callback,
                $branchId,
                $customerId,
                $orderContext
            );

            Log::info('Square payment link created.', [
                'reference' => $result['reference'] ?? null,
                'branch_id' => $branchId,
                'customer_id' => $customerId,
                'has_cart' => !empty($orderContext['cart']),
                'has_rewards' => !empty($orderContext['rewards']),
                'has_product_ids' => !empty($orderContext['product_ids']),
                'tip_price' => $orderContext['tip_price'] ?? ($orderContext['tip_amount'] ?? null),
            ]);

            return response()->json($result);
        } catch (\Exception $exception) {
            Log::error('Square payment link creation failed: ' . $exception->getMessage(), [
                'branch_id' => $branchId,
                'customer_id' => $customerId,
                'order_amount' => $order_amount,
                'has_cart' => !empty($orderContext['cart']),
                'has_rewards' => !empty($orderContext['rewards']),
            ]);

            return response()->json(['error' => $exception->getMessage()], 500);
        }
    }

    private function checkoutContext(Request $request)
    {
        $context = [];
        foreach ($this->checkoutInputKeyMap() as $inputKey => $key) {
            if (in_array($key, ['customer_id', 'callback', 'order_amount', 'branch_id'], true)) {
                continue;
            }

            $value = $request->has($inputKey) ? $request->input($inputKey) : session($key);
            if ($value !== null && $value !== '') {
                $context[$key] = $value;
            }
        }
        if (session('data')) {
            $context['customer'] = session('data');
        }

        return $context;
    }

    private function checkoutInputKeyMap()
    {
        return [
            'customer_id' => 'customer_id',
            'customerId' => 'customer_id',
            'user_id' => 'customer_id',
            'userId' => 'customer_id',
            'callback' => 'callback',
            'callback_url' => 'callback',
            'callbackUrl' => 'callback',
            'redirect_url' => 'callback',
            'redirectUrl' => 'callback',
            'order_amount' => 'order_amount',
            'orderAmount' => 'order_amount',
            'amount' => 'order_amount',
            'branch_id' => 'branch_id',
            'branchId' => 'branch_id',
            'branch' => 'branch_id',
            'store_id' => 'branch_id',
            'storeId' => 'branch_id',
            'location_branch_id' => 'branch_id',
            'locationBranchId' => 'branch_id',
            'location_id' => 'branch_id',
            'locationId' => 'branch_id',
            'square_location_id' => 'branch_id',
            'squareLocationId' => 'branch_id',
            'cart' => 'cart',
            'items' => 'cart',
            'product_ids' => 'product_ids',
            'productIds' => 'product_ids',
            'delivery_date' => 'delivery_date',
            'deliveryDate' => 'delivery_date',
            'delivery_time' => 'delivery_time',
            'deliveryTime' => 'delivery_time',
            'order_type' => 'order_type',
            'orderType' => 'order_type',
            'order_note' => 'order_note',
            'orderNote' => 'order_note',
            'note' => 'order_note',
            'coupon_code' => 'coupon_code',
            'couponCode' => 'coupon_code',
            'coupon_discount_amount' => 'coupon_discount_amount',
            'couponDiscountAmount' => 'coupon_discount_amount',
            'coupon_discount_title' => 'coupon_discount_title',
            'couponDiscountTitle' => 'coupon_discount_title',
            'tip_price' => 'tip_price',
            'tipPrice' => 'tip_price',
            'tip_amount' => 'tip_amount',
            'tipAmount' => 'tip_amount',
            'tip' => 'tip_amount',
            'reward' => 'rewards',
            'reward_item' => 'rewards',
            'rewardItem' => 'rewards',
            'rewards' => 'rewards',
            'reward_items' => 'rewards',
            'rewardItems' => 'rewards',
            'order_rewards' => 'rewards',
            'orderRewards' => 'rewards',
            'redeemed_rewards' => 'rewards',
            'redeemedRewards' => 'rewards',
            'selected_rewards' => 'rewards',
            'selectedRewards' => 'rewards',
            'reward_ids' => 'rewards',
            'rewardIds' => 'rewards',
            'transaction_reference' => 'transaction_reference',
            'transactionReference' => 'transaction_reference',
            'transaction_key' => 'transaction_reference',
            'transactionKey' => 'transaction_reference',
            'reference' => 'transaction_reference',
        ];
    }

    private function inputOrSession(Request $request, $key, array $aliases = [])
    {
        foreach (array_merge([$key], $aliases) as $inputKey) {
            if ($request->has($inputKey)) {
                $value = $request->input($inputKey);
                if ($value !== null && $value !== '') {
                    return $value;
                }
            }
        }

        foreach (array_merge([$key], $aliases) as $sessionKey) {
            $value = session($sessionKey);
            if ($value !== null && $value !== '') {
                return $value;
            }
        }

        return null;
    }

    private function branchIdFromSessionAliases()
    {
        foreach (['branch_id', 'branchId', 'branch', 'store_id', 'storeId', 'location_branch_id', 'locationBranchId', 'location_id', 'locationId', 'square_location_id', 'squareLocationId'] as $key) {
            $value = session($key);
            if ($value === null || $value === '') {
                continue;
            }

            $branchId = $this->normalizeBranchId($value);
            if ($branchId) {
                return $branchId;
            }
        }

        return null;
    }

    private function cartFromSession()
    {
        foreach (['cart', 'items', 'cart_items', 'cartItems', 'products', 'data', 'details', 'order_details', 'orderDetails'] as $key) {
            $value = session($key);
            if ($value !== null && $value !== '') {
                return $value;
            }
        }

        return null;
    }

    private function branchIdFromCart($cart)
    {
        $cart = $this->normalizeStructuredInput($cart);
        if (!is_array($cart)) {
            return null;
        }

        $branchIds = [];
        $itemsKey = $this->cartItemsKey($cart);
        if ($itemsKey) {
            $rootBranchId = $this->branchIdFromArray($cart);
            $cart = $cart[$itemsKey];
        } else {
            $rootBranchId = $this->branchIdFromArray($cart);
        }

        if ($rootBranchId) {
            $branchIds[] = (string) $rootBranchId;
        }

        foreach ($cart as $item) {
            if (!is_array($item)) {
                continue;
            }

            $branchId = $this->branchIdFromArray($item);
            if ($branchId) {
                $branchIds[] = (string) $branchId;
            }
        }

        $branchIds = array_values(array_unique($branchIds));
        return count($branchIds) === 1 ? $branchIds[0] : null;
    }

    private function cartItemsKey(array $cart)
    {
        foreach (['cart', 'items', 'cart_items', 'cartItems', 'products', 'data', 'details', 'order_details', 'orderDetails'] as $key) {
            if (isset($cart[$key]) && is_array($cart[$key])) {
                return $key;
            }
        }

        return null;
    }

    private function branchIdFromArray(array $data)
    {
        foreach (['branch_id', 'branchId', 'store_id', 'storeId', 'location_branch_id', 'locationBranchId', 'location_id', 'locationId', 'square_location_id', 'squareLocationId'] as $key) {
            if (isset($data[$key]) && $data[$key] !== '') {
                $branchId = $this->normalizeBranchId($data[$key]);
                if ($branchId) {
                    return $branchId;
                }
            }
        }

        foreach (['branch', 'store', 'location'] as $key) {
            if (!isset($data[$key]) || !is_array($data[$key])) {
                continue;
            }

            foreach (['id', 'branch_id', 'branchId', 'store_id', 'storeId', 'location_id', 'locationId', 'square_location_id', 'squareLocationId'] as $nestedKey) {
                if (isset($data[$key][$nestedKey]) && $data[$key][$nestedKey] !== '') {
                    $branchId = $this->normalizeBranchId($data[$key][$nestedKey]);
                    if ($branchId) {
                        return $branchId;
                    }
                }
            }
        }

        return null;
    }

    private function normalizeBranchId($value)
    {
        if (is_array($value)) {
            if (isset($value['id']) && $value['id'] !== '') {
                $branch = Branch::where('id', $value['id'])->first(['id']);
                if ($branch) {
                    return (string) $branch->id;
                }
            }

            return $this->branchIdFromArray($value);
        }

        if ($value === null || $value === '') {
            return null;
        }

        $structured = $this->normalizeStructuredInput($value);
        if (is_array($structured)) {
            return $this->normalizeBranchId($structured);
        }

        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        if (is_numeric($value)) {
            $branch = Branch::where('id', $value)->first(['id']);
            if ($branch) {
                return (string) $branch->id;
            }
        }

        if ($this->branchHasSquareLocationIdColumn()) {
            $branch = Branch::where('square_location_id', $value)->first(['id']);
            if ($branch) {
                return (string) $branch->id;
            }
        }

        $matchingBranchIds = Branch::whereRaw('LOWER(name) = ?', [strtolower($value)])
            ->limit(2)
            ->pluck('id');

        if ($matchingBranchIds->count() === 1) {
            return (string) $matchingBranchIds->first();
        }

        return null;
    }

    private function branchHasSquareLocationIdColumn()
    {
        static $hasColumn = null;

        if ($hasColumn === null) {
            $hasColumn = Schema::hasColumn('branches', 'square_location_id');
        }

        return $hasColumn;
    }

    private function normalizeStructuredInput($value)
    {
        if (is_array($value)) {
            return $value;
        }

        if ($value === null || $value === '') {
            return null;
        }

        $raw = (string) $value;
        $decoded = rawurldecode($raw);
        $json = json_decode($decoded, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($json)) {
            return $json;
        }

        $base64 = base64_decode($raw, true);
        if ($base64 !== false) {
            $json = json_decode($base64, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($json)) {
                return $json;
            }
        }

        return $raw;
    }

    public function success(Request $request, SquareService $square)
    {
        $callback = $request['callback'];

        //token string generate
        $transaction_reference = $request['transaction_reference'];
        $token_string = 'payment_method=square&&transaction_reference=' . $transaction_reference;

        if (!$transaction_reference) {
            Log::warning('Square checkout returned without a local transaction reference.');

            if ($callback != null) {
                return redirect($callback . '/fail' . '?token=' . base64_encode($token_string));
            }

            return \redirect()->route('payment-fail', ['token' => base64_encode($token_string)]);
        }

        $square->markPaymentReferenceReturned($transaction_reference);

        if (!$square->confirmCheckoutPaymentReference($transaction_reference, 3, 500000)) {
            Log::warning('Square checkout returned before Square payment confirmation was available.', [
                'transaction_reference' => $transaction_reference,
            ]);
        }

        $paymentReference = $square->checkoutReferenceDetails($transaction_reference);
        if ($paymentReference) {
            $tokenParts = [
                'payment_method=square',
                'transaction_reference=' . $transaction_reference,
                'branch_id=' . $paymentReference->branch_id,
                'customer_id=' . $paymentReference->customer_id,
                'order_amount=' . $paymentReference->amount,
            ];
            $token_string = implode('&&', array_filter($tokenParts, function ($part) {
                return substr($part, -1) !== '=';
            }));
        }

        Log::info('Square checkout success callback returning to mobile.', [
            'transaction_reference' => $transaction_reference,
            'has_callback' => (bool) $callback,
            'branch_id' => $paymentReference ? $paymentReference->branch_id : null,
            'customer_id' => $paymentReference ? $paymentReference->customer_id : null,
            'amount' => $paymentReference ? $paymentReference->amount : null,
        ]);

        //success
        if ($callback != null) {
            return redirect($callback . '/success' . '?token=' . base64_encode($token_string));
        } else {
            return \redirect()->route('payment-success', ['token' => base64_encode($token_string)]);
        }
    }

    public function fail(Request $request)
    {
        $callback = $request['callback'];

        //token string generate
        $transaction_reference = $request['transaction_reference'];
        $token_string = 'payment_method=square&&transaction_reference=' . $transaction_reference;

        //fail
        if ($callback != null) {
            return redirect($callback . '/fail' . '?token=' . base64_encode($token_string));
        } else {
            return \redirect()->route('payment-fail', ['token' => base64_encode($token_string)]);
        }
    }
}
