<?php

namespace App\Http\Controllers\Api\V1;

use App\CentralLogics\Helpers;
use App\CentralLogics\OrderLogic;
use App\Http\Controllers\Controller;
use App\Model\BusinessSetting;
use App\Model\Branch;
use App\Model\CustomerAddress;
use App\Model\DMReview;
use App\Model\Order;
use App\Model\OrderDetail;
use App\Model\RewardTransaction;
use App\Traits\SendInvoiceFaxTrait;
use App\Services\SquareService;
use App\Model\Product;
use App\Model\Review;
use Brian2694\Toastr\Facades\Toastr;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use function App\CentralLogics\translate;
use App\Jobs\DialCallJob;
use App\User;

class OrderController extends Controller
{
    use SendInvoiceFaxTrait;

    public function track_order(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'order_id' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        return response()->json(OrderLogic::track_order($request['order_id']), 200);
    }

    public function place_order(Request $request)
    {
        $isSquarePayment = $request->payment_method === 'square';
        $isGuestCustomer = $this->isGuestCustomer($request->user());
        
        $validator = Validator::make($request->all(), [
            'order_amount' => 'required',
            'order_type' => 'required',
            'branch_id' => 'required|exists:branches,id',
            'delivery_time' => 'required',
            'delivery_date' => 'required',
            'cart' => 'required|array|min:1',
            'delivery_address_id' => 'required_if:order_type,delivery',
            'distance' => 'required_if:order_type,delivery',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        $branch = Branch::find($request->branch_id);
        if (!$isSquarePayment && (!$branch || (int) $branch->status !== 1)) {
            return response()->json(['errors' => [[
                'code' => 'branch',
                'message' => 'This branch is currently closed.'
            ]]], 403);
        }

        $selectedRewards = $this->normalizeRewards(array_merge(
            $this->normalizeRewards($request->input('rewards', [])),
            $this->rewardsFromCart($request->input('cart', []))
        ));
        if (!$this->selectedRewardsAreValid($selectedRewards)) {
            return response()->json(['errors' => [[
                'code' => 'rewards',
                'message' => 'One or more selected rewards are unavailable.'
            ]]], 403);
        }

        $redeemedRewardPoints = $this->rewardPointsRequested($selectedRewards);
        if ($redeemedRewardPoints > 0) {
            if ($isGuestCustomer) {
                return response()->json(['errors' => [[
                    'code' => 'rewards',
                    'message' => 'Rewards require a logged-in customer account.'
                ]]], 403);
            }

            if ((float) $request->user()->point < $redeemedRewardPoints) {
                return response()->json(['errors' => [[
                    'code' => 'rewards',
                    'message' => 'Insufficient reward points.'
                ]]], 403);
            }
        }

        if ($request->transaction_reference) {
            $lockKey = 'order_place_ref_' . $request->transaction_reference;
        } elseif ($isGuestCustomer) {
            $lockKey = 'order_place_guest_' . md5(json_encode($request->cart));
        } else {
            $lockKey = 'order_place_user_' . $request->user()->id;
        }
        $lock = \Illuminate\Support\Facades\Cache::lock($lockKey, 10);
        if (!$lock->get()) {
            return response()->json(['errors' => [['code' => 'concurrency', 'message' => 'Your order is currently processing. Please wait.']]], 429);
        }

        try {
            if ($request->transaction_reference) {
                $existingOrder = Order::where('transaction_reference', $request->transaction_reference)->first();

                if (!$existingOrder && $isSquarePayment && Schema::hasTable('square_payment_references')) {
                    $paymentRef = DB::table('square_payment_references')
                        ->where('reference', $request->transaction_reference)
                        ->first();
                    if ($paymentRef && !empty($paymentRef->local_order_id)) {
                        $existingOrder = Order::find($paymentRef->local_order_id);
                    }
                }

                if ($existingOrder) {
                    if (
                        $isSquarePayment
                        && (
                            $existingOrder->payment_method !== 'square'
                            || (string) $existingOrder->user_id !== (string) $request->user()->id
                            || (string) $existingOrder->branch_id !== (string) $request['branch_id']
                        )
                    ) {
                        Log::warning('Square payment reference collision rejected.', [
                            'transaction_reference' => $request->transaction_reference,
                            'existing_order_id' => $existingOrder->id,
                            'user_id' => optional($request->user())->id,
                            'branch_id' => $request['branch_id'],
                        ]);

                        return response()->json(['errors' => [[
                            'code' => 'square_reference_conflict',
                            'message' => 'This Square payment reference is already associated with another order.',
                        ]]], 409);
                    }

                    Log::info('Duplicate order place request returned existing order.', [
                        'order_id' => $existingOrder->id,
                        'transaction_reference' => $request->transaction_reference,
                        'user_id' => optional($request->user())->id,
                    ]);

                    return response()->json([
                        'message' => translate('order_success'),
                        'order_id' => $existingOrder->id
                    ], 200);
                }
            }

            if ($isSquarePayment) {
                if (!$request->transaction_reference) {
                    return response()->json(['errors' => [['code' => 'square', 'message' => 'Square payment reference is required.']]], 403);
                }

                $currencyCode = strtoupper(Helpers::get_business_settings('currency') ?? 'USD');
                $squareValidation = app(SquareService::class)->validateCheckoutPayment(
                    $request->transaction_reference,
                    $request->user()->id,
                    Helpers::set_price($request['order_amount']),
                    $currencyCode,
                    null,
                    $request['branch_id']
                );

                if (!$squareValidation['valid']) {
                    Log::warning('Square order place validation failed.', [
                        'transaction_reference' => $request->transaction_reference,
                        'user_id' => $request->user()->id,
                        'branch_id' => $request['branch_id'],
                        'order_amount' => $request['order_amount'],
                        'message' => $squareValidation['message'],
                    ]);

                    return response()->json(['errors' => [['code' => 'square', 'message' => $squareValidation['message']]]], 403);
                }
            }

            //order scheduling
            if ($request['delivery_time'] == 'now') {
                $orderTimestamp = Helpers::order_now();
                $del_date = $orderTimestamp->format('Y-m-d');
                $del_time = $orderTimestamp->format('H:i:s');
            } else {
                $del_date = $request['delivery_date'];
                $del_time = $request['delivery_time'];
            }

            $orderTimestamp = $orderTimestamp ?? Helpers::order_now();
            $order_id = max((int) Order::max('id'), 100000) + 1;
            $isDeliveryOrder = $request['order_type'] === 'delivery';
            $deliveryAddressId = $isDeliveryOrder ? $request->delivery_address_id : null;
            $deliveryAddress = $deliveryAddressId ? CustomerAddress::find($deliveryAddressId) : null;
            $defaultPreparationTime = Helpers::get_business_settings('default_preparation_time');
            $defaultPreparationTime = is_numeric($defaultPreparationTime) && (int) $defaultPreparationTime > 0
                ? (int) $defaultPreparationTime
                : 15;

            $or = [
                'id' => $order_id,
                'user_id' => $request->user()->id,
                'order_amount' => Helpers::set_price($request['order_amount']),
                'coupon_discount_amount' => Helpers::set_price($request->coupon_discount_amount),
                'coupon_discount_title' => (!empty($request->coupon_discount_title) && $request->coupon_discount_title != 0) ? $request->coupon_discount_title : null,
                'payment_status' => ($request->payment_method=='cash_on_delivery')?'unpaid':'paid',
                'order_status' => ($request->payment_method=='cash_on_delivery')?'pending':'confirmed',
                'coupon_code' => $request['coupon_code'],
                'payment_method' => $request->payment_method,
                'transaction_reference' => $request->transaction_reference ?? null,
                'order_note' => $request['order_note'],
                'order_type' => $request['order_type'],
                'branch_id' => $request['branch_id'],
                'delivery_address_id' => $deliveryAddressId,
                'delivery_date' => $del_date,
                'delivery_time' => $del_time,
                'delivery_address' => json_encode($deliveryAddress),
                'delivery_charge' => $isDeliveryOrder ? Helpers::get_delivery_charge($request['distance']) : 0,
                'preparation_time' => $defaultPreparationTime,
                'order_source' => $this->isGuestCustomer($request->user()) ? 'walkin' : 'app',
                'created_at' => $orderTimestamp,
                'updated_at' => $orderTimestamp,
            ];

            if ($isSquarePayment && Schema::hasColumn('orders', 'payment_idempotency_key')) {
                $or['payment_idempotency_key'] = $request->transaction_reference;
            }

            $total_tax_amount = 0;
            $pendingOrderDetails = [];
            $pendingProductIds = [];

            foreach ($request['cart'] as $c) {
                if (empty($c['product_id']) && $this->isRewardCartItem($c)) {
                    continue;
                }

                $product = Product::find($c['product_id']);
                if (!$product) {
                    throw new \RuntimeException('Product not found for cart item: ' . ($c['product_id'] ?? 'unknown'));
                }

                $price = Helpers::set_price($c['price'] ?? $product['price']);
                $instruction = null;
                if (isset($c['instruction']) && $c['instruction'] != '') {
                    $instruction = $c['instruction'];
                }
                if (isset($c['instructions']) && $c['instructions'] != '') {
                    $instruction = $c['instructions'];
                }
                $variation = $this->cartItemVariationForOrder($c);

                $or_d = [
                    'order_id' => $order_id,
                    'product_id' => $c['product_id'],
                    'product_details' => $product->toJson(),
                    'quantity' => $c['quantity'],
                    'price' => $price,
                    'tax_amount' => Helpers::tax_calculate($product, $price),
                    'discount_on_product' => Helpers::discount_calculate($product, $price),
                    'discount_type' => 'discount_on_product',
                    'variant' => json_encode($c['variant'] ?? []),
                    'variation' => json_encode($variation),
                    'add_on_ids' => json_encode($c['add_on_ids'] ?? []),
                    'add_on_qtys' => json_encode($c['add_on_qtys'] ?? []),
                    'instruction' => $instruction ?? null,
                    'created_at' => now(),
                    'updated_at' => now()
                ];

                $total_tax_amount += $or_d['tax_amount'] * $c['quantity'];
                $pendingOrderDetails[] = $or_d;
                $pendingProductIds[] = $product->id;
            }

            $coupnText = '';
            if (isset($request->coupon_code) && $request->coupon_code != '') {
                $coupn = DB::table('coupons')->where('code', $request->coupon_code)->first();
                if ($coupn && (int) $coupn->discount == 0) {
                    $coupnText = $coupn->invoice_message;
                }
            }

            $or['total_tax_amount'] = $total_tax_amount;
            $cart = $request->cart;

            $tipPrice = $request->input('tip_price', $request->input('tip_amount', $request->input('tip')));
            if (($tipPrice === null || $tipPrice === '') && isset($cart[0]['tip_price'])) {
                $tipPrice = $cart[0]['tip_price'];
            }
            if (($tipPrice === null || $tipPrice === '') && isset($cart[0]['tip_amount'])) {
                $tipPrice = $cart[0]['tip_amount'];
            }

            $tipPrice = max(0, Helpers::set_price((float) $tipPrice));
            if ($tipPrice > 0 && Schema::hasColumn('orders', 'tip_price')) {
                $or['tip_price'] = $tipPrice;
            }

            // Insert the order first so the unique payment idempotency key wins
            // before any detail, popularity, or reward side effects are written.
            // The transaction also prevents partially-created local orders.
            $o_id = DB::transaction(function () use (
                $or,
                $order_id,
                $pendingOrderDetails,
                $pendingProductIds,
                $selectedRewards
            ) {
                $insertedOrderId = DB::table('orders')->insertGetId($or);

                if (count($pendingOrderDetails) > 0) {
                    DB::table('order_details')->insert($pendingOrderDetails);
                }

                foreach ($pendingProductIds as $productId) {
                    Product::whereKey($productId)->increment('popularity_count');
                }

                $this->syncSelectedRewards($order_id, $selectedRewards);

                return $insertedOrderId;
            });

            if ($request->payment_method === 'square') {
                app(SquareService::class)->attachLocalOrder(Order::find($order_id), $request->transaction_reference);

                Log::info('Square order created and attached.', [
                    'order_id' => $order_id,
                    'transaction_reference' => $request->transaction_reference,
                    'user_id' => $request->user()->id,
                    'branch_id' => $request['branch_id'],
                    'order_amount' => $request['order_amount'],
                ]);
            }

            if ($request['order_type'] === 'delivery') {
                try {
                    OrderLogic::place_doordash_order($o_id);
                } catch (\Throwable $e) {
                    Log::error('DoorDash order creation failed after local order was saved: ' . $e->getMessage(), [
                        'order_id' => $order_id,
                    ]);
                }
            }

            $rewards_points = $redeemedRewardPoints;
            $fcm_token = $isGuestCustomer ? null : $request->user()->cm_firebase_token;
            $value = Helpers::order_status_update_message(($request->payment_method == 'cash_on_delivery') ? 'pending' : 'confirmed');

            try {
                //send push notification
                if ($value) {
                    $data = [
                        'title' => translate('Order'),
                        'description' => $value,
                        'order_id' => $order_id,
                        'image' => '',
                        'type' => 'order_status',
                    ];
                    Helpers::send_push_notif_to_device($fcm_token, $data);
                }

                $faxConfig = Helpers::get_business_settings('fax_settings');
                if (isset($faxConfig['status']) && $faxConfig['status'] == '1' && !$isSquarePayment) {
                    Log::info("======Order place FAX IN =========");
                    $this->send_invoice_fax(Order::find($order_id));
                    Log::info("======Order place FAX come========");
                } elseif (isset($faxConfig['status']) && $faxConfig['status'] == '1' && $isSquarePayment) {
                    Log::info('Invoice notification skipped during Square mobile checkout response.', [
                        'order_id' => $order_id,
                        'branch_id' => $or['branch_id'],
                    ]);
                }
                Log::info("======Order place FAX out========");

                //send email
                $emailServices = Helpers::get_business_settings('mail_config');
                if (isset($emailServices['status']) && $emailServices['status'] == 1) {
                    $orderMailSent = Order::where('id', $order_id)->first();
                    $coupnText = '';
                    if (isset($orderMailSent->coupon_code) && $orderMailSent->coupon_code != '') {
                        $coupn = DB::table('coupons')->where('code', $orderMailSent->coupon_code)->first();
                        if ($coupn && (int) $coupn->discount == 0) {
                            $coupnText = $coupn->invoice_message;
                        }
                    }
                    Mail::to($request->user()->email)->send(new \App\Mail\OrderPlaced($orderMailSent, $coupnText));
                }
            } catch (\Exception $e) {
                Log::info('Email + Reward points Error checking: ' . $e->getMessage());
            }

            if ($or['order_status'] == 'confirmed') {
                $data = [
                    'title' => translate('You have a new order - (Order Confirmed).'),
                    'description' => $order_id,
                    'order_id' => $order_id,
                    'image' => '',
                ];

                if (!$isGuestCustomer) {
                    try {
                        $pointResult = $this->applyPointTransactions(
                            $request->user()->id,
                            $rewards_points,
                            $or['order_amount'],
                            $request->payment_method,
                            $order_id
                        );

                        if ($pointResult['earned_points'] > 0 && !empty($pointResult['email'])) {
                            try {
                                Mail::to($pointResult['email'])->send(new \App\Mail\WalletPointNotification(
                                    $pointResult['balance'],
                                    $pointResult['earned_points'],
                                    $pointResult['customer_name']
                                ));
                            } catch (\Exception $e) {
                                Log::info('Wallet point mail failed: ' . $e->getMessage(), [
                                    'order_id' => $order_id,
                                    'user_id' => $request->user()->id,
                                ]);
                            }
                        }
                    } catch (\Throwable $e) {
                        Log::error('Reward point accounting failed after order creation.', [
                            'order_id' => $order_id,
                            'user_id' => $request->user()->id,
                            'message' => $e->getMessage(),
                        ]);
                    }
                }

                try {
                    $callConfig = Helpers::get_business_settings('branch_call_notification_settings');
                    $kitchenNotificationSent = Helpers::send_push_notif_to_topic($data, "kitchen-{$or['branch_id']}", 'general');
                    if (!$kitchenNotificationSent) {
                        Log::warning('Kitchen push notification was not delivered.', [
                            'order_id' => $order_id,
                            'branch_id' => $or['branch_id'],
                            'topic' => "kitchen-{$or['branch_id']}",
                        ]);
                    }
                    if (isset($callConfig['status']) && $callConfig['status'] == '1' && !$isSquarePayment) {
                        DialCallJob::dispatch($or['branch_id']);
                    } elseif (isset($callConfig['status']) && $callConfig['status'] == '1' && $isSquarePayment) {
                        Log::info('Twilio call skipped during Square mobile checkout response.', [
                            'order_id' => $order_id,
                            'branch_id' => $or['branch_id'],
                        ]);
                    }
                } catch (\Exception $e) {
                    Log::info('Notification Error checking: ' . $e->getMessage());
                    Toastr::warning(translate('Push notification failed!'));
                }
            }

            return response()->json([
                'message' => translate('order_success'),
                'order_id' => $order_id
            ], 200);

        } catch (\Illuminate\Database\QueryException $e) {
            // The database unique key is the final guard when two callbacks pass
            // the cache check concurrently. Return the winning order instead of
            // creating a second order or showing a misleading payment failure.
            if ($request->transaction_reference && (string) $e->getCode() === '23000') {
                $existingOrder = Order::where('transaction_reference', $request->transaction_reference)->first();
                if ($existingOrder) {
                    Log::info('Concurrent duplicate callback returned existing order.', [
                        'order_id' => $existingOrder->id,
                        'transaction_reference' => $request->transaction_reference,
                        'user_id' => optional($request->user())->id,
                    ]);

                    return response()->json([
                        'message' => translate('order_success'),
                        'order_id' => $existingOrder->id,
                    ], 200);
                }
            }

            Log::error('Order database write failed: ' . $e->getMessage(), [
                'user_id' => optional($request->user())->id,
                'transaction_reference' => $request->transaction_reference,
            ]);

            return response()->json(['errors' => [[
                'code' => 'order_place_failed',
                'message' => 'Unable to place order. Please try again.',
            ]]], 500);
        } catch (\Throwable $e) {
            Log::error('Order place failed: ' . $e->getMessage(), [
                'user_id' => optional($request->user())->id,
                'order_type' => $request['order_type'] ?? null,
                'branch_id' => $request['branch_id'] ?? null,
            ]);
            return response()->json([
                'errors' => [
                    ['code' => 'order_place_failed', 'message' => 'Unable to place order. Please try again.']
                ]
            ], 500);
        } finally {
            $lock->release();
        }
    }

    public function get_order_list(Request $request)
    {
        $orders = Order::with(['customer', 'delivery_man.rating'])
            ->withCount('details')
            ->where(['user_id' => $request->user()->id])
            ->get();

        $orders->map(function ($data) {
            $data['deliveryman_review_count'] = DMReview::where([
                'delivery_man_id' => $data['delivery_man_id'],
                'order_id' => $data['id']
            ])->count();

            //is product available
            $order_id = $data->id;
            $order_details = OrderDetail::where('order_id', $order_id)->first();
            $product_id = null;
            $product = null;
            if (isset($order_details)) {
                $product_id = $order_details->product_id;
            }
            if (isset($product_id)) {
                $product = Product::find($product_id);
            }
            $data['is_product_available'] = isset($product) ? 1 : 0;

            $order_rewards = DB::table('order_rewards')->where('order_id', $order_id)->get();
            $rewards = [];
            foreach ($order_rewards as $order_reward) {
                $rewards[] = [
                    'reward' => DB::table('rewards')->where('id', $order_reward->reward_id)->first(),
                    'qty' => $order_reward->qty
                ];
            }
            $data['rewards'] = $rewards;

            // Add source label
            $data['source_label'] = ($data->order_source ?? 'app') === 'walkin' ? '🚶 Walk-in Customer' : '📱 App/Website Customer';

            return $data;
        });

        return response()->json($orders->map(function ($data) {
            $data->details_count = (integer) $data->details_count;
            return $data;
        }), 200);
    }

    public function get_order_details(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'order_id' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        $details = OrderDetail::with(['order.branch'])
            ->withCount(['reviews'])
            ->where(['order_id' => $request['order_id']])
            ->get();

        if ($details->count() < 1) {
            return response()->json([
                'errors' => [
                    ['code' => 'order', 'message' => translate('not found!')]
                ]
            ], 404);
        }

        $details = Helpers::order_details_formatter($details);
        return response()->json($details, 200);
    }

    public function cancel_order(Request $request)
    {
        if (Order::where(['user_id' => $request->user()->id, 'id' => $request['order_id']])->first()) {
            Order::where(['user_id' => $request->user()->id, 'id' => $request['order_id']])->update([
                'order_status' => 'canceled'
            ]);
            return response()->json(['message' => translate('order_canceled')], 200);
        }
        return response()->json([
            'errors' => [
                ['code' => 'order', 'message' => translate('no_data_found')]
            ]
        ], 401);
    }

    public function update_payment_method(Request $request)
    {
        $order = Order::where(['user_id' => $request->user()->id, 'id' => $request['order_id']])->first();
        if ($order) {
            $transactionReference = $request->has('transaction_reference') ? $request['transaction_reference'] : $order->transaction_reference;
            if ($request['payment_method'] === 'square') {
                $currencyCode = strtoupper(Helpers::get_business_settings('currency') ?? 'USD');
                $squareValidation = app(SquareService::class)->validateCheckoutPayment(
                    $transactionReference,
                    $request->user()->id,
                    $order->order_amount,
                    $currencyCode,
                    $order->id,
                    $order->branch_id
                );

                if (!$squareValidation['valid']) {
                    return response()->json(['errors' => [['code' => 'square', 'message' => $squareValidation['message']]]], 403);
                }
            }

            $order->payment_method = $request['payment_method'];
            if ($request->has('transaction_reference')) {
                $order->transaction_reference = $transactionReference;
            }
            $order->save();

            if ($order->payment_method === 'square') {
                app(SquareService::class)->attachLocalOrder($order, $order->transaction_reference);
            }

            return response()->json(['message' => translate('payment_method_updated')], 200);
        }
        return response()->json([
            'errors' => [
                ['code' => 'order', 'message' => translate('no_data_found')]
            ]
        ], 401);
    }

    private function cartItemVariationForOrder(array $cartItem)
    {
        if (array_key_exists('variation', $cartItem) && $this->hasMeaningfulVariation($cartItem['variation'])) {
            return $cartItem['variation'];
        }

        foreach (['size', 'selected_size', 'selectedSize', 'size_name', 'sizeName', 'item_size', 'itemSize', 'drink_size', 'drinkSize', 'selected_size_name', 'selectedSizeName'] as $key) {
            if (array_key_exists($key, $cartItem) && $this->hasMeaningfulVariation($cartItem[$key])) {
                return [['Size' => $cartItem[$key]]];
            }
        }

        foreach (['variation_name', 'variationName', 'variant_name', 'variantName', 'selected_variation', 'selectedVariation', 'selected_variant', 'selectedVariant'] as $key) {
            if (array_key_exists($key, $cartItem) && $this->hasMeaningfulVariation($cartItem[$key])) {
                return [['Variation' => $cartItem[$key]]];
            }
        }

        return [];
    }

    private function hasMeaningfulVariation($value)
    {
        if ($value === null || $value === '') {
            return false;
        }

        if (is_string($value)) {
            $trimmed = trim($value);
            return $trimmed !== '' && $trimmed !== '[]' && $trimmed !== '{}';
        }

        if (is_array($value)) {
            return count(array_filter($value, function ($item) {
                return $this->hasMeaningfulVariation($item);
            })) > 0;
        }

        return true;
    }

    private function isGuestCustomer($user)
    {
        // जर user नसेल किंवा login_medium 'guest' असेल तर guest customer
        return !$user || ($user->login_medium === 'guest');
    }

    private function rewardPointsRequested($rewards)
    {
        $rewards = $this->normalizeRewards($rewards);
        if (count($rewards) < 1) {
            return 0;
        }

        $points = 0;
        foreach ($rewards as $reward) {
            $rewardId = is_array($reward) ? ($reward['id'] ?? $reward['reward_id'] ?? null) : $reward;
            if (!$rewardId) {
                continue;
            }

            $qty = is_array($reward) ? ($reward['qty'] ?? $reward['quantity'] ?? 1) : 1;
            $qty = max(1, (int) $qty);
            $rewardPoint = (float) DB::table('rewards')->where('id', $rewardId)->value('reward_point');

            $points += $rewardPoint * $qty;
        }

        return $points;
    }

    private function normalizeRewards($rewards)
    {
        if (is_numeric($rewards)) {
            $rewards = [['id' => (int) $rewards]];
        }

        if (!is_array($rewards) || count($rewards) < 1) {
            return [];
        }

        if (isset($rewards['id']) || isset($rewards['reward_id']) || isset($rewards['rewardId'])) {
            $rewards = [$rewards];
        }

        $normalized = [];
        foreach ($rewards as $index => $reward) {
            if (is_numeric($reward)) {
                $reward = ['id' => (int) $reward];
            }
            if (!is_array($reward)) {
                continue;
            }

            $rewardId = $reward['id'] ?? $reward['reward_id'] ?? $reward['rewardId'] ?? null;
            $quantity = max(1, (int) ($reward['qty'] ?? $reward['quantity'] ?? 1));
            $key = $rewardId && is_numeric($rewardId)
                ? 'id_' . (int) $rewardId
                : 'item_' . $index;

            if (!isset($normalized[$key])) {
                $reward['qty'] = $quantity;
                unset($reward['quantity']);
                $normalized[$key] = $reward;
            } else {
                $normalized[$key]['qty'] += $quantity;
            }
        }

        return array_values($normalized);
    }

    private function rewardsFromCart($cart)
    {
        if (!is_array($cart) || count($cart) < 1) {
            return [];
        }

        $rewards = [];
        foreach ($cart as $cartItem) {
            if (!is_array($cartItem) || !$this->isRewardCartItem($cartItem)) {
                continue;
            }

            $nestedReward = isset($cartItem['reward']) && is_array($cartItem['reward']) ? $cartItem['reward'] : [];
            $rewardId = $cartItem['reward_id'] ?? $cartItem['rewardId'] ?? $nestedReward['id'] ?? $nestedReward['reward_id'] ?? null;
            if (!$rewardId) {
                continue;
            }

            $rewards[] = [
                'id' => $rewardId,
                'qty' => $cartItem['qty'] ?? $cartItem['quantity'] ?? $nestedReward['qty'] ?? $nestedReward['quantity'] ?? 1,
            ];
        }

        return $this->normalizeRewards($rewards);
    }

    private function isRewardCartItem(array $cartItem)
    {
        $nestedReward = isset($cartItem['reward']) && is_array($cartItem['reward']) ? $cartItem['reward'] : [];

        return !empty($cartItem['is_reward'])
            || !empty($cartItem['reward_id'])
            || !empty($cartItem['rewardId'])
            || !empty($nestedReward);
    }

    private function selectedRewardsAreValid(array $rewards)
    {
        if (count($rewards) < 1) {
            return true;
        }

        $rewardIds = [];
        foreach ($rewards as $reward) {
            $rewardId = is_array($reward) ? ($reward['id'] ?? $reward['reward_id'] ?? null) : $reward;
            if (!$rewardId || !is_numeric($rewardId)) {
                return false;
            }
            $rewardIds[] = (int) $rewardId;
        }

        $query = DB::table('rewards')->whereIn('id', array_values(array_unique($rewardIds)));
        if (Schema::hasColumn('rewards', 'status')) {
            $query->where('status', 1);
        }

        return $query->count() === count(array_unique($rewardIds));
    }

    private function syncSelectedRewards($orderId, array $rewards)
    {
        foreach ($rewards as $reward) {
            $rewardId = is_array($reward) ? ($reward['id'] ?? $reward['reward_id'] ?? null) : $reward;
            if (!$rewardId) {
                continue;
            }

            DB::table('order_rewards')->updateOrInsert(
                [
                    'order_id' => $orderId,
                    'reward_id' => $rewardId,
                ],
                [
                    'qty' => is_array($reward) ? max(1, (int) ($reward['qty'] ?? $reward['quantity'] ?? 1)) : 1,
                ]
            );
        }
    }

    private function applyPointTransactions($userId, $redeemedPoints, $orderAmount, $paymentMethod, $orderId)
    {
        return DB::transaction(function () use ($userId, $redeemedPoints, $orderAmount, $paymentMethod, $orderId) {
            $user = User::where('id', $userId)->lockForUpdate()->first();
            if (!$user) {
                throw new \RuntimeException('Customer was not found while applying reward points.');
            }

            $redeemedPoints = max(0, round((float) $redeemedPoints, 2));

            // Null-guard: point column may be NULL for older accounts
            $currentPoints = (float) ($user->point ?? 0);

            if ($redeemedPoints > $currentPoints) {
                throw new \RuntimeException('Customer no longer has enough points for the selected rewards.');
            }

            $earnedPoints = 0;
            $pointSettings = Helpers::get_business_settings('wallet_point_settings');
            if (
                isset($pointSettings['status'])
                && (string) $pointSettings['status'] === '1'
                && $paymentMethod !== 'internal_point'
            ) {
                $earningRate = max(0, (float) ($pointSettings['value'] ?? 0));
                $earnedPoints = max(0, round($earningRate * (float) $orderAmount));
            }

            // Repeated order callbacks must not debit or credit rewards twice.
            $orderDescription = '%order ID : ' . $orderId;
            $existingTypes = DB::table('point_transitions')
                ->where('user_id', $user->id)
                ->where('description', 'like', $orderDescription)
                ->pluck('type');

            $redeemedPointsToApply = $existingTypes->contains('point_out') ? 0 : $redeemedPoints;
            $earnedPointsToApply = $existingTypes->contains('point_in') ? 0 : $earnedPoints;
            $newBalance = max(0, $currentPoints - $redeemedPointsToApply + $earnedPointsToApply);

            Log::info('Reward point balance update.', [
                'user_id'          => $user->id,
                'order_id'         => $orderId,
                'current_points'   => $currentPoints,
                'redeemed_points'  => $redeemedPointsToApply,
                'earned_points'    => $earnedPointsToApply,
                'new_balance'      => $newBalance,
            ]);

            // Use a direct DB update to bypass Eloquent's isDirty() check.
            // Because `point` is cast to integer in User.php, Eloquent compares
            // the float $newBalance against the already-cast integer and sees no
            // change — silently skipping the SQL UPDATE. The raw DB call forces
            // the write unconditionally.
            DB::table('users')->where('id', $user->id)->update(['point' => $newBalance]);

            if ($redeemedPointsToApply > 0) {
                DB::table('point_transitions')->insert([
                    'user_id'     => $user->id,
                    'description' => 'Redeemed points for order ID : ' . $orderId,
                    'type'        => 'point_out',
                    'amount'      => $redeemedPointsToApply,
                    'created_at'  => now(),
                    'updated_at'  => now(),
                ]);
            }

            if ($earnedPointsToApply > 0) {
                DB::table('point_transitions')->insert([
                    'user_id'     => $user->id,
                    'description' => 'Earned points for order ID : ' . $orderId,
                    'type'        => 'point_in',
                    'amount'      => $earnedPointsToApply,
                    'created_at'  => now(),
                    'updated_at'  => now(),
                ]);
            }

            return [
                'balance'       => round($newBalance),
                'earned_points' => $earnedPointsToApply,
                'email'         => $user->email,
                'customer_name' => trim($user->f_name . ' ' . $user->l_name),
            ];
        });
    }
}
