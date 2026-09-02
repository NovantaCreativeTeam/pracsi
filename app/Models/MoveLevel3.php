<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MoveLevel3 extends Model
{
    protected $table = 'move_level_3';
    protected $fillable = ['name'];

    public function moves()
    {
        return $this->belongsToMany(Move::class, 'move_move_level_3', 'move_level_3_id', 'move_id');
    }
}
