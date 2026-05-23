<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MicroTask extends Model
{
    protected $fillable = [
        'task_id',
        'type_id',
        'begin',
        'end',
    ];

    public function task()
    {
        return $this->belongsTo(Task::class);
    }

    public function type()
    {
        return $this->belongsTo(MicroTaskType::class, 'type_id');
    }

    public function moves()
    {
        return $this->hasMany(Move::class);
    }
}
