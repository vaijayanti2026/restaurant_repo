<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Model\RateUs;

class RateUsController extends Controller
{
    public function get_rate_up_popup(){
        try {
            return response()->json(RateUs::Where('status', 1)->get(), 200);
        } catch (\Exception $e) {
            return response()->json([], 200);
        }
    }
}
