<?php

namespace App\Http\Controllers\Admin;


use App\Http\Controllers\Controller;
use App\Model\GeneralReview;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Support\Facades\DB;

class GeneralReviewController extends Controller{
    public function index(Request $request){
       
        $reviews=GeneralReview::orderBy('created_at','DESC')->paginate(10);
        $search='';
        return view('admin-views.general_review.index',compact('reviews','search'));
    }
    public function store(Request $request){
       $validate=Validator::make($request->all(),[
            'name'=>'required',
            'attachment'=>"required|image|mimes:jpeg,jpg,png",
            'comment'=>'required',
            'branch_name'=>'required',
            'ratting'=>'required'
        ]);
        if($validate->fails()){
            return response()->json(['status'=>false,'errors'=>$validate->errors()],422);
        }
        $icon=$request->attachment;
        $imageName = time() . '.' . $icon->getClientOriginalExtension();
        $destinationPath = public_path('/assets/admin/img/reviewer-profile/'); 
        $icon->move($destinationPath, $imageName);
        
        $create=GeneralReview::create([
            'name'=>$request->name,
            'attachment'=>$imageName,
            'comment'=>$request->comment,
            'branch_name'=>$request->branch_name,
            'ratting'=>$request->ratting
        ]);

        if($create){
            return response()->json(['status'=>true,'message'=>'created successfully']);
            }
        return response()->json(['status'=>true,'message'=>'not created']);
    }
    public function destroy(Request $request){
        $review=DB::table('general_reviews')->where('id',$request->id)->first();
        $reviewIcon=$review->attachment;
        $bg=GeneralReview::find($request->id);
        $icon=$bg->attachment;
       
        if($bg->delete()){
            if(file_exists(public_path('/assets/admin/img/reviewer-profile/'.$reviewIcon)))
              unlink(public_path('/assets/admin/img/reviewer-profile/'.$reviewIcon));
            Toastr::success('deleted successfully');
            return redirect()->back();
        }
        Toastr::error('Not Deleted');
        return redirect()->back();
    }
     public function edit(Request $request){
        $review=GeneralReview::where('id',$request->id)->first();
        return view('admin-views.general_review.edit',compact('review'));
    }
    public function update(Request $request){
        $request->validate([
            'name'=>'required',
            'attachment'=>"required|image|mimes:jpeg,jpg,png",
            'comment'=>'required',
            'branch_name'=>'required',
            'ratting'=>'required'
        ]);
        $review=GeneralReview::where('id',$request->id)->first();
        $bg=DB::table('general_reviews')->where('id',$request->id)->first();
        $iconName=$bg->attachment;
        $icon=$request->attachment;
        $imageName = time() . '.' . $icon->getClientOriginalExtension();
        $destinationPath = public_path('/assets/admin/img/reviewer-profile/'); 
        $icon->move($destinationPath, $imageName);
        if($review){
            $review->name=$request->name;
            $review->attachment=$imageName;
            $review->comment=$request->comment;
            $review->branch_name=$request->branch_name;
            $review->ratting=$request->ratting;
            if($review->save()){
                if(file_exists(public_path('/assets/admin/img/reviewer-profile/'.$iconName)))
                 unlink(public_path('/assets/admin/img/reviewer-profile/'.$iconName));
                Toastr::success('Updated successfully');
                return redirect()->route('admin.reviews.add-new');
            }
        }
        Toastr::error('Not updated please try again');
        return redirect()->back();
    }
}