<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MoveLevel1 extends Model
{
    protected $table = 'move_level_1';
    protected $fillable = ['name'];

    public function moves()
    {
        return $this->belongsToMany(Move::class, 'move_move_level_1', 'move_level_1_id', 'move_id');
    }
}
