<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Model\RateUs;
use Brian2694\Toastr\Facades\Toastr;
class RateUsController extends Controller
{
     function index()
    {
        $rate_us = RateUs::orderBy('id')->get();
        return view('admin-views.rate-us.index', compact('rate_us'));
    }

    function list(Request $request)
    {
        $rate_us=RateUs::all();

        return view('admin-views.rate-us.list',compact('rate_us'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'status' => 'required',
            'time_interval' => 'required',
            'start_date' => 'required',
        ]);

        $new_rate_us = new RateUs;
        $new_rate_us->status = $request->status;
        $new_rate_us->time_interval = $request->time_interval;
        $new_rate_us->start_date = $request->start_date;
        $new_rate_us->save();
        Toastr::success(translate('Advertisement added successfully!'));
        return redirect('admin/rate-us/list');
    }

    public function edit($id)
    {
        $rate_us = RateUs::find($id);
        return view('admin-views.rate-us.edit', compact('rate_us'));
    }

    public function status(Request $request)
    {
        $rate_us = RateUs::find($request->id);
        $rate_us->status = $request->status;
        $rate_us->save();
        Toastr::success(translate('Rate Us status updated!'));
        return back();
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'status' => 'required',
            'time_interval' => 'required',
            'start_date' => 'required',
        ]);

        $rate_us = RateUs::find($id);
        $rate_us->status = $request->status;
        $rate_us->time_interval = $request->time_interval;
        $rate_us->start_date = $request->start_date;
        $rate_us->save();
        Toastr::success(translate('Rate Us updated successfully!'));
        return redirect('admin/rate-us/list');
    }

    public function delete(Request $request)
    {
        $rate_us = RateUs::find($request->id);
        $rate_us->delete();
        Toastr::success(translate('Rate us removed!'));
        return back();
    }
}
