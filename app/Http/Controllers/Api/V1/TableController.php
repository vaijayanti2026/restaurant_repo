<?php

namespace App\Http\Controllers\Api\V1;

use App\CentralLogics\Helpers;
use App\Http\Controllers\Controller;
use App\Model\Notification;
use App\Model\Order;
use App\Model\OrderDetail;
use App\Model\Product;
use App\Model\Table;
use App\Model\TableOrder;
use App\Traits\SendInvoiceFaxTrait;
use App\Services\SquareService;
use Brian2694\Toastr\Facades\Toastr;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use function App\CentralLogics\translate;

class TableController extends Controller
{
    use SendInvoiceFaxTrait;

    public function list()
    {
        $tables = Table::where('is_active', 1)->paginate(Helpers::getPagination());
        return response()->json($tables, 200);
    }

    public function place_order(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'order_amount' => 'required',
            'table_id' => 'required',
            'branch_id' => 'required',
            'delivery_time' => 'required',
            'delivery_date' => 'required',
            'number_of_people' => 'required',
            'payment_status' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        if ($request->payment_method === 'square') {
            $currencyCode = strtoupper(Helpers::get_business_settings('currency') ?? 'USD');
            $squareValidation = app(SquareService::class)->validateCheckoutPayment(
                $request->transaction_reference,
                $request->id,
                Helpers::set_price($request['order_amount']),
                $currencyCode,
                null,
                $request['branch_id']
            );

            if (!$squareValidation['valid']) {
                return response()->json(['errors' => [['code' => 'square', 'message' => $squareValidation['message']]]], 403);
            }
        }

        try {

            $order = new Order();
            $orderTimestamp = Helpers::order_now();
            $order->id = 100000 + Order::all()->count() + 1;
            $order->user_id = $request->id;
            $order->order_amount = Helpers::set_price($request['order_amount']);
            $order->coupon_discount_amount = Helpers::set_price($request->coupon_discount_amount);
            $order->coupon_discount_title = (!empty($request->coupon_discount_title) && $request->coupon_discount_title != 0) ? $request->coupon_discount_title : null;
            $order->payment_method = $request->payment_method;
            $order->payment_status = $request->payment_method === 'square' ? 'paid' : $request->payment_status;
            $order->order_status = 'confirmed';
            $order->coupon_code = $request['coupon_code'];
            $order->transaction_reference = $request->transaction_reference ?? null;
            $order->order_note = $request['order_note'];
            $order->order_type = 'dine_in';
            $order->branch_id = $request['branch_id'];
            $order->checked = 1;

            $order->delivery_date = $orderTimestamp->format('Y-m-d');
            $order->delivery_time = $orderTimestamp->format('H:i:s');

            $order->preparation_time = Helpers::get_business_settings('default_preparation_time') ?? 0;

            $order->table_id = $request['table_id'];
            $order->number_of_people = $request['number_of_people'];

            $order->created_at = $orderTimestamp;
            $order->updated_at = $orderTimestamp;

            $token_check = TableOrder::where(['table_id' => $request->table_id, 'branch_table_token' => $request->branch_table_token, 'branch_table_token_is_expired' => '0'])->first();

            if (isset($token_check)){
                $order->table_order_id = $token_check->id;

                if ($request->payment_status == 'paid'){
                    $check_unpaid_orders = Order::where(['table_order_id' => $token_check->id])->get();
                    foreach ($check_unpaid_orders as $check_unpaid_order){
                        $check_unpaid_order->payment_status = 'paid';
                        $check_unpaid_order->save();
                    }
                }
            }else{
                $table_order = new TableOrder();
                $table_order->table_id = $request->table_id;
                $table_order->branch_table_token = Str::random(50);
                $table_order->branch_table_token_is_expired = 0;

                //dd($table_order);
                $table_order->save();

                $order->table_order_id = $table_order->id;
            }

            //dd($table_order);

            $order->save();

            if ($order->payment_method === 'square') {
                app(SquareService::class)->attachLocalOrder($order, $order->transaction_reference);
            }

            foreach ($request['cart'] as $c) {
                $product = Product::find($c['product_id']);
                if (array_key_exists('variation', $c) && count(json_decode($product['variations'], true)) > 0) {
                    $price = Helpers::variation_price($product, json_encode($c['variation']));
                } else {
                    $price = Helpers::set_price($product['price']);
                }

                $variation = $this->cartItemVariationForOrder($c);
                $order_d = [
                    'order_id' => $order->id,
                    'product_id' => $c['product_id'],
                    'product_details' => $product,
                    'quantity' => $c['quantity'],
                    'price' => $price,
                    'tax_amount' => Helpers::tax_calculate($product, $price),
                    'discount_on_product' => Helpers::discount_calculate($product, $price),
                    'discount_type' => 'discount_on_product',
                    'variant' => json_encode($c['variant']),
                    'variation' => json_encode($variation),
                    'add_on_ids' => json_encode($c['add_on_ids']),
                    'add_on_qtys' => json_encode($c['add_on_qtys']),
                    'created_at' => now(),
                    'updated_at' => now()
                ];

                DB::table('order_details')->insert($order_d);

                //update product popularity point
                Product::find($c['product_id'])->increment('popularity_count');
            }

            //send notification to kitchen
            $notification = new Notification;
            $notification->title =  "You have a new order from Table - (Order Confirmed). ";
            $notification->description = $order->id;
            $notification->status = 1;

            try {
                Helpers::send_push_notif_to_topic($notification, "kitchen-{$order->branch_id}",'general');
                Toastr::success(translate('Notification sent successfully!'));
            } catch (\Exception $e) {
                Toastr::warning(translate('Push notification failed!'));
            }
            $token = TableOrder::where('id', $order['table_order_id'])->first();

            $this->send_invoice_fax($order);

            return response()->json([
                'message' => translate('order_placed_successfully!!'),
                'order_id' => $order->id,
                'branch_table_token' => $token->branch_table_token,
            ], 200);


        } catch (\Exception $e) {
            return response()->json([$e], 403);
        }
    }

