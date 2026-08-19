<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Poster extends Model
{
    protected $fillable = [ 'image', 'title', 'price', 'status'];
    
     public function scopeActive($query)
    {
        return $query->where('status', '=', 1);
    }

}
