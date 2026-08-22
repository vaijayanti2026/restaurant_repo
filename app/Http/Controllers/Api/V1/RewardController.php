<?php

namespace App\Http\Controllers\Api\V1;

use App\CentralLogics\CategoryLogic;
use App\Http\Controllers\Controller;
use App\Model\PointTransitions;
use App\Model\Reward;
use App\User;
use Illuminate\Http\Request;

class RewardController extends Controller
{
    public function get_rewards(){
        try {
            return response()->json(Reward::active()->get(), 200);
        } catch (\Exception $e) {
            return response()->json([], 200);
        }
    }

    public function getEarnedRewardPoints(Request $request){
        try {
            $points = PointTransitions::where('user_id', $request->user()->id)
                ->where('type', 'point_in')
                ->where('description', 'like', '%order ID : ' . (int) $request->order_id)
                ->sum('amount');
            return response()->json(['points' => $points], 200);
        } catch (\Exception $e) {
            return response()->json([], 400);
        }
    }

    public function getAvailableRewardPoints(Request $request){
        try {
            // users.point is the canonical balance used by redemption checks and
            // order accounting; reward_transactions is a separate unused ledger.
            $points = (float) (User::whereKey($request->user()->id)->value('point') ?? 0);
            return response()->json(['points' => $points], 200);
        } catch (\Exception $e) {
            return response()->json([], 400);
        }
    }
}
