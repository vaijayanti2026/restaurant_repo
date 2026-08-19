<?php

namespace App\Http\Controllers\Api\V1;

use App\CentralLogics\Helpers;
use App\Http\Controllers\Controller;
use App\Model\BusinessSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class MapApiController extends Controller
{
    public function place_api_autocomplete(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'search_text' => 'required',
        ]);
        if ($validator->errors()->count() > 0) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }
        $api_key = Helpers::get_business_settings('map_api_key');
        $response = Http::get('https://maps.googleapis.com/maps/api/place/autocomplete/json?input=' . $request['search_text'] . '&key=' . $api_key);
        return $response->json();
    }

    public function distance_api(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'origin_lat' => 'required',
            'origin_lng' => 'required',
            'destination_lat' => 'required',
            'destination_lng' => 'required',
        ]);
        if ($validator->errors()->count() > 0) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }
        $api_key = Helpers::get_business_settings('map_api_key');
        $response = Http::get('https://maps.googleapis.com/maps/api/distancematrix/json?origins=' . $request['origin_lat'] . ',' . $request['origin_lng'] . '&destinations=' . $request['destination_lat'] . ',' . $request['destination_lng'] . '&key=' . $api_key);
        return $response->json();
    }

    public function place_api_details(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'placeid' => 'required',
        ]);
        if ($validator->errors()->count() > 0) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }
        $api_key = Helpers::get_business_settings('map_api_key');
        $response = Http::get('https://maps.googleapis.com/maps/api/place/details/json?placeid=' . $request['placeid'] . '&key=' . $api_key);
        return $response->json();
    }

    public function geocode_api(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'lat' => 'required',
            'lng' => 'required',
        ]);
        if ($validator->errors()->count() > 0) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }
        $api_key = Helpers::get_business_settings('map_api_key');
        $response = Http::get('https://maps.googleapis.com/maps/api/geocode/json?latlng=' . $request->lat . ',' . $request->lng . '&key=' . $api_key);
        return $response->json();
    }
    private function calculateDistance($customerLat, $CustomerLon, $branchLat, $branchLon) {
        $customerLat = deg2rad($customerLat);
        $CustomerLon = deg2rad($CustomerLon);
        $branchLat = deg2rad($branchLat);
        $branchLon = deg2rad($branchLon);
        $deltaLat = $branchLat - $customerLat;
        $deltaLon = $branchLon - $CustomerLon;
        $a = sin($deltaLat / 2) * sin($deltaLat / 2) + cos($customerLat) * cos($branchLat) * sin($deltaLon / 2) * sin($deltaLon / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        $radius = 6371;
        $distance = $radius * $c;
        return $distance;
    }   
    public function getBranchDistance(Request $request){
        $validate=Validator::make($request->all(),[
            'origin_lat'=>'required',
            'origin_lng'=>'required'
        ]);
        if($validate->fails()){
            return response()->json(['status'=>false,'message'=>'validation error','errors'=>$validate->errors()],422);
        }
        $branches=DB::table('branches')->where('status','1')->get();
        $data=[];
        foreach($branches as $branch){
            $branchLat=$branch->latitude;
            $BranchLong=$branch->longitude;
            $distance=$this->calculateDistance($request->origin_lat,$request->origin_lng,$branchLat,$BranchLong);
            $array=[
                'id'=>$branch->id,
                'name'=>$branch->name,
                'distance'=>$distance,
                'type'=>'km'
            ];
            array_push($data,$array);
        }
        if(count($data)>0){
            return response()->json(['status'=>true,'message'=>'successfull','data'=>$data],200);
        }
        return response()->json(['status'=>false,'message'=>'not found'],201);

}
    
}
