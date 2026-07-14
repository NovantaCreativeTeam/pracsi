<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NonVerbalAction extends Model
{
    protected $table = 'non_verbal_actions';
    protected $fillable = ['name'];
}
