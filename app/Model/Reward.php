<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Reward extends Model
{
    protected $fillable = [ 'image', 'title', 'reward_point', 'branch_txt', 'instruction', 'status'];
    
     public function scopeActive($query)
    {
        return $query->where('status', '=', 1);
    }

}
