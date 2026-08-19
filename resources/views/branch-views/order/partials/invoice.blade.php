<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <style>
        .h5,
        body,
        h5 {
            font-size: .875rem
        }

        .table,
        body {
            color: #677788
        }

        .col-12,
        .col-6 {
            position: relative;
            width: 100%;
            padding-right: 15px;
            padding-left: 15px
        }

        .col-12 {
            -ms-flex: 0 0 100%;
            flex: 0 0 100%;
            max-width: 100%
        }

        .col-6 {
            -ms-flex: 0 0 50%;
            flex: 0 0 50%;
            max-width: 50%
        }

        .mt-4,
        .my-4 {
            margin-top: 1.5rem !important
        }

        .row {
            display: -ms-flexbox;
            display: flex;
            -ms-flex-wrap: wrap;
            flex-wrap: wrap;
            margin-right: -15px;
            margin-left: -15px
        }

        .table-bordered td,
        .table-bordered th {
            border: .0625rem solid rgba(231, 234, 243, .7)
        }

        .table td,
        .table th {
            /*padding: .75rem;*/
            /*padding: 6px 10px !important;*/
            vertical-align: top;
            border-top: .0625rem solid rgba(231, 234, 243, .7)
        }

        .table {
            width: 100%;
            margin-bottom: 1rem
        }

        table {
            border-collapse: collapse
        }

        .hr-style-2 {
            border: 0;
            height: 1px;
             margin: 8px 0px;
            background-image: linear-gradient(to right, rgba(0, 0, 0, 0), rgba(0, 0, 0, .75), rgba(0, 0, 0, 0))
        }

        .hr-style-1 {
            overflow: visible;
            padding: 0;
            border: none;
             margin: 8px 0px;
            border-top: double #000;
            text-align: center
        }

        #printableAreaContent * {
            color: #000;
            font-weight: 600 !important;
            font-family: 'Roboto Mono', monospace !important
        }

        .text-center {
            text-align: center !important
        }

        .pt-4,
        .py-4 {
            padding-top: 1.5rem !important
        }

        .mb-3,
        .my-3 {
            margin-bottom: 1rem !important
        }

        *,
        ::after,
        ::before {
            box-sizing: border-box
        }

        .footer-offset {
            margin-bottom: 3.5rem
        }

        .h5,
        h2,
        h4,
        h5,
        h6 {
            margin-bottom: 0px;
        }

        body {
            margin: 0;
            font-family: "Open Sans", sans-serif;
            font-weight: 400;
            line-height: 1.6;
            text-align: left;
            background-color: #fff
        }

        main {
            -ms-flex-positive: 1;
            flex-grow: 1
        }

        .h5,
        h2,
        h4,
        h5 {
            font-weight: 600;
            line-height: 1.4;
            color: #1e2022
        }

        h2,
        h4,
        h5,
        h6 {
            margin-top: 0
        }

        ,
        main {
            display: block
        }

        #printableAreaContent h5,h4 {
            font-weight: 700 !important
        }
    </style>

    <style type="text/css" media="print">
        @page {
            size: auto;
            margin: 2px;
        }
    </style>


</head>

