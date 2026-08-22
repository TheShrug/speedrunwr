<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class EasterEgg extends Model
{



	protected $fillable = ['time', 'ip'];
    protected $table = 'easteregg';
}
