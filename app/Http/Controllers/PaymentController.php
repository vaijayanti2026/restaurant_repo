<?php

namespace App\Http\Controllers;

use App\Model\Order;
use App\Model\Branch;
use App\Model\Product;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class PaymentController extends Controller
{
    public function payment(Request $request)
    {

        $previousBranchId = session('branch_id');
        $tokenContextKeys = [];

        if (session()->has('payment_method') == false) {
            session()->put('payment_method', 'ssl_commerz_payment');
        }

        if ($this->hasCheckoutContextInput($request)) {
            session()->forget($this->checkoutContextKeys());
        }

        if ($request->filled('token')) {
            $decoded = base64_decode($request->input('token'), true);
            if ($decoded !== false) {
                $params = explode('&&', $decoded);
                foreach ($params as $param) {
                    $data = explode('=', $param, 2);
                    if (count($data) !== 2) {
                        continue;
                    }

                    $key = $this->canonicalCheckoutKey(trim($data[0]));
                    $value = rawurldecode($data[1]);

                    if (!$key) {
                        continue;
                    }

                    $tokenContextKeys[] = $key;

                    if (in_array($key, $this->structuredCheckoutContextKeys(), true)) {
                        session()->put($key, $this->normalizeStructuredInput($data[1]));
                    } else {
                        session()->put($key, $value);
                    }
                }
            } else {
                Log::warning('Payment mobile opened with an invalid token.');
            }
        }

        foreach ($this->checkoutInputKeyMap() as $inputKey => $key) {
            if ($request->has($inputKey)) {
                session()->put(
                    $key,
                    in_array($key, $this->structuredCheckoutContextKeys(), true)
                        ? $this->normalizeStructuredInput($request->input($inputKey))
                        : $request->input($inputKey)
                );
            }
        }

        if (!session('branch_id') && $request->filled('token') && $previousBranchId) {
            $branchId = $this->normalizeBranchId($previousBranchId);
            if ($branchId) {
                session()->put('branch_id', $branchId);
            }
        }

        if (session('branch_id')) {
            $branchId = $this->normalizeBranchId(session('branch_id'));
            if ($branchId) {
                session()->put('branch_id', $branchId);
            } else {
                session()->forget('branch_id');
            }
        }

        if (!session('branch_id')) {
            $branchId = $this->branchIdFromSessionAliases();
            if ($branchId) {
                session()->put('branch_id', $branchId);
            }
        }

        if (!session('branch_id')) {
            $branchId = $this->branchIdFromCart($this->cartFromSession());
            if ($branchId) {
                session()->put('branch_id', $branchId);
            }
        }

        $customer = User::firstWhere(['id' => session('customer_id'), 'is_active' => 1]);
        $order_amount = session('order_amount');
        Log::info('Payment mobile opened.', [
            'has_token' => $request->filled('token'),
            'customer_id' => session('customer_id'),
            'branch_id' => session('branch_id'),
            'has_callback' => (bool) session('callback'),
            'has_order_amount' => isset($order_amount),
            'has_cart' => (bool) session('cart'),
            'has_rewards' => (bool) session('rewards'),
            'delivery_date' => session('delivery_date'),
            'delivery_time' => session('delivery_time'),
            'tip_price' => session('tip_price') ?: session('tip_amount'),
        ]);
//        $customer = User::latest()->first();
//        $order_amount = '1000';
        if (isset($customer) && isset($order_amount)) {
            if (!session('branch_id')) {
                Log::warning('Payment mobile could not start because branch was missing.', [
                    'customer_id' => session('customer_id'),
                    'has_cart' => (bool) session('cart'),
                    'request_keys' => array_keys($request->all()),
                    'token_context_keys' => array_values(array_unique($tokenContextKeys)),
                ]);

                return response()->view('payment-error', ['message' => 'Branch/location was not found. Please return to cart, select the branch again, and retry payment.'], 200);
            }

            $data = [
                'name' => $customer['f_name'],
                'email' => $customer['email'],
                'phone' => $customer['phone'],
            ];

            session()->put('data', $data);
            return view('payment-view');
        }

        if (!isset($customer)) {
            Log::warning('Payment mobile could not start because customer was missing or inactive.', [
                'customer_id' => session('customer_id'),
            ]);

            return response()->view('payment-error', ['message' => 'Customer not found or unauthenticated. Please login again and retry payment.'], 200);
        } elseif (!isset($order_amount)) {
            Log::warning('Payment mobile could not start because order amount was missing.', [
                'customer_id' => session('customer_id'),
                'branch_id' => session('branch_id'),
            ]);

            return response()->view('payment-error', ['message' => 'Payment amount was not found. Please return to cart and try again.'], 200);
        } else {
            Log::warning('Payment mobile could not start for an unknown reason.', [
                'customer_id' => session('customer_id'),
                'branch_id' => session('branch_id'),
            ]);

            return response()->view('payment-error', ['message' => 'Unable to open payment. Please try again.'], 200);
        }

    }

    private function checkoutContextKeys()
    {
        $map = $this->checkoutInputKeyMap();

        return array_values(array_unique(array_merge(array_keys($map), array_values($map))));
    }

    private function hasCheckoutContextInput(Request $request)
    {
        if ($request->filled('token')) {
            return true;
        }

        $freshContextKeys = [
            'cart',
            'items',
            'product_ids',
            'productIds',
            'branch_id',
            'branchId',
            'branch',
            'store_id',
            'storeId',
            'location_branch_id',
            'locationBranchId',
            'location_id',
            'locationId',
            'square_location_id',
            'squareLocationId',
        ];

        foreach ($freshContextKeys as $inputKey) {
            if ($request->has($inputKey)) {
                return true;
            }
        }

        return false;
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
            'product_ids' => 'product_ids',
            'productIds' => 'product_ids',
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
        ];
    }

    private function canonicalCheckoutKey($key)
    {
        $map = $this->checkoutInputKeyMap();
        return isset($map[$key]) ? $map[$key] : null;
    }

    private function structuredCheckoutContextKeys()
    {
        return ['cart', 'rewards'];
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
        $cart = json_decode($decoded, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($cart)) {
            return $cart;
        }

        $base64 = base64_decode($raw, true);
        if ($base64 !== false) {
            $cart = json_decode($base64, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($cart)) {
                return $cart;
            }
        }

        return $raw;
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

    public function set_payment_method($name)
    {
        session()->put('payment_method', $name);
        return back();
    }

    public function success()
    {
        if (session()->has('callback')) {
            return redirect(session('callback') . '/success');
        }
        return response()->json(['message' => 'Payment succeeded'], 200);
    }

    public function fail()
    {
        if (session()->has('callback')) {
            return redirect(session('callback') . '/fail');
        }
        return response()->json(['message' => 'Payment failed'], 403);
    }
}
