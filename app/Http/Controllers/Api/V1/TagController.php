<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Model\Tag;

class TagController extends Controller
{
    public function get_tags(){
        try {
            return response()->json(Tag::Where('status', 1)->get(), 200);
        } catch (\Exception $e) {
            return response()->json([], 200);
        }
    }
}