    public function get_order_details(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'order_id' => 'required',
            'branch_table_token' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        $branch_table_token = TableOrder::where(['branch_table_token' => $request->branch_table_token])->first();

        if(isset($branch_table_token)){
            $order = Order::where(['id' => $request->order_id, 'table_order_id' => $branch_table_token->id])->first();
            $details = OrderDetail::where(['order_id' => $order->id])->get();
            $details = isset($details) ? Helpers::order_details_formatter($details) : null;

            return response()->json([
                'order' => $order,
                'details' => $details
                ], 200);
        }
        return response()->json(['message' => 'No data found']);
    }

    public function table_order_list(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'branch_table_token' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }
        $token_check = TableOrder::where(['branch_table_token' => $request->branch_table_token, 'branch_table_token_is_expired' => '0'])->first();
        if(isset($token_check)) {
            $order = Order::where(['table_order_id' => $token_check->id])->get();
            return response()->json(['order' => $order], 200);
        }
        return response()->json(['message' => 'no data found']);
    }

    private function cartItemVariationForOrder(array $cartItem)
    {
        if (array_key_exists('variation', $cartItem) && $this->hasMeaningfulVariation($cartItem['variation'])) {
            return $cartItem['variation'];
        }

        foreach (['size', 'selected_size', 'size_name', 'item_size', 'drink_size', 'selected_size_name'] as $key) {
            if (array_key_exists($key, $cartItem) && $this->hasMeaningfulVariation($cartItem[$key])) {
                return [['Size' => $cartItem[$key]]];
            }
        }

        foreach (['variation_name', 'variant_name', 'selected_variation', 'selected_variant'] as $key) {
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
}
