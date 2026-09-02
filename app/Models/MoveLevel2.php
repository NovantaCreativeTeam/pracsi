<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MoveLevel2 extends Model
{
    protected $table = 'move_level_2';
    protected $fillable = ['name'];

    public function moves()
    {
        return $this->belongsToMany(Move::class, 'move_move_level_2', 'move_level_2_id', 'move_id');
    }
}
