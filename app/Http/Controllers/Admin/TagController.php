<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Model\Tag;
use App\Model\RateUs;
use App\CentralLogics\Helpers;
use Brian2694\Toastr\Facades\Toastr;

class TagController extends Controller
{
      function index()
    {
        $tags = Tag::orderBy('name')->get();
        $rate_us = RateUs::get();
        return view('admin-views.tag.index', ['tags' => $tags, 'rate_us' => $rate_us]);
    }

    function list(Request $request)
    {
        $tags=Tag::orderBy('id')->get();
        $rate_us = RateUs::get();
        return view('admin-views.tag.list', ['tags' => $tags, 'rate_us' => $rate_us]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required',
            // 'icon' => 'required',
            'status' => 'required'
        ]);
        $tag = new Tag;
        $tag->name = $request->name;
        $tag->status = $request->status;
        $tag->icon = Helpers::upload('tag/', 'png', $request->file('icon'));
        $tag->save();
        Toastr::success(translate('Tag added successfully!'));
        return redirect('admin/tag/list');
    }

    public function edit($id)
    {
        $tag = Tag::find($id);
        return view('admin-views.tag.edit', compact('tag'));
    }

    public function status(Request $request)
    {
        $tag = Tag::find($request->id);
        $tag->status = $request->status;
        $tag->save();
        Toastr::success(translate('Tag status updated!'));
        return back();
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required',
            'status' => 'required'
        ]);

        $tag = Tag::find($id);
        $tag->name = $request->name;
        $tag->status = $request->status;
        // $tag->icon = $request->has('icon') ? Helpers::update('tag/', $tag->icon,'png', $request->file('icon')):$tag->icon;
        $tag->save();
        Toastr::success(translate('Tag updated successfully!'));
        return redirect('admin/tag/list');
    }

    public function delete(Request $request)
    {
        $tag = Tag::find($request->id);
        Helpers::delete('tag/' . $tag['icon']);
        $tag->delete();
        Toastr::success(translate('Tag removed!'));
        return back();
    }
}
