@extends('layouts.admin.app')

@section('title', '')

@push('css_or_js')
    <style>
        @media print {
            .non-printable {
                display: none;
            }

            .printable {
                display: block;
            }
        }
        
        #printableArea h5{
            margin-bottom: 0px !important;
        }
        
        #printableArea table tr th, #printableArea table tr td {
            padding: 6px 10px !important;
        }
    
        #printableArea dd{
            margin-bottom: 0px !important;
        }
        #printableArea hr {
            margin: 6px 0px !important;
        }
        
        .hr-style-2 {
            border: 0;
            height: 1px;
            margin: 8px 0px;
            background-image: linear-gradient(to right, rgba(0, 0, 0, 0), rgba(0, 0, 0, 0.75), rgba(0, 0, 0, 0));
        }

        .hr-style-1 {
            overflow: visible;
            padding: 0;
            border: none;
            margin: 8px 0px;
            border-top: medium double #000000;
            text-align: center;
        }

        #printableAreaContent * {
            font-weight: normal !important;
        }
    </style>

    <style type="text/css" media="print">
        @page {
            size: auto;
            /* auto is the initial value */
            margin: 2px;
        }
    </style>
@endpush

@section('content')

    <div class="content container-fluid" style="color: black">
        <div class="row justify-content-center" id="printableArea">
            <div class="col-md-12">
                <center>
                    <input type="button" class="btn btn-primary non-printable" onclick="printDiv('printableArea')"
                        value="{{ translate('Proceed, If thermal printer is ready.') }}" />
                    <a href="{{ url()->previous() }}" class="btn btn-danger non-printable">{{ translate('Back') }}</a>
                </center>
                <hr class="non-printable">
            </div>
            <div class="col-5" id="printableAreaContent">
                <div class="text-center pt-4 mb-3">
                    <h2 style="line-height: 1">
                        {{ \App\Model\BusinessSetting::where(['key' => 'restaurant_name'])->first()->value }}</h2>
                    <h5 style="font-size: 20px;font-weight: lighter;line-height: 1">
                       {{ $order->branch->address }}
                    </h5>
                    <h5 style="font-size: 16px;font-weight: lighter;line-height: 1">
                        Phone : {{ $order->branch->phone }}
                    </h5>
                    <h5 style="font-size: 16px;font-weight: lighter;line-height: 1">
                        {{ translate('Branch') }}
                        : {{ $order->branch->name }}
                    </h5>
                </div>

                <hr class="text-dark hr-style-1">

                <div class="row mt-2">
                    <div class="col-6">
                        <h5>{{ translate('Order ID : ') }}{{ $order['id'] }}</h5>
                    </div>
                    <div class="col-6">
                        <h5 style="font-weight: lighter">
                            <span
                                class="font-weight-normal">{{ date('d/M/Y h:m a', strtotime($order['created_at'])) }}</span>
                        </h5>
                    </div>
                    <div class="col-6">
                        @if (isset($order->customer))
                            <h5>
                                {{ translate('Customer Name : ') }}<span
                                    class="font-weight-normal">{{ $order->customer['f_name'] . ' ' . $order->customer['l_name'] }}</span>
                            </h5>
                            <h5>
                                {{ translate('Phone : ') }}<span
                                    class="font-weight-normal">{{ $order->customer['phone'] }}</span>
                            </h5>
                            @php($address = \App\Model\CustomerAddress::find($order['delivery_address_id']))
                            <h5>
                                {{ translate('Address : ') }}<span
                                    class="font-weight-normal">{{ isset($address) ? $address['address'] : '' }}</span>
                            </h5>
                        @endif
                    </div>
                    <div class="col-6">
                        <h5 style="font-weight: lighter">
                            {{ translate('Payment Status') }} : {{ translate($order['payment_status']) }}
                        </h5>
                        <h5 style="font-weight: lighter">
                            {{ translate('Payment Method') }} : {{translate($order['payment_method'])}}
                        </h5>
                        <h5 style="font-weight: lighter">
                            {{ translate('Order Type') }} : {{ translate($order['order_type']) }}
                        </h5>
                        <!--@if ($order['transaction_reference'])-->
                        <!--    <h5 style="font-weight: lighter">-->
                        <!--        {{ translate('Reference Code') }} : {{ $order['transaction_reference'] }}-->
                        <!--    </h5>-->
                        <!--@endif-->
                    </div>
                    <div class="col-12 d-flex flex-column my-4 justify-content-center align-items-center ">
                         <h2 style="font-weight: lighter">
                            {{ translate('Catering Date') }} : {{ $order['delivery_date'] }}
                        </h2>
                        <h2 style="font-weight: lighter">
                            {{ translate('Catering Time') }} : {{ $order['delivery_time'] }}
                        </h2>
                    </div>
                </div>
                <h5 class="text-uppercase"></h5>
                <hr class="text-dark hr-style-2">
                <table class="table table-bordered mt-3">
                    <thead>
                        <tr>
                            <th style="width: 10%">{{ translate('QTY') }}</th>
                            <th class="">{{ translate('DESC') }}</th>
                            <th style="text-align:right; padding-right:4px">{{ translate('Price') }}</th>
                        </tr>
                    </thead>

                    <tbody>
                        @php($sub_total = 0)
                        @php($total_tax = 0)
                        @php($total_dis_on_pro = 0)
                        @php($add_ons_cost = 0)
                        @foreach ($order->details as $detail)
                            @php($product_details = json_decode($detail['product_details'], true) ?: [])
                            @php($product_name = data_get($detail->product, 'name', data_get($product_details, 'name', translate('Product unavailable'))))
                            @php($variation_details = json_decode($detail['variation'], true) ?: [])
                            @php($addon_ids = json_decode($detail['add_on_ids'], true) ?: [])
                                @php($add_on_qtys = json_decode($detail['add_on_qtys'], true))
                                <tr>
                                    <td class="">
                                        {{ $detail['quantity'] }}
                                    </td>
                                    <td class="">
                                        {{ $product_name }} <br>
                                        @if (count($variation_details) > 0 && isset($variation_details[0]) && is_array($variation_details[0]))
                                            <strong><u>{{ translate('Variation : ') }}</u></strong>
                                            @foreach ($variation_details[0] as $key1 => $variation)
                                                <div class="font-size-sm text-body" style="color: black!important;">
                                                    <span>{{ $key1 }} : </span>
                                                    <span
                                                        class="font-weight-bold">{{ $key1 == 'price' ? Helpers::set_symbol($variation) : $variation }}</span>
                                                </div>
                                            @endforeach
                                        @endif

                                        @foreach ($addon_ids as $key2 => $id)
                                            @php($addon = \App\Model\AddOn::find($id))
                                            @if ($key2 == 0)
                                                <strong><u>{{ translate('Addons : ') }}</u></strong>
                                            @endif

                                            @if ($add_on_qtys == null)
                                                @php($add_on_qty = 1)
                                            @else
                                                @php($add_on_qty = $add_on_qtys[$key2])
                                            @endif

                                            @if($addon)
                                                <div class="font-size-sm text-body">
                                                    <span>{{ $addon['name'] }} : </span>
                                                    <span class="font-weight-bold">
                                                        {{ $add_on_qty }} x
                                                        {{ \App\CentralLogics\Helpers::set_symbol($addon['price']) }}
                                                    </span>
                                                </div>
                                                @php($add_ons_cost += $addon['price'] * $add_on_qty)
                                            @endif
                                        @endforeach
                                        
                                        @if($detail['discount_on_product'] != '0')
                                            {{ translate('Discount : ') }} {{ \App\CentralLogics\Helpers::set_symbol($detail['discount_on_product']) }}
                                        @endif
                                        <div class="font-size-sm text-body">
                                                <span>Instruction : </span>
                                                <span class="font-weight-bold">
                                                     {{ $detail['instruction']  }}
                                                </span>
                                            </div>
                                       
                                    </td>
                                    <td style="width: 28%;padding-right:4px; text-align:right">
                                        @php($amount = ($detail['price'] - $detail['discount_on_product']) * $detail['quantity'])
                                        {{ \App\CentralLogics\Helpers::set_symbol($amount) }}
                                    </td>
                                </tr>
                                @php($sub_total += $amount)
                                @php($total_tax += $detail['tax_amount'] * $detail['quantity'])
                        @endforeach
                    </tbody>
                </table>


                <div class="row justify-content-md-end mb-3" style="width: 99%">
                    <div class="col-md-7 col-lg-7">
                        <dl class="row text-right" style="color: black!important;">
                            <dt class="col-6">{{ translate('Items Price:') }}</dt>
                            <dd class="col-6">{{ \App\CentralLogics\Helpers::set_symbol($sub_total) }}</dd>
                           <dt class="col-6">{{ translate('Tax / VAT:') }}</dt>
                            <dd class="col-6">{{ \App\CentralLogics\Helpers::set_symbol($total_tax) }}</dd>
                            
                            <!--<dt class="col-6">{{ translate('Tip Price') }}</dt>-->
                            <!--<dd class="col-6">{{ \App\CentralLogics\Helpers::set_symbol($order['tip_price']) }}</dd>-->
                            @if($add_ons_cost != '0')
                            <dt class="col-6">{{ translate('Addon Cost:') }}</dt>
                            <dd class="col-6">
                                {{ \App\CentralLogics\Helpers::set_symbol($add_ons_cost) }}
                                <hr>
                            </dd>
                            @endif
                            <dt class="col-6">{{ translate('Subtotal:') }}</dt>
                            <dd class="col-6">
                            <dd class="col-6">
                             {{ \App\CentralLogics\Helpers::set_symbol($sub_total + $total_tax + $add_ons_cost + $order['tip_price']) }}</dd>
                            <dt class="col-6">{{ translate('Extra Discount') }}:</dt>
                            <dd class="col-6">
                               {{ \App\CentralLogics\Helpers::set_symbol($sub_total + $total_tax + $add_ons_cost + $order['tip_price']) }}</dd>
                            @php($couponLabel = $order['coupon_code'] ?? ($order['coupon_discount_title'] ?? null))
                            <dt class="col-6">{{ translate('Coupon Discount') }}@if($couponLabel) ({{ $couponLabel }})@endif:</dt>
                            <dd class="col-6">
                                - {{ \App\CentralLogics\Helpers::set_symbol($order['coupon_discount_amount']) }}</dd>
                            
                          
                            <dt class="col-6">{{ translate('Delivery Fee:') }}</dt>
                            <dd class="col-6">
                                @if ($order['order_type'] == 'take_away')
                                    @php($del_c = 0)
                                @else
                                    @php($del_c = $order['delivery_charge'])
                                @endif
                                {{ \App\CentralLogics\Helpers::set_symbol($del_c) }}
                                <hr>
                            </dd>

                            <dt class="col-6" style="font-size: 20px">{{ translate('Total:') }}</dt>
                            <dd class="col-6" style="font-size: 20px">
                               @php($invoice_total = $sub_total + $del_c + $total_tax + $add_ons_cost - $order['coupon_discount_amount'] - $order['extra_discount'] + $order['tip_price'])
                                {{ \App\CentralLogics\Helpers::set_symbol($invoice_total) }}
                            </dd>
                        </dl>
                    </div>
                </div>
                <div>
                    @foreach(\App\Model\Poster::active()->get() as $key => $offer)
                        
                        
                       
                       {{-- @if($offer->price <= $total) 
                            @if ($loop->first)
                                 <h5>Offer:</h5>
                            @endif
                            <h5><span>{{ $loop->iteration }}.</span> {{ $offer->title }}</h5>
                        @endif --}}
                   
                    @endforeach
                </div>
                 @if ($coupnText && $coupnText!='')
                <div style="padding: 1rem;">
                    <span class="h5 pt-3">
                        Offer:
                    </span>
                    {{ $coupnText }}
                </div>
            @endif
                <hr class="text-dark hr-style-2">
            
             <h5 class="text-uppercase">Reward Point Reedemption:</h5>
                <table class="table table-bordered mt-3">
                    <thead>
                        <tr>
                            <th style="width: 10%">{{ translate('QTY') }}</th>
                            <th class="">{{ translate('Branch_Text') }}</th>
                            <th style="text-align:right; padding-right:4px">{{ translate('Reward_Price') }}</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach($rewards as $reward)
                        <tr>
                        <td> {{ !isset($reward['qty']) ? 1 : $reward['qty'] }}</td>
                        <td> {{ $reward['reward']->branch_txt }}</td>
                        <td> {{ $reward['reward']->reward_point }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                    </table>
            
            
                <hr class="text-dark hr-style-2">
                <h5 class="text-center pt-1">
                    {{ translate('"""THANK YOU"""') }}
                </h5>
                <hr class="text-dark hr-style-2">
                <div class="text-center">{{ \App\Model\BusinessSetting::where(['key' => 'footer_text'])->first()->value }}
                </div>
                @if ($order->order_note)
                    <div style="padding: 1rem;border: 1px solid black; border-radius: 10px">
                        <span class="h5 pt-3">
                            Note:
                        </span>
                        {{ $order->order_note }}
                    </div>
                @endif
            </div>
        </div>
    </div>

@endsection

@push('script')
    <script>
        function printDiv(divName) {
            var printContents = document.getElementById(divName).innerHTML;
            var originalContents = document.body.innerHTML;
            document.body.innerHTML = printContents;
            window.print();
            document.body.innerHTML = originalContents;
        }
    </script>
@endpush