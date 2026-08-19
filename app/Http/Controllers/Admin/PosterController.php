<?php

namespace App\Http\Controllers\Admin;

use App\CentralLogics\Helpers;
use App\Http\Controllers\Controller;
use App\Model\Poster;
use Illuminate\Http\Request;
use Brian2694\Toastr\Facades\Toastr;

class PosterController extends Controller
{
    
    
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view('admin-views.poster.index');
    }
    
    public function list(Request $request)
    {
        $search = $request->search;
        $query_param = ['search' => $search];

        $posters = Poster::when($search, function ($query) use($search, $query_param){
            $keywords = explode(' ' ,$search);
            foreach ($keywords as $keyword) {
                $query->orWhere('title', 'LIKE', "%$keyword%")
                    ->orwhere('id', 'LIKE', "%$keyword%");
            }
        })
            ->latest()
            ->paginate(Helpers::getPagination())
            ->appends($query_param);
            
        return view('admin-views.poster.list', compact('posters'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
    
        $request->validate([
            'title' => 'required|max:255',
            'image' => 'required',
            'price' => 'required'
        ], [
            'title.max' => translate('Title is too long'),
        ]);

        $poster = new Poster;
        $poster->title = $request->title;
        $poster->price = $request->price;
        $poster->image = Helpers::upload('poster/', 'png', $request->file('image'));
        $poster->save();
   
        Toastr::success(translate('Poster added successfully!'));
        return redirect('admin/poster/list');
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Poster  $poster
     * @return \Illuminate\Http\Response
     */
    public function show(Poster $poster)
    {
        
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Poster  $poster
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $poster = Poster::find($id);
        return view('admin-views.poster.edit', compact('poster'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Poster  $poster
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        
        $request->validate([
            'title' => 'required|max:255',
            'price' => 'required'
        ], [
            'title.max' => translate('Title is too long!'),
        ]);

        $poster = Poster::find($id);
        $poster->title = $request->title;
        $poster->price = $request->price;
        $poster->image = $request->has('image') ? Helpers::update('poster/', $poster->image,'png', $request->file('image')):$poster->image;
        $poster->save();
        Toastr::success(translate('Poster updated successfully!'));
        return redirect('admin/poster/list');
    }
    
    public function status(Request $request)
    {
        $poster = Poster::find($request->id);
        $poster->status = $request->status;
        $poster->save();
        Toastr::success(translate('Poster status updated!'));
        return back();
    }

    public function delete(Request $request)
    {
        $poster = Poster::find($request->id);
        Helpers::delete('banner/' . $poster['image']);
        $poster->delete();
        Toastr::success(translate('Poster removed!'));
        return back();
    }
}
