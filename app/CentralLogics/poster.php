<?php

namespace App\CentralLogics;

use App\Model\Poster;

class PosterLogic
{
    public static function get_posters()
    {
        return Poster::latest()->get();
    }
}