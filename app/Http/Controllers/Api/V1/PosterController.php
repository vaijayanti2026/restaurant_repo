<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Model\Poster;
use Illuminate\Http\Request;

class PosterController extends Controller
{
    public function get_posters(){
        try {
            return response()->json(Poster::active()->get(), 200);
        } catch (\Exception $e) {
            return response()->json([], 200);
        }
    }
}