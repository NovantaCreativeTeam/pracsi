<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NonVerbalAction extends Model
{
    protected $table = 'non_verbal_actions';
    protected $fillable = ['name'];

    public function moves()
    {
        return $this->belongsToMany(Move::class, 'move_non_verbal_action', 'non_verbal_action_id', 'move_id');
    }
}