<body class="footer-offset">

    <main id="content" role="main" class="main pointer-event">

        <div id="printableAreaContent">
            <div class="text-center pt-3 mb-3">
                <h2 style="line-height: 1">
                    {{ \App\Model\BusinessSetting::where(['key' => 'restaurant_name'])->first()->value }}
                </h2>
                <h5 style="font-size: 20px;font-weight: lighter;line-height: 1">
                    {{ $order->branch->address }}
                </h5>
                <h5 style="font-size: 16px;font-weight: lighter;line-height: 1">
                    {{ translate('Phone') }} :
                    <!--{{ \App\Model\BusinessSetting::where(['key' => 'phone'])->first()->value }}-->
                    {{$order->branch->phone}}
                </h5>
                <h5 style="font-size: 16px;font-weight: lighter;line-height: 1">
                    {{ translate('Branch') }}
                    : {{ $order->branch->name }}
                </h5>
            </div>

            <hr class="text-dark hr-style-1">
            <table class="" style="width: 100%; margin-top: 6px;">
                <tr>
                    <td class="">
                        <h4>{{ translate('Order ID') }} : {{ $order['id'] }}</h4>
                    </td>
                    <td class="">
                        <h4 style="font-weight: lighter">
                            {{ date('d/M/Y h:m a', strtotime($order['created_at'])) }}
                        </h4>
                    </td>
                </tr>
                @if ($order->customer)
                    <tr>
                        <td class="">
                            <h4>
                                {{ translate('Customer Name') }} : <span
                                    class="font-weight-normal">{{ $order->customer['f_name'] . ' ' . $order->customer['l_name'] }}</span>
                            </h4>
                        </td>
                        <td class="">
                            <h4>
                                {{ translate('Phone') }} : <span
                                    class="font-weight-normal">{{ $order->customer['phone'] }}</span>
                            </h4>
                        </td>
                    </tr>
                @endif
                <tr>
                    @if ($order->order_type != 'pos')
                        <td class="">
                            @php($address = \App\Model\CustomerAddress::find($order['delivery_address_id']))
                            <h4>
                                {{ translate('Address') }} : <span
                                    class="font-weight-normal">{{ isset($address) ? $address['address'] : '' }}</span>
                            </h4>
                        </td>
                    @endif
                    <td class="">
                        <h4 style="font-weight: lighter">
                            {{ translate('Payment Status') }} : {{ translate($order['payment_status']) }}
                        </h4>
                    </td>
                </tr>
                <tr>
                    <td class="">
                        <h4 style="font-weight: lighter">
                            {{ translate('Payment Method') }} : {{ translate($order['payment_method']) }}
                        </h4>
                    </td>
                    <td class="">
                        <h4 style="font-weight: lighter">
                            {{ translate('Order Type') }} : {{ translate($order['order_type']) }}
                        </h4>
                    </td>
                </tr>
                <tr class="text-center" style="text-align:center;">
                    <td class="">
                        <h2 style="font-weight: lighter">
                            {{ translate('Order Date') }} : {{ $order['delivery_date'] }}
                        </h2>
                        <h2 style="font-weight: lighter">
                            {{ translate('Order Time') }} : {{ $order['delivery_time'] }}
                        </h2>
                    </td>
                </tr>

                <!--@if ($order['transaction_reference'])-->
                <!--    <div class="">-->
                <!--        <h5 style="font-weight: lighter">-->
                <!--            {{ translate('Reference Code') }} : {{ $order['transaction_reference'] }}-->
                <!--        </h5>-->
                <!--    </div>-->
                <!--@endif-->

            </table>
            <h5 class="text-uppercase"></h5>
            <hr class="text-dark hr-style-2">
            <table class="table table-bordered mt-3">
                <thead>
                    <tr>
                        <th style="width: 10%">{{ translate('QTY') }}</th>
                        <th class="">{{ translate('DESC') }}</th>
                        <th style="text-align:right;">{{ translate('Price') }}</th>
                    </tr>
                </thead>

                <tbody>
                    @php($sub_total = 0)
                    @php($total_tax = 0)
                    @php($total_dis_on_pro = 0)
                    @php($add_ons_cost = 0)
                    @foreach ($order->details as $detail)
                        @if ($detail->product)
                            @php($add_on_qtys = json_decode($detail['add_on_qtys'], true))
                            <tr>
                                <td class="">
                                    {{ $detail['quantity'] }}
                                </td>
                                <td class="">
                                    {{ $detail->product['name'] }} <br>
                                    @if (count(json_decode($detail['variation'], true)) > 0)
                                        <strong><u>{{ translate('Variation') }} : </u></strong>
                                        @foreach (json_decode($detail['variation'], true)[0] as $key1 => $variation)
                                            <div class="font-size-sm text-body" style="color: black!important;">
                                                <span>{{ $key1 }} : </span>
                                                <span
                                                    class="font-weight-bold">{{ $key1 == 'price' ? Helpers::set_symbol($variation) : $variation }}</span>
                                            </div>
                                        @endforeach
                                    @endif

                                    @foreach (json_decode($detail['add_on_ids'], true) as $key2 => $id)
                                        @php($addon = \App\Model\AddOn::find($id))
                                        @if ($key2 == 0)
                                            <strong><u>Addons : </u></strong>
                                        @endif

                                        @if ($add_on_qtys == null)
                                            @php($add_on_qty = 1)
                                        @else
                                            @php($add_on_qty = $add_on_qtys[$key2])
                                        @endif

                                        <div class="font-size-sm text-body">
                                            <span>{{ $addon['name'] }} : </span>
                                            <span class="font-weight-bold">
                                                {{ $add_on_qty }} x
                                                {{ \App\CentralLogics\Helpers::set_symbol($addon['price']) }}
                                            </span>
                                        </div>
                                        @php($add_ons_cost += $addon['price'] * $add_on_qty)
                                    @endforeach
                                    
                                    @if($detail['discount_on_product'] != '0')
                                        {{ translate('Discount') }} : {{ \App\CentralLogics\Helpers::set_symbol($detail['discount_on_product']) }}
                                    @endif
                                     <div class="font-size-sm text-body">
                                                <span>Instruction : </span>
                                                <span class="font-weight-bold">
                                                     {{ $detail['instruction']  }}
                                                </span>
                                            </div>
                                </td>
                                <td style="width: 28%; text-align:right">
                                    @php($amount = ($detail['price'] - $detail['discount_on_product']) * $detail['quantity'])
                                    {{ \App\CentralLogics\Helpers::set_symbol($amount) }}
                                </td>
                            </tr>
                            @php($sub_total += $amount)
                            @php($total_tax += $detail['tax_amount'] * $detail['quantity'])
                        @endif
                    @endforeach
                    <tr>
                        <th colspan="2" style="text-align: right">{{ translate('Items Price') }}:</th>
                        <th style="text-align: right">{{ \App\CentralLogics\Helpers::set_symbol($sub_total) }}</th>
                    </tr>
                    <tr>
                        <th colspan="2" style="text-align: right">{{ translate('Tax') }} / {{ translate('VAT') }}:
                        </th>
                        <th style="text-align: right">{{ \App\CentralLogics\Helpers::set_symbol($total_tax) }}</th>
                    </tr>
                    @if($add_ons_cost != '0')
                    <tr>
                        <th colspan="2" style="text-align: right">{{ translate('Addon Cost') }}:</th>
                        <th style="text-align: right">
                            {{ \App\CentralLogics\Helpers::set_symbol($add_ons_cost) }}
                        </th>

                    </tr>
                    @endif
                    <tr>
                        <th colspan="2" style="text-align: right">{{ translate('Subtotal') }}:</th>
                        <th style="text-align: right">
                            {{ \App\CentralLogics\Helpers::set_symbol($sub_total + $total_tax + $add_ons_cost) }}</th>

                    </tr>
                    @if($order['coupon_discount_amount'] != '0')
                    @php($couponLabel = $order['coupon_code'] ?? ($order['coupon_discount_title'] ?? null))
                    <tr>
                        <th colspan="2" style="text-align: right">{{ translate('Coupon Discount') }}@if($couponLabel) ({{ $couponLabel }})@endif:</th>
                        <th style="text-align: right">
                            - {{ \App\CentralLogics\Helpers::set_symbol($order['coupon_discount_amount']) }}</th>

                    </tr>
                    @endif
                    <tr>
                        <th colspan="2" style="text-align: right">{{ translate('Delivery Fee') }}:</th>
                        <th style="text-align: right">
                            @if ($order['order_type'] == 'take_away')
                                @php($del_c = 0)
                            @else
                                @php($del_c = $order['delivery_charge'])
                            @endif
                            {{ \App\CentralLogics\Helpers::set_symbol($del_c) }}


                    </tr>

                    <tr>
                        <th colspan="2" style="text-align: right; font-size: 20px">{{ translate('Total') }}:</th>
                        <th style="font-size: 20px; text-align: right">
                             @php( $total = $sub_total + $del_c + $total_tax + $add_ons_cost - $order['coupon_discount_amount'])
                            {{ \App\CentralLogics\Helpers::set_symbol($sub_total + $del_c + $total_tax + $add_ons_cost - $order['coupon_discount_amount']) }}
                        </th>
                    </tr>
                </tbody>
            </table>

            <div>
                @foreach(\App\Model\Poster::active()->get() as $key => $offer)
                    
                    
                   
                    {{--@if($offer->price <= $total) 
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
            <h5 class="text-center pt-3">
                {{ translate('Thank You for ordering in Catrina Fresh Mex') }}
            </h5>
            <hr class="text-dark hr-style-2">
            @if ($order->order_note)
                <div style="padding: 1rem;border: 1px solid black; border-radius: 10px">
                    <span class="h5 pt-3">
                        Note:
                    </span>
                    {{ $order->order_note }}
                </div>
            @endif
             
        </div>

    </main>
</body>

</html>
