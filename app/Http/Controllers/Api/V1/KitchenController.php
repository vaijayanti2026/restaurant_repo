<?php

namespace App\Http\Controllers\Api\V1;

use App\CentralLogics\Helpers;
use App\Http\Controllers\Controller;
use App\Model\Branch;
use App\Model\ChefBranch;
use App\Model\Order;
use App\Model\OrderDetail;
use App\Model\Product;
use App\Model\TableOrder;
use App\User;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use function App\CentralLogics\translate;

class KitchenController extends Controller
{
    public function get_order_list(Request $request)
    {
        $branchId = $this->assignedBranchId();
        if (!$branchId) {
            return $this->missingBranchResponse();
        }

        $orders = Order::with('table')
        ->whereIn('order_status', ['confirmed', 'cooking'])
            ->where('branch_id', $branchId)
            ->latest()
            ->paginate(Helpers::getPagination());

        return response()->json($orders,200);
    }

    public function search(Request $request)
    {
        $branchId = $this->assignedBranchId();
        if (!$branchId) {
            return $this->missingBranchResponse();
        }

        $search = $request['search'];
        $key = explode(' ', $request['search']);
        $orders = Order::where('branch_id', $branchId)
            ->whereIn('order_status', ['confirmed', 'cooking', 'done'])
            ->when($search!=null, function($query) use($key){
                foreach ($key as $value) {
                    $query->Where('id', 'like', "%{$value}%");
                }
            })->latest()->paginate(Helpers::getPagination());
        return response()->json($orders,200);
    }

    public function filter_by_status(Request $request)
    {
        $branchId = $this->assignedBranchId();
        if (!$branchId) {
            return $this->missingBranchResponse();
        }

        $order_status = $request->order_status;
        if ($order_status == 'cooking'){
            $orders = Order::where(['order_status' => $order_status, 'branch_id' => $branchId])
                ->orderBy('created_at', 'ASC')
                ->paginate(Helpers::getPagination());
        }
        else{
            $orders = Order::where(['order_status' => $order_status, 'branch_id' => $branchId])
                ->latest()
                ->paginate(Helpers::getPagination());
        }
        /*else{
            $orders = Order::where(['order_status' => $order_status, 'branch_id' => auth()->user()->branch_id])
                ->latest()
                ->paginate(Helpers::getPagination());
        }*/

        return response()->json($orders,200);
    }

    public function get_order_details(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'order_id' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }
        $chefBranch = ChefBranch::where('user_id', auth()->user()->id)->first();
        if (!$chefBranch) {
            return response()->json([
                'message' => 'Kitchen user is not assigned to a branch'
            ], 403);
        }

        $order = Order::with('table')->where([
            'id' => $request->order_id,
            'branch_id' => $chefBranch->branch_id,
        ])->first();
        if (isset($order)){
            $details = OrderDetail::where(['order_id' => $order->id])->get();
            $details = isset($details) ? Helpers::order_details_formatter($details) : null;
            $rewards = DB::table('order_rewards as order_reward')
                ->leftJoin('rewards as reward', 'reward.id', '=', 'order_reward.reward_id')
                ->where('order_reward.order_id', $order->id)
                ->get([
                    'order_reward.reward_id',
                    'order_reward.qty',
                    'reward.title',
                    'reward.reward_point',
                    'reward.instruction',
                    'reward.image',
                ])
                ->map(function ($reward) {
                    $reward->id = (int) $reward->reward_id;
                    $reward->reward_id = (int) $reward->reward_id;
                    $reward->qty = max(1, (int) $reward->qty);
                    $reward->quantity = $reward->qty;
                    $reward->is_reward = true;
                    $reward->name = $reward->title ?: 'Reward item';
                    $reward->note = $reward->instruction;

                    return $reward;
                })
                ->values();

            return response()->json([
                'order' => $order,
                'details' => $details,
                'rewards' => $rewards,
                'reward_details' => $rewards,
            ], 200);
        }
        else{
            return response()->json([
                'message' => 'no order found'
            ]);
        }

    }

    public function change_status(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'order_id' => 'required',
            'order_status' => 'required',
        ]);
        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        $branchId = $this->assignedBranchId();
        if (!$branchId) {
            return $this->missingBranchResponse();
        }

        $order = Order::where([
            'id' => $request->order_id,
            'branch_id' => $branchId,
        ])->first();
        if (!$order) {
            return response()->json([
                'errors' => [
                    ['code' => 'order', 'message' => translate('Order not found')]
                ]
            ], 404);
        }

        $order->order_status = $request->order_status;

        //send notification to deliveryman after done
        if($request->order_status == 'done') {
            $fcm_token = null;
            if(isset($order->delivery_man)) {
                $fcm_token = $order->delivery_man->fcm_token;
            }
            try {
                $data = [
                    'title' => translate('Order'),
                    'description' => translate('cooking done'),
                    'order_id' => $order->id,
                    'image' => '',
                    'type'=>'',
                ];
                if(!is_null($fcm_token)) {
                    Helpers::send_push_notif_to_device($fcm_token, $data);
                }
            } catch (\Exception $e) {
                Toastr::warning(translate('Push notification failed for DeliveryMan!'));
            }
        }
        $isUpdate = $order->update();

        if ($isUpdate){
            return response()->json(['orders' => $order ,'message' => translate('Order status updated!')], 200);
        }

        return response()->json([
            'errors' => [
                ['code' => 'order', 'message' => translate('Status did not changed')]
            ]
        ], 401);
    }

    public function update_fcm_token(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'token' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }
        $kitchen = User::find(auth()->user()->id);

        if (isset($kitchen) == false) {
            return response()->json([
                'errors' => [
                    ['code' => 'Kitchen', 'message' => translate('Invalid token!')]
                ]
            ], 401);
        }

        $kitchen->cm_firebase_token = $request->token;
        $kitchen->update();

        return response()->json(['kitchen' => $kitchen, 'message'=>translate('successfully updated!')], 200);
    }

    public function get_profile()
    {
        $kitchen = User::find(auth()->user()->id);
        $branchId = $this->assignedBranchId();
        if (!$branchId) {
            return $this->missingBranchResponse();
        }
        $branch = Branch::find($branchId);

        return response()->json([
            'profile' => $kitchen,
            'branch' => $branch
        ], 200);
    }

    private function assignedBranchId()
    {
        $user = auth()->user();
        if (!$user) {
            return null;
        }

        $chefBranch = ChefBranch::where('user_id', $user->id)->first();
        if (!$chefBranch || !Branch::where('id', $chefBranch->branch_id)->exists()) {
            return null;
        }

        return (int) $chefBranch->branch_id;
    }

    private function missingBranchResponse()
    {
        return response()->json([
            'errors' => [
                ['code' => 'branch', 'message' => 'Kitchen user is not assigned to a valid branch']
            ]
        ], 403);
    }

}
