<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    protected $fillable = [
        'dialog_id',
        'type_id',
        'begin',
        'end',
    ];

    public function dialog()
    {
        return $this->belongsTo(Dialog::class);
    }

    public function type()
    {
        return $this->belongsTo(TaskType::class, 'type_id');
    }

    public function microTasks()
    {
        return $this->hasMany(MicroTask::class);
    }
}
