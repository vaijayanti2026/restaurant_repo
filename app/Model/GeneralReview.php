<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class GeneralReview extends Model
{
    protected $primaryKey="id";
    protected $table="general_reviews";
    protected $fillable=['name','branch_name','attachment','ratting','comment'];
    public function getAttachmentAttribute($value){
        return asset('public/assets/admin/img/reviewer-profile/'.$value);
    }
    

   
}
