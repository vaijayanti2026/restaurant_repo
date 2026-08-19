<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Model\Advertisement;

class AdvertisementController extends Controller
{
    public function get_advertisements(){
        try {
            return response()->json(Advertisement::Where('status', 1)->get(), 200);
        } catch (\Exception $e) {
            return response()->json([], 200);
        }
    }
}
