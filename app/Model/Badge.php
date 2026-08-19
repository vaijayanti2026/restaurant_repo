<?php

namespace App\Model;

use App\CentralLogics\Helpers;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Badge extends Model
{
     protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];
    protected $primaryKey='id';
    protected $table='badges';
    protected $fillable=['title','icon'];
    public function getIconAttribute($value){
        return asset('public/assets/admin/img/badge-icon/'.$value);
    }
}