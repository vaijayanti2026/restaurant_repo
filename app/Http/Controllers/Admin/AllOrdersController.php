<?php

namespace App\Http\Controllers\Admin;

use App\CentralLogics\Helpers;
use App\Http\Controllers\Controller;
use App\Model\AddOn;
use App\Model\Branch;
use App\Model\Order;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AllOrdersController extends Controller
{
    /** Standalone endpoint; the existing order-list API remains unchanged. */
    public function index(Request $request)
    {
        $search = $request->query('search');
        $from = $request->query('from');
        $to = $request->query('to');
        $branchId = $request->query('branch');

        $orders = Order::with(['customer', 'branch', 'details'])
            ->when($search, function ($query) use ($search) {
                $terms = preg_split('/\s+/', trim($search));
                $query->where(function ($orderQuery) use ($terms) {
                    foreach ($terms as $term) {
                        $orderQuery->where(function ($termQuery) use ($term) {
                            $termQuery->where('id', 'like', "%{$term}%")
                                ->orWhere('order_status', 'like', "%{$term}%")
                                ->orWhere('transaction_reference', 'like', "%{$term}%");
                        });
                    }
                });
            })
            ->when($branchId, fn ($query) => $query->where('branch_id', $branchId))
            ->when($from, fn ($query) => $query->whereDate('created_at', '>=', $from))
            ->when($to, fn ($query) => $query->where('created_at', '<=', Carbon::parse($to)->endOfDay()))
            ->orderBy('id', 'desc')
            ->paginate(Helpers::getPagination())
            ->withQueryString();

        $addonIds = [];
        foreach ($orders as $order) {
            foreach ($order->details as $detail) {
                $addonIds = array_merge($addonIds, json_decode($detail->add_on_ids, true) ?: []);
            }
        }
        $addons = AddOn::whereIn('id', array_unique($addonIds))->get()->keyBy('id');

        $orders->getCollection()->transform(function ($order) use ($addons) {
            $itemPrice = 0;
            $tax = 0;
            $addonCost = 0;

            foreach ($order->details as $detail) {
                $quantity = (int) $detail->quantity;
                $itemPrice += ((float) $detail->price - (float) $detail->discount_on_product) * $quantity;
                $tax += (float) $detail->tax_amount * $quantity;

                $ids = json_decode($detail->add_on_ids, true) ?: [];
                $quantities = json_decode($detail->add_on_qtys, true) ?: [];
                foreach ($ids as $index => $id) {
                    $addon = $addons->get($id);
                    if ($addon) {
                        $addonCost += (float) $addon->price * (int) ($quantities[$index] ?? 1);
                    }
                }
            }

            $subtotal = $itemPrice + $tax + $addonCost;
            $order->setAttribute('invoice_total', $subtotal - (float) $order->coupon_discount_amount - (float) $order->extra_discount);

            return $order;
        });

        $branches = Branch::orderBy('name')->get(['id', 'name']);

        return view('admin-views.order.all-orders', compact('orders', 'branches', 'search', 'from', 'to', 'branchId'));
    }
}