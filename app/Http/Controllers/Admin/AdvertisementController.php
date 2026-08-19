<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Model\Advertisement;
use App\Model\Category;
use App\Model\Product;
use App\CentralLogics\Helpers;
use Brian2694\Toastr\Facades\Toastr;

class AdvertisementController extends Controller
{
    function index()
    {
        $products = Product::orderBy('name')->get();
        $categories = Category::where(['parent_id'=>0])->orderBy('name')->get();
        return view('admin-views.advertisement.index', compact('products', 'categories'));
    }

    function list(Request $request)
    {
        $advertisements=Advertisement::all();

        return view('admin-views.advertisement.list',compact('advertisements'));
    }

    public function store(Request $request)
    {
        //dd($request->all());
        $request->validate([
            'trigger_time' => 'required',
            'image' => 'required',
            'item_type' => 'required',
            'status' => 'required'
        ], [
            'title.max' => translate('Title is too long'),
        ]);

        $advertisement = new Advertisement();
        $advertisement->trigger_time = $request->trigger_time;
        $advertisement->status = $request->status;
        if ($request['item_type'] == 'product') {
            //dd('yes');
            $advertisement->product_id = $request->product_id;
        } elseif ($request['item_type'] == 'category') {
            $advertisement->category_id = $request->category_id;
        }
        $advertisement->image = Helpers::upload('advertisement/', 'png', $request->file('image'));
        $advertisement->save();
       // dd($advertisement);
        Toastr::success(translate('Advertisement added successfully!'));
        return redirect('admin/advertisement/list');
    }

    public function edit($id)
    {
        $products = Product::orderBy('name')->get();
        $advertisement = Advertisement::find($id);
        $categories = Category::where(['parent_id'=>0])->orderBy('name')->get();
        return view('admin-views.advertisement.edit', compact('advertisement', 'products', 'categories'));
    }

    public function status(Request $request)
    {
        $advertisement = Advertisement::find($request->id);
        $advertisement->status = $request->status;
        $advertisement->save();
        Toastr::success(translate('Advertisement status updated!'));
        return back();
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'item_type' => 'required'
        ]);

        $advertisement = Advertisement::find($id);
        $advertisement->trigger_time = $request->trigger_time;
        $advertisement->status = $request->status;
        if ($request['item_type'] == 'product') {
            $advertisement->product_id = $request->product_id;
            $advertisement->category_id = null;
        } elseif ($request['item_type'] == 'category') {
            $advertisement->product_id = null;
            $advertisement->category_id = $request->category_id;
        }
        $advertisement->image = $request->has('image') ? Helpers::update('advertisement/', $advertisement->image,'png', $request->file('image')):$advertisement->image;
        $advertisement->save();
        Toastr::success(translate('Advertisement updated successfully!'));
        return redirect('admin/advertisement/list');
    }

    public function delete(Request $request)
    {
        $advertisement = Advertisement::find($request->id);
        Helpers::delete('advertisement/' . $advertisement['image']);
        $advertisement->delete();
        Toastr::success(translate('Advertisement removed!'));
        return back();
    }
}
