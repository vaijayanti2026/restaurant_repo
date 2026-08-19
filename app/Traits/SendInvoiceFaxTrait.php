<?php

namespace App\Traits;

use App\CentralLogics\Helpers;
use App\Model\Order;
use App\Services\TwilioService;
use Barryvdh\DomPDF\Facade\Pdf;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

trait SendInvoiceFaxTrait
{
    protected function send_invoice_fax(Order $order)
    {
        $faxConfig = Helpers::get_business_settings('fax_settings');
        if (!isset($faxConfig['status']) || (string) $faxConfig['status'] !== '1') {
            return;
        }

        try {
            $branch = $order->branch;
            $to_number = $branch ? ($branch->phone ?: $branch->fax) : null;

            if (!$to_number) {
                Log::warning('Invoice notification skipped because branch notification number was not found.', [
                    'order_id' => $order->id,
                    'branch_id' => $order->branch_id,
                ]);
                Toastr::warning(translate('Branch notification number not found!'));
                return;
            }

            $this->store_invoice($order);
            $invoiceUrl = url("/storage/app/public/invoices/$order->id.pdf");
            app(TwilioService::class)->sendSMS(
                $to_number,
                "New order invoice #{$order->id}: {$invoiceUrl}"
            );

            Log::info('Invoice notification sent through Twilio.', [
                'order_id' => $order->id,
                'to' => $to_number,
            ]);
            Toastr::success(translate('Invoice notification sent successfully!'));
        } catch (\Throwable $exception) {
            Log::error('Invoice notification failed: ' . $exception->getMessage(), [
                'order_id' => $order->id,
                'branch_id' => $order->branch_id,
            ]);
            Toastr::warning(translate('Failed to send invoice notification!'));
        }
    }

    private function store_invoice(Order $order)
    {
        $coupnText='';
            if(isset($order->coupon_code) && $order->coupon_code!=''){
                $coupn=DB::table('coupons')->where('code',$order->coupon_code)->first();
                if((int)$coupn->discount==0){
                    $coupnText=$coupn->invoice_message;
                }
            }
            $order_rewards = DB::table('order_rewards')->where('order_id', $order->id)->get();
           $rewards=[];
        foreach($order_rewards as $order_reward){
            $rewards[]=[
                'reward'=> DB::table('rewards')->where('id', $order_reward->reward_id)->first(),
                'qty' => $order_reward->qty
                ];
        }
      
        $pdf = Pdf::loadView('branch-views.order.partials.invoice', compact('order','coupnText', 'rewards'));
        $content = $pdf->download()->getOriginalContent();
        Storage::disk('public')->put("/invoices/$order->id.pdf", $content);
        Log::info('Invoice PDF stored for notification.', ['order_id' => $order->id]);
    }
}
