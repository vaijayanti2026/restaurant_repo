<?php

namespace App\Http\Controllers\Api\V1;

use App\CentralLogics\CategoryLogic;
use App\Http\Controllers\Controller;
use App\Model\Reward;
use App\Model\RewardTransaction;
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
            $points = RewardTransaction::getEarnedPoints($request->user()->id, $request->order_id);
            return response()->json(['points' => $points], 200);
        } catch (\Exception $e) {
            return response()->json([], 400);
        }
    }

    public function getAvailableRewardPoints(Request $request){
        try {
            $points = RewardTransaction::getAvailablePoints($request->user()->id, $request->order_id);
            return response()->json(['points' => $points], 200);
        } catch (\Exception $e) {
            return response()->json([], 400);
        }
    }
}
