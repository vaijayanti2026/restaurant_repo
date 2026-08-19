<?php

namespace App\Http\Controllers\Admin;


use App\Http\Controllers\Controller;
use App\Model\Badge;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Support\Facades\DB;

class BadgeController extends Controller{
    public function index(Request $request){
        $badges=Badge::orderBy('created_at','DESC')->paginate(10);
        $search='';
        return view('admin-views.badge.index',compact('badges','search'));
    }
    public function store(Request $request){
       $validate=Validator::make($request->all(),[
            'title'=>'required',
            'icon'=>"required|image|mimes:jpeg,jpg,png"
        ]);
        if($validate->fails()){
            return response()->json(['status'=>false,'errors'=>$validate->errors()],422);
        }
        $icon=$request->icon;
        $imageName = time() . '.' . $icon->getClientOriginalExtension();
        $destinationPath = public_path('/assets/admin/img/badge-icon'); 
        $icon->move($destinationPath, $imageName);
        
        $create=Badge::create([
            'title'=>$request->title,
            'icon'=>$imageName
        ]);

        if($create){
            return response()->json(['status'=>true,'message'=>'created successfully']);
            }
        return response()->json(['status'=>true,'message'=>'not created']);
    }
    public function destroy(Request $request){
        $badge=DB::table('badges')->where('id',$request->id)->first();
        $badgeIcon=$badge->icon;
        $bg=Badge::find($request->id);
        $icon=$bg->icon;
       
        if($bg->delete()){
            if(file_exists(public_path('/assets/admin/img/badge-icon/'.$badgeIcon)))
              unlink(public_path('/assets/admin/img/badge-icon/'.$badgeIcon));
            Toastr::success('deleted successfully');
            return redirect()->back();
        }
        Toastr::error('Not Deleted');
        return redirect()->back();
    }
     public function edit(Request $request){
        $badge=Badge::where('id',$request->id)->first();
        return view('admin-views.badge.edit',compact('badge'));
    }
    public function update(Request $request){
        $request->validate([
            'title'=>'required',
            'icon'=>"required|image|mimes:jpeg,jpg,png"
        ]);
        $badge=Badge::where('id',$request->id)->first();
        $bg=DB::table('badges')->where('id',$request->id)->first();
        $iconName=$bg->icon;
        $icon=$request->icon;
        $imageName = time() . '.' . $icon->getClientOriginalExtension();
        $destinationPath = public_path('/assets/admin/img/badge-icon'); 
        $icon->move($destinationPath, $imageName);
        if($badge){
            $badge->title=$request->title;
            $badge->icon=$imageName;
            if($badge->save()){
                if(file_exists(public_path('/assets/admin/img/badge-icon/'.$iconName)))
                 unlink(public_path('/assets/admin/img/badge-icon/'.$iconName));
                Toastr::success('Updated successfully');
                return redirect()->route('admin.badge.add-new');
            }
        }
        Toastr::error('Not updated please try again');
        return redirect()->back();
    }
}