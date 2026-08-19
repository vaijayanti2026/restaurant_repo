<?php

namespace App\CentralLogics;

use App\Model\CustomerAddress;
use App\Model\Order;
use App\Model\Branch;
use App\Model\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

class OrderLogic
{
    public static function base64UrlEncode(string $data): string
    {
        $base64Url = strtr(base64_encode($data), '+/', '-_');

        return rtrim($base64Url, '=');
    }

    public static function base64UrlDecode(string $base64Url): string
    {
        return base64_decode(strtr($base64Url, '-_', '+/'));
    }

    public static function place_doordash_order($order_id) {
        $header = json_encode([
            'alg' => 'HS256',
            'typ' => 'JWT',
            'dd-ver' => 'DD-JWT-V1'
        ]);

        $payload = json_encode([
            'aud' => 'doordash',
            'iss' => env('DOOR_DASH_DEVELOPER_ID'),
            'kid' => env('DOOR_DASH_KEY_ID'),
            'exp' => time() + 300,
            'iat' => time()
        ]);

        $base64UrlHeader = self::base64UrlEncode($header);
        $base64UrlPayload = self::base64UrlEncode($payload);

        $signature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, self::base64UrlDecode(env('DOOR_DASH_SIGNING_SECRET')), true);
        $base64UrlSignature = self::base64UrlEncode($signature);

        $jwt = $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;

        $order = Order::find($order_id);

        if (!$order) {
            return false;
        }

        $branch = Branch::find($order->branch_id);
        $customerAddress = CustomerAddress::find($order->delivery_address_id);

        if (!$order || !$customerAddress) {
            return false;
        }
        $deliveryDate=$order->delivery_date;
        $default_preparation_time=\App\CentralLogics\Helpers::get_business_settings('default_preparation_time');
        $pickUPTime=$order->delivery_date. ' '.$order->delivery_time;
        $pickUpTimeEst=date('Y-m-d\TH:i:s\Z',strtotime('+5 hours '.$default_preparation_time.' minutes',strtotime($pickUPTime)));
        $customerName=explode(' ',$customerAddress->contact_person_name);
        $firstName=isset($customerName[0])?$customerName[0]:'';
        $lastName=isset($customerName[1])?$customerName[1]:'';
        $lastName.=isset($customerName[2])?' '.$customerName[2]:'';
        $request_body = [
            "external_delivery_id" => $order->id,
            "pickup_address"=> $branch->address,
            "pickup_business_name"=> $branch->name,
            "pickup_phone_number"=> $branch->phone,
            "dropoff_address"=> $customerAddress->address,
            "dropoff_business_name"=> $customerAddress->contact_person_name,
            "dropoff_phone_number"=> $customerAddress->contact_person_number,
            "order_value"=> (int) ($order->order_amount * 100),
            "dropoff_contact_family_name"=>$firstName,
            "dropoff_contact_given_name"=>$lastName,
            "pickup_reference_tag"=>$order->id,
            //"pickup_external_business_id"=>'default',
            //"pickup_external_store_id"=>'3841201a-83cb-4771-8d96-8ba5cdf9773a',
            'pickup_time'=>$pickUpTimeEst
        ];
        if($order->tip_price!=''){
            $request_body['tip']=(float)$order->tip_price*100;
        }
     

        $headers = array(
            "Content-type: application/json"
        );

        $request = Http::withToken($jwt)->withHeaders($headers)
            ->post('https://openapi.doordash.com/drive/v2/deliveries/', $request_body);

        return $request->json();
    }

    public static function track_order($order_id)
    {
        return Helpers::order_data_formatting(Order::with(['details', 'delivery_man.rating'])->where(['id' => $order_id])->first(), false);
    }

    public static function place_order($customer_id, $email, $customer_info, $cart, $payment_method, $discount, $coupon_code = null)
    {
        try {
            $orderTimestamp = Helpers::order_now();
            $or = [
                'id' => 100000 + Order::all()->count() + 1,
                'user_id' => $customer_id,
                'order_amount' => CartManager::cart_grand_total($cart) - $discount,
                'payment_status' => 'unpaid',
                'order_status' => 'pending',
                'payment_method' => $payment_method,
                'transaction_ref' => null,
                'discount_amount' => $discount,
                'coupon_code' => $coupon_code,
                'discount_type' => $discount == 0 ? null : 'coupon_discount',
                'shipping_address' => $customer_info['address_id'],
               'created_at' => $orderTimestamp,
               'updated_at' => $orderTimestamp
            ];

            $o_id = DB::table('orders')->insertGetId($or);

            foreach ($cart as $c) {
                $product = Product::where('id', $c['id'])->first();
                $or_d = [
                    'order_id' => $o_id,
                    'product_id' => $c['id'],
                    'seller_id' => $product->added_by == 'seller' ? $product->user_id : '0',
                    'product_details' => $product,
                    'qty' => $c['quantity'],
                    'price' => $c['price'],
                    'tax' => $c['tax'] * $c['quantity'],
                    'discount' => $c['discount'] * $c['quantity'],
                    'discount_type' => 'discount_on_product',
                    'variant' => $c['variant'],
                    'variation' => json_encode($c['variations']),
                    'delivery_status' => 'pending',
                    'shipping_method_id' => $c['shipping_method_id'],
                    'payment_status' => 'unpaid',
                    'created_at' => now(),
                    'updated_at' => now()
                ];
                DB::table('order_details')->insert($or_d);
            }

            $emailServices = Helpers::get_business_settings('mail_config');
            if (isset($emailServices['status']) && $emailServices['status'] == 1) {
                Mail::to($email)->send(new \App\Mail\OrderPlaced($o_id));
            }

        } catch (\Exception $e) {

        }

        return $o_id;
    }
}
